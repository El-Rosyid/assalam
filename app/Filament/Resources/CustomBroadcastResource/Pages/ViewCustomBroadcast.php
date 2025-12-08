<?php

namespace App\Filament\Resources\CustomBroadcastResource\Pages;

use App\Filament\Resources\CustomBroadcastResource;
use App\Jobs\SendCustomBroadcastJob;
use App\Models\CustomBroadcastLog;
use App\Services\WhatsAppNotificationService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\ViewRecord\Concerns\HasPages;

class ViewCustomBroadcast extends ViewRecord
{
    protected static string $resource = CustomBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send')
                ->label('📤 Kirim Sekarang')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pengiriman Broadcast')
                ->modalDescription(fn () => 
                    "Anda akan mengirim broadcast ke {$this->record->total_recipients} orang tua siswa. " .
                    "Proses akan dilakukan secara bertahap melalui queue. Lanjutkan?"
                )
                ->modalSubmitActionLabel('Ya, Kirim Sekarang')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function () {
                    $this->sendBroadcast();
                }),
            
            Actions\Action::make('retry_failed')
                ->label('🔄 Kirim Ulang yang Gagal')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Kirim Ulang Pesan Gagal')
                ->modalDescription(fn () => 
                    "Mengirim ulang ke {$this->record->failed_count} penerima yang gagal. Lanjutkan?"
                )
                ->visible(fn () => $this->record->failed_count > 0 && $this->record->status === 'completed')
                ->action(function () {
                    $this->retryFailed();
                }),
            
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
            
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Broadcast')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Judul'),
                        
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Dibuat Oleh'),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Dibuat')
                            ->dateTime('d M Y, H:i'),
                        
                        Infolists\Components\TextEntry::make('sent_at')
                            ->label('Dikirim Pada')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),
                        
                        Infolists\Components\TextEntry::make('target_type_text')
                            ->label('Target Penerima'),
                        
                        Infolists\Components\TextEntry::make('status_badge')
                            ->label('Status'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Statistik Pengiriman')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_recipients')
                                    ->label('Total Penerima')
                                    ->badge()
                                    ->color('info'),
                                
                                Infolists\Components\TextEntry::make('sent_count')
                                    ->label('Terkirim')
                                    ->badge()
                                    ->color('success'),
                                
                                Infolists\Components\TextEntry::make('failed_count')
                                    ->label('Gagal')
                                    ->badge()
                                    ->color('danger'),
                                
                                Infolists\Components\TextEntry::make('progress_percentage')
                                    ->label('Progress')
                                    ->formatStateUsing(fn ($state) => "{$state}%")
                                    ->badge()
                                    ->color(fn ($state) => $state >= 90 ? 'success' : 'warning'),
                            ]),
                    ])
                    ->visible(fn () => $this->record->total_recipients > 0),
                
                Infolists\Components\Section::make('Isi Pesan')
                    ->schema([
                        Infolists\Components\TextEntry::make('message')
                            ->label('')
                            ->columnSpanFull()
                            ->markdown(),
                    ]),
                
                Infolists\Components\Section::make('Gambar Attachment')
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_path')
                            ->label('')
                            ->disk('public')
                            ->height(200),
                    ])
                    ->visible(fn () => !empty($this->record->image_path)),
            ]);
    }

    protected function sendBroadcast(): void
    {
        $broadcast = $this->record;
        $whatsappService = app(WhatsAppNotificationService::class);
        
        // Mark as sending
        $broadcast->markAsSending();
        
        // Get recipients
        $recipients = $broadcast->getRecipients();
        
        if ($recipients->isEmpty()) {
            Notification::make()
                ->title('Error')
                ->body('Tidak ada penerima yang ditemukan.')
                ->danger()
                ->send();
            
            $broadcast->markAsFailed();
            return;
        }
        
        // Create logs and dispatch jobs
        $dispatched = 0;
        
        foreach ($recipients as $siswa) {
            // Validate phone number
            $phoneNumber = $whatsappService->validatePhoneNumber($siswa->no_telp_ortu_wali);
            
            if (!$phoneNumber) {
                continue; // Skip invalid phone numbers
            }
            
            // Format message dengan placeholder replacement dan title
            $formattedMessage = $whatsappService->formatCustomMessage(
                $broadcast->message, 
                $siswa,
                $broadcast->title
            );
            
            // Create log
            $log = CustomBroadcastLog::create([
                'custom_broadcast_id' => $broadcast->id,
                'siswa_nis' => $siswa->nis,
                'phone_number' => $phoneNumber,
                'message' => $formattedMessage,
                'status' => 'pending',
            ]);
            
            // Dispatch job
            SendCustomBroadcastJob::dispatch($log->id);
            $dispatched++;
        }
        
        Notification::make()
            ->title('Broadcast Sedang Dikirim')
            ->body("{$dispatched} pesan telah masuk ke antrian. Refresh halaman untuk melihat progress.")
            ->success()
            ->send();
    }

    protected function retryFailed(): void
    {
        $failedLogs = $this->record->failedLogs;
        
        foreach ($failedLogs as $log) {
            // Reset status to pending
            $log->update(['status' => 'pending', 'error_message' => null]);
            
            // Dispatch job again
            SendCustomBroadcastJob::dispatch($log->id);
        }
        
        Notification::make()
            ->title('Pengiriman Ulang Dimulai')
            ->body("{$failedLogs->count()} pesan sedang dikirim ulang.")
            ->success()
            ->send();
    }
}
