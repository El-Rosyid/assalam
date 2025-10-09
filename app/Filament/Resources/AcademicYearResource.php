<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Filament\Resources\AcademicYearResource\RelationManagers;
use App\Models\academic_year;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;


class AcademicYearResource extends Resource
{
    protected static ?string $model = academic_year::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Tahun Ajaran';
    protected static ?string $pluralLabel = 'Tahun Ajaran';
    protected static ?string $navigationGroup = 'Administrasi';

    public static function form(Form $form): Form
    
    {
        
        return $form
            ->schema([
                Card ::make()->schema([
                    TextInput::make('year')
                        ->required()
                        ->label('Tahun Ajaran')
                        ->placeholder('2023/2024')
                        ->maxLength(9)
                        ->unique(ignoreRecord: true),
                    Select::make('semester')
                        ->label('Semester')
                        ->options([
                            'Ganjil' => 'Ganjil',
                            'Genap' => 'Genap',
                        ])
                        ->required(),
                    DatePicker::make('pembagian_raport')
                        ->label('Pembagian Raport')
                        ->required()
                        ->placeholder('Pilih Tanggal'),
                    
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')->label('Tahun Ajaran')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('semester')->label('Semester')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('pembagian_raport')->label('Pembagian Raport')->date()->sortable()->searchable(),
              ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),  
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view data admin');
    }
}
