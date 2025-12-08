<?php

namespace App\Filament\Resources\AttendanceRecordResource\Pages;

use App\Filament\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\data_kelas;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ManageAttendanceRecord extends ViewRecord implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = AttendanceRecordResource::class;

    protected static string $view = 'filament.resources.attendance-record-resource.pages.manage-attendance-record';
    
    public function mount(int|string $record): void
    {
        // Override parent mount to use data_kelas instead of AttendanceRecord
        $this->record = data_kelas::findOrFail($record);
        
        // Check if current user is wali kelas of this class
        $user = auth()->user();
        if (!$user || !$user->guru || $this->record->walikelas_id !== $user->guru->guru_id) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }
        
        // Generate attendance records if not exist
        $this->generateAttendanceRecords();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AttendanceRecordResource::getUrl()),
                
            Actions\Action::make('save_all')
                ->label('Simpan Semua')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function () {
                    Notification::make()
                        ->title('Data kehadiran berhasil disimpan')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(AttendanceRecord::query()->where('data_kelas_id', $this->record->kelas_id))
            ->columns([
                Tables\Columns\TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextInputColumn::make('alfa')
                    ->label('Alfa')
                    ->type('number')
                    ->rules(['min:0'])
                    ->step(1),
                    
                Tables\Columns\TextInputColumn::make('ijin')
                    ->label('Ijin')
                    ->type('number')
                    ->rules(['min:0'])
                    ->step(1),
                    
                Tables\Columns\TextInputColumn::make('sakit')
                    ->label('Sakit')
                    ->type('number')
                    ->rules(['min:0'])
                    ->step(1),
                    
                Tables\Columns\TextColumn::make('total_absen')
                    ->label('Total')
                    ->getStateUsing(fn (AttendanceRecord $record): string => 
                        $record->alfa + $record->ijin + $record->sakit
                    )
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        (int) $state === 0 => 'success',
                        (int) $state <= 5 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                // No filters needed
            ])
            ->actions([
                // No individual actions needed
            ])
            ->bulkActions([
                // No bulk actions needed
            ])
            ->paginated(false) // Show all students
            ->searchable()
            ->striped();
    }
    
    protected function generateAttendanceRecords(): void
    {
        $user = auth()->user();
        $records = AttendanceRecord::generateAttendanceRecords($user->guru->guru_id, $this->record->kelas_id);
        
        if (!empty($records)) {
            AttendanceRecord::insert($records);
        }
    }
    
    public function getTitle(): string
    {
        return "Kelola Kehadiran - {$this->record->nama_kelas}";
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            AttendanceRecordResource::getUrl() => 'Data Kehadiran',
            '#' => "Kelola - {$this->record->nama_kelas}",
        ];
    }
}