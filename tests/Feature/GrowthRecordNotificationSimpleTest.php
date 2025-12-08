<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\GrowthRecordCompletedNotification;
use App\Models\data_guru;
use App\Models\data_kelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class GrowthRecordNotificationSimpleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_growth_record_notification_content_and_structure()
    {
        // Create roles
        Role::create(['name' => 'admin']);
        
        // Create admin user
        $adminUser = new User([
            'name' => 'Test Admin',
            'username' => 'testadmin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);
        $adminUser->save();
        $adminUser->assignRole('admin');

        // Create minimal model instances for testing
        $guru = new data_guru([
            'nama_lengkap' => 'Test Guru'
        ]);
        $guru->id = 1;

        $kelas = new data_kelas([
            'nama_kelas' => '1A'
        ]);
        $kelas->id = 1;

        // Test notification creation
        $month = 3; // March
        $totalStudents = 5;
        
        $notification = new GrowthRecordCompletedNotification($guru, $kelas, $month, $totalStudents);
        $notificationArray = $notification->toArray($adminUser);

        // Assert notification content
        $this->assertEquals('Data Pertumbuhan Lengkap', $notificationArray['title']);
        $this->assertStringContainsString('Guru Test Guru', $notificationArray['body']);
        $this->assertStringContainsString('kelas 1A', $notificationArray['body']);
        $this->assertStringContainsString('bulan Maret', $notificationArray['body']);
        $this->assertStringContainsString('(5 siswa)', $notificationArray['body']);
        $this->assertEquals('heroicon-o-chart-bar-square', $notificationArray['icon']);
        $this->assertEquals('success', $notificationArray['iconColor']);
        $this->assertEquals('growth_record_completed', $notificationArray['type']);

        // Assert action structure
        $this->assertArrayHasKey('actions', $notificationArray);
        $this->assertCount(1, $notificationArray['actions']);
        $this->assertEquals('Lihat Data', $notificationArray['actions'][0]['name']);
        $this->assertEquals('/admin/growth-records/manage/3', $notificationArray['actions'][0]['url']);
        $this->assertEquals('primary', $notificationArray['actions'][0]['color']);
    }

    /** @test */
    public function test_notification_different_months()
    {
        // Test different month names
        $guru = new data_guru([
            'nama_lengkap' => 'Test Guru'
        ]);
        $guru->id = 1;
        
        $kelas = new data_kelas([
            'nama_kelas' => '2B'
        ]);
        $kelas->id = 1;

        $testMonths = [
            1 => 'Januari',
            6 => 'Juni', 
            12 => 'Desember'
        ];

        $adminUser = new User([
            'name' => 'Test Admin',
            'username' => 'testadmin2',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password')
        ]);
        $adminUser->save();

        foreach ($testMonths as $monthNum => $monthName) {
            $notification = new GrowthRecordCompletedNotification($guru, $kelas, $monthNum, 3);
            $notificationArray = $notification->toArray($adminUser);

            $this->assertStringContainsString("bulan $monthName", $notificationArray['body']);
            $this->assertEquals("/admin/growth-records/manage/$monthNum", $notificationArray['actions'][0]['url']);
        }
    }

    /** @test */
    public function test_notification_action_url_format()
    {
        $guru = new data_guru([
            'nama_lengkap' => 'Test Guru'
        ]);
        $guru->id = 1;
        
        $kelas = new data_kelas([
            'nama_kelas' => '3C'
        ]);
        $kelas->id = 1;

        $adminUser = new User([
            'name' => 'Test Admin',
            'username' => 'testadmin3', 
            'email' => 'admin3@test.com',
            'password' => bcrypt('password')
        ]);
        $adminUser->save();

        $month = 8; // August
        $notification = new GrowthRecordCompletedNotification($guru, $kelas, $month, 4);
        $notificationArray = $notification->toArray($adminUser);

        // Verify URL format for different months
        $this->assertEquals('/admin/growth-records/manage/8', $notificationArray['actions'][0]['url']);
        $this->assertStringContainsString('bulan Agustus', $notificationArray['body']);
    }

    /** @test */
    public function test_notification_preserves_all_required_fields()
    {
        $guru = new data_guru([
            'nama_lengkap' => 'Test Guru Complete'
        ]);
        $guru->id = 1;
        
        $kelas = new data_kelas([
            'nama_kelas' => '4D'
        ]);
        $kelas->id = 1;

        $adminUser = new User([
            'name' => 'Test Admin',
            'username' => 'testadmin4',
            'email' => 'admin4@test.com', 
            'password' => bcrypt('password')
        ]);
        $adminUser->save();

        $notification = new GrowthRecordCompletedNotification($guru, $kelas, 10, 7);
        $notificationArray = $notification->toArray($adminUser);

        // Check all required fields exist
        $requiredFields = ['title', 'body', 'icon', 'iconColor', 'actions', 'type'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $notificationArray, "Missing required field: $field");
        }

        // Check action structure  
        $this->assertIsArray($notificationArray['actions']);
        $this->assertNotEmpty($notificationArray['actions']);
        $this->assertArrayHasKey('name', $notificationArray['actions'][0]);
        $this->assertArrayHasKey('url', $notificationArray['actions'][0]); 
        $this->assertArrayHasKey('color', $notificationArray['actions'][0]);
    }
}