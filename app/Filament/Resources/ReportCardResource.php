<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportCardResource\Pages;
use App\Models\data_kelas;
use App\Models\data_siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportCardResource extends Resource
{
    protected static ?string $model = data_kelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Cetak Raport';
    
    protected static ?string $navigationGroup = 'Penilaian';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $modelLabel = 'Cetak Raport';
    
    protected static ?string $pluralModelLabel = 'Cetak Raport';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Show classes for wali kelas and kepala sekolah
                $user = auth()->user();
                if ($user && $user->guru) {
                    // If user is kepala sekolah, show all classes
                    $isKepalaSekolah = \App\Models\sekolah::where('kepala_sekolah', $user->guru->id)->exists();
                    
                    if (!$isKepalaSekolah) {
                        // If not kepala sekolah, only show classes where user is wali kelas
                        $query->where('walikelas_id', $user->guru->id);
                    }
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('nama_kelas')
                    ->label('Nama Kelas')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('waliKelas.nama_lengkap')
                    ->label('Wali Kelas')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('siswa_count')
                    ->label('Jumlah Siswa')
                    ->getStateUsing(function (data_kelas $record) {
                        $count = data_siswa::where('kelas', $record->id)->count();
                        return $count . ' siswa';
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('detail_siswa')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (data_kelas $record): string => route('filament.admin.resources.report-cards.students', ['record' => $record->id]))
            ])
            ->bulkActions([
                // No bulk actions needed
            ])
            ->emptyStateHeading('Tidak ada kelas')
            ->emptyStateDescription('Tidak ada kelas yang dapat diakses untuk cetak raport.');
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
            'index' => Pages\ListReportCards::route('/'),
            'students' => Pages\ReportCardStudents::route('/{record}/students'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canViewAny(): bool
    {
        // Only wali kelas and kepala sekolah can access
        $user = auth()->user();
        return $user && $user->guru;
    }
    
    public static function canView($record): bool
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
}
