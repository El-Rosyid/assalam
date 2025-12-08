<?php

namespace App\Filament\Resources\StudentReportCardResource\Pages;

use App\Filament\Resources\StudentReportCardResource;
use App\Models\data_siswa;
use App\Models\student_assessment;
use App\Models\GrowthRecord;
use App\Models\AttendanceRecord;
use App\Models\academic_year;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListStudentReportCards extends ListRecords
{
    protected static string $resource = StudentReportCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('select_semester')
                ->label('Pilih Semester')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->modalHeading('Pilih Tahun Ajaran & Semester')
                ->modalDescription('Pilih tahun ajaran dan semester untuk melihat raport')
                ->form([
                    Select::make('academic_year_id')
                        ->label('Tahun Ajaran')
                        ->options(function () {
                            $user = Auth::user();
                            if (!$user || !$user->siswa) {
                                return [];
                            }
                            
                            // Get tahun ajaran yang punya data assessment untuk siswa ini
                            return academic_year::whereHas('assessments', function ($query) use ($user) {
                                $query->where('siswa_nis', $user->siswa->nis);
                            })
                            ->orderBy('year', 'desc')
                            ->get()
                            ->mapWithKeys(fn ($year) => [
                                $year->id => $year->year . ' - ' . $year->semester . 
                                ($year->is_active ? ' (Aktif)' : '')
                            ])
                            ->toArray();
                        })
                        ->default(function () {
                            return academic_year::where('is_active', true)->first()?->id;
                        })
                        ->searchable()
                        ->required()
                        ->helperText('Hanya tampil tahun ajaran yang memiliki data raport Anda'),
                ])
                ->action(function (array $data) {
                    $academicYear = academic_year::find($data['academic_year_id']);
                    
                    if ($academicYear) {
                        session(['selected_academic_year_id' => $academicYear->id]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Semester dipilih')
                            ->body("Menampilkan raport {$academicYear->year} semester {$academicYear->semester}")
                            ->success()
                            ->send();
                        
                        return redirect(request()->header('Referer'));
                    }
                })
                ->modalSubmitActionLabel('Pilih Semester')
                ->modalWidth('md'),
                
            Actions\Action::make('my_statistics')
                ->label('Statistik')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('Statistik Raport Saya')
                ->modalWidth('4xl')
                ->modalContent(function () {
                    $user = Auth::user();
                    if (!$user || !$user->siswa) {
                        return view('filament.modals.no-data');
                    }
                    
                    $siswa = $user->siswa;
                    
                    return view('filament.modals.student-statistics', [
                        'siswa' => $siswa,
                        'totalAssessments' => student_assessment::where('siswa_nis', $siswa->nis)->count(),
                        'totalGrowths' => GrowthRecord::where('siswa_nis', $siswa->nis)->count(),
                        'totalAttendances' => AttendanceRecord::where('siswa_nis', $siswa->nis)->count(),
                        'completionPercentage' => $this->calculateCompletionPercentage($siswa),
                        'kelas' => $siswa->kelasInfo,
                        'tahunAjaran' => $siswa->kelasInfo?->tahunAjaran,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user || !$user->siswa) {
            return data_siswa::query()->whereRaw('1 = 0');
        }
        
        return data_siswa::query()
            ->where('nis', $user->siswa->nis)
            ->with(['kelasInfo', 'kelasInfo.waliKelas', 'kelasInfo.tahunAjaran']);
    }

    protected function getTableHeading(): string
    {
        $user = Auth::user();
        if ($user && $user->siswa) {
            return 'Raport ' . $user->siswa->nama_lengkap;
        }
        
        return 'Raport Siswa';
    }

    protected function getTableDescription(): string
    {
        return 'Lihat dan cetak raport Anda untuk semua semester dan tahun ajaran.';
    }

    private function calculateCompletionPercentage(data_siswa $siswa): float
    {
        $totalComponents = 3; // Assessment, Growth, Attendance
        $completedComponents = 0;
        
        // Check assessments
        if (student_assessment::where('siswa_nis', $siswa->nis)->exists()) {
            $completedComponents++;
        }
        
        // Check growth records
        if (GrowthRecord::where('siswa_nis', $siswa->nis)->exists()) {
            $completedComponents++;
        }
        
        // Check attendance records
        if (AttendanceRecord::where('siswa_nis', $siswa->nis)->exists()) {
            $completedComponents++;
        }
        
        return ($completedComponents / $totalComponents) * 100;
    }

    public function getTitle(): string
    {
        return 'Raport Saya';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Belum Ada Data Raport';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Raport Anda belum tersedia. Hubungi wali kelas untuk informasi lebih lanjut.';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-academic-cap';
    }
}