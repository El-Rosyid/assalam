<?php

namespace App\Filament\Resources\DataGuruResource\Pages;

use App\Filament\Pages\Traits\HasBackButton;
use App\Filament\Resources\DataGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditDataGuru extends EditRecord
{
    use HasBackButton;

    protected static ?string $title = 'Edit Data Guru';
    protected static string $resource = DataGuruResource::class;

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

    // TAMBAHKAN METHOD INI untuk handle update password
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
