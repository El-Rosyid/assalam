<?php

namespace App\Filament\Resources\GrowthRecordResource\Pages;

use App\Filament\Resources\GrowthRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGrowthRecord extends EditRecord
{
    protected static string $resource = GrowthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
