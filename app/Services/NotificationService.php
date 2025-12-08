<?php

namespace App\Services;

use App\Models\User;
use App\Models\data_guru;
use App\Models\data_kelas;
use App\Models\data_siswa;
use App\Models\monthly_reports;
use App\Models\student_assessment;
use App\Notifications\MonthlyReportCompletedNotification;
use App\Notifications\StudentAssessmentCompletedNotification;

class NotificationService
{
    /**
     * Check if monthly reports are completed for a class in a specific month
     * LINEAR HIERARCHY: Sekolah → Tahun Ajaran → Kelas → Siswa
     */
    public function checkMonthlyReportCompletion(data_guru $guru, int $month, int $year): void
    {
        // Get active academic year (Level 2 of hierarchy)
        $activeYear = \App\Models\academic_year::where('is_active', true)->first();
        
        // Get all classes taught by this guru in active year (Level 3 of hierarchy)
        $classes = data_kelas::where('walikelas_id', $guru->guru_id)
            ->where('tahun_ajaran_id', $activeYear?->tahun_ajaran_id)
            ->get();

        foreach ($classes as $kelas) {
            // Get total students in this class (Level 4 of hierarchy)
            $totalStudents = data_siswa::where('kelas', $kelas->kelas_id)->count();
            
            if ($totalStudents == 0) continue;

            // Get completed monthly reports for this class and month
            $completedReports = monthly_reports::whereHas('siswa', function($query) use ($kelas) {
                    $query->where('kelas', $kelas->kelas_id);
                })
                ->where('month', $month)
                ->where('year', $year)
                ->count();

            // If all students have monthly reports, send notification
            if ($completedReports >= $totalStudents) {
                $this->sendMonthlyReportNotification($guru, $kelas, $month, $year, $totalStudents);
            }
        }
    }

    /**
     * Check if student assessments are completed for a class
     * LINEAR HIERARCHY: Sekolah → Tahun Ajaran → Kelas → Siswa
     */
    public function checkStudentAssessmentCompletion(data_guru $guru, string $semester): void
    {
        // Get active academic year (Level 2 of hierarchy)
        $activeYear = \App\Models\academic_year::where('is_active', true)->first();
        
        // Get all classes taught by this guru in active year (Level 3 of hierarchy)
        $classes = data_kelas::where('walikelas_id', $guru->guru_id)
            ->where('tahun_ajaran_id', $activeYear?->tahun_ajaran_id)
            ->get();

        foreach ($classes as $kelas) {
            // Get total students in this class (Level 4 of hierarchy)
            $totalStudents = data_siswa::where('kelas', $kelas->kelas_id)->count();
            
            if ($totalStudents == 0) continue;

            // Get completed assessments for this class and semester
            $completedAssessments = student_assessment::whereHas('siswa', function($query) use ($kelas) {
                    $query->where('kelas', $kelas->kelas_id);
                })
                ->where('semester', $semester)
                ->where('status', 'selesai')
                ->count();

            // If all students have completed assessments, send notification
            if ($completedAssessments >= $totalStudents) {
                $this->sendStudentAssessmentNotification($guru, $kelas, $semester, $totalStudents);
            }
        }
    }

    /**
     * Send monthly report completion notification to admins
     */
    private function sendMonthlyReportNotification(data_guru $guru, data_kelas $kelas, int $month, int $year, int $totalStudents): void
    {
        // Get month name in Indonesian
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthName = $monthNames[$month] ?? $month;

        // Get one monthly report as sample for notification (needed by notification constructor)
        $sampleReport = monthly_reports::whereHas('siswa', function($query) use ($kelas) {
                $query->where('kelas', $kelas->kelas_id);
            })
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        // Skip if no report found
        if (!$sampleReport) return;

        // Get all admin users (super_admin dan admin)
        $admins = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        // Check if notification already sent for this completion
        $notificationExists = \Illuminate\Notifications\DatabaseNotification::where('data->guru_id', $guru->guru_id)
            ->where('data->kelas_id', $kelas->kelas_id)
            ->where('data->month', $monthName)
            ->where('data->year', $year)
            ->where('data->type', 'monthly_report_completed')
            ->exists();

        if (!$notificationExists) {
            foreach ($admins as $admin) {
                $admin->notify(new MonthlyReportCompletedNotification($sampleReport, $guru, $kelas));
            }
        }
    }

    /**
     * Send student assessment completion notification to admins
     * Uses tahunAjaran relationship from linear hierarchy
     */
    private function sendStudentAssessmentNotification(data_guru $guru, data_kelas $kelas, string $semester, int $totalStudents): void
    {
        $tahunAjaran = $kelas->tahunAjaran;
        
        // Get one student assessment as sample for notification (needed by notification constructor)
        $sampleAssessment = student_assessment::whereHas('siswa', function($query) use ($kelas) {
                $query->where('kelas', $kelas->kelas_id);
            })
            ->where('semester', $semester)
            ->where('status', 'selesai')
            ->with('siswa')
            ->first();

        // Skip if no assessment found
        if (!$sampleAssessment || !$sampleAssessment->siswa || !$tahunAjaran) return;

        // Get all admin users (super_admin dan admin)
        $admins = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        // Check if notification already sent for this completion
        $notificationExists = \Illuminate\Notifications\DatabaseNotification::where('data->guru_id', $guru->guru_id)
            ->where('data->kelas_id', $kelas->kelas_id)
            ->where('data->semester', $semester)
            ->where('data->tahun_ajaran', $tahunAjaran->year)
            ->where('data->type', 'student_assessment_completed')
            ->exists();

        if (!$notificationExists) {
            foreach ($admins as $admin) {
                $admin->notify(new StudentAssessmentCompletedNotification($sampleAssessment->siswa, $kelas, $tahunAjaran, $guru));
            }
        }
    }
}