<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali ke Dashboard')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url('/admin'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ambil current_password dari request (karena dehydrated)
        // Filament v3 pakai data dari form state, bukan request langsung
        $formData = $this->form->getRawState();
        $currentPassword = $formData['current_password'] ?? null;
        $newPassword = $data['password'] ?? null;

        // Debug
        Log::info('Before save mutation', [
            'has_password' => !empty($newPassword),
            'has_current' => !empty($currentPassword)
        ]);

        // Validasi password saat ini jika user ingin mengubah password
        if (!empty($newPassword)) {
            // Cek current_password hanya jika user mau ubah password
            if (empty($currentPassword)) {
                Notification::make()
                    ->title('Password saat ini wajib diisi')
                    ->danger()
                    ->send();
                
                $this->halt();
            }
            
            if (!Hash::check($currentPassword, auth()->user()->password)) {
                Notification::make()
                    ->title('Password saat ini salah')
                    ->danger()
                    ->send();
                
                $this->halt();
            }
            
            // Set flag bahwa password akan diubah
            $this->passwordChanged = true;
        }

        // Remove password jika kosong
        if (empty($data['password'])) {
            unset($data['password']);
            $this->passwordChanged = false;
        }

        return $data;
    }
    
    protected bool $passwordChanged = false;

    protected function afterSave(): void
    {
        Log::info('AfterSave called', [
            'passwordChanged_flag' => $this->passwordChanged,
            'user_id' => $this->record->user_id
        ]);
        
        // Jika password diubah, update session agar tidak logout
        if ($this->passwordChanged) {
            Log::info('Attempting to re-login user');
            
            // Refresh auth instance dengan user terbaru
            $user = $this->record->fresh();
            
            // Force set user di auth session (tanpa regenerate!)
            auth()->login($user, false); // false = don't remember
            
            Log::info('Re-login successful without session regeneration');
            
            Notification::make()
                ->success()
                ->title('Password berhasil diubah')
                ->body('Password Anda telah berhasil diperbarui.')
                ->send();
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile berhasil diperbarui')
            ->body('Data profile Anda telah berhasil disimpan.');
    }

    public function mount(int | string $record): void
    {
        // Pastikan user hanya bisa edit profile mereka sendiri
        if ($record != auth()->id()) {
            $this->redirect(ProfileResource::getUrl('edit', ['record' => auth()->id()]));
            return;
        }

        parent::mount($record);
    }

    protected function getRedirectUrl(): ?string
    {
        // Jangan redirect jika password berhasil diubah - stay di halaman
        if ($this->passwordChanged) {
            return null; // Stay di current page
        }
        
        // Stay di halaman edit profile
        return $this->getResource()::getUrl('edit', ['record' => $this->record->user_id]);
    }
}