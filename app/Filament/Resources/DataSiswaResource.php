<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataSiswaResource\Pages;
use App\Filament\Resources\DataSiswaResource\RelationManagers;
use App\Models\data_kelas;
use App\Models\data_siswa;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;

class DataSiswaResource extends Resource
{
    protected static ?string $model = data_siswa::class;

    protected static ?string $navigationLabel = 'Manajemen Data Siswa';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Manajemen Data';

    protected static ?string $pluralLabel = 'Data Siswa';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['kelas.walikelas', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Akun Siswa')
                    ->description('Username akan otomatis terisi dari NISN')
                    ->schema([
                        TextInput::make('account.user_id')
                            ->hidden(),
                        TextInput::make('account.username')
                            ->label('Username')
                            ->required()                            
                            ->maxLength(255)
                            ->helperText('Otomatis terisi dari NISN')
                            ->disabled(fn ($context) => $context === 'create')
                            ->dehydrated(true)
                             ->unique(
                                table: 'users',
                                column: 'username',
                                modifyRuleUsing: function ($rule, $record) {
                                    if ($record && $record->user) {
                                        return $rule->ignore($record->user->id);
                                    }
                                    return $rule;
                                }
                            ),
                        TextInput::make('account.name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Otomatis terisi dari nama lengkap')
                            ->disabled(fn ($context) => $context === 'create')
                            ->dehydrated(true),
                        
                        TextInput::make('account.password')
                            ->label('Password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->minLength(8)
                            ->same('account.passwordConfirmation')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->helperText('Min. 8 karakter. Kosongkan jika tidak ingin mengubah password'),
                        
                        TextInput::make('account.passwordConfirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(false)
                            ->visible(fn ($context) => $context === 'create' || fn ($get) => filled($get('account.password'))),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Data Pribadi Siswa')
                    ->schema([
                        TextInput::make('nisn')
                            ->label('NISN')
                            ->required()
                            ->numeric()
                            ->length(10)
                            ->unique('data_siswa', 'nisn', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get, $context) {
                                if ($state) {
                                    // Auto-fill username dari NISN
                                    if (!$get('account.username')) {
                                        $set('account.username', $state);
                                    }
                                    
                                    // Auto-fill password default dari NISN (hanya saat create)
                                    if ($context === 'create' && !$get('account.password')) {
                                        $set('account.password', $state);
                                        $set('account.passwordConfirmation', $state);
                                    }
                                }
                            }),
                        
                        TextInput::make('nis')
                            ->label('NIS')
                            ->required()
                            ->numeric()
                            ->unique('data_siswa', 'nis', ignoreRecord: true),
                        
                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if ($state) {
                                    if (!$get('account.name')) {
                                        $set('account.name', $state);
                                    }
                                }                                   
                            }),
                        
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ]),
                        
                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->required(),
                        
                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->native(false),
                        
                        Select::make('agama')
                            ->label('Agama')
                            ->required()
                            ->options([
                                'Islam' => 'Islam',
                                'Kristen' => 'Kristen',
                                'Hindu' => 'Hindu',
                                'Buddha' => 'Buddha',
                                'Konghucu' => 'Konghucu',
                            ]),
                        
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->required()
                            
                    ])
                    ->columns(2),

