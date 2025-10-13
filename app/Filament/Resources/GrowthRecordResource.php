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
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class GrowthRecordResource extends Resource
{
    protected static ?string $model = GrowthRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $navigationLabel = 'Catatan Pertumbuhan';
    
    protected static ?string $navigationGroup = 'Penilaian';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Catatan Pertumbuhan';
    
    protected static ?string $pluralModelLabel = 'Catatan Pertumbuhan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Pertumbuhan')
                    ->schema([
                        Forms\Components\Select::make('data_siswa_id')
                            ->label('Siswa')
                            ->relationship('siswa', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ])
                            ->required()
                            ->default(now()->month),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Hasil Pengukuran')
                    ->schema([
                        Forms\Components\TextInput::make('lingkar_kepala')
                            ->label('Lingkar Kepala (cm)')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('cm'),
                            
                        Forms\Components\TextInput::make('lingkar_lengan')
                            ->label('Lingkar Lengan (cm)')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(50)
                            ->suffix('cm'),
                            
                        Forms\Components\TextInput::make('berat_badan')
                            ->label('Berat Badan (kg)')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('kg'),
                            
                        Forms\Components\TextInput::make('tinggi_badan')
                            ->label('Tinggi Badan (cm)')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(200)
                            ->suffix('cm'),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show records for current user's class
                $user = auth()->user();
                if ($user && $user->guru) {
                    $query->forWaliKelas($user->guru->id);
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('bulan_tahun')
                    ->label('Bulan')
                    ->sortable(['month'])
                    ->searchable(),
                    
                Tables\Columns\TextInputColumn::make('lingkar_kepala')
                    ->label('L. Kepala (cm)')
                    ->type('number')
                    ->step(0.1),
                    
                Tables\Columns\TextInputColumn::make('lingkar_lengan')
                    ->label('L. Lengan (cm)')
                    ->type('number')
                    ->step(0.1),
                    
                Tables\Columns\TextInputColumn::make('berat_badan')
                    ->label('BB (kg)')
                    ->type('number')
                    ->step(0.1),
                    
                Tables\Columns\TextInputColumn::make('tinggi_badan')
                    ->label('TB (cm)')
                    ->type('number')
                    ->step(0.1),
                    
                Tables\Columns\TextColumn::make('bmi')
                    ->label('BMI')
                    ->getStateUsing(fn (GrowthRecord $record): ?string => 
                        $record->bmi ? number_format($record->bmi, 2) : '-'
                    )
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        !$state || $state === '-' => 'gray',
                        (float) $state < 18.5 => 'warning',
                        (float) $state > 25 => 'danger',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('data_siswa_id')
                    ->label('Siswa')
                    ->relationship('siswa', 'nama_lengkap')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('3xl'),
                    
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('month', 'desc');
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
            'create' => Pages\CreateGrowthRecord::route('/create'),
            'edit' => Pages\EditGrowthRecord::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        // Allow create for wali kelas
        $user = auth()->user();
        return $user && $user->guru;
    }
    
    public static function canViewAny(): bool
    {
        // Only wali kelas can access
        $user = auth()->user();
        return $user && $user->guru;
    }
}
