<?php

namespace App\Observers;

use App\Jobs\SendMonthlyReportWhatsAppJob;
use App\Models\MonthlyReportBroadcast;
use App\Models\monthly_reports;
use App\Models\User;
use App\Notifications\InvalidPhoneNumberNotification;
use App\Notifications\MonthlyReportCompletedNotification;
use App\Services\NotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MonthlyReportObserver
{
    protected NotificationService $notificationService;
    protected WhatsAppNotificationService $whatsappService;

    public function __construct(NotificationService $notificationService, WhatsAppNotificationService $whatsappService)
    {
        $this->notificationService = $notificationService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the monthly_reports "created" event.
     */
    public function created(monthly_reports $monthlyReport): void
    {
        // Send notification when monthly report is completed
        if ($monthlyReport->status === 'final') {
            $this->sendCompletionNotification($monthlyReport);
        }
        
        $this->checkCompletion($monthlyReport);
        $this->handleWhatsAppBroadcast($monthlyReport);
    }

    /**
     * Handle the monthly_reports "updated" event.
     */
    public function updated(monthly_reports $monthlyReport): void
    {
        // Auto-delete old photos when updating
        if ($monthlyReport->isDirty('photos')) {
            $oldPhotos = $monthlyReport->getOriginal('photos');
            $newPhotos = $monthlyReport->photos;
            
            if (!empty($oldPhotos) && is_array($oldPhotos)) {
                // Find photos that are removed
                $removedPhotos = array_diff($oldPhotos, $newPhotos ?? []);
                
                foreach ($removedPhotos as $photoPath) {
                    $this->moveOldFileToTrash($photoPath, $monthlyReport->id, 'photos');
                }
            }
        }
        
        // Send notification when status changes to final
        if ($monthlyReport->wasChanged('status') && $monthlyReport->status === 'final') {
            $this->sendCompletionNotification($monthlyReport);
        }
        
        $this->checkCompletion($monthlyReport);
        
        // Hanya trigger WhatsApp jika catatan berubah
        if ($monthlyReport->wasChanged('catatan')) {
            $this->handleWhatsAppBroadcast($monthlyReport);
        }
    }
    
    /**
     * Handle the monthly_reports "deleting" event.
     */
    public function deleting(monthly_reports $monthlyReport): void
    {
        // Cleanup files when deleting
        $this->cleanupFiles($monthlyReport);
    }

    /**
     * Send notification when monthly report is completed
     */
    private function sendCompletionNotification(monthly_reports $monthlyReport): void
    {
        $siswa = $monthlyReport->siswa;
        $kelas = $siswa?->kelasInfo;
        $guru = $kelas?->walikelas;

        if (!$siswa || !$kelas || !$guru) {
            return;
        }

        // Send notification to all admin users
        $adminUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            $admin->notify(new MonthlyReportCompletedNotification(
                $monthlyReport,
                $guru,
                $kelas
            ));
        }
    }

    /**
     * Check if monthly reports are completed for the class
     */
    private function checkCompletion(monthly_reports $monthlyReport): void
    {
        // Get the student and class info
        $siswa = $monthlyReport->siswa;
        if (!$siswa || !$siswa->kelasInfo) {
            return;
        }

        $kelas = $siswa->kelasInfo;
        $waliKelas = $kelas->walikelas;

        if (!$waliKelas) {
            return;
        }

        // Check completion for this month and year
        $this->notificationService->checkMonthlyReportCompletion(
            $waliKelas,
            $monthlyReport->month,
            $monthlyReport->year
        );
    }
    
    /**
     * Handle WhatsApp broadcast untuk orang tua/wali siswa
     */
    private function handleWhatsAppBroadcast(monthly_reports $monthlyReport): void
    {
        // Cek apakah fitur broadcast aktif
        if (!$this->whatsappService->isBroadcastEnabled()) {
            return;
        }
        
        // Validasi: hanya kirim jika catatan tidak kosong dan bukan draft
        if (empty($monthlyReport->catatan) || trim($monthlyReport->catatan) === '') {
            Log::info('Skipped WhatsApp broadcast: catatan is empty', [
                'monthly_report_id' => $monthlyReport->id
            ]);
            return;
        }
        
        // Cek apakah catatan berisi kata "draft" (case insensitive)
        if (stripos($monthlyReport->catatan, 'draft') !== false) {
            Log::info('Skipped WhatsApp broadcast: catatan contains draft', [
                'monthly_report_id' => $monthlyReport->id
            ]);
            return;
        }
        
        // Cek apakah sudah pernah dikirim sebelumnya (untuk menghindari duplikat)
        $existingBroadcast = MonthlyReportBroadcast::where('monthly_report_id', $monthlyReport->id)
            ->where('status', 'sent')
            ->exists();
            
        if ($existingBroadcast) {
            Log::info('Skipped WhatsApp broadcast: already sent', [
                'monthly_report_id' => $monthlyReport->id
            ]);
            return;
        }
        
        // Ambil nomor telepon siswa
        $siswa = $monthlyReport->siswa;
        
        if (!$siswa) {
            Log::error('Siswa not found for monthly report', [
                'monthly_report_id' => $monthlyReport->id
            ]);
            return;
        }
        
        $phoneNumber = $siswa->no_telp_ortu_wali ?? null;
        
        // Jika tidak ada nomor telepon, buat notifikasi untuk admin
        if (empty($phoneNumber)) {
            $adminUsers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->get();
            
            foreach ($adminUsers as $admin) {
                $admin->notify(new InvalidPhoneNumberNotification($siswa->nama));
            }
            
            Log::warning('Skipped WhatsApp broadcast: no phone number', [
                'monthly_report_id' => $monthlyReport->id,
                'siswa_nis' => $siswa->nis,
                'siswa_nama' => $siswa->nama
            ]);
            
            return;
        }
        
        // Validasi format nomor
        $validatedPhone = $this->whatsappService->validatePhoneNumber($phoneNumber);
        
        if (!$validatedPhone) {
            $adminUsers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->get();
            
            foreach ($adminUsers as $admin) {
                $admin->notify(new InvalidPhoneNumberNotification($siswa->nama, $phoneNumber));
            }
            
            return;
        }
        
        // Buat record broadcast
        $broadcast = MonthlyReportBroadcast::create([
            'monthly_report_id' => $monthlyReport->id,
            'siswa_nis' => $siswa->nis,
            'phone_number' => $validatedPhone,
            'message' => $this->whatsappService->formatMonthlyReportMessage($monthlyReport),
            'status' => 'pending',
            'retry_count' => 0,
        ]);
        
        // Dispatch job ke queue
        SendMonthlyReportWhatsAppJob::dispatch($monthlyReport->id, $broadcast->id);
        
        Log::info('WhatsApp broadcast job dispatched', [
            'broadcast_id' => $broadcast->id,
            'monthly_report_id' => $monthlyReport->id,
            'siswa' => $siswa->nama,
            'phone' => $validatedPhone
        ]);
    }
    
    /**
     * Move old file to .trash folder
     */
    protected function moveOldFileToTrash(string $filePath, int $reportId, string $column): void
    {
        try {
            // Remove storage/ prefix if exists
            $filePath = str_replace(['storage/', '/storage/'], '', $filePath);
            
            if (Storage::disk('public')->exists($filePath)) {
                $fileSize = Storage::disk('public')->size($filePath);
                $fileName = basename($filePath);
                
                // Create .trash folder with date
                $trashFolder = '.trash/' . date('Y-m-d');
                Storage::disk('public')->makeDirectory($trashFolder);
                
                // Move file to trash
                $trashPath = $trashFolder . '/' . $fileName;
                
                // If file exists in trash, append timestamp
                if (Storage::disk('public')->exists($trashPath)) {
                    $trashPath = $trashFolder . '/' . time() . '_' . $fileName;
                }
                
                Storage::disk('public')->move($filePath, $trashPath);
                
                Log::info('Old monthly report photo moved to trash', [
                    'report_id' => $reportId,
                    'column' => $column,
                    'old_file' => $filePath,
                    'trash_path' => $trashPath,
                    'size' => $this->formatBytes($fileSize),
                    'user_id' => auth()->id()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to move monthly report photo to trash', [
                'report_id' => $reportId,
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cleanup all files when permanently deleting
     */
    protected function cleanupFiles(monthly_reports $monthlyReport): void
    {
        try {
            if (!empty($monthlyReport->photos) && is_array($monthlyReport->photos)) {
                foreach ($monthlyReport->photos as $photoPath) {
                    $path = str_replace(['storage/', '/storage/'], '', $photoPath);
                    
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                        Log::info("Deleted monthly report photo on force delete", [
                            'report_id' => $monthlyReport->id,
                            'file' => $path
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to cleanup monthly report files', [
                'report_id' => $monthlyReport->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return number_format($bytes / 1024, 2) . ' KB';
        return number_format($bytes / 1048576, 2) . ' MB';
    }
}