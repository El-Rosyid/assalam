<?php

namespace App\Filament\Resources\DataGuruResource\Pages;

use App\Filament\Resources\DataGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Testing\Fluent\Concerns\Has;



class ListDataGurus extends ListRecords
{
    protected static string $resource = DataGuruResource::class;
    
    protected static ?string $title = 'Data Guru';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data Guru'),
        ];
    }
}
