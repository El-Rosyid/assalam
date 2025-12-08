<?php

namespace App\Filament\Resources\GrowthRecordResource\Pages;

use App\Filament\Pages\Traits\HasBackButton;
use App\Filament\Resources\GrowthRecordResource;
use App\Models\GrowthRecord;
use App\Models\data_siswa;
use App\Models\data_kelas;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ManageGrowthRecords extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = GrowthRecordResource::class;

    use HasBackButton;


    protected static string $view = 'filament.resources.growth-record-resource.pages.manage-growth-records';

    public $month;
    public $kelasId;
    public $kelasData;

    public function mount($kelas, $month): void
    {
        $this->month = $month;
        $this->kelasId = $kelas;
        
        // Get kelas data for current wali kelas
        $user = auth()->user();
        if (!$user || !$user->guru) {
            abort(403, 'Anda bukan wali kelas');
        }
        
        // Verify this kelas belongs to this wali kelas
        $this->kelasData = $user->guru->kelasWali->where('kelas_id', $kelas)->first();
        
        if (!$this->kelasData) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini');
        }
    }

    public function getTitle(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        return 'Catatan Pertumbuhan - ' . $this->kelasData->nama_kelas . ' - ' . $months[$this->month] . ' ' . now()->year;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Get growth records and manually add siswa data via DB to avoid model issues
                GrowthRecord::query()
                    ->where('month', $this->month)
                    ->where('data_kelas_id', $this->kelasData->kelas_id)
                    ->leftJoin('data_siswa', 'growth_records.siswa_nis', '=', 'data_siswa.nis')
                    ->select([
                        'growth_records.pertumbuhan_id',
                        'growth_records.siswa_nis',
                        'growth_records.data_guru_id',
                        'growth_records.data_kelas_id',
                        'growth_records.tahun_ajaran_id',
                        'growth_records.month',
                        'growth_records.year',
                        'growth_records.lingkar_kepala',
                        'growth_records.lingkar_lengan',
                        'growth_records.berat_badan',
                        'growth_records.tinggi_badan',
                        'growth_records.created_at',
                        'growth_records.updated_at',
                        'data_siswa.nama_lengkap as siswa_nama',
                        'data_siswa.nisn as siswa_nisn'
                    ])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('siswa_nama')
                    ->label('Nama Siswa')
                    ->searchable(['data_siswa.nama_lengkap']),

                TextColumn::make('siswa_nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                TextInputColumn::make('lingkar_kepala')
                    ->label('L. Kepala (cm)')
                    ->type('number')
                    ->step(0.1)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100'])
                    ->updateStateUsing(function ($record, $state) {
                        $this->updateOrCreateRecord($record, 'lingkar_kepala', $state);
                    }),
                    
                TextInputColumn::make('lingkar_lengan')
                    ->label('L. Lengan (cm)')
                    ->type('number')
                    ->step(0.1)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:50'])
                    ->updateStateUsing(function ($record, $state) {
                        $this->updateOrCreateRecord($record, 'lingkar_lengan', $state);
                    }),
                    
                TextInputColumn::make('berat_badan')
                    ->label('BB (kg)')
                    ->type('number')
                    ->step(0.1)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100'])
                    ->updateStateUsing(function ($record, $state) {
                        $this->updateOrCreateRecord($record, 'berat_badan', $state);
                    }),
                    
                TextInputColumn::make('tinggi_badan')
                    ->label('TB (cm)')
                    ->type('number')
                    ->step(0.1)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:200'])
                    ->updateStateUsing(function ($record, $state) {
                        $this->updateOrCreateRecord($record, 'tinggi_badan', $state);
                    }),
                    
                TextColumn::make('bmi')
                    ->label('BMI')
                    ->getStateUsing(function ($record) {
                        if ($record->berat_badan && $record->tinggi_badan) {
                            $tinggiMeter = $record->tinggi_badan / 100;
                            $bmi = round($record->berat_badan / ($tinggiMeter * $tinggiMeter), 2);
                            return number_format($bmi, 2);
                        }
                        return '-';
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        !$state || $state === '-' => 'gray',
                        (float) $state < 18.5 => 'warning',
                        (float) $state > 25 => 'danger',
                        default => 'success',
                    }),
            ])
            ->emptyStateHeading('Tidak ada data pertumbuhan')
            ->emptyStateDescription('Klik tombol "Generate" untuk membuat data pertumbuhan.')
            ->defaultSort('siswa_nis')  // Sort by NIS instead
            ->defaultPaginationPageOption(50);
    }
    
    protected function updateOrCreateRecord($record, $field, $value)
    {
        $user = auth()->user();
        
        // Get the actual GrowthRecord by ID to avoid relationship loading
        $growthRecord = GrowthRecord::find($record->pertumbuhan_id);
        
        if (!$growthRecord) {
            // If not found, it's a new record
            $growthRecord = new GrowthRecord();
            $growthRecord->siswa_nis = $record->siswa_nis;
            $growthRecord->month = $this->month;
            $growthRecord->year = now()->year;
            $growthRecord->data_guru_id = $user->guru->guru_id;
            $growthRecord->data_kelas_id = $this->kelasData->kelas_id;
        }
        
        // Update the specific field
        $growthRecord->{$field} = $value;
        $growthRecord->save();
        
        Notification::make()
            ->title('Data berhasil disimpan')
            ->success()
            ->send();
    }
}