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

    public function getTableRecordKey($record): string
    {
        // Return month as the key for grouped records
        return (string) ($record->month ?? 'unknown');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_monthly_records')
                ->label('Generate Catatan Bulanan')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    Select::make('kelas_id')
                        ->label('Kelas')
                        ->options(function () {
                            $user = auth()->user();
                            if (!$user || !$user->guru) {
                                return [];
                            }
                            return $user->guru->kelasWali->pluck('nama_kelas', 'kelas_id');
                        })
                        ->required()
                        ->searchable()
                        ->helperText('Pilih kelas yang akan digenerate'),
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
                    $this->generateMonthlyRecords($data['kelas_id'], $data['month']);
                })
                ->modalHeading('Generate Catatan Pertumbuhan Bulanan')
                ->modalDescription('Akan membuat template catatan pertumbuhan untuk semua siswa di kelas yang dipilih pada bulan tertentu')
                ->modalSubmitActionLabel('Generate')
                ->modalWidth('md'),
                
            Actions\CreateAction::make()
                ->label('Tambah Catatan'),
        ];
    }
    
    protected function generateMonthlyRecords($kelasId, $month)
    {
        $user = auth()->user();
        if (!$user || !$user->guru) {
            Notification::make()
                ->title('Akses ditolak')
                ->danger()
                ->send();
            return;
        }
        
        // Verify this kelas belongs to this wali kelas
        $kelas = $user->guru->kelasWali->where('kelas_id', $kelasId)->first();
        if (!$kelas) {
            Notification::make()
                ->title('Anda tidak memiliki akses ke kelas ini')
                ->danger()
                ->send();
            return;
        }
        
        // Check if records already exist for this kelas + month
        $existingCount = GrowthRecord::where('data_kelas_id', $kelasId)
            ->where('month', $month)
            ->count();
            
        if ($existingCount > 0) {
            Notification::make()
                ->title("Catatan untuk {$kelas->nama_kelas} bulan ini sudah ada")
                ->info()
                ->send();
            return;
        }
        
        // Get students in this specific kelas
        $siswaList = \App\Models\data_siswa::where('kelas', $kelasId)->get();
        
        if ($siswaList->isEmpty()) {
            Notification::make()
                ->title('Tidak ada siswa di kelas ini')
                ->warning()
                ->send();
            return;
        }
        
        $records = [];
        foreach ($siswaList as $siswa) {
            $records[] = [
                'siswa_nis' => $siswa->nis,
                'data_guru_id' => $user->guru->guru_id,
                'data_kelas_id' => $kelasId,
                'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
                'month' => $month,
                'year' => now()->year,
                'lingkar_kepala' => null,
                'lingkar_lengan' => null,
                'berat_badan' => null,
                'tinggi_badan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Bulk insert the records
        GrowthRecord::insert($records);
        
        $monthName = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$month];
        
        Notification::make()
            ->title("Berhasil membuat " . count($records) . " template catatan pertumbuhan untuk {$kelas->nama_kelas} - {$monthName}")
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
        
        return parent::getTableQuery()->forWaliKelas($user->guru->guru_id);
    }
}
