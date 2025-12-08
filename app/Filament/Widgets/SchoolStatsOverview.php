<?php

namespace App\Filament\Widgets;

use App\Models\data_guru;
use App\Models\data_kelas;
use App\Models\data_siswa;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SchoolStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    // Cache stats for 5 minutes to improve performance
    protected static ?string $pollingInterval = null; // Disable auto-refresh

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['admin', 'Admin']);
    }

    protected function getStats(): array
    {
        // Cache counts for 5 minutes to reduce database queries
        // Use DB::table() to avoid primary key issues with custom primary keys
        $siswaCount = cache()->remember('stats.total_siswa', 300, fn() => DB::table('data_siswa')->count());
        $guruCount = cache()->remember('stats.total_guru', 300, fn() => DB::table('data_guru')->count());
        $kelasCount = cache()->remember('stats.total_kelas', 300, fn() => DB::table('data_kelas')->count());
        
        return [
            Stat::make('Total Siswa', $siswaCount)
                ->description('Jumlah semua siswa terdaftar')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),
            Stat::make('Total Guru', $guruCount)
                ->description('Jumlah semua guru terdaftar')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('info'),
            Stat::make('Total Kelas', $kelasCount)
                ->description('Jumlah semua kelas')
                ->descriptionIcon('heroicon-o-building-library')
                ->color('warning'),
        ];
    }
}
