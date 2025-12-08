<?php

namespace App\Filament\Resources\DataGuruResource\Pages;

use App\Filament\Resources\DataGuruResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class CreateDataGuru extends CreateRecord
{
    protected static string $resource = DataGuruResource::class;
    protected static ?string $title = 'Tambah Data Guru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Parse tanggal lahir - handle berbagai format dari paste
        if (isset($data['tanggal_lahir']) && is_string($data['tanggal_lahir'])) {
            $data['tanggal_lahir'] = $this->parseDateFormat($data['tanggal_lahir']);
        }

        // Ambil data account dari form
        $username = $data['account']['username'] ?? null;
        $password = $data['account']['password'] ?? null;
        $name     = $data['account']['name'] ?? $data['nama_lengkap'];

        if (!$username || !$password) {
            throw new \Exception('Username dan Password harus diisi');
        }

        // Password sudah di-hash di form (dehydrateStateUsing bcrypt)
        // Tapi kita hash lagi untuk memastikan
        $hashedPassword = is_string($password) && strlen($password) > 20 
            ? $password // sudah hashed
            : Hash::make($password); // belum hashed, hash sekarang

        // Buat user baru
        $user = User::create([
            'username' => $username,
            'password' => $hashedPassword,
            'name'     => $name,
            'email'    => $data['email'] ?? ($username . '@sekolah.local'),
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
