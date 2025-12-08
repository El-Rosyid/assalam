<?php

namespace App\Filament\Resources\VariableAssessmentResource\Pages;

use App\Filament\Resources\VariableAssessmentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateVariableAssessment extends CreateRecord
{
    protected static string $resource = VariableAssessmentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Assessment Variable berhasil dibuat! Otomatis membuat 4 deskripsi rating.';
    }
}