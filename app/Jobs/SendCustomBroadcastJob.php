<?php

namespace App\Jobs;

use App\Models\CustomBroadcastLog;
use App\Models\User;
use App\Notifications\InvalidPhoneNumberNotification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCustomBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Cukup 1x attempt untuk prevent spam
    public $backoff = 30; // Tunggu 30 detik jika retry (seharusnya tidak terjadi)
    
    protected $logId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $logId)
    {
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppNotificationService $whatsappService): void
    {
        $log = CustomBroadcastLog::with(['broadcast', 'siswa'])->find($this->logId);
        
        if (!$log) {
            Log::error('Custom broadcast log not found', ['id' => $this->logId]);
            return;
        }

        // Cek jika sudah terkirim, skip untuk prevent duplicate
        if ($log->status === 'sent') {
            Log::info('Custom broadcast already sent, skipping', ['log_id' => $this->logId]);
            return;
        }

        // Increment retry count
        $log->incrementRetry();
        
        // Validasi nomor telepon
        $phoneNumber = $whatsappService->validatePhoneNumber($log->phone_number);
        
        if (!$phoneNumber) {
            $log->markAsFailed('Nomor telepon tidak valid');
            
            // Notifikasi admin
            $this->notifyAdmins($log->siswa->nama_lengkap ?? 'Unknown', 'Nomor telepon tidak valid');
            
            return;
        }
        
        $baseUrl = env('APP_URL', 'https://abaassalam.my.id');
        
        // Kirim WhatsApp text only (tanpa attachment)
        $result = $whatsappService->sendWhatsApp($phoneNumber, $log->message, null, null);
        
        Log::info('SendCustomBroadcastJob result', [
            'log_id' => $this->logId,
            'result_success' => $result['success'],
            'result_error' => $result['error'] ?? null,
        ]);
        
        if ($result['success']) {
            $log->markAsSent(json_encode($result['response'] ?? null));
            
            Log::info('Custom broadcast sent successfully', [
                'log_id' => $this->logId,
                'broadcast_id' => $log->custom_broadcast_id,
                'phone' => $phoneNumber,
                'siswa' => $log->siswa->nama_lengkap ?? 'Unknown'
            ]);
            
            // Check if all broadcasts are completed
            $this->checkAndNotifyCompletion($log->broadcast);
            
        } else {
            $errorMessage = $result['error'] ?? 'Unknown error';
            
            Log::error('Custom broadcast send failed', [
                'log_id' => $this->logId,
                'broadcast_id' => $log->custom_broadcast_id,
                'phone' => $phoneNumber,
                'error' => $errorMessage,
                'attempt' => $this->attempts()
            ]);
            
            // Mark as failed tanpa throw exception (prevent retry)
            $log->markAsFailed($errorMessage);
            
            // Notifikasi admin tentang kegagalan
            if ($log->siswa) {
                $this->notifyAdmins(
                    $log->siswa->nama_lengkap ?? 'Unknown',
                    $errorMessage
                );
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $log = CustomBroadcastLog::find($this->logId);
        
        if ($log && $log->status !== 'failed') {
            $log->markAsFailed($exception->getMessage());
        }
        
        // Notifikasi admin tentang kegagalan setelah 3 percobaan
        if ($log && $log->siswa) {
            $this->notifyAdmins(
                $log->siswa->nama_lengkap ?? 'Unknown',
                $exception->getMessage()
            );
        }
        
        Log::error('Custom broadcast job failed after max attempts', [
            'log_id' => $this->logId,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Check if broadcast is completed and notify user
     */
    private function checkAndNotifyCompletion($broadcast): void
    {
        // Reload from database to get fresh counts
        $broadcast->refresh();
        
        // Check if all messages have been processed (sent or failed)
        if (($broadcast->sent_count + $broadcast->failed_count) >= $broadcast->total_recipients) {
            // Send completion notification to the user who created the broadcast
            \Filament\Notifications\Notification::make()
                ->title('Broadcast selesai terkirim')
                ->body("Broadcast \"{$broadcast->title}\" telah selesai. {$broadcast->sent_count} terkirim, {$broadcast->failed_count} gagal dari total {$broadcast->total_recipients} penerima.")
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->sendToDatabase($broadcast->user);
        }
    }

    /**
     * Notify admins about failure
     */
    private function notifyAdmins(string $siswaName, string $errorMessage): void
    {
        $adminUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new InvalidPhoneNumberNotification($siswaName . ': ' . $errorMessage));
        }
    }
}
