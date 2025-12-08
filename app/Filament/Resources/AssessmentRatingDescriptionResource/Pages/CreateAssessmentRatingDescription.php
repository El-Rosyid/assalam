<?php

namespace App\Filament\Resources\AssessmentRatingDescriptionResource\Pages;

use App\Filament\Resources\AssessmentRatingDescriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAssessmentRatingDescription extends CreateRecord
{
    protected static string $resource = AssessmentRatingDescriptionResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
