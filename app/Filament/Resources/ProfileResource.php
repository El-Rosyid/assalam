<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class ProfileResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Profile';
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Akun';
    protected static ?string $pluralLabel = 'Profile';
    protected static ?string $slug = 'profile';
    protected static ?string $recordRouteKeyName = 'user_id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Profile')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->helperText('Upload foto profile Anda (max. 2MB)')
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Keamanan Akun')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->autocomplete('current-password')
                            ->dehydrated(false)
                            ->helperText('Wajib diisi jika ingin mengubah password'),

                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->minLength(3)
                            ->same('password_confirmation')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->helperText('Minimal 3 karakter. Kosongkan jika tidak ingin mengubah password'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->dehydrated(false)
                            ->helperText('Ulangi password baru untuk konfirmasi'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Profile resource tidak menggunakan table, langsung redirect ke edit
        return $table
            ->query(fn () => static::getEloquentQuery()->where('user_id', auth()->id()))
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('roles.name')
                    ->label('Role')
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'guru', 
                        'success' => 'siswa',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Profile'),
            ])
            ->paginated(false)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(), // Hide create button
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Hanya tampilkan data user yang sedang login
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Tidak bisa create user baru dari profile
    }

    public static function canDelete($record): bool
    {
        return false; // Tidak bisa delete profile sendiri
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return true; // Semua user bisa akses profile mereka sendiri
    }

    public static function canView($record): bool
    {
        return $record->user_id === auth()->id(); // Hanya bisa lihat profile sendiri
    }

    public static function canEdit($record): bool
    {
        return $record->user_id === auth()->id(); // Hanya bisa edit profile sendiri
    }
}