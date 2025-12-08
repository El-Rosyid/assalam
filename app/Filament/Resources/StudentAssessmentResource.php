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
use Illuminate\Support\Facades\DB;

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
                $user = auth()->user();
                if ($user && $user->guru) {
                    // Get kelas IDs for this wali kelas
                    $kelasIds = \App\Models\data_kelas::where('walikelas_id', $user->guru->guru_id)
                        ->pluck('kelas_id')
                        ->toArray();
                    
                    // Get siswa NIS in these kelas
                    $siswaNis = \App\Models\data_siswa::whereIn('kelas', $kelasIds)
                        ->pluck('nis')
                        ->toArray();
                    
                    // Group by semester only
                    $query->select([
                        'semester',
                        DB::raw('COUNT(DISTINCT siswa_nis) as total_siswa'),
                        DB::raw("SUM(CASE WHEN status = 'belum_dinilai' THEN 1 ELSE 0 END) as belum_dinilai_count"),
                        DB::raw("SUM(CASE WHEN status = 'sebagian' THEN 1 ELSE 0 END) as sebagian_count"),
                        DB::raw("SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai_count"),
                        DB::raw('MIN(penilaian_id) as penilaian_id')
                    ])
                    ->whereIn('siswa_nis', $siswaNis)
                    ->groupBy('semester')
                    ->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')");
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->formatStateUsing(fn ($state) => 'Semester ' . ($state == 'Ganjil' ? '1 (Ganjil)' : '2 (Genap)'))
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color(fn ($state) => $state == 'Ganjil' ? 'info' : 'success'),
                    
                Tables\Columns\TextColumn::make('total_siswa')
                    ->label('Jumlah Siswa')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('belum_dinilai_count')
                    ->label('Belum Dinilai')
                    ->alignCenter()
                    ->color('danger')
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('sebagian_count')
                    ->label('Sebagian')
                    ->alignCenter()
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('selesai_count')
                    ->label('Selesai')
                    ->alignCenter()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('semester')
                    ->label('Filter Semester')
                    ->options([
                        'Ganjil' => '1 (Ganjil)',
                        'Genap' => '2 (Genap)',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('kelola')
                    ->label('Kelola Penilaian')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(function ($record) {
                        return static::getUrl('manage', [
                            'semester' => $record->semester,
                        ]);
                    }),
                    
                Tables\Actions\Action::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Penilaian Semester')
                    ->modalDescription(function ($record) {
                        $semesterName = $record->semester == 'Ganjil' ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
                        return "Apakah Anda yakin ingin menghapus semua data penilaian untuk {$semesterName}? Tindakan ini tidak dapat diurungkan.";
                    })
                    ->action(function ($record) {
                        $user = auth()->user();
                        if ($user && $user->guru) {
                            // Get kelas IDs for this wali kelas
                            $kelasIds = \App\Models\data_kelas::where('walikelas_id', $user->guru->guru_id)
                                ->pluck('kelas_id')
                                ->toArray();
                            
                            // Get siswa NIS in these kelas
                            $siswaNis = \App\Models\data_siswa::whereIn('kelas', $kelasIds)
                                ->pluck('nis')
                                ->toArray();
                            
                            student_assessment::where('semester', $record->semester)
                                ->whereIn('siswa_nis', $siswaNis)
                                ->delete();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Data Dihapus')
                                ->body('Semua data penilaian untuk semester yang dipilih telah dihapus.')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Hapus yang Dipilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Penilaian yang Dipilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua data penilaian untuk semester yang dipilih? Tindakan ini tidak dapat diurungkan.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $user = auth()->user();
                            if ($user && $user->guru) {
                                // Get kelas IDs for this wali kelas
                                $kelasIds = \App\Models\data_kelas::where('walikelas_id', $user->guru->guru_id)
                                    ->pluck('kelas_id')
                                    ->toArray();
                                
                                // Get siswa NIS in these kelas
                                $siswaNis = \App\Models\data_siswa::whereIn('kelas', $kelasIds)
                                    ->pluck('nis')
                                    ->toArray();
                                
                                foreach ($records as $record) {
                                    student_assessment::where('semester', $record->semester)
                                        ->whereIn('siswa_nis', $siswaNis)
                                        ->delete();
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Data Dihapus')
                                    ->body('Semua data penilaian untuk semester yang dipilih telah dihapus.')
                                    ->success()
                                    ->send();
                            }
                        })
                ]),
            ])
            ->emptyStateHeading('Belum Ada Data Penilaian')
            ->emptyStateDescription('Penilaian siswa akan dibuat otomatis oleh sistem.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
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
            'manage' => Pages\ManageStudentAssessments::route('/manage/{semester}'),
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