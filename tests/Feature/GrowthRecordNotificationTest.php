<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\data_guru;
use App\Models\data_kelas;
use App\Models\data_siswa;
use App\Models\GrowthRecord;
use App\Notifications\GrowthRecordCompletedNotification;
use App\Observers\GrowthRecordObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Role;

class GrowthRecordNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $guruUser;
    protected $guru;
    protected $kelas;
    protected $students;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'guru']);

        // Create admin user
        $this->adminUser = new User([
            'name' => 'Test Admin',
            'username' => 'testadmin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);
        $this->adminUser->save();
        $this->adminUser->assignRole('admin');

        // Create guru
        $this->guru = data_guru::create([
            'nip' => '123456789',
            'nama_lengkap' => 'Test Guru',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1980-01-01',
            'alamat' => 'Test Address',
            'telepon' => '08123456789',
            'email' => 'guru@test.com',
            'status' => 'Aktif'
        ]);

        // Create guru user
        $this->guruUser = new User([
            'name' => 'Test Guru User',
            'username' => 'testguru',
            'email' => 'guru@test.com',
            'password' => bcrypt('password')
        ]);
        $this->guruUser->save();
        $this->guruUser->assignRole('guru');

        // Create class
        $this->kelas = data_kelas::create([
            'nama_kelas' => '1A',
            'walikelas_id' => $this->guru->id,
            'tingkat' => 1
        ]);

        // Create students
        $this->students = collect();
        for ($i = 1; $i <= 3; $i++) {
            $student = data_siswa::create([
                'nis' => sprintf('%010d', $i),
                'nisn' => sprintf('%010d', $i + 1000),
                'nama_lengkap' => "Student $i",
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '2010-01-01',
                'alamat' => 'Test Address',
                'agama' => 'Islam',
                'asal_sekolah' => 'TK Test',
                'anak_ke' => 1,
                'jumlah_saudara' => 2,
                'diterima_kelas' => '1A',
                'tanggal_diterima' => '2023-01-01',
                'kelas' => $this->kelas->id, // Use standard id
                'user_id' => 1 // Dummy user_id
            ]);
            $this->students->push($student);
        }

        // Register observer
        GrowthRecord::observe(GrowthRecordObserver::class);
    }

    /** @test */
    public function test_notification_sent_when_all_students_complete_growth_records()
    {
        // Fake notifications to test
        Notification::fake();

        $month = now()->month;

        // Create partial growth records (should not trigger notification)
        $this->students->take(2)->each(function ($student) use ($month) {
            GrowthRecord::create([
                'data_siswa_id' => $student->id,
                'data_guru_id' => $this->guru->id,
                'data_kelas_id' => $this->kelas->id,
                'month' => $month,
                'berat_badan' => 25.5,
                'tinggi_badan' => 120.0
            ]);
        });

        // Verify notification not sent yet
        $this->assertDatabaseMissing('notifications', [
            'type' => GrowthRecordCompletedNotification::class
        ]);

        // Complete the last student's growth record (should trigger notification)
        GrowthRecord::create([
            'data_siswa_id' => $this->students->last()->id,
            'data_guru_id' => $this->guru->id,
            'data_kelas_id' => $this->kelas->id,
            'month' => $month,
            'berat_badan' => 24.0,
            'tinggi_badan' => 118.5
        ]);

        // Verify notification was sent
        $this->assertDatabaseHas('notifications', [
            'type' => GrowthRecordCompletedNotification::class,
            'notifiable_id' => $this->adminUser->id
        ]);
    }

    /** @test */
    public function test_notification_not_sent_for_empty_growth_records()
    {
        $month = now()->month;

        // Create empty growth records (should not trigger notification)
        $this->students->each(function ($student) use ($month) {
            GrowthRecord::create([
                'data_siswa_id' => $student->id,
                'data_guru_id' => $this->guru->id,
                'data_kelas_id' => $this->kelas->id,
                'month' => $month,
                'lingkar_kepala' => null,
                'lingkar_lengan' => null,
                'berat_badan' => null,
                'tinggi_badan' => null
            ]);
        });

        // Verify notification was not sent
        $this->assertDatabaseMissing('notifications', [
            'type' => GrowthRecordCompletedNotification::class
        ]);
    }

    /** @test */
    public function test_notification_sent_when_updating_growth_record_with_data()
    {
        $month = now()->month;

        // Create empty growth records first
        $growthRecords = $this->students->map(function ($student) use ($month) {
            return GrowthRecord::create([
                'data_siswa_id' => $student->id,
                'data_guru_id' => $this->guru->id,
                'data_kelas_id' => $this->kelas->id,
                'month' => $month
            ]);
        });

        // Update records with measurement data (except last one)
        $growthRecords->take(2)->each(function ($record) {
            $record->update([
                'berat_badan' => 25.0,
                'tinggi_badan' => 120.0
            ]);
        });

        // Verify notification not sent yet
        $this->assertDatabaseMissing('notifications', [
            'type' => GrowthRecordCompletedNotification::class
        ]);

        // Update last record (should trigger notification)
        $growthRecords->last()->update([
            'berat_badan' => 24.5,
            'tinggi_badan' => 119.0
        ]);

        // Verify notification was sent
        $this->assertDatabaseHas('notifications', [
            'type' => GrowthRecordCompletedNotification::class
        ]);
    }

    /** @test */
    public function test_notification_content_is_correct()
    {
        $month = 3; // March
        $notification = new GrowthRecordCompletedNotification(
            $this->guru, 
            $this->kelas, 
            $month, 
            3
        );

        $notificationArray = $notification->toArray($this->adminUser);

        $this->assertEquals('Data Pertumbuhan Lengkap', $notificationArray['title']);
        $this->assertStringContainsString('Guru Test Guru', $notificationArray['body']);
        $this->assertStringContainsString('kelas 1A', $notificationArray['body']);
        $this->assertStringContainsString('bulan Maret', $notificationArray['body']);
        $this->assertStringContainsString('(3 siswa)', $notificationArray['body']);
        $this->assertEquals('heroicon-o-chart-bar-square', $notificationArray['icon']);
        $this->assertEquals('growth_record_completed', $notificationArray['type']);
    }

    /** @test */
    public function test_duplicate_notifications_are_prevented()
    {
        $month = now()->month;

        // Complete all growth records
        $this->students->each(function ($student) use ($month) {
            GrowthRecord::create([
                'data_siswa_id' => $student->id,
                'data_guru_id' => $this->guru->id,
                'data_kelas_id' => $this->kelas->id,
                'month' => $month,
                'berat_badan' => 25.0,
                'tinggi_badan' => 120.0
            ]);
        });

        // Count initial notifications
        $initialCount = DatabaseNotification::where([
            'type' => GrowthRecordCompletedNotification::class,
            'notifiable_id' => $this->adminUser->id
        ])->count();

        // Update a growth record again (should not create duplicate notification)
        $growthRecord = GrowthRecord::where('month', $month)->first();
        $growthRecord->update(['lingkar_kepala' => 52.0]);

        // Verify no additional notifications were created
        $finalCount = DatabaseNotification::where([
            'type' => GrowthRecordCompletedNotification::class,
            'notifiable_id' => $this->adminUser->id
        ])->count();

        $this->assertEquals($initialCount, $finalCount);
    }

    /** @test */
    public function test_notification_sent_to_multiple_admin_users()
    {
        // Create additional admin user
        $superAdmin = new User([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password')
        ]);
        $superAdmin->save();
        $superAdmin->assignRole('super_admin');

        $month = now()->month;

        // Complete all growth records
        $this->students->each(function ($student) use ($month) {
            GrowthRecord::create([
                'data_siswa_id' => $student->id,
                'data_guru_id' => $this->guru->id,
                'data_kelas_id' => $this->kelas->kelas_id,
                'month' => $month,
                'berat_badan' => 25.0,
                'tinggi_badan' => 120.0
            ]);
        });

        // Verify both admin users received notification
        $this->assertDatabaseHas('notifications', [
            'type' => GrowthRecordCompletedNotification::class,
            'notifiable_id' => $this->adminUser->id
        ]);

        $this->assertDatabaseHas('notifications', [
            'type' => GrowthRecordCompletedNotification::class,
            'notifiable_id' => $superAdmin->id
        ]);
    }

    /** @test */
    public function test_partial_measurement_data_triggers_completion_check()
    {
        $month = now()->month;

        // Create records with only some measurement data
        $this->students->each(function ($student, $index) use ($month) {
            $data = [
                'data_siswa_id' => $student->id,
                'data_guru_id' => $this->guru->id,
                'data_kelas_id' => $this->kelas->id,
                'month' => $month,
            ];

            // Different measurement combinations
            switch ($index) {
                case 0:
                    $data['lingkar_kepala'] = 52.0;
                    break;
                case 1:
                    $data['lingkar_lengan'] = 18.5;
                    break;
                case 2:
                    $data['berat_badan'] = 25.0;
                    break;
            }

            GrowthRecord::create($data);
        });

        // Verify notification was sent (all students have at least one measurement)
        $this->assertDatabaseHas('notifications', [
            'type' => GrowthRecordCompletedNotification::class
        ]);
    }

    /** @test */
    public function test_observer_handles_missing_relationships_gracefully()
    {
        // Create growth record with missing relationships
        $record = new GrowthRecord([
            'data_siswa_id' => 999, // Non-existent student
            'data_guru_id' => 999,  // Non-existent guru
            'data_kelas_id' => 999, // Non-existent class
            'month' => now()->month,
            'berat_badan' => 25.0
        ]);
        $record->save();

        // Test should not crash and no notification should be sent
        $this->assertDatabaseMissing('notifications', [
            'type' => GrowthRecordCompletedNotification::class
        ]);
    }

    /** @test */
    public function test_notification_action_url_is_correct()
    {
        $month = 5; // May
        $notification = new GrowthRecordCompletedNotification(
            $this->guru, 
            $this->kelas, 
            $month, 
            3
        );

        $notificationArray = $notification->toArray($this->adminUser);

        $this->assertArrayHasKey('actions', $notificationArray);
        $this->assertCount(1, $notificationArray['actions']);
        $this->assertEquals('Lihat Data', $notificationArray['actions'][0]['name']);
        $this->assertEquals('/admin/growth-records/manage/5', $notificationArray['actions'][0]['url']);
        $this->assertEquals('primary', $notificationArray['actions'][0]['color']);
    }
}