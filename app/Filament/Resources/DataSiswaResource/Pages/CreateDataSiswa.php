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
use Carbon\Carbon;

class CreateDataSiswa extends CreateRecord
{
    protected static ?string $title = 'Tambah Data Siswa';
    protected static string $resource = DataSiswaResource::class;

    use HasBackButton;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Parse tanggal lahir - handle berbagai format dari paste
        if (isset($data['tanggal_lahir']) && is_string($data['tanggal_lahir'])) {
            $data['tanggal_lahir'] = $this->parseDateFormat($data['tanggal_lahir']);
        }

        // Ambil data account dari form
        $username = $data['account']['username'] ?? null;
        $password = $data['account']['password'] ?? null;
        $name = $data['account']['name'] ?? $data['nama_lengkap'];

        if (!$username || !$password) {
            throw new \Exception('Username dan Password harus diisi');
        }

        // Password sudah di-hash di form (dehydrateStateUsing Hash::make)
        // Tapi kita hash lagi untuk memastikan
        $hashedPassword = is_string($password) && strlen($password) > 20 
            ? $password // sudah hashed
            : Hash::make($password); // belum hashed, hash sekarang

        // Buat user baru
        $user = User::create([
            'username' => $username,
            'password' => $hashedPassword,
            'name' => $name,
            'email' => $data['email'] ?? ($username . '@sekolah.local'),
        ]);

        // Set user_id ke data siswa
        $data['user_id'] = $user->user_id;

        $user->assignRole('siswa');

        // Hapus data account agar tidak error saat simpan
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