                Section::make('Data Keluarga')
                    ->schema([
                        TextInput::make('anak_ke')
                            ->label('Anak Ke')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        
                        TextInput::make('jumlah_saudara')
                            ->label('Jumlah Saudara')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        
                        TextInput::make('nama_ayah')
                            ->label('Nama Ayah')
                            ->required(),
                        
                        TextInput::make('pekerjaan_ayah')
                            ->label('Pekerjaan Ayah')
                            ->required(),
                        
                        TextInput::make('nama_ibu')
                            ->label('Nama Ibu')
                            ->required(),
                        
                        TextInput::make('pekerjaan_ibu')
                              ->label('Pekerjaan Ibu')
                            ->required(),
                        
                        TextInput::make('no_telp_ortu_wali')
                            ->label('No. Telp Orang Tua/Wali')
                            ->tel()
                            ->required(),
                        
                        TextInput::make('email_ortu_wali')
                            ->label('Email Orang Tua/Wali')
                            ->email()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Data Sekolah')
                    ->schema([
                        TextInput::make('asal_sekolah')
                            ->label('Asal Sekolah')
                            ->nullable(),
                        
                        Select::make('diterima_kelas')
                            ->label('Diterima di Kelas')
                            ->required()
                            ->options([
                                'A' => 'A',
                                'B' => 'B',
                            ]),
                        
                        DatePicker::make('tanggal_diterima')
                            ->label('Tanggal Diterima')
                            ->required()
                            ->native(false),
                        
                        Select::make('kelas')
                            ->label('Kelas Saat Ini')
                            ->placeholder('-- Pilih Kelas --')
                            ->options(function () {
                                return data_kelas::query()
                                    ->with(['walikelas', 'tahunAjaran'])
                                    ->orderBy('tingkat')
                                    ->orderBy('nama_kelas')
                                    ->get()
                                    ->mapWithKeys(function ($kelas) {
                                        $waliKelas = $kelas->walikelas 
                                            ? ' - Wali: ' . $kelas->walikelas->nama_lengkap 
                                            : '';
                                        $tahun = $kelas->tahunAjaran 
                                            ? ' (' . $kelas->tahunAjaran->year . ')' 
                                            : '';
                                        
                                        return [
                                            $kelas->id => $kelas->nama_kelas . ' [Tingkat ' . $kelas->tingkat . ']' . $waliKelas . $tahun
                                        ];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        
                        Hidden::make('status')
                            ->default('Aktif')
                            ->dehydrated(true)
                            ->visible(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                        
                        Select::make('status')
                            ->label('Status Siswa')
                            ->options([
                                'Aktif' => 'Aktif',
                                'Non_Aktif' => 'Non Aktif',
                            ])
                            ->required()
                            ->visible(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state === 'Laki-laki' ? 'L' : 'P'),
                
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->badge()
                    ->color('success')
                    ->description(function ($record) {
                        try {
                            $walikelas = $record->getWaliKelasInfo();
                            return $walikelas ? 'Wali: ' . $walikelas->nama_lengkap : null;
                        } catch (\Exception $e) {
                            return null;
                        }
                    }),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Aktif',
                        'danger' => 'Non_Aktif',
                    ]),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kelas')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Non_Aktif' => 'Non Aktif',
                    ]),
                
                SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    //pindah kelas
                    BulkAction::make('pindahKelas')
                        ->label('Pindah Kelas')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('info')
                        ->form([
                            Select::make('kelas')
                                ->label('Pilih Kelas Baru')
                                ->placeholder('-- Pilih Kelas Tujuan --')
                                ->options(function () {
                                    return data_kelas::query()
                                        ->orderBy('tingkat')
                                        ->orderBy('nama_kelas')
                                        ->get()
                                        ->mapWithKeys(function ($kelas) {
                                            $waliKelas = $kelas->walikelas 
                                                ? ' - ' . $kelas->walikelas->nama_lengkap 
                                                : '';
                                            return [
                                                $kelas->id => $kelas->nama_kelas . ' [Tingkat ' . $kelas->tingkat . ']' . $waliKelas
                                            ];
                                        });
                                })
                                ->required()
                                ->searchable()
                        ])
                        ->action(function (array $data, $records) {
                            $records->each->update(['kelas' => $data['kelas']]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Siswa berhasil dipindahkan ke kelas baru'),

                        //aktif non aktif
                        BulkAction::make('aktifkan')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'Aktif']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDataSiswas::route('/'),
            'create' => Pages\CreateDataSiswa::route('/create'),
            'edit' => Pages\EditDataSiswa::route('/{record}/edit')
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view data admin');
    }
}