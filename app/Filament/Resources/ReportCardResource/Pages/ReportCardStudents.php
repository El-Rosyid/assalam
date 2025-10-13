<?php

namespace App\Filament\Resources\ReportCardResource\Pages;

use App\Filament\Resources\ReportCardResource;
use App\Models\data_kelas;
use App\Models\data_siswa;
use App\Models\student_assessment;
use App\Models\GrowthRecord;
use App\Models\AttendanceRecord;
use App\Services\ReportCardService;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class ReportCardStudents extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = ReportCardResource::class;

    protected static string $view = 'filament.resources.report-card-resource.pages.report-card-students';
    
    public data_kelas $record;
    
    public function mount(data_kelas $record): void
    {
        $this->record = $record;
        
        // Check authorization
        $user = auth()->user();
        if ($user && $user->guru) {
            $isKepalaSekolah = \App\Models\sekolah::where('kepala_sekolah', $user->guru->id)->exists();
            
            if (!$isKepalaSekolah && $record->walikelas_id !== $user->guru->id) {
                abort(403, 'Anda tidak memiliki akses ke kelas ini.');
            }
        } else {
            abort(403, 'Anda tidak memiliki akses untuk cetak raport.');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                data_siswa::query()
                    ->where('kelas', $this->record->id)
                    ->orderBy('nama_lengkap')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Siswa')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('assessments_count')
                    ->label('Penilaian')
                    ->getStateUsing(function (data_siswa $record) {
                        $count = \App\Models\student_assessment::where('data_siswa_id', $record->id)->count();
                        return $count . ' penilaian';
                    }),
                    
                Tables\Columns\TextColumn::make('growth_records_count')
                    ->label('Pertumbuhan')
                    ->getStateUsing(function (data_siswa $record) {
                        $count = GrowthRecord::where('data_siswa_id', $record->id)->count();
                        return $count . ' record';
                    }),
                    
                Tables\Columns\TextColumn::make('attendance_records_count')
                    ->label('Kehadiran')
                    ->getStateUsing(function (data_siswa $record) {
                        $attendance = AttendanceRecord::where('data_siswa_id', $record->id)->first();
                        if ($attendance) {
                            $total = ($attendance->alfa ?? 0) + ($attendance->ijin ?? 0) + ($attendance->sakit ?? 0);
                            return $total > 0 ? $total . ' absen' : 'Hadir';
                        }
                        return 'Belum ada data';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print_pdf')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->modalHeading('Print Raport')
                    ->modalContent(function (data_siswa $record) {
                        return view('raport.print-modal', ['siswa' => $record]);
                    })
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('download_from_modal')
                            ->label('Download PDF')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('success')
                            ->url(function (data_siswa $record) {
                                return route('download.raport', ['siswa' => $record->id]);
                            })
                            ->openUrlInNewTab(),
                    ]),
            ])
            ->emptyStateHeading('Tidak ada siswa')
            ->emptyStateDescription('Tidak ada siswa di kelas ' . $this->record->nama_kelas . '.');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Daftar Kelas')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => ReportCardResource::getUrl('index')),
                
            Action::make('download_all')
                ->label('Download Semua PDF')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Bulk PDF')
                        ->body('Fitur download semua PDF akan segera tersedia. Saat ini silakan download satu per satu.')
                        ->info()
                        ->send();
                })
        ];
    }
    
    public function getTitle(): string
    {
        return 'Raport Siswa - ' . $this->record->nama_kelas;
    }
    
    public function getBreadcrumb(): string
    {
        return $this->record->nama_kelas;
    }
}