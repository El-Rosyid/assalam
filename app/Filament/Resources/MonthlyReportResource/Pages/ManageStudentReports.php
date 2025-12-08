<?php

namespace App\Filament\Resources\MonthlyReportResource\Pages;

use App\Filament\Resources\MonthlyReportResource;
use App\Models\monthly_reports;
use App\Models\data_siswa;
use App\Models\data_kelas;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManageStudentReports extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    
    protected static string $resource = MonthlyReportResource::class;

    protected static string $view = 'filament.resources.monthly-report-resource.pages.manage-student-reports';

    public $month;
    public $year; 
    public $kelas;
    public $kelasData;

    public function mount($month, $year, $kelas): void
    {
        $this->month = $month;
        $this->year = $year;
        $this->kelas = $kelas;
        
        // Get kelas data
        $this->kelasData = data_kelas::where('nama_kelas', $kelas)->first();
        
        if (!$this->kelasData) {
            abort(404, 'Kelas tidak ditemukan');
        }

        // Check if current user is the wali kelas
        $user = auth()->user();
        if ($user && $user->guru && $user->guru->guru_id !== $this->kelasData->walikelas_id) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini');
        }
    }

    public function getTitle(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        return 'Kelola Siswa - ' . $this->kelas . ' - ' . $months[$this->month] . ' ' . $this->year;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                data_siswa::query()
                    ->where('kelas', $this->kelasData->kelas_id)
                    ->leftJoin('monthly_reports', function ($join) {
                        $join->on('data_siswa.nis', '=', 'monthly_reports.siswa_nis')
                             ->where('monthly_reports.month', $this->month)
                             ->where('monthly_reports.year', $this->year);
                    })
                    ->select([
                        'data_siswa.*',
                        'monthly_reports.id as report_id',
                        'monthly_reports.catatan',
                        'monthly_reports.photos'
                    ])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable(),

                TextColumn::make('catatan')
                    ->label('Catatan Perkembangan')
                    ->limit(50)
                    ->placeholder('Belum ada catatan...')
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->formatStateUsing(function ($state) {
                        return $state ?: 'Belum ada catatan...';
                    })
                    ->color(function ($state) {
                        return $state ? 'success' : 'gray';
                    }),

                ViewColumn::make('photos')
                    ->label('Foto')
                    ->view('filament.tables.columns.photos-stack')
                    ->state(function ($record) {
                        // Ambil photos dari monthly_reports
                        $photos = $record->photos;
                        
                        // Jika masih string JSON, decode
                        if (is_string($photos)) {
                            $photos = json_decode($photos, true);
                        }
                        
                        // Return array atau empty array
                        return is_array($photos) ? $photos : [];
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($record) {
                        return $record->catatan ? 'Sudah Diisi' : 'Belum Diisi';
                    })
                    ->badge()
                    ->color(function ($record) {
                        return $record->catatan ? 'success' : 'warning';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'filled' => 'Sudah Diisi',
                        'empty' => 'Belum Diisi',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'filled',
                            fn (Builder $query): Builder => $query->whereNotNull('monthly_reports.catatan')
                                ->where('monthly_reports.catatan', '!=', ''),
                        )->when(
                            $data['value'] === 'empty',
                            fn (Builder $query): Builder => $query->where(function ($q) {
                                $q->whereNull('monthly_reports.catatan')
                                  ->orWhere('monthly_reports.catatan', '');
                            }),
                        );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('edit_report')
                    ->label('Edit Catatan')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->modalHeading(fn ($record) => 'Edit Catatan - ' . $record->nama_lengkap)
                    ->modalWidth('7xl')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('Foto Siswa')
                                    ->description('Upload foto siswa (maksimal 5 foto)')
                                    ->schema([
                                        Forms\Components\FileUpload::make('photos')
                                            ->label('Foto')
                                            ->multiple()
                                            ->image()
                                            ->maxFiles(5)
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->directory('monthly-reports/photos')
                                            ->disk('public')
                                            ->downloadable()
                                            ->previewable()
                                            ->reorderable()
                                            ->deletable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan(1),
                                
                                Forms\Components\Section::make('Catatan')
                                    ->description('Tambahkan catatan perkembangan siswa')
                                    ->schema([
                                        Forms\Components\Textarea::make('catatan')
                                            ->label('Catatan')
                                            ->rows(8)
                                            ->placeholder('Masukkan catatan perkembangan siswa...')
                                            ->maxLength(1000)
                                            ->helperText('Maksimal 1000 karakter')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan(1),
                            ])
                    ])
                    ->fillForm(function ($record) {
                        // Cari atau buat monthly report
                        $monthlyReport = monthly_reports::firstOrCreate([
                            'siswa_nis' => $record->nis,
                            'month' => $this->month,
                            'year' => $this->year,
                        ], [
                            'data_guru_id' => Auth::id() ? Auth::user()->guru->guru_id ?? null : null,
                            'data_kelas_id' => $record->kelas ?? null
                        ]);

                        return [
                            'catatan' => $monthlyReport->catatan,
                            'photos' => array_values(array_filter($monthlyReport->photos ?? [], fn($p) => is_string($p) ? !str_starts_with($p, 'livewire-tmp/') : false)),
                        ];
                    })
                    ->action(function (array $data, $record) {
                        // Update atau buat monthly report
                        $monthlyReport = monthly_reports::updateOrCreate([
                            'siswa_nis' => $record->nis,
                            'month' => $this->month,
                            'year' => $this->year,
                        ], [
                            'data_guru_id' => Auth::id() ? Auth::user()->guru->guru_id ?? null : null,
                            'data_kelas_id' => $record->kelas ?? null,
                            'catatan' => $data['catatan'] ?? null,
                            'photos' => array_values(array_filter($data['photos'] ?? [], fn($p) => is_string($p) ? !str_starts_with($p, 'livewire-tmp/') : false))
                        ]);
                        
                        Notification::make()
                            ->title('Data berhasil disimpan')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Tidak ada siswa')
            ->emptyStateDescription('Tidak ada siswa di kelas ini.')
            ->defaultSort('nama_lengkap');
    }
}