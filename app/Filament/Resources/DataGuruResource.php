<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataGuruResource\Pages;
use App\Models\data_guru;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;

class DataGuruResource extends Resource
{
    protected static ?string $model = data_guru::class;
    protected static ?string $navigationLabel = 'Manajemen Data Guru';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Manajemen Data';
    protected static ?string $pluralLabel = 'Data Guru';

    public static function form(Form $form): Form
    {
        $statusComponent = request()->routeIs('filament.resources.data-guru.create')
            ? Hidden::make('status')->default('Aktif')
            : Select::make('status')
                ->label('Status')
                ->options([
                    'Aktif' => 'Aktif',
                    'Non_Aktif' => 'Non_Aktif',
                ])
                ->required();

        return $form->schema([
            Section::make('Informasi Akun')
                ->hidden(fn (?data_guru $record) => $record !== null) // Hide on edit, show only on create
                ->schema([
                    TextInput::make('account.username')
                        ->label('Username (Akun)')
                        ->required()
                        ->unique(
                            table: 'users',
                            column: 'username',
                            ignorable: fn($record) => $record?->user?->id // fix: ignore user ID saat edit
                        )
                        ->maxLength(255)
                        ->stripCharacters('— ',),

                    TextInput::make('account.name')
                        ->label('Nama (Akun)')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Otomatis terisi dari Nama Lengkap, bisa diubah manual')
                        ->stripCharacters('— ',),

                    TextInput::make('account.password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->required(fn ($context) => $context === 'create')
                        ->minLength(3)
                        ->same('account.passwordConfirmation')
                        ->dehydrated(true)
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->helperText('Min. 3 karakter. Kosongkan saat edit jika tidak diubah')
                        ->stripCharacters('— ',),

                    TextInput::make('account.passwordConfirmation')
                        ->label('Konfirmasi Password')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->required(fn ($context) => $context === 'create')
                        ->dehydrated(false)
                        ->stripCharacters('— ',),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Data Pribadi Guru')
                ->schema([
                    TextInput::make('nama_lengkap')
                        ->required()
                        ->label('Nama Lengkap')
                        ->stripCharacters('— ',)
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($state) {
                                // isi account.name dari nama_lengkap
                                if (!$get('account.name')) {
                                    $set('account.name', $state);
                                }
                            }
                        }),

                    TextInput::make('nip')
                        ->label('NIP')
                        ->numeric()
                        ->stripCharacters('— ',),

                    TextInput::make('nuptk')
                        ->label('NUPTK')
                        ->numeric()
                        ->stripCharacters('— ',),

                    Select::make('jenis_kelamin')
                        ->required()
                        ->label('Jenis Kelamin')
                        ->options([
                            'Laki-laki' => 'Laki-laki',
                            'Perempuan' => 'Perempuan',
                        ]),

                    TextInput::make('tempat_lahir')
                        ->required()
                        ->stripCharacters('— ',),
                    
                    DatePicker::make('tanggal_lahir')
                        ->required()
                        ->format('Y-m-d')
                        ->displayFormat('Y-m-d'),
                    
                    TextInput::make('no_telp')
                        ->required()
                        ->stripCharacters('— ',),
                    
                    TextInput::make('email')
                        ->email()
                        ->stripCharacters('— ',),
                    
                    Textarea::make('alamat')
                        ->required()
                        ->columnSpanFull()
                        ->stripCharacters('— ',),
                    
                ])
                ->columns(2)
                ->collapsible(),

            
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_lengkap')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nuptk')->label('NUPTK')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('jenis_kelamin')->label('JK')->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Aktif',
                        'danger' => 'Non_Aktif',
                    ])
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('Aktifkan')
                    ->action(fn ($records) => $records->each->update(['status' => 'Aktif']))
                    ->color('success'),
                Tables\Actions\BulkAction::make('NonAktifkan')
                    ->action(fn ($records) => $records->each->update(['status' => 'Non_Aktif']))
                    ->color('danger'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataGurus::route('/'),
            'create' => Pages\CreateDataGuru::route('/create'),
            'edit' => Pages\EditDataGuru::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only admin can access this resource
        $user = auth()->user();
        return $user && $user->hasRole('admin');
    }
}
