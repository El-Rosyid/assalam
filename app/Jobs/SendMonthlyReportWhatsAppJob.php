<?php

namespace App\Jobs;

use App\Models\MonthlyReportBroadcast;
use App\Models\monthly_reports;
use App\Models\User;
use App\Notifications\InvalidPhoneNumberNotification;
use App\Notifications\WhatsAppFailedNotification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMonthlyReportWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Cukup 1x attempt untuk prevent spam
    public $backoff = 30; // Tunggu 30 detik jika retry
    
    protected $monthlyReportId;
    protected $broadcastId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $monthlyReportId, int $broadcastId)
    {
        $this->monthlyReportId = $monthlyReportId;
        $this->broadcastId = $broadcastId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppNotificationService $whatsappService): void
    {
        $broadcast = MonthlyReportBroadcast::find($this->broadcastId);
        
        if (!$broadcast) {
            Log::error('Broadcast record not found', ['id' => $this->broadcastId]);
            return;
        }
        
        // Cek jika sudah terkirim, skip untuk prevent duplicate
        if ($broadcast->status === 'sent') {
            Log::info('Monthly report already sent, skipping', ['broadcast_id' => $this->broadcastId]);
            return;
        }
        
        $monthlyReport = monthly_reports::find($this->monthlyReportId);
        
        if (!$monthlyReport) {
            Log::error('Monthly report not found', ['id' => $this->monthlyReportId]);
            $broadcast->markAsFailed('Laporan bulanan tidak ditemukan');
            return;
        }
        
        // Validasi nomor telepon
        $phoneNumber = $whatsappService->validatePhoneNumber($broadcast->phone_number);
        
        if (!$phoneNumber) {
            $broadcast->markAsFailed('Nomor telepon tidak valid');
            
            // Notifikasi admin menggunakan Laravel native
            $adminUsers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->get();
            
            foreach ($adminUsers as $admin) {
                $admin->notify(new InvalidPhoneNumberNotification($monthlyReport->siswa->nama));
            }
            
            return;
        }
        
        // Format pesan
        $message = $whatsappService->formatMonthlyReportMessage($monthlyReport);
        
        // Get image URLs jika ada foto
        $imageUrls = $whatsappService->getImageUrls($monthlyReport);
        
        // Kirim WhatsApp dengan gambar (jika ada)
        $result = $whatsappService->sendWhatsApp($phoneNumber, $message, $imageUrls);
        
        if ($result['success']) {
            $broadcast->markAsSent(json_encode($result['response'] ?? null));
            
            Log::info('WhatsApp sent successfully', [
                'broadcast_id' => $this->broadcastId,
                'phone' => $phoneNumber,
                'siswa' => $monthlyReport->siswa->nama_lengkap,
                'images_count' => count($imageUrls)
            ]);
            
        } else {
            $errorMessage = $result['error'] ?? 'Unknown error';
            $broadcast->markAsFailed($errorMessage);
            
            Log::error('WhatsApp send failed', [
                'broadcast_id' => $this->broadcastId,
                'phone' => $phoneNumber,
                'error' => $errorMessage
            ]);
            
            // Notifikasi admin tentang kegagalan
            if ($monthlyReport) {
                $adminUsers = User::whereHas('roles', function($query) {
                    $query->whereIn('name', ['super_admin', 'admin']);
                })->get();
                
                foreach ($adminUsers as $admin) {
                    $admin->notify(new WhatsAppFailedNotification(
                        $monthlyReport->siswa->nama,
                        $errorMessage,
                        1
                    ));
                }
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $broadcast = MonthlyReportBroadcast::find($this->broadcastId);
        $monthlyReport = monthly_reports::find($this->monthlyReportId);
        
        if ($broadcast) {
            $broadcast->markAsFailed($exception->getMessage());
        }
        
        // Notifikasi admin tentang kegagalan setelah 3 percobaan
        if ($monthlyReport) {
            $adminUsers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->get();
            
            foreach ($adminUsers as $admin) {
                $admin->notify(new WhatsAppFailedNotification(
                    $monthlyReport->siswa->nama,
                    $exception->getMessage(),
                    $this->tries
                ));
            }
        }
        
        Log::error('WhatsApp job failed after max attempts', [
            'broadcast_id' => $this->broadcastId,
            'monthly_report_id' => $this->monthlyReportId,
            'error' => $exception->getMessage()
        ]);
    }
}
