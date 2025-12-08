<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyReportResource\Pages;
use App\Models\monthly_reports;
use App\Models\data_siswa;
use App\Models\data_kelas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MonthlyReportResource extends Resource
{
    protected static ?string $model = monthly_reports::class;

    protected static ?string $navigationLabel = 'Catatan Perkembangan Bulanan';
    
    protected static ?string $navigationGroup = 'Penilaian';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $pluralLabel = 'Catatan Perkembangan Bulanan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('siswa_nis')
                    ->label('Siswa')
                    ->relationship('siswa', 'nama_lengkap')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $siswa = data_siswa::where('nis', $state)->first();
                            if ($siswa && $siswa->kelasInfo) {
                                $set('data_kelas_id', $siswa->kelasInfo->kelas_id);
                            }
                        }
                    }),
                    
                Forms\Components\Select::make('data_kelas_id')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->required()
                    ->disabled(fn (callable $get) => !$get('siswa_nis')),
                    
                Forms\Components\Hidden::make('data_guru_id')
                    ->default(fn () => auth()->user()->guru?->guru_id),
                    
                Forms\Components\Select::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ])
                    ->default(now()->month)
                    ->required(),
                    
                Forms\Components\TextInput::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->default(now()->year)
                    ->required(),
                    
                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan Perkembangan')
                    ->rows(5)
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'final' => 'Final',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Get current user's classes
                $user = auth()->user();
                if ($user && $user->guru) {
                    // Get all months that have been generated for this teacher's classes
                    $query->select([
                        'month',
                        'year',
                        'data_kelas_id',
                        DB::raw('COUNT(DISTINCT siswa_nis) as total_reports'),
                        DB::raw('COUNT(CASE WHEN catatan IS NOT NULL AND catatan != "" THEN 1 END) as completed_reports'),
                        DB::raw('MIN(id) as id') // Use MIN(id) as the primary key for Filament
                    ])
                    ->with(['kelas']) // Eager load the kelas relationship
                    ->where('data_guru_id', $user->guru->guru_id)
                    ->groupBy(['month', 'year', 'data_kelas_id'])
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc');
                }
                return $query;
            })
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
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
                    ->searchable(),
                    
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('total_reports')
                    ->label('Total Siswa')
                    ->alignCenter(),
                    
                TextColumn::make('completed_reports')
                    ->label('Sudah Diisi')
                    ->alignCenter(),                   
                
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
            ])
            ->actions([
                Tables\Actions\Action::make('kelola')
                    ->label('Kelola Siswa')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->url(function ($record) {
                        return static::getUrl('students', [
                            'month' => $record->month,
                            'year' => $record->year,
                            'kelas' => $record->kelas->nama_kelas ?? 'Unknown'
                        ]);
                    }),
                    
                Tables\Actions\Action::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Catatan Bulanan')
                    ->modalDescription(function ($record) {
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        $monthName = $months[$record->month] . ' ' . $record->year;
                        $kelasName = $record->kelas->nama_kelas ?? 'Unknown';
                        return "Apakah Anda yakin ingin menghapus semua catatan bulanan untuk {$kelasName} bulan {$monthName}?";
                    })
                    ->action(function ($record) {
                        try {
                            $month = $record->month;
                            $year = $record->year;
                            $kelasId = $record->data_kelas_id;
                            
                            $deleted = monthly_reports::where('month', $month)
                                ->where('year', $year)
                                ->where('data_kelas_id', $kelasId)
                                ->delete();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Dihapus')
                                ->body("Berhasil menghapus {$deleted} catatan bulanan.")
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
            ->bulkActions([
                Tables\Actions\BulkAction::make('delete')
                    ->label('Hapus Catatan Bulanan')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Catatan Bulanan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus catatan bulanan ini?')
                    ->action(function ($records) {
                        // $records are grouped rows (month, year, data_kelas_id)
                        DB::transaction(function () use ($records) {
                            foreach ($records as $record) {
                                $month = $record->month;
                                $year = $record->year;
                                $kelasId = $record->data_kelas_id;

                                // Find all monthly report ids for that group
                                $ids = monthly_reports::where('month', $month)
                                    ->where('year', $year)
                                    ->where('data_kelas_id', $kelasId)
                                    ->pluck('id')
                                    ->toArray();

                                if (!empty($ids)) {
                                    // Delete related broadcast logs first (if model exists)
                                    if (class_exists(\App\Models\MonthlyReportBroadcast::class)) {
                                        \App\Models\MonthlyReportBroadcast::whereIn('monthly_report_id', $ids)->delete();
                                    }

                                    // Delete the monthly reports (hard delete)
                                    monthly_reports::whereIn('id', $ids)->delete();
                                }
                            }
                        });
                    })
            ])
            ->emptyStateHeading('Tidak ada catatan bulanan')
            ->emptyStateDescription('Belum ada catatan perkembangan bulanan yang dibuat.');
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
            'index' => Pages\ListMonthlyReports::route('/'),
            'create' => Pages\CreateMonthlyReport::route('/create'),
            'edit' => Pages\EditMonthlyReport::route('/{record}/edit'),
            'students' => Pages\ManageStudentReports::route('/students/{month}/{year}/{kelas}'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only wali kelas can access
        $user = auth()->user();
        return $user && $user->guru;
    }
}
