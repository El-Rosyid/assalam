<?php

namespace App\Filament\Resources\DataSiswaResource\Pages;

use App\Filament\Resources\DataSiswaResource;
use Filament\Actions;
use App\Filament\Pages\Traits\HasBackButton;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Carbon\Carbon;

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
                'user_id' => $this->record->user->user_id,
                'username' => $this->record->user->username,
                'name' => $this->record->user->name,
                // Password tidak perlu diisi saat edit
            ];
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Parse tanggal lahir - handle berbagai format dari paste
        if (isset($data['tanggal_lahir']) && is_string($data['tanggal_lahir'])) {
            $data['tanggal_lahir'] = $this->parseDateFormat($data['tanggal_lahir']);
        }

        // Update user account jika ada perubahan
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

    /**
     * Parse berbagai format tanggal
     */
    private function parseDateFormat($date): string
    {
        if (empty($date)) return null;
        
        $date = trim($date);
        
        try {
            // Format: YYYY-MM-DD (sudah benar)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date;
            }
            
            // Format: DD-MM-YYYY atau DD/MM/YYYY (umum dari Word)
            if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $date, $matches)) {
                $parsed = Carbon::createFromFormat('d-m-Y', sprintf('%02d-%02d-%04d', $matches[1], $matches[2], $matches[3]));
                return $parsed->format('Y-m-d');
            }
            
            // Format: DD MM YYYY (spasi)
            if (preg_match('/^(\d{1,2})\s+(\d{1,2})\s+(\d{4})$/', $date, $matches)) {
                $parsed = Carbon::createFromFormat('d m Y', sprintf('%02d %02d %04d', $matches[1], $matches[2], $matches[3]));
                return $parsed->format('Y-m-d');
            }
            
            // Coba parse dengan Carbon (lebih fleksibel)
            $parsed = Carbon::parse($date);
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return $date;
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Siswa Berhasil Diperbarui!')
            ->body('Perubahan data siswa telah berhasil disimpan.')
            ->duration(3000);
    }
     
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
