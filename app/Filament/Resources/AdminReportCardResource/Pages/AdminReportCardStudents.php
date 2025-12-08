<?php

namespace App\Filament\Resources\AdminReportCardResource\Pages;

use App\Filament\Resources\AdminReportCardResource;
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

class AdminReportCardStudents extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = AdminReportCardResource::class;

    protected static string $view = 'filament.resources.admin-report-card-resource.pages.admin-report-card-students';
    
    public data_kelas $record;
    
    public function mount(data_kelas $record): void
    {
        $this->record = $record;
        
        // Check authorization - only admin can access
        $user = auth()->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat mengakses halaman ini.');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                data_siswa::query()
                    ->where('kelas', $this->record->kelas_id)
                    ->orderBy('nama_lengkap')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Siswa')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->tooltip('Klik untuk copy NIS'),
                    
                Tables\Columns\TextColumn::make('assessments_count')
                    ->label('Penilaian')
                    ->getStateUsing(function (data_siswa $record) {
                        $count = \App\Models\student_assessment::where('siswa_nis', $record->nis)->count();
                        return $count . ' penilaian';
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, '0') ? 'warning' : 'success'),
                    
                Tables\Columns\TextColumn::make('growth_records_count')
                    ->label('Pertumbuhan')
                    ->getStateUsing(function (data_siswa $record) {
                        $count = GrowthRecord::where('siswa_nis', $record->nis)->count();
                        return $count . ' record';
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, '0') ? 'warning' : 'info'),
                    
                Tables\Columns\TextColumn::make('attendance_records_count')
                    ->label('Kehadiran')
                    ->getStateUsing(function (data_siswa $record) {
                        $attendance = AttendanceRecord::where('siswa_nis', $record->nis)->first();
                        if ($attendance) {
                            $total = ($attendance->alfa ?? 0) + ($attendance->ijin ?? 0) + ($attendance->sakit ?? 0);
                            return $total > 0 ? $total . ' absen' : 'Hadir';
                        }
                        return 'Belum ada data';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        if (str_contains($state, 'Hadir')) return 'success';
                        if (str_contains($state, 'Belum')) return 'warning';
                        return 'danger';
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('has_assessments')
                    ->label('Status Penilaian')
                    ->options([
                        'with' => 'Ada Penilaian',
                        'without' => 'Belum Ada Penilaian',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'with',
                            fn (Builder $query): Builder => $query->whereHas('studentAssessments'),
                        )->when(
                            $data['value'] === 'without',
                            fn (Builder $query): Builder => $query->whereDoesntHave('studentAssessments'),
                        );
                    }),
                    
                Tables\Filters\SelectFilter::make('has_growth')
                    ->label('Status Pertumbuhan')
                    ->options([
                        'with' => 'Ada Data Pertumbuhan',
                        'without' => 'Belum Ada Data Pertumbuhan',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'with',
                            fn (Builder $query): Builder => $query->whereHas('growthRecords'),
                        )->when(
                            $data['value'] === 'without',
                            fn (Builder $query): Builder => $query->whereDoesntHave('growthRecords'),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_pdf')
                        ->label('Lihat Raport PDF')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->url(fn (data_siswa $record) => route('view.raport.inline', $record))
                        ->openUrlInNewTab(),
                        
                    Tables\Actions\Action::make('view_details')
                        ->label('Detail Siswa')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->modalHeading(fn (data_siswa $record) => 'Detail Siswa - ' . $record->nama_lengkap)
                        ->modalContent(function (data_siswa $record) {
                            $assessments = student_assessment::where('siswa_nis', $record->nis)->count();
                            $growth = GrowthRecord::where('siswa_nis', $record->nis)->count();
                            $attendance = AttendanceRecord::where('siswa_nis', $record->nis)->first();
                            
                            return view('filament.modals.student-detail', [
                                'siswa' => $record,
                                'assessments' => $assessments,
                                'growth' => $growth,
                                'attendance' => $attendance,
                            ]);
                        })
                        ->modalWidth('lg')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_download_pdf')
                    ->label('Download PDF Terpilih')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('primary')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Download')
                            ->body('Download PDF untuk ' . $records->count() . ' siswa akan segera tersedia.')
                            ->info()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Download PDF Massal')
                    ->modalDescription('Apakah Anda yakin ingin mendownload PDF raport untuk semua siswa yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Download'),
            ])
            ->emptyStateHeading('Tidak ada siswa')
            ->emptyStateDescription('Tidak ada siswa di kelas ' . $this->record->nama_kelas . '.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->striped()
            ->paginated([10, 25, 50]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => AdminReportCardResource::getUrl('index')),
                
            Action::make('class_stats')
                ->label('Statistik Kelas')
                ->icon('heroicon-o-chart-pie')
                ->color('info')
                ->modalHeading('Statistik Kelas - ' . $this->record->nama_kelas)
                ->modalContent(function () {
                    $totalSiswa = data_siswa::where('kelas', $this->record->kelas_id)->count();
                    $siswaWithAssessments = data_siswa::where('kelas', $this->record->kelas_id)
                        ->whereHas('studentAssessments')->count();
                    $siswaWithGrowth = data_siswa::where('kelas', $this->record->kelas_id)
                        ->whereHas('growthRecords')->count();
                    $siswaWithAttendance = data_siswa::where('kelas', $this->record->kelas_id)
                        ->whereHas('attendanceRecords')->count();
                    
                    return view('filament.modals.class-statistics', [
                        'kelas' => $this->record,
                        'totalSiswa' => $totalSiswa,
                        'siswaWithAssessments' => $siswaWithAssessments,
                        'siswaWithGrowth' => $siswaWithGrowth,
                        'siswaWithAttendance' => $siswaWithAttendance,
                    ]);
                })
                ->modalWidth('lg')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
                
            Action::make('download_all_class')
                ->label('Download Semua PDF Kelas')
                ->icon('heroicon-o-folder-arrow-down')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Download Kelas')
                        ->body('Download semua PDF untuk kelas ' . $this->record->nama_kelas . ' akan segera tersedia.')
                        ->info()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Download Semua PDF Kelas')
                ->modalDescription('Apakah Anda yakin ingin mendownload PDF raport untuk semua siswa di kelas ' . $this->record->nama_kelas . '?')
                ->modalSubmitActionLabel('Ya, Download Semua')
        ];
    }
    
    public function getTitle(): string
    {
        return 'Admin - Raport Kelas ' . $this->record->nama_kelas;
    }
    
    public function getBreadcrumb(): string
    {
        return $this->record->nama_kelas;
    }
}