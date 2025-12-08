<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Filament\Resources\AcademicYearResource\RelationManagers;
use App\Models\academic_year;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Checkbox;
use App\Services\AcademicYearTransitionService;


class AcademicYearResource extends Resource
{
    protected static ?string $model = academic_year::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Tahun Ajaran';
    protected static ?string $pluralLabel = 'Tahun Ajaran';
    protected static ?string $navigationGroup = 'Administrasi';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Tahun Ajaran')
                    ->description('Kelola data tahun ajaran dan semester')
                    ->schema([
                        TextInput::make('year')
                            ->required()
                            ->label('Tahun Ajaran')
                            ->placeholder('2024/2025')
                            ->maxLength(9)
                            ->regex('/^\d{4}\/\d{4}$/')
                            ->helperText('Format: YYYY/YYYY (contoh: 2024/2025). Satu tahun ajaran bisa memiliki 2 semester (Ganjil & Genap)')
                            ->reactive()
                            ->validationMessages([
                                'regex' => 'Format tahun ajaran harus YYYY/YYYY (contoh: 2024/2025)',
                            ]),
                            
                        Select::make('semester')
                            ->label('Semester')
                            ->options([
                                'Ganjil' => 'Ganjil',
                                'Genap' => 'Genap',
                            ])
                            ->required()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                // Check if combination already exists
                                $year = $get('year');
                                if ($year && $state) {
                                    $exists = academic_year::where('year', $year)
                                        ->where('semester', $state)
                                        ->exists();
                                    
                                    if ($exists) {
                                        $set('semester', null);
                                        \Filament\Notifications\Notification::make()
                                            ->title('Tahun ajaran sudah ada!')
                                            ->body("Tahun ajaran {$year} semester {$state} sudah terdaftar.")
                                            ->danger()
                                            ->send();
                                    }
                                }
                            })
                            ->rules([
                                function ($get, $record) {
                                    return function (string $attribute, $value, $fail) use ($get, $record) {
                                        $year = $get('year');
                                        $query = academic_year::where('year', $year)
                                            ->where('semester', $value);
                                        
                                        if ($record) {
                                            $query->where('id', '!=', $record->id);
                                        }
                                        
                                        if ($query->exists()) {
                                            $fail("Kombinasi tahun ajaran {$year} dan semester {$value} sudah ada.");
                                        }
                                    };
                                },
                            ]),
                            
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->helperText('Hanya satu tahun ajaran yang bisa aktif')
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($state && $record) {
                                    // Nonaktifkan tahun ajaran lain
                                    academic_year::where('id', '!=', $record->id)
                                        ->update(['is_active' => false]);
                                }
                            }),
                    ])
                    ->columns(2),
                    
                Section::make('Tanggal Penting')
                    ->description('Tentukan tanggal pembagian raport')
                    ->schema([
                        DatePicker::make('tanggal_penerimaan_raport')
                            ->label('Tanggal Penerimaan Raport')
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->helperText('Tanggal yang tertera di raport'),
                            
                        DatePicker::make('pembagian_raport')
                            ->label('Tanggal Pembagian Raport')
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->helperText('Tanggal aktual pembagian raport ke orang tua'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun Ajaran')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->size('base'),
                    
                BadgeColumn::make('semester')
                    ->label('Semester')
                    ->sortable()
                    ->colors([
                        'primary' => 'Ganjil',
                        'success' => 'Genap',
                    ]),
                    
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('tanggal_penerimaan_raport')
                    ->label('Tgl Penerimaan Raport')
                    ->date('d F Y')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('pembagian_raport')
                    ->label('Tgl Pembagian Raport')
                    ->date('d F Y')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('students_count')
                    ->label('Jumlah Siswa')
                    ->getStateUsing(function ($record) {
                        // Count unique students who have assessments in this academic year
                        return $record->assessments()
                            ->distinct('siswa_nis')
                            ->count('siswa_nis');
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->tooltip('Jumlah siswa yang memiliki penilaian di tahun ajaran ini'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->label('Tahun Ajaran Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),
                    
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun Ajaran')
                    ->options(function () {
                        return academic_year::orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->unique();
                    })
                    ->searchable(),
                    
                Tables\Filters\SelectFilter::make('semester')
                    ->label('Semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap' => 'Genap',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('transition')
                    ->label('Aktifkan & Transisi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Transisi ke Tahun Ajaran Baru')
                    ->modalDescription(fn ($record) => 
                        "Anda akan mengaktifkan tahun ajaran {$record->year} semester {$record->semester}. Sistem akan:\n" .
                        "• Nonaktifkan tahun ajaran yang sedang aktif\n" .
                        "• Generate struktur penilaian untuk semua siswa aktif\n" .
                        "• (Opsional) Copy data pertumbuhan terakhir"
                    )
                    ->form([
                        Section::make('Opsi Transisi')
                            ->description('Pilih opsi untuk proses transisi')
                            ->schema([
                                Checkbox::make('copy_last_growth')
                                    ->label('Copy Data Pertumbuhan Terakhir')
                                    ->helperText('Data berat & tinggi badan terakhir akan dicopy sebagai data awal di tahun ajaran baru')
                                    ->default(true),
                                    
                                Checkbox::make('generate_assessments')
                                    ->label('Generate Struktur Penilaian')
                                    ->helperText('Buat record penilaian kosong untuk semua siswa aktif')
                                    ->default(true)
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                    ])
                    ->action(function ($record, array $data) {
                        $service = app(AcademicYearTransitionService::class);
                        
                        // Get summary first
                        $summary = $service->getTransitionSummary($record);
                        
                        // Perform transition
                        $result = $service->transitionToNewYear($record, [
                            'copy_last_data' => $data['copy_last_growth'] ?? false,
                        ]);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Transisi Berhasil!')
                                ->body(
                                    "Tahun ajaran {$record->year} {$record->semester} telah diaktifkan.\n" .
                                    "• {$summary['will_create_assessments']} siswa siap dinilai\n" .
                                    "• Struktur penilaian telah dibuat"
                                )
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Transisi Gagal!')
                                ->body($result['message'])
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    })
                    ->after(fn () => redirect()->route('filament.admin.resources.academic-years.index')),
                
                Tables\Actions\Action::make('activate')
                    ->label('Aktifkan Saja')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan Tahun Ajaran')
                    ->modalDescription('Hanya mengaktifkan tanpa generate data. Yakin?')
                    ->action(function ($record) {
                        academic_year::where('id', '!=', $record->id)->update(['is_active' => false]);
                        $record->update(['is_active' => true]);
                        
                        Notification::make()
                            ->title('Tahun ajaran berhasil diaktifkan')
                            ->warning()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('view_data')
                    ->label('Lihat Data')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.student-assessments.index', [
                        'tableFilters' => [
                            'academic_year_id' => ['value' => $record->id],
                        ],
                    ]))
                    ->openUrlInNewTab()
                    ->tooltip('Lihat data penilaian semester ini'),
                    
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Tahun Ajaran')
                    ->modalDescription(fn ($record) => 
                        "Yakin ingin menghapus tahun ajaran {$record->year} semester {$record->semester}?"
                    )
                    ->before(function ($record, $action) {
                        // Check if has related data
                        $hasAssessments = $record->assessments()->exists();
                        $hasKelas = $record->kelas()->exists();
                        
                        if ($hasAssessments || $hasKelas) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak dapat dihapus!')
                                ->body('Tahun ajaran ini memiliki data penilaian atau kelas yang terkait.')
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
     public static function canViewAny(): bool
    {        
        $user = auth()->user();
        return $user && $user->hasRole('admin');
    }
}
