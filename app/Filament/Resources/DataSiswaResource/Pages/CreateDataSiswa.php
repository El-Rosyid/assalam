<?php

namespace App\Filament\Resources\DataSiswaResource\Pages;

use App\Filament\Resources\DataSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Testing\Fluent\Concerns\Has;
use App\Filament\Pages\Traits\HasBackButton;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class CreateDataSiswa extends CreateRecord
{
    protected static ?string $title = 'Tambah Data Siswa';
    protected static string $resource = DataSiswaResource::class;

    use HasBackButton;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ambil data account dari form
        $username = $data['account']['username'] ?? $data['nisn'];
        $password = $data['account']['password'] ?? $data['nisn'];
        $name = $data['account']['name'] ?? $data['nama_lengkap'];

        // Buat user baru
        $user = User::create([
            'username' => $username,
            'password' => Hash::make($password),
            'name' => $name,
        ]);

        // Set user_id ke data siswa
        $data['user_id'] = $user->id;

        $user->assignRole('siswa');

        // Hapus data account agar tidak error saat simpan
        unset($data['account']);

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Siswa Berhasil Ditambahkan!')
            ->body('Data siswa baru telah berhasil disimpan ke dalam sistem.')
            ->duration(5000);
    }

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
