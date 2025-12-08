<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentRatingDescriptionResource\Pages;
use App\Filament\Resources\AssessmentRatingDescriptionResource\RelationManagers;
use App\Models\AssessmentRatingDescription;
use App\Models\assessment_variable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssessmentRatingDescriptionResource extends Resource
{
    protected static ?string $model = AssessmentRatingDescription::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Deskripsi Penilaian';
    
    protected static ?string $modelLabel = 'Deskripsi Rating';
    
    protected static ?string $pluralModelLabel = 'Deskripsi Rating Assessment';
    
    protected static ?string $navigationGroup = 'Master Data';
    
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Deskripsi Rating')
                    ->schema([
                        Forms\Components\Select::make('assessment_variable_id')
                            ->label('Assessment Variable')
                            ->relationship('assessmentVariable', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\Select::make('rating')
                            ->label('Rating')
                            ->options(AssessmentRatingDescription::getRatingOptions())
                            ->required(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('assessmentVariable.name')
                    ->label('Assessment Variable')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Berkembang' => 'success',
                        'Berkembang Sesuai Harapan' => 'info',
                        'Mulai Berkembang' => 'warning',
                        'Belum Berkembang' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(80)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 80) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assessment_variable_id')
                    ->label('Assessment Variable')
                    ->relationship('assessmentVariable', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Rating')
                    ->options(AssessmentRatingDescription::getRatingOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('assessmentVariable.name');
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
            'index' => Pages\ListAssessmentRatingDescriptions::route('/'),
            'create' => Pages\CreateAssessmentRatingDescription::route('/create'),
            'edit' => Pages\EditAssessmentRatingDescription::route('/{record}/edit'),
        ];
    }
    
    // Tidak bisa create manual karena sudah ada seed untuk semua kombinasi
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canViewAny(): bool
    {
        // Only admin can access this resource
        $user = auth()->user();
        return $user && $user->hasRole('admin');
    }
}
