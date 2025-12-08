<?php

namespace App\Filament\Widgets;

use App\Models\data_kelas;
use App\Models\data_siswa;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GuruStatsOverview extends BaseWidget
{
    // Disable auto-refresh for better performance
    protected static ?string $pollingInterval = null;
    
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

        // Cache stats untuk 5 menit per guru
        $guruId = $guru->guru_id;
        $kelasCount = cache()->remember("stats.guru_{$guruId}.kelas", 300, function() use ($guruId) {
            return data_kelas::where('walikelas_id', $guruId)->count();
        });
        
        $siswaCount = cache()->remember("stats.guru_{$guruId}.siswa", 300, function() use ($guruId) {
            return data_siswa::whereHas('kelas', function($query) use ($guruId) {
                $query->where('walikelas_id', $guruId);
            })->count();
        });

        return [
            Stat::make('Total Kelas Diampu', $kelasCount)
                ->description('Kelas yang Anda ampu saat ini')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700',
                ]),
            
            Stat::make('Total Siswa', $siswaCount)
                ->description('Siswa di kelas yang Anda ampu')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->extraAttributes([
                    'class' => 'bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700',
                ]),
            
            Stat::make('Status', 'Aktif')
                ->description('Status sebagai Wali Kelas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info')
                ->extraAttributes([
                    'class' => 'bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700',
                ]),
        ];
    }

    public static function canView(): bool
    {
        // Hanya tampil untuk guru
        return auth()->check() && auth()->user()->hasRole('guru');
    }
}