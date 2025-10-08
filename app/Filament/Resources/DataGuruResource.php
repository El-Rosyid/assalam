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
            Section::make('Data Akun Guru')
                ->schema([
                    Hidden::make('account.user_id'),
                    
                    TextInput::make('account.username')
                        ->label('Username')
                        ->required()
                        ->unique(
                            table: 'users',
                            column: 'username',
                            ignorable: fn($record) => $record?->user // biar aman saat edit
                        )
                        ->maxLength(255),

                    TextInput::make('account.name')
                        ->hidden(fn ($context) => $context === 'create')
                        ->label('Nama (Akun)')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Otomatis terisi dari Nama Lengkap, bisa diubah manual')
                        ->readOnly(fn ($context) => $context === 'create') // pakai readOnly, bukan disabled
                        ->dehydrated(true),

                    TextInput::make('account.password')
                        ->label('Password')
                        ->password()
                        ->required(fn ($context) => $context === 'create')
                        ->minLength(8)
                        ->same('account.passwordConfirmation')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->helperText('Min. 8 karakter. Kosongkan saat edit jika tidak diubah'),

                    TextInput::make('account.passwordConfirmation')
                        ->label('Konfirmasi Password')
                        ->password()
                        ->required(fn ($context) => $context === 'create')
                        ->dehydrated(false),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Data Pribadi Guru')
                ->schema([
                    TextInput::make('nama_lengkap')
                        ->required()
                        ->label('Nama Lengkap')
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
                        ->numeric(),

                    TextInput::make('nuptk')
                        
                        ->label('NUPTK')
                        ->numeric(),

                    Select::make('jenis_kelamin')
                        ->required()
                        ->label('Jenis Kelamin')
                        ->options([
                            'Laki-laki' => 'Laki-laki',
                            'Perempuan' => 'Perempuan',
                        ]),

                    TextInput::make('tempat_lahir')->required(),
                    DatePicker::make('tanggal_lahir')->required(),
                    TextInput::make('no_telp')->required(),
                    TextInput::make('email')->email(),
                    Textarea::make('alamat')
                    ->required()
                    ->columnSpanFull(),
                    
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
        return auth()->user()->can('can view admin');
    }
}
