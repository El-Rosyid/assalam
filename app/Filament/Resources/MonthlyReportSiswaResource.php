<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyReportSiswaResource\Pages;
use App\Models\monthly_reports;
use App\Models\data_siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MonthlyReportSiswaResource extends Resource
{
    protected static ?string $model = monthly_reports::class;

    protected static ?string $navigationLabel = 'Catatan Perkembangan Saya';
    
    protected static ?string $navigationGroup = 'Siswa';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $title = 'Catatan Perkembangan Saya';
    
    protected static ?string $pluralLabel = 'Catatan Perkembangan Saya';

    public static function form(Form $form): Form
    {
        // Form tidak digunakan karena menggunakan Infolist di view page
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Filter hanya catatan untuk siswa yang sedang login
                $user = Auth::user();
                if ($user && $user->siswa) {
                    return $query->where('siswa_nis', $user->siswa->nis)
                        ->with(['siswa', 'guru', 'kelas'])
                        ->orderBy('year', 'desc')
                        ->orderBy('month', 'desc');
                }
                
                // Jika bukan siswa, return query kosong
                return $query->whereRaw('1 = 0');
            })
            ->columns([
                TextColumn::make('month')
                    ->label('Periode')
                    ->formatStateUsing(function ($record) {
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        return $months[$record->month] . ' ' . $record->year;
                    })
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                    
                TextColumn::make('guru.nama_lengkap')
                    ->label('Guru')
                    ->default('Belum ada guru')
                    ->sortable(),
                    
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->color('success')
                    ->default('Belum ada kelas')
                    ->sortable(),
                    
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50)
                    ->default('Belum ada catatan')
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->color(function ($state) {
                        return $state && $state !== 'Belum ada catatan' ? 'success' : 'gray';
                    }),
                    
                TextColumn::make('photos')
                    ->label('Foto')
                    ->getStateUsing(function ($record) {
                        // Ambil langsung dari record, bukan dari state
                        $photos = $record->photos;
                        
                        // Handle jika photos adalah string JSON
                        if (is_string($photos)) {
                            $photos = json_decode($photos, true);
                        }
                        
                        // Cek apakah ada foto
                        if (!$photos || !is_array($photos) || count($photos) == 0) {
                            return 'Belum ada foto';
                        }
                        
                        // Filter foto yang valid (tidak null atau empty)
                        $validPhotos = array_filter($photos, fn($photo) => !empty($photo));
                        
                        if (count($validPhotos) == 0) {
                            return 'Belum ada foto';
                        }
                        
                        return count($validPhotos) . ' foto';
                    })
                    ->badge()
                    ->color(function ($record) {
                        // Ambil langsung dari record
                        $photos = $record->photos;
                        
                        // Handle jika photos adalah string JSON
                        if (is_string($photos)) {
                            $photos = json_decode($photos, true);
                        }
                        
                        // Cek apakah ada foto
                        if (!$photos || !is_array($photos) || count($photos) == 0) {
                            return 'gray';
                        }
                        
                        // Filter foto yang valid
                        $validPhotos = array_filter($photos, fn($photo) => !empty($photo));
                        
                        return count($validPhotos) > 0 ? 'success' : 'gray';
                    }),
                                 
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ]),
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $currentYear = date('Y');
                        $years = [];
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),
                Tables\Filters\Filter::make('with_catatan')
                    ->label('Sudah Ada Catatan')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('catatan')->where('catatan', '!=', '')),
                Tables\Filters\Filter::make('with_photos')
                    ->label('Sudah Ada Foto')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('photos')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                // Tidak ada bulk actions untuk siswa
            ])
            ->emptyStateHeading('Belum Ada Catatan')
            ->emptyStateDescription('Belum ada catatan perkembangan dari guru.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->defaultSort('year', 'desc');
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
            'index' => Pages\ListMonthlyReportSiswas::route('/'),
            'view' => Pages\ViewMonthlyReportSiswa::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Siswa tidak bisa create
    }
    
    public static function canEdit($record): bool
    {
        return false; // Siswa tidak bisa edit
    }
    
    public static function canDelete($record): bool
    {
        return false; // Siswa tidak bisa delete
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
        return $user && $user->siswa && $user->siswa->nis === $record->siswa_nis;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && $user->siswa;
    }
}