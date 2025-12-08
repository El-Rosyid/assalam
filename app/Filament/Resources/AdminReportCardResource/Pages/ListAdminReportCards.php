<?php

namespace App\Filament\Resources\AdminReportCardResource\Pages;

use App\Filament\Resources\AdminReportCardResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAdminReportCards extends ListRecords
{
    protected static string $resource = AdminReportCardResource::class;
    
    public function getTitle(): string
    {
        return 'Raport Admin';
    }
    
    public function getHeading(): string
    {
        return 'Manajemen Raport Siswa (Admin)';
    }
    
    public function getSubheading(): ?string
    {
        return 'Admin dapat mengakses dan mengelola raport siswa dari semua kelas';
    }
    
    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('statistics')
            //     ->label('Statistik')
            //     ->icon('heroicon-o-chart-bar')
            //     ->color('info')
            //     ->modalHeading('Statistik Raport')
            //     ->modalContent(function () {
            //         $totalKelas = \App\Models\data_kelas::count();
            //         $totalSiswa = \App\Models\data_siswa::count();
            //         $totalPenilaian = \App\Models\student_assessment::count();
            //         $totalPertumbuhan = \App\Models\GrowthRecord::count();
            //         $totalKehadiran = \App\Models\AttendanceRecord::count();
                    
            //         return view('filament.pages.admin-report-statistics', [
            //             'totalKelas' => $totalKelas,
            //             'totalSiswa' => $totalSiswa,
            //             'totalPenilaian' => $totalPenilaian,
            //             'totalPertumbuhan' => $totalPertumbuhan,
            //             'totalKehadiran' => $totalKehadiran,
            //         ]);
            //     })
            //     ->modalWidth('xl')
            //     ->modalSubmitAction(false)
            //     ->modalCancelActionLabel('Tutup'),
                
            // Actions\Action::make('export_all')
            //     ->label('Export Data')
            //     ->icon('heroicon-o-arrow-down-tray')
            //     ->color('success')
            //     ->action(function () {
            //         \Filament\Notifications\Notification::make()
            //             ->title('Export Data')
            //             ->body('Fitur export data raport akan segera tersedia.')
            //             ->info()
            //             ->send();
            //     })
            //     ->tooltip('Export semua data raport ke Excel/CSV'),
        ];
    }
}