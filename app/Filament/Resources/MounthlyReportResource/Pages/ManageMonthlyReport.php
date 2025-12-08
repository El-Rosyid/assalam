<?php

namespace App\Filament\Resources\MounthlyReportResource\Pages;

use App\Filament\Resources\MonthlyReportResource;
use Filament\Actions;
use App\Models\data_kelas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ManageMonthlyReport extends ManageRelatedRecords
{
    use InteractsWithTable;
    protected static string $resource = MonthlyReportResource::class;

    protected static string $relationship = 'data_siswa';

   public function mount(int|string $record): void
    {
        // Override parent mount to use data_kelas instead of AttendanceRecord
        $this->record = data_kelas::findOrFail($record);
        
        // Check if current user is wali kelas of this class
        $user = auth()->user();
        if (!$user || !$user->guru || $this->record->walikelas_id !== $user->guru->id) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }
        
        // Generate attendance records if not exist
        $this->generateAttendanceRecords();
    }

    public static function getNavigationLabel(): string
    {
        return 'Data Siswa';
    }

     protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(MonthlyReportResource::getUrl()),             
           
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('Kelola Catatan')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Kelola Catatan')
            ->columns([
                Tables\Columns\TextColumn::make('Kelola Catatan'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DissociateAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
