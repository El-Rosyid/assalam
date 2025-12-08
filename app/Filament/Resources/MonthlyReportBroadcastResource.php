<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyReportBroadcastResource\Pages;
use App\Filament\Resources\MonthlyReportBroadcastResource\RelationManagers;
use App\Models\MonthlyReportBroadcast;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonthlyReportBroadcastResource extends Resource
{
    protected static ?string $model = MonthlyReportBroadcast::class;

    protected static ?string $navigationLabel = 'Catatan Perkembangan Bulanan';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('siswa_nis')
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('monthly_report_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('phone_number')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('response')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('error_message')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('retry_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DateTimePicker::make('sent_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('siswa_nis')
                    ->searchable(),
                Tables\Columns\TextColumn::make('monthly_report_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('retry_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListMonthlyReportBroadcasts::route('/'),
            'create' => Pages\CreateMonthlyReportBroadcast::route('/create'),
            'edit' => Pages\EditMonthlyReportBroadcast::route('/{record}/edit'),
        ];
    }
}
