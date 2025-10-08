<?php

namespace App\Filament\Resources\DataSiswaResource\Pages;

use App\Filament\Resources\DataSiswaResource;
use Filament\Actions;
use App\Filament\Pages\Traits\HasBackButton;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class EditDataSiswa extends EditRecord
{
    use HasBackButton;
    protected static ?string $title = 'Edit Data Siswa';
    protected static string $resource = DataSiswaResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load data user ke form account
        if ($this->record->user) {
            $data['account'] = [
                'user_id' => $this->record->user->id,
                'username' => $this->record->user->username,
                'name' => $this->record->user->name,
                // Password tidak perlu diisi saat edit
            ];
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update data user jika ada perubahan
        if (isset($data['account']) && $this->record->user) {
            $user = $this->record->user;
            
            $updateData = [
                'username' => $data['account']['username'],
                'name' => $data['account']['name'],
            ];
            
            // Update password hanya jika diisi
            if (filled($data['account']['password'] ?? null)) {
                $updateData['password'] = Hash::make($data['account']['password']);
            }
            
            $user->update($updateData);
        }
        
        // Hapus data account agar tidak error saat save data siswa
        unset($data['account']);
        
        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Siswa Berhasil Diperbarui!')
            ->body('Perubahan data siswa telah berhasil disimpan.')
            ->duration(5000);
    }
     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
