<?php

namespace App\Filament\Resources\GrowthRecordResource\Pages;

use App\Filament\Resources\GrowthRecordResource;
use App\Models\GrowthRecord;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ListGrowthRecords extends ListRecords
{
    protected static string $resource = GrowthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_monthly_records')
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
                ->modalHeading('Generate Catatan Pertumbuhan Bulanan')
                ->modalDescription('Akan membuat template catatan pertumbuhan untuk semua siswa di kelas Anda pada bulan yang dipilih')
                ->modalSubmitActionLabel('Generate')
                ->modalWidth('md'),
                
            Actions\CreateAction::make()
                ->label('Tambah Catatan'),
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
        
        $records = GrowthRecord::generateSpecificMonthRecords($month, $user->guru->id);
        
        if (empty($records)) {
            Notification::make()
                ->title('Catatan untuk bulan ini sudah ada')
                ->info()
                ->send();
            return;
        }
        
        // Bulk insert the records
        GrowthRecord::insert($records);
        
        $monthName = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$month];
        
        Notification::make()
            ->title("Berhasil membuat " . count($records) . " template catatan pertumbuhan untuk {$monthName}")
            ->success()
            ->send();
    }
    
    protected function getTableQuery(): Builder
    {
        // Override query to show only current user's students
        $user = auth()->user();
        if (!$user || !$user->guru) {
            return parent::getTableQuery()->whereRaw('1 = 0'); // Return empty result
        }
        
        return parent::getTableQuery()->forWaliKelas($user->guru->id);
    }
}
