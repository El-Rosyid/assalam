<?php

namespace App\Filament\Resources\DataGuruResource\Pages;

use App\Filament\Resources\DataGuruResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateDataGuru extends CreateRecord
{
    protected static string $resource = DataGuruResource::class;
    protected static ?string $title = 'Tambah Data Guru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ambil data account dari form
        $username = $data['account']['username'] ?? $data['nip'];
        $password = $data['account']['password'] ?? $data['nip'];
        $name     = $data['account']['name'] ?? $data['nama_lengkap'];

        // Pastikan password minimal 8
        if (strlen($password) < 8) {
            $password = $password . '1234';
        }

        // Buat user baru
        $user = User::create([
            'username' => $username,
            'password' => $password, // sudah bcrypt di form
            'name'     => $name,
        ]);

        // Pastikan role guru ada
        if (!Role::where('name', 'guru')->exists()) {
            Role::create(['name' => 'guru']);
        }
        $user->assignRole('guru');

        // Set user_id ke data guru
        $data['user_id'] = $user->id;

        // Hapus data account agar tidak error
        unset($data['account']);

        return $data;
    }
}
