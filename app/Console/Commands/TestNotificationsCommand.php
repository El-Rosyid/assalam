<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\data_siswa;
use App\Models\data_guru;
use App\Models\data_kelas;
use App\Models\academic_year;
use App\Models\monthly_reports;
use App\Models\GrowthRecord;
use App\Notifications\StudentAssessmentCompletedNotification;
use App\Notifications\MonthlyReportCompletedNotification;
use App\Notifications\GrowthRecordCompletedNotification;
use App\Notifications\InvalidPhoneNumberNotification;

class TestNotificationsCommand extends Command
{
    protected $signature = 'test:notifications {--type=all : Type of notification to test (all, student, monthly, growth, phone)}';
    protected $description = 'Test all notification types with sample data';

    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('🔔 Testing Notification System');
        $this->newLine();

        // Get admin users
        $adminUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        if ($adminUsers->isEmpty()) {
            $this->error('No admin users found!');
            return 1;
        }

        $this->info("Found {$adminUsers->count()} admin users");
        $this->newLine();

        // Get sample data
        $siswa = data_siswa::with(['kelasInfo', 'kelasInfo.walikelas'])->first();
        $guru = data_guru::with('kelasWali')->first();
        $kelas = data_kelas::first();
        $tahunAjaran = academic_year::where('is_active', true)->first();

        if (!$siswa || !$guru || !$kelas || !$tahunAjaran) {
            $this->error('Missing required sample data (siswa, guru, kelas, or academic year)');
            return 1;
        }

        if ($type === 'all' || $type === 'student') {
            $this->testStudentAssessmentNotification($adminUsers, $siswa, $kelas, $tahunAjaran, $guru);
        }

        if ($type === 'all' || $type === 'monthly') {
            $this->testMonthlyReportNotification($adminUsers, $guru, $kelas);
        }

        if ($type === 'all' || $type === 'growth') {
            $this->testGrowthRecordNotification($adminUsers, $guru, $kelas);
        }

        if ($type === 'all' || $type === 'phone') {
            $this->testInvalidPhoneNotification($adminUsers, $siswa);
        }

        $this->newLine();
        $this->info('✅ All notifications sent successfully!');
        $this->info('Check the notification bell in admin dashboard.');

        return 0;
    }

    private function testStudentAssessmentNotification($adminUsers, $siswa, $kelas, $tahunAjaran, $guru)
    {
        $this->info('📚 Testing Student Assessment Completed Notification...');
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new StudentAssessmentCompletedNotification(
                $siswa,
                $kelas, 
                $tahunAjaran,
                $guru
            ));
        }
        
        $this->line("   ✓ Sent to {$adminUsers->count()} admin(s)");
    }

    private function testMonthlyReportNotification($adminUsers, $guru, $kelas)
    {
        $this->info('📄 Testing Monthly Report Completed Notification...');
        
        // Create a mock monthly report
        $mockReport = new monthly_reports();
        $mockReport->month = now()->month;
        $mockReport->year = now()->year;
        $mockReport->status = 'final';
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new MonthlyReportCompletedNotification(
                $mockReport,
                $guru,
                $kelas
            ));
        }
        
        $this->line("   ✓ Sent to {$adminUsers->count()} admin(s)");
    }

    private function testGrowthRecordNotification($adminUsers, $guru, $kelas)
    {
        $this->info('📊 Testing Growth Record Completed Notification...');
        
        $currentMonth = now()->month;
        $totalStudents = data_siswa::where('kelas', $kelas->kelas_id)->count();
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new GrowthRecordCompletedNotification(
                $guru,
                $kelas,
                $currentMonth,
                $totalStudents
            ));
        }
        
        $this->line("   ✓ Sent to {$adminUsers->count()} admin(s)");
    }

    private function testInvalidPhoneNotification($adminUsers, $siswa)
    {
        $this->info('📞 Testing Invalid Phone Number Notification...');
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new InvalidPhoneNumberNotification(
                $siswa->nama_lengkap,
                '081234567890' // Sample invalid phone
            ));
        }
        
        $this->line("   ✓ Sent to {$adminUsers->count()} admin(s)");
    }
}