<?php

namespace App\Filament\Resources\MonthlyReportResource\Pages;

use App\Filament\Resources\MonthlyReportResource;
use App\Models\monthly_reports;
use Filament\Actions\Action;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListMonthlyReports extends ListRecords
{
    protected static string $resource = MonthlyReportResource::class;

    protected static ?string $title = 'Catatan Perkembangan Bulanan';

    protected function getHeaderActions(): array
    {
        return [
           Action::make('generate_monthly_records')
                ->label('Generate Catatan Bulanan')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    Select::make('month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari',
                            2 => 'Februari', 
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember',
                        ])
                        ->default(now()->month)
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $this->generateMonthlyRecords($data['month']);
                })
                ->modalHeading('Generate Catatan Perkembangan Bulanan')
                ->modalDescription('Akan membuat template catatan bulanan untuk semua siswa di kelas Anda pada bulan yang dipilih')
                ->modalSubmitActionLabel('Generate')
                ->modalWidth('md'),
            ];
    }

    protected function generateMonthlyRecords($month)
    {
        $user = auth()->user();
        if (!$user || !$user->guru) {
            Notification::make()
                ->title('Akses ditolak')
                ->danger()
                ->send();
            return;
        }
        
        // Generate the records
        $records = monthly_reports::generateSpecificMonthRecords($month, $user->guru->guru_id);
        
        if (empty($records)) {
            Notification::make()
                ->title('Semua siswa sudah memiliki catatan untuk bulan ini')
                ->info()
                ->send();
            return;
        }
        
        // Bulk insert the records
        monthly_reports::insert($records);
        
        $monthName = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$month];
        
        Notification::make()
            ->title("Berhasil membuat catatan untuk {$monthName}")
            ->body("Ditambahkan " . count($records) . " catatan baru untuk siswa yang belum memiliki catatan")
            ->success()
            ->send();
    }
}
