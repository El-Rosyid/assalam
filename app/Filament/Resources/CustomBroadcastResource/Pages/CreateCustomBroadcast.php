<?php

namespace App\Filament\Resources\CustomBroadcastResource\Pages;

use App\Filament\Resources\CustomBroadcastResource;
use App\Jobs\SendCustomBroadcastJob;
use App\Models\CustomBroadcastLog;
use App\Models\data_siswa;
use App\Services\WhatsAppNotificationService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCustomBroadcast extends CreateRecord
{
    protected static string $resource = CustomBroadcastResource::class;
    
    protected static ?string $title = 'Kirim Pesan WhatsApp';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('history')
                ->label('Riwayat Broadcast')
                ->icon('heroicon-o-clock')
                ->url(fn (): string => static::getResource()::getUrl('history'))
                ->color('gray'),
        ];
    }
    
    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Kirim')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Pengiriman')
            ->modalDescription(function () {
                $data = $this->form->getState();
                $total = $this->calculateTotalRecipients($data);
                
                return "Anda akan mengirim pesan ke **{$total} orang tua siswa**. Pesan akan dikirim secara bertahap. Lanjutkan?";
            })
            ->modalSubmitActionLabel('Ya, Kirim')
            ->icon('heroicon-o-paper-airplane')
            ->color('success');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'draft';
        
        // Calculate total recipients
        $data['total_recipients'] = $this->calculateTotalRecipients($data);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $broadcast = $this->record;
        
        // Mark as sending
        $broadcast->markAsSending();
        
        // Get recipients
        $recipients = $broadcast->getRecipients();
        
        // Create broadcast logs and dispatch jobs
        $jobCount = 0;
        foreach ($recipients as $siswa) {
            if (empty($siswa->no_telp_ortu_wali)) {
                continue;
            }
            
            // Create log
            $log = CustomBroadcastLog::create([
                'custom_broadcast_id' => $broadcast->id,
                'siswa_nis' => $siswa->nis,
                'phone_number' => $siswa->no_telp_ortu_wali,
                'message' => $broadcast->message,
                'status' => 'pending',
            ]);
            
            // Dispatch job
            SendCustomBroadcastJob::dispatch($log->id);
            $jobCount++;
        }
        
        // Send notification to user
        Notification::make()
            ->title('Broadcast sedang diproses')
            ->body("Broadcast ke {$jobCount} penerima sedang dalam proses pengiriman. Anda akan menerima notifikasi setelah selesai.")
            ->icon('heroicon-o-clock')
            ->iconColor('warning')
            ->sendToDatabase(auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('history');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Broadcast sedang diproses')
            ->body('Pengiriman WhatsApp sedang dalam proses. Cek notifikasi untuk update status.')
            ->send();
    }

    private function calculateTotalRecipients(array $data): int
    {
        return match($data['target_type']) {
            'all' => data_siswa::count(),
            'class' => data_siswa::whereIn('kelas', $data['target_ids'] ?? [])->count(),
            'individual' => count($data['target_ids'] ?? []),
            default => 0,
        };
    }
}
