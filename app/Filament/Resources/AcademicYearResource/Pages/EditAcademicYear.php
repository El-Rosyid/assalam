<?php

namespace App\Filament\Resources\AcademicYearResource\Pages;

use App\Filament\Pages\Traits\HasBackButton;
use App\Filament\Resources\AcademicYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicYear extends EditRecord
{
    protected static string $resource = AcademicYearResource::class;

    use HasBackButton;

    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
