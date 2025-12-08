<?php

namespace App\Observers;

use App\Models\GrowthRecord;
use App\Models\User;
use App\Notifications\GrowthRecordCompletedNotification;

class GrowthRecordObserver
{
    /**
     * Handle the GrowthRecord "created" event.
     */
    public function created(GrowthRecord $growthRecord): void
    {
        $this->checkMonthlyCompletion($growthRecord);
    }

    /**
     * Handle the GrowthRecord "updated" event.
     */
    public function updated(GrowthRecord $growthRecord): void
    {
        // Only check if actual measurement data was added
        if ($this->hasMeasurementData($growthRecord)) {
            $this->checkMonthlyCompletion($growthRecord);
        }
    }

    /**
     * Check if growth record has measurement data
     */
    private function hasMeasurementData(GrowthRecord $growthRecord): bool
    {
        return $growthRecord->lingkar_kepala !== null ||
               $growthRecord->lingkar_lengan !== null ||
               $growthRecord->berat_badan !== null ||
               $growthRecord->tinggi_badan !== null;
    }

    /**
     * Check if all students in the class have completed growth records for the month
     */
    private function checkMonthlyCompletion(GrowthRecord $growthRecord): void
    {
        $guru = $growthRecord->guru;
        $kelas = $growthRecord->kelas;

        if (!$guru || !$kelas) {
            return;
        }

        // Get all students in this class using the correct primary key
        $allStudentsInClass = \App\Models\data_siswa::where('kelas', $kelas->kelas_id)
            ->pluck('nis'); // Use 'nis' - custom primary key

        if ($allStudentsInClass->isEmpty()) {
            return;
        }

        // Count completed growth records for this month and class
        $completedRecords = GrowthRecord::where('month', $growthRecord->month)
            ->where('data_kelas_id', $kelas->kelas_id)
            ->whereIn('siswa_nis', $allStudentsInClass) // siswa_nis is the correct foreign key
            ->where(function ($query) {
                $query->whereNotNull('lingkar_kepala')
                      ->orWhereNotNull('lingkar_lengan')
                      ->orWhereNotNull('berat_badan')
                      ->orWhereNotNull('tinggi_badan');
            })
            ->count();

        // If all students have completed growth records, send notification
        if ($completedRecords >= $allStudentsInClass->count()) {
            $this->sendCompletionNotification($guru, $kelas, $growthRecord->month, $allStudentsInClass->count());
        }
    }

    /**
     * Send notification when all growth records for a month are completed
     */
    private function sendCompletionNotification($guru, $kelas, $month, $totalStudents): void
    {
        // Check if notification was already sent for this month/class combination
        $existingNotification = \App\Models\User::whereHas('notifications', function ($query) use ($month, $kelas) {
            $query->where('data->type', 'growth_record_completed')
                  ->where('data->month', $month)
                  ->where('data->kelas_id', $kelas->kelas_id)
                  ->where('created_at', '>=', now()->subDay()); // Within last 24 hours
        })->exists();

        if ($existingNotification) {
            return; // Notification already sent
        }

        // Send notification to all admin users
        $adminUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            $notification = new GrowthRecordCompletedNotification($guru, $kelas, $month, $totalStudents);
            
            // Add extra data for deduplication
            $notificationData = $notification->toArray($admin);
            $notificationData['month'] = $month;
            $notificationData['kelas_id'] = $kelas->kelas_id;
            $notificationData['guru_id'] = $guru->guru_id;
            
            // Create custom notification with extra data
            $admin->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => get_class($notification),
                'notifiable_type' => get_class($admin),
                'notifiable_id' => $admin->id,
                'data' => $notificationData,
            ]);
        }
    }
}