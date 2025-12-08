<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class StudentProfileWidget extends Widget
{
    protected static string $view = 'filament.widgets.student-profile-widget';
    
    protected int | string | array $columnSpan = 1;
    
    protected static ?int $sort = 1;
    
    public function getData(): array
    {
        $user = Auth::user();
        $siswa = $user?->siswa;
        
        if (!$siswa) {
            return [];
        }
        
        return [
            'avatar' => $user->avatar ?? null, // Avatar dari tabel users
            'nama' => $siswa->nama_lengkap ?? 'N/A',
            'nis' => $siswa->nis ?? 'N/A',
            'nisn' => $siswa->nisn ?? 'N/A',
            'kelas' => $siswa->kelasInfo->nama_kelas ?? 'Belum ada kelas',
            'jenis_kelamin' => $siswa->jenis_kelamin ?? 'N/A',
            'tanggal_lahir' => $siswa->tanggal_lahir?->format('d M Y') ?? 'N/A',
            'tempat_lahir' => $siswa->tempat_lahir ?? 'N/A',
            'agama' => $siswa->agama ?? 'N/A',
            'alamat' => $siswa->alamat ?? 'N/A',
            'nama_ayah' => $siswa->nama_ayah ?? 'N/A',
            'nama_ibu' => $siswa->nama_ibu ?? 'N/A',
        ];
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->siswa;
    }
}
