<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentReportCardResource\Pages;
use App\Models\data_siswa;
use App\Models\student_assessment;
use App\Models\GrowthRecord;
use App\Models\AttendanceRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentReportCardResource extends Resource
{
    protected static ?string $model = data_siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'Raport Siswa';
    
    protected static ?string $modelLabel = 'Raport Siswa';
    
    protected static ?string $pluralModelLabel = 'Raport Siswa';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = 'Siswa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->disabled(),
                Forms\Components\TextInput::make('nisn')
                    ->label('NISN')
                    ->disabled(),
                Forms\Components\Select::make('kelas')
                    ->label('Kelas')
                    ->relationship('kelasInfo', 'nama_kelas')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelasInfo.nama_kelas')
                    ->label('Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelasInfo.waliKelas.nama_lengkap')
                    ->label('Wali Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('student_assessments_count')
                    ->label('Penilaian')
                    ->counts('studentAssessments')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('growth_records_count')
                    ->label('Pertumbuhan')
                    ->counts('growthRecords')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('attendance_records_count')
                    ->label('Kehadiran')
                    ->counts('attendanceRecords')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->filters([
                // Filter dihapus sesuai permintaan
            ])
            ->actions([               
                Tables\Actions\Action::make('view_pdf')
                    ->label('Lihat PDF')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn (data_siswa $record) => route('view.raport.inline', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // Tidak ada bulk actions untuk siswa
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Filter hanya siswa yang sedang login
                $user = Auth::user();
                if ($user && $user->siswa) {
                    return $query->where('nis', $user->siswa->nis);
                }
                
                // Jika bukan siswa, return query kosong
                return $query->whereRaw('1 = 0');
            })
            ->defaultSort('nama_lengkap');
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
            'index' => Pages\ListStudentReportCards::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }
    
    public static function canDeleteAny(): bool
    {
        return false;
    }
    
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->siswa;
    }
    
    public static function canView($record): bool
    {
        $user = Auth::user();
        return $user && $user->siswa && $user->siswa->nis === $record->nis;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && $user->siswa;
    }
    
    private static function calculateCompletionPercentage(data_siswa $siswa): float
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
}