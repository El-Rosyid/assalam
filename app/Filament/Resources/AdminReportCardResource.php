<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminReportCardResource\Pages;
use App\Models\data_kelas;
use App\Models\data_siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminReportCardResource extends Resource
{
    protected static ?string $model = data_kelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    protected static ?string $navigationLabel = 'Raport Admin';
    
    protected static ?string $navigationGroup = 'Administrasi';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Raport Admin';
    
    protected static ?string $pluralModelLabel = 'Raport Admin';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Admin can access all classes - no restrictions
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
                        $count = data_siswa::where('kelas', $record->kelas_id)->count();
                        return $count . ' siswa';
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('walikelas_id')
                    ->label('Wali Kelas')
                    ->relationship('waliKelas', 'nama_lengkap')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('detail_siswa')
                    ->label('Kelola Raport')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn (data_kelas $record): string => route('filament.admin.resources.admin-report-cards.students', ['record' => $record->kelas_id]))
                    ->tooltip('Lihat dan kelola raport siswa dalam kelas ini'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_download')
                    ->label('Download Semua Kelas')
                    ->icon('heroicon-o-folder-arrow-down')
                    ->color('success')
                    ->action(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Download')
                            ->body('Fitur download bulk untuk semua kelas akan segera tersedia.')
                            ->info()
                            ->send();
                    })
                    ->tooltip('Download raport semua siswa dari semua kelas'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_download_selected')
                    ->label('Download Kelas Terpilih')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('primary')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Download')
                            ->body('Download ' . $records->count() . ' kelas akan segera tersedia.')
                            ->info()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->tooltip('Download raport semua siswa dari kelas yang dipilih'),
            ])
            ->emptyStateHeading('Tidak ada kelas')
            ->emptyStateDescription('Belum ada kelas yang tersedia dalam sistem.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->defaultSort('nama_kelas', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListAdminReportCards::route('/'),
            'students' => Pages\AdminReportCardStudents::route('/{record}/students'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canViewAny(): bool
    {
        // Only admin can access this resource
        $user = auth()->user();
        return $user && $user->hasRole('admin');
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
    
    public static function getNavigationBadge(): ?string
    {
        return data_kelas::count() . ' kelas';
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }
}