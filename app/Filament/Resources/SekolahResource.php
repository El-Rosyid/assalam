<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SekolahResource\Pages;
use App\Filament\Resources\SekolahResource\RelationManagers;
use App\Models\Sekolah;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
// use Filament\Forms\Components\TextColumn; // Remove this line
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SekolahResource extends Resource
{
    protected static ?string $model = Sekolah::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administrasi';
    

    protected static ?string $navigationLabel = 'Sekolah';


    public static function form(Form $form): Form
     
    
    
    {
        return $form
            ->schema([
                TextInput::make('nama_sekolah')
                    ->required()->label('Nama Sekolah'),
                TextInput::make('alamat')
                    ->required()->label('Alamat'),
                TextInput::make('npsn')
                    ->required()->label('NPSN')->numeric(),
                TextInput::make('nss')
                    ->required()->label('NSS')->numeric(),
                TextInput::make('kode_pos')
                    ->required()->label('Kode Pos')->numeric(),
                
                Forms\Components\Select::make('kepala_sekolah_id')
                    ->label('Kepala Sekolah')
                    ->relationship('kepalaSekolah', 'nama_lengkap')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $guru = \App\Models\data_guru::find($state);
                            if ($guru) {
                                $set('nip_kepala_sekolah', $guru->nip);
                            }
                        } else {
                            $set('nip_kepala_sekolah', null);
                        }
                    }),
                    
                TextInput::make('nip_kepala_sekolah')
                    ->label('NIP Kepala Sekolah')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('NIP akan terisi otomatis setelah memilih kepala sekolah'),
                    
                FileUpload::make('logo_sekolah')->label('Logo Sekolah')->image()->nullable(),  
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_sekolah')->label('Nama Sekolah')->searchable()->sortable(),
                TextColumn::make('alamat')->label('Alamat')->searchable()->sortable(),
                TextColumn::make('npsn')->label('NPSN')->searchable()->sortable(),
                TextColumn::make('nss')->label('NSS')->searchable()->sortable(),
                TextColumn::make('kode_pos')->label('Kode Pos')->searchable()->sortable(),
                TextColumn::make('kepalaSekolah.nama_lengkap')
                    ->label('Kepala Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nip_kepala_sekolah')->label('NIP Kepala Sekolah')->searchable()->sortable(),
                ImageColumn::make('logo_sekolah')->label('Logo Sekolah')->rounded(),                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat Pada')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Diubah Pada')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),      
            ])
            ->filters([
                //
            ])
            ->actions([
                
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListSekolahs::route('/'),
            'create' => Pages\CreateSekolah::route('/create'),
            'edit' => Pages\EditSekolah::route('/{record}/edit'),
        ];
    }
    public static function getNavigationUrl(): string
    {
        $sekolah = \App\Models\Sekolah::first();

        return $sekolah
            ? static::getUrl('edit', ['record' => $sekolah->getKey()])
            : static::getUrl('create');
    }


     public static function canViewAny(): bool
    {        
        $user = auth()->user();
        return $user && $user->hasRole('admin');
    }

}
