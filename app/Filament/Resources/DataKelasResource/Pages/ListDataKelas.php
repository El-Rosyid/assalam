<?php

namespace App\Filament\Resources\DataKelasResource\Pages;

use App\Filament\Resources\DataKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataKelas extends ListRecords
{
   protected static string $resource = DataKelasResource::class;
    protected static ?string $title = 'Data Kelas';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kelas')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
