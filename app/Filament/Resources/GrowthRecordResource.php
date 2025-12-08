<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GrowthRecordResource\Pages;
use App\Models\GrowthRecord;
use App\Models\data_siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GrowthRecordResource extends Resource
{
    protected static ?string $model = GrowthRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $navigationLabel = 'Catatan Pertumbuhan';
    
    protected static ?string $navigationGroup = 'Penilaian';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Catatan Pertumbuhan';
    
    protected static ?string $pluralModelLabel = 'Catatan Pertumbuhan';

    // Disable default form - we'll use custom page
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Get current user's classes
                $user = auth()->user();
                if ($user && $user->guru) {
                    // Get kelas for this wali kelas
                    $kelasIds = $user->guru->kelasWali->pluck('kelas_id');
                    
                    if ($kelasIds->isNotEmpty()) {
                        // Get current students in the wali kelas's classes
                        $currentStudentNis = \App\Models\data_siswa::whereIn('kelas', $kelasIds)
                            ->pluck('nis');
                        
                        if ($currentStudentNis->isNotEmpty()) {
                            // Query growth records that match current students only
                            $query->select([
                                'data_kelas_id',
                                'month',
                                DB::raw('COUNT(DISTINCT growth_records.siswa_nis) as total_students'),
                                DB::raw('SUM(CASE WHEN lingkar_kepala IS NOT NULL OR lingkar_lengan IS NOT NULL OR berat_badan IS NOT NULL OR tinggi_badan IS NOT NULL THEN 1 ELSE 0 END) as filled_count'),
                                DB::raw('CONCAT(data_kelas_id, "-", month) as id')
                            ])
                            ->where('data_guru_id', $user->guru->guru_id)
                            ->whereIn('siswa_nis', $currentStudentNis)
                            ->whereIn('data_kelas_id', $kelasIds)
                            ->groupBy('data_kelas_id', 'month')
                            ->orderBy('data_kelas_id', 'asc')
                            ->orderBy('month', 'desc');
                        } else {
                            // No students in class, return empty result
                            $query->whereRaw('1 = 0');
                        }
                    } else {
                        // No class assigned, return empty result
                        $query->whereRaw('1 = 0');
                    }
                }
                return $query;
            })
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                TextColumn::make('data_kelas_id')
                    ->label('Kelas')
                    ->formatStateUsing(function ($state) {
                        $kelas = \App\Models\data_kelas::find($state);
                        return $kelas ? $kelas->nama_kelas : '-';
                    })
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('month')
                    ->label('Periode')
                    ->formatStateUsing(function ($record) {
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        return $months[$record->month] . ' ' . now()->year;
                    })
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('total_students')
                    ->label('Total Siswa')
                    ->alignCenter(),
                    
                TextColumn::make('filled_count')
                    ->label('Sudah Diisi')
                    ->alignCenter(),
            ])
            ->filters([
                // No filters for grouped view
            ])
            ->actions([
                Tables\Actions\Action::make('kelola')
                    ->label('Kelola')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(function ($record) {
                        return static::getUrl('manage', [
                            'month' => $record->month,
                            'kelas' => $record->data_kelas_id,
                        ]);
                    }),
                    
                Tables\Actions\Action::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Catatan Pertumbuhan')
                    ->modalDescription(function ($record) {
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        $monthName = $months[$record->month] . ' ' . now()->year;
                        $kelas = \App\Models\data_kelas::find($record->data_kelas_id);
                        $kelasName = $kelas ? $kelas->nama_kelas : 'Unknown';
                        return "Apakah Anda yakin ingin menghapus semua catatan pertumbuhan untuk {$kelasName} bulan {$monthName}?";
                    })
                    ->action(function ($record) {
                        try {
                            $month = $record->month;
                            $kelasId = $record->data_kelas_id;
                            
                            $deleted = GrowthRecord::where('month', $month)
                                ->where('data_kelas_id', $kelasId)
                                ->delete();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Dihapus')
                                ->body("Berhasil menghapus {$deleted} catatan pertumbuhan.")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Menghapus')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_missing')
                    ->label('Sinkronkan Siswa')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->action(function () {
                        $user = auth()->user();
                        if ($user && $user->guru) {
                            $createdCount = GrowthRecord::ensureAllStudentsHaveRecords($user->guru->guru_id);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Sinkronisasi Selesai')
                                ->body("Berhasil menambahkan {$createdCount} catatan untuk siswa yang belum ada.")
                                ->success()
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('clean_data')
                    ->label('Bersihkan Data')
                    ->icon('heroicon-o-trash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Bersihkan Data Tidak Valid')
                    ->modalDescription('Menghapus catatan pertumbuhan untuk siswa yang sudah tidak ada di kelas Anda. Data siswa yang masih aktif tidak akan terhapus.')
                    ->action(function () {
                        $user = auth()->user();
                        if ($user && $user->guru) {
                            // Get current students
                            $kelasIds = $user->guru->kelasWali->pluck('kelas_id');
                            $currentStudentNis = \App\Models\data_siswa::whereIn('kelas', $kelasIds)->pluck('nis');
                            
                            // Delete orphaned records
                            $deletedCount = GrowthRecord::where('data_guru_id', $user->guru->guru_id)
                                ->whereNotIn('siswa_nis', $currentStudentNis)
                                ->delete();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Data Dibersihkan')
                                ->body("Berhasil menghapus {$deletedCount} catatan untuk siswa yang tidak aktif.")
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Belum Ada Data Pertumbuhan')
            ->emptyStateDescription('Klik tombol "Generate Bulan Baru" untuk membuat catatan pertumbuhan.')
            ->emptyStateIcon('heroicon-o-chart-bar-square')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Hapus yang Dipilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Catatan Pertumbuhan yang Dipilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua data untuk bulan yang dipilih? Tindakan ini tidak dapat diurungkan.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $totalDeleted = 0;
                            foreach ($records as $record) {
                                $deleted = GrowthRecord::where('month', $record->month)
                                    ->where('data_kelas_id', $record->data_kelas_id)
                                    ->delete();
                                $totalDeleted += $deleted;
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Data Dihapus')
                                ->body("Berhasil menghapus {$totalDeleted} catatan pertumbuhan.")
                                ->success()
                                ->send();
                        })
                ]),
            ]);
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
            'index' => Pages\ListGrowthRecords::route('/'),
            'manage' => Pages\ManageGrowthRecords::route('/manage/{kelas}/{month}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Disable manual create
    }
    
    public static function canViewAny(): bool
    {
        // Only wali kelas can access
        $user = auth()->user();
        return $user && $user->guru;
    }
}
