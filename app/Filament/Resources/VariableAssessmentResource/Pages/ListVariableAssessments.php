<?php

namespace App\Filament\Resources\VariableAssessmentResource\Pages;

use App\Filament\Resources\VariableAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVariableAssessments extends ListRecords
{
    protected static string $resource = VariableAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Tambah Assessment Variable')
                ->modalWidth('md'),
        ];
    }
}