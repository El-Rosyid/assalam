<?php

namespace App\Filament\Resources\AssessmentRatingDescriptionResource\Pages;

use App\Filament\Resources\AssessmentRatingDescriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentRatingDescription extends EditRecord
{
    protected static string $resource = AssessmentRatingDescriptionResource::class;

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
