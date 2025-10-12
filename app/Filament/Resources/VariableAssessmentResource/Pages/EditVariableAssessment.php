<?php

namespace App\Filament\Resources\VariableAssessmentResource\Pages;

use App\Filament\Resources\VariableAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVariableAssessment extends EditRecord
{
    protected static string $resource = VariableAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}