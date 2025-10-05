<?php

namespace App\Filament\Resources\DataGuruResource\Pages;

use App\Filament\Pages\Traits\HasBackButton;
use App\Filament\Resources\DataGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataGuru extends EditRecord
{
    use HasBackButton;

    protected static ?string $title = 'Edit Data Guru';
    protected static string $resource = DataGuruResource::class;

    protected function getHeaderActions(): array
    {
       
        return [
            Actions\DeleteAction::make(),

            
        ];
    }
}
