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

    /**
     * Override getTableRecordKey for grouped records
     * Since we're grouping by semester, use semester as the key
     */
    public function getTableRecordKey($record): string
    {
        return (string) ($record->semester ?? 'unknown');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_assessments')
                ->label('Generate Penilaian Semester')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Select::make('semester')
                        ->label('Semester')
                        ->options([
                            'Ganjil' => 'Semester 1 (Ganjil)',
                            'Genap' => 'Semester 2 (Genap)',
                        ])
                        ->default(function () {
                            $currentMonth = now()->month;
                            return ($currentMonth >= 7 && $currentMonth <= 12) ? 'Ganjil' : 'Genap';
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->generateAssessmentsForSemester($data['semester']);
                })
                ->modalHeading('Generate Penilaian Semester')
                ->modalDescription('Pilih semester untuk membuat penilaian bagi semua siswa di kelas Anda')
                ->modalSubmitActionLabel('Generate Penilaian')
                ->modalWidth('md'),
        ];
    }
    
    protected function generateAssessmentsForSemester($semester)
    {
        $user = auth()->user();
        if (!$user || !$user->guru) {
            return;
        }
        
        // Get kelas IDs for this wali kelas
        $kelasIds = \App\Models\data_kelas::where('walikelas_id', $user->guru->guru_id)
            ->pluck('kelas_id')
            ->toArray();
        
        if (empty($kelasIds)) {
            Notification::make()
                ->title('Tidak ada kelas yang Anda ampu')
                ->warning()
                ->send();
            return;
        }
        
        // Get students in these classes
        $siswaList = data_siswa::whereIn('kelas', $kelasIds)->get();
        
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
                'siswa_nis' => $siswa->nis,
                'semester' => $semester
            ])->first();
            
            if (!$existing) {
                // Get tahun_ajaran_id from siswa's kelas
                $tahunAjaranId = $siswa->kelasInfo?->tahun_ajaran_id;
                
                student_assessment::create([
                    'siswa_nis' => $siswa->nis,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester,
                    'status' => 'belum_dinilai'
                ]);
                $created++;
            }
        }
        
        if ($created > 0) {
            Notification::make()
                ->title("Berhasil membuat {$created} penilaian baru untuk Semester {$semester}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("Semua penilaian untuk Semester {$semester} sudah ada")
                ->info()
                ->send();
        }
    }
    
    protected function getTableQuery(): Builder
    {
        // Query sudah dihandle di Resource level
        return parent::getTableQuery();
    }
}