<?php

namespace App\Filament\Resources\StudentAssessmentResource\Pages;

use App\Filament\Resources\StudentAssessmentResource;
use App\Models\student_assessment;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ManageStudentAssessments extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = StudentAssessmentResource::class;

    protected static string $view = 'filament.resources.student-assessment-resource.pages.manage-student-assessments';

    public $semester;
    public $newStudentsCount = 0;

    public function mount($semester): void
    {
        $this->semester = $semester;
        
        // Verify user is wali kelas
        $user = auth()->user();
        if (!$user || !$user->guru) {
            abort(403, 'Anda bukan wali kelas');
        }
        
        // Check for new students without assessment
        $this->checkNewStudents();
    }
    
    protected function checkNewStudents(): void
    {
        $user = auth()->user();
        
        // Get kelas IDs for this wali kelas
        $kelasIds = \App\Models\data_kelas::where('walikelas_id', $user->guru->guru_id)
            ->pluck('kelas_id')
            ->toArray();
        
        // Get all students in these classes
        $allStudents = \App\Models\data_siswa::whereIn('kelas', $kelasIds)->pluck('nis');
        
        // Get students that already have assessment for this semester
        $studentsWithAssessment = student_assessment::where('semester', $this->semester)
            ->whereIn('siswa_nis', $allStudents)
            ->pluck('siswa_nis');
        
        // Count students without assessment
        $this->newStudentsCount = $allStudents->diff($studentsWithAssessment)->count();
    }
    
    public function syncNewStudents(): void
    {
        $user = auth()->user();
        
        // Get kelas IDs for this wali kelas
        $kelasIds = \App\Models\data_kelas::where('walikelas_id', $user->guru->guru_id)
            ->pluck('kelas_id')
            ->toArray();
        
        // Get all students in these classes
        $allStudents = \App\Models\data_siswa::whereIn('kelas', $kelasIds)->get();
        
        $created = 0;
        foreach ($allStudents as $siswa) {
            $existing = student_assessment::where([
                'siswa_nis' => $siswa->nis,
                'semester' => $this->semester
            ])->first();
            
            if (!$existing) {
                // Get tahun_ajaran_id from siswa's kelas
                $tahunAjaranId = $siswa->kelasInfo?->tahun_ajaran_id;
                
                student_assessment::create([
                    'siswa_nis' => $siswa->nis,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $this->semester,
                    'status' => 'belum_dinilai'
                ]);
                $created++;
            }
        }
        
        if ($created > 0) {
            \Filament\Notifications\Notification::make()
                ->title("Berhasil menambahkan {$created} siswa baru ke daftar penilaian")
                ->success()
                ->send();
                
            // Reset counter
            $this->newStudentsCount = 0;
        } else {
            \Filament\Notifications\Notification::make()
                ->title('Semua siswa sudah ada dalam daftar penilaian')
                ->info()
                ->send();
        }
    }

    public function getTitle(): string
    {
        $semesterName = $this->semester == 'Ganjil' ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
        return 'Kelola Penilaian - ' . $semesterName;
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        // Get kelas IDs for this wali kelas
        $kelasIds = \App\Models\data_kelas::where('walikelas_id', $user->guru->guru_id)
            ->pluck('kelas_id')
            ->toArray();
        
        // Get siswa NIS in these kelas
        $siswaNis = \App\Models\data_siswa::whereIn('kelas', $kelasIds)
            ->pluck('nis')
            ->toArray();
        
        return $table
            ->query(
                student_assessment::query()
                    ->where('semester', $this->semester)
                    ->whereIn('siswa_nis', $siswaNis)
                    ->with(['siswa'])
                    // Sort: belum_dinilai first, then sebagian, then selesai
                    ->orderByRaw("FIELD(status, 'belum_dinilai', 'sebagian', 'selesai')")
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_dinilai' => 'Belum Dinilai',
                        'sebagian' => 'Sebagian', 
                        'selesai' => 'Selesai',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'belum_dinilai',
                        'warning' => 'sebagian',
                        'success' => 'selesai',
                    ]),
                    
                TextColumn::make('completed_at')
                    ->label('Selesai Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'belum_dinilai' => 'Belum Dinilai',
                        'sebagian' => 'Sebagian',
                        'selesai' => 'Selesai',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('input_nilai')
                    ->label('Input Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (student_assessment $record): string => 
                        StudentAssessmentResource::getUrl('input', ['record' => $record])),
                        
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (student_assessment $record): string => 
                        "Hasil Penilaian: {$record->siswa->nama_lengkap}")
                    ->modalContent(function (student_assessment $record) {
                        $details = $record->details()->with('assessmentVariable')->get();
                        
                        if ($details->isEmpty()) {
                            return view('filament.components.empty-assessment', [
                                'student' => $record->siswa,
                                'message' => 'Belum ada data penilaian untuk siswa ini.'
                            ]);
                        }
                        
                        return view('filament.components.assessment-results', [
                            'student' => $record->siswa,
                            'semester' => $record->semester,
                            'details' => $details,
                            'status' => $record->status
                        ]);
                    })
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Penilaian')
                    ->modalDescription('Apakah Anda yakin ingin menghapus penilaian ini?')
                    ->successNotificationTitle('Penilaian berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus yang Dipilih')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Penilaian yang Dipilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus penilaian yang dipilih?')
                        ->successNotificationTitle('Penilaian berhasil dihapus'),
                ]),
            ])
            ->emptyStateHeading('Tidak ada penilaian')
            ->emptyStateDescription('Tidak ada data penilaian untuk semester ini.')
            ->defaultPaginationPageOption(25);
    }
}
