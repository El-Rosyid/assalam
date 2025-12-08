<?php

namespace App\Filament\Resources\AssessmentRatingDescriptionResource\Pages;

use App\Filament\Resources\AssessmentRatingDescriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentRatingDescriptions extends ListRecords
{
    protected static string $resource = AssessmentRatingDescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
