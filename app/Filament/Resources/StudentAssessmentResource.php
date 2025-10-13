<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentAssessmentResource\Pages;
use App\Models\student_assessment;
use App\Models\data_siswa;
use App\Models\data_guru;
use App\Models\assessment_variable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class StudentAssessmentResource extends Resource
{
    protected static ?string $model = student_assessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'Penilaian Siswa';
    
    protected static ?string $navigationGroup = 'Penilaian';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'Penilaian Siswa';
    
    protected static ?string $pluralModelLabel = 'Penilaian Siswa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak digunakan untuk create/edit karena menggunakan custom page
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Hanya tampilkan siswa dari kelas yang diampu guru yang login
                $user = auth()->user();
                if ($user && $user->guru) {
                    $query->whereHas('kelas', function ($kelasQuery) use ($user) {
                        $kelasQuery->where('walikelas_id', $user->guru->id);
                    });
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('academicYear.nama_tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
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
                    
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Belum selesai'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'belum_dinilai' => 'Belum Dinilai',
                        'sebagian' => 'Sebagian',
                        'selesai' => 'Selesai',
                    ]),
                    
                SelectFilter::make('semester')
                    ->label('Semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap' => 'Genap',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('input_nilai')
                    ->label('Input Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (student_assessment $record): string => 
                        static::getUrl('input', ['record' => $record])),
                        
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Hasil Penilaian')
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
                            'academicYear' => $record->academicYear,
                            'details' => $details,
                            'status' => $record->status
                        ]);
                    })
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('siswa.nama_lengkap');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentAssessments::route('/'),
            'input' => Pages\InputStudentAssessment::route('/{record}/input'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Assessment dibuat otomatis
    }
    
    public static function canViewAny(): bool
    {
        // Hanya guru yang bisa akses
        $user = auth()->user();
        return $user && $user->guru; // Cek apakah user memiliki relasi guru
    }
}