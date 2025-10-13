<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VariableAssessmentResource\Pages;
use App\Models\assessment_variable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VariableAssessmentResource extends Resource
{
    protected static ?string $model = assessment_variable::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Assessment Variables';
    
    protected static ?string $navigationGroup = 'Manajemen Data';
    
    protected static ?int $navigationSort = 10;
    
    protected static ?string $modelLabel = 'Assessment Variable';
    
    protected static ?string $pluralModelLabel = 'Assessment Variables';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Assessment Variable')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Variable')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('dekripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Variable')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('dekripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Assessment Variable')
                    ->modalWidth('md'),
                
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
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
            'index' => Pages\ListVariableAssessments::route('/'),
            'create' => Pages\CreateVariableAssessment::route('/create'),
            'edit' => Pages\EditVariableAssessment::route('/{record}/edit'),
        ];
    }
    
    public static function canViewAny(): bool
    {
        return true; // Allow all authenticated users for now
    }
}