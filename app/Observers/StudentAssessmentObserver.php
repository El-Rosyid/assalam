<?php

namespace App\Observers;

use App\Models\student_assessment;
use App\Services\NotificationService;
use App\Notifications\StudentAssessmentCompletedNotification;
use App\Models\User;

class StudentAssessmentObserver
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the student_assessment "created" event.
     */
    public function created(student_assessment $studentAssessment): void
    {
        $this->checkCompletion($studentAssessment);
    }

    /**
     * Handle the student_assessment "updated" event.
     */
    public function updated(student_assessment $studentAssessment): void
    {
        // Check if status changed to 'selesai'
        if ($studentAssessment->wasChanged('status') && $studentAssessment->status === 'selesai') {
            $this->sendCompletionNotification($studentAssessment);
        }
        
        $this->checkCompletion($studentAssessment);
    }

    /**
     * Send notification when individual student assessment is completed
     */
    private function sendCompletionNotification(student_assessment $studentAssessment): void
    {
        $siswa = $studentAssessment->siswa;
        $kelas = $siswa?->kelasInfo;
        $tahunAjaran = $studentAssessment->tahunAjaran;
        $guru = $kelas?->walikelas;

        if (!$siswa || !$kelas || !$tahunAjaran || !$guru) {
            return;
        }

        // Send notification to all admin users
        $adminUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            $admin->notify(new StudentAssessmentCompletedNotification(
                $siswa, 
                $kelas, 
                $tahunAjaran, 
                $guru
            ));
        }
    }

    /**
     * Check if student assessments are completed for the class
     */
    private function checkCompletion(student_assessment $studentAssessment): void
    {
        // Get the student and class info
        $siswa = $studentAssessment->siswa;
        if (!$siswa || !$siswa->kelasInfo) {
            return;
        }

        $kelas = $siswa->kelasInfo;
        $waliKelas = $kelas->walikelas;

        if (!$waliKelas) {
            return;
        }

        // Check completion for this class
        $this->notificationService->checkStudentAssessmentCompletion($waliKelas, 'default');
    }
}