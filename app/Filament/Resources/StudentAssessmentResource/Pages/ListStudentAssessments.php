<?php

namespace App\Filament\Resources\StudentAssessmentResource\Pages;

use App\Filament\Resources\StudentAssessmentResource;
use App\Models\student_assessment;
use App\Models\data_siswa; 
use App\Models\academic_year;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class ListStudentAssessments extends ListRecords
{
    protected static string $resource = StudentAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_assessments')
                ->label('Generate Penilaian Semester')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Select::make('academic_year_id')
                        ->label('Tahun Ajaran')
                        ->options(function () {
                            return academic_year::orderBy('year', 'desc')
                                ->get()
                                ->mapWithKeys(function ($academicYear) {
                                    return [$academicYear->id => $academicYear->nama_tahun_ajaran];
                                })
                                ->toArray();
                        })
                        ->default(function () {
                            return academic_year::orderBy('year', 'desc')->first()?->id;
                        })
                        ->required()
                        ->searchable(),
                                        
                ])
                ->action(function (array $data) {
                    $this->generateAssessmentsForCurrentSemester($data['academic_year_id']);
                })
                ->modalHeading('Generate Penilaian Semester')
                ->modalDescription('Pilih tahun ajaran untuk membuat penilaian bagi semua siswa di kelas Anda')
                ->modalSubmitActionLabel('Generate Penilaian')
                ->modalWidth('md'),
        ];
    }
    
    protected function generateAssessmentsForCurrentSemester($academicYearId)
    {
        $user = auth()->user();
        if (!$user || !$user->guru) {
            return;
        }
        
        // Get selected academic year
        $academicYear = academic_year::find($academicYearId);
        if (!$academicYear) {
            Notification::make()
                ->title('Tahun ajaran tidak ditemukan')
                ->danger()
                ->send();
            return;
        }
        
        // Determine current semester based on current month
        $currentMonth = now()->month;
        $semester = ($currentMonth >= 7 && $currentMonth <= 12) ? 'Ganjil' : 'Genap';
        
        // Get students in classes where user is wali kelas
        $siswaList = data_siswa::whereHas('kelas', function ($query) use ($user) {
            $query->where('walikelas_id', $user->guru->id);
        })->get();
        
        if ($siswaList->isEmpty()) {
            Notification::make()
                ->title('Tidak ada siswa di kelas yang Anda ampu')
                ->warning()
                ->send();
            return;
        }
        
        $created = 0;
        foreach ($siswaList as $siswa) {
            $existing = student_assessment::where([
                'data_siswa_id' => $siswa->id,
                'academic_year_id' => $academicYearId,
                'semester' => $semester
            ])->first();
            
            if (!$existing) {
                student_assessment::create([
                    'data_siswa_id' => $siswa->id,
                    'data_guru_id' => $user->guru->id,
                    'data_kelas_id' => $siswa->kelas,  // kelas field in data_siswa
                    'academic_year_id' => $academicYearId,
                    'semester' => $semester,
                    'status' => 'belum_dinilai'
                ]);
                $created++;
            }
        }
        
        if ($created > 0) {
            Notification::make()
                ->title("Berhasil membuat {$created} penilaian baru untuk {$academicYear->nama_tahun_ajaran} - Semester {$semester}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("Semua penilaian untuk {$academicYear->nama_tahun_ajaran} - Semester {$semester} sudah ada")
                ->info()
                ->send();
        }
    }
    
    protected function getTableQuery(): Builder
    {
        // Override query to show only current user's students
        $user = auth()->user();
        if (!$user || !$user->guru) {
            return parent::getTableQuery()->whereRaw('1 = 0'); // Return empty result
        }
        
        return parent::getTableQuery()
            ->whereHas('kelas', function ($query) use ($user) {
                $query->where('walikelas_id', $user->guru->id);
            });
    }
}