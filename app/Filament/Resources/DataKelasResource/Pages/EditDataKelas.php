<?php

namespace App\Filament\Resources\DataKelasResource\Pages;

use App\Filament\Pages\Traits\HasBackButton;
use App\Filament\Resources\DataKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataKelas extends EditRecord
{
    protected static string $resource = DataKelasResource::class;

    use HasBackButton;

    protected static ?string $title = 'Edit Data Kelas';

    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data Kelas berhasil diperbarui';
    }
}