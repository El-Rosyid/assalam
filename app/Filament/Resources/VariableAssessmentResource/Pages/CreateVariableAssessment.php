<?php

namespace App\Filament\Resources\VariableAssessmentResource\Pages;

use App\Filament\Resources\VariableAssessmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVariableAssessment extends CreateRecord
{
    protected static string $resource = VariableAssessmentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}