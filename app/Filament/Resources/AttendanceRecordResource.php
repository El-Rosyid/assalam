<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceRecordResource\Pages;
use App\Models\AttendanceRecord;
use App\Models\data_kelas;
use App\Models\data_guru;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = data_kelas::class; // Using kelas model for list view

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Data Kehadiran';
    
    protected static ?string $navigationGroup = 'Penilaian';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $modelLabel = 'Data Kehadiran';
    
    protected static ?string $pluralModelLabel = 'Data Kehadiran';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show classes where current user is wali kelas
                $user = auth()->user();
                if ($user && $user->guru) {
                    $query->where('walikelas_id', $user->guru->guru_id);
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('waliKelas.nama_lengkap')
                    ->label('Wali Kelas')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('nama_kelas')
                    ->label('Nama Kelas')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('siswa_count')
                    ->label('Jumlah Siswa')
                    ->getStateUsing(function (data_kelas $record) {
                        return $record->siswa()->count() . ' siswa';
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('kelola_kehadiran')
                    ->label('Kelola')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (data_kelas $record): string => route('filament.admin.resources.attendance-records.manage', ['record' => $record->kelas_id]))
            ])
            ->bulkActions([
                // No bulk actions needed
            ])
            ->emptyStateHeading('Tidak ada kelas yang diampu')
            ->emptyStateDescription('Anda belum menjadi wali kelas di kelas manapun.');
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
            'index' => Pages\ListAttendanceRecords::route('/'),
            'manage' => Pages\ManageAttendanceRecord::route('/{record:kelas_id}/manage'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Disable create action
    }
    
    public static function canViewAny(): bool
    {
        // Only wali kelas can access
        $user = auth()->user();
        return $user && $user->guru;
    }
    
    public static function canView($record): bool
    {
        return false; // Disable view action
    }
    
    public static function canEdit($record): bool
    {
        return false; // Disable edit action
    }
    
    public static function canDelete($record): bool
    {
        return false; // Disable delete action
    }
}
