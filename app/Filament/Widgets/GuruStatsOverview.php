<?php

namespace App\Filament\Widgets;

use App\Models\data_kelas;
use App\Models\data_siswa;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GuruStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $guru = $user->guru ?? null;
        
        if (!$guru) {
            return [
                Stat::make('Total Kelas Diampu', 0)
                    ->description('Anda belum ditugaskan sebagai wali kelas')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color('danger'),
            ];
        }

        // Hitung kelas yang diampu
        $kelasCount = data_kelas::where('walikelas_id', $guru->id)->count();
        
        // Hitung total siswa di kelas yang diampu
        $siswaCount = data_siswa::whereHas('kelas', function($query) use ($guru) {
            $query->where('walikelas_id', $guru->id);
        })->count();

        return [
            Stat::make('Total Kelas Diampu', $kelasCount)
                ->description('Kelas yang Anda ampu saat ini')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
            
            Stat::make('Total Siswa', $siswaCount)
                ->description('Siswa di kelas yang Anda ampu')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            
            Stat::make('Status', 'Aktif')
                ->description('Status sebagai Wali Kelas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        // Hanya tampil untuk guru
        return auth()->check() && auth()->user()->hasRole('guru');
    }
}