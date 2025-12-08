<?php

namespace App\Filament\Resources\DataKelasResource\Pages;
use App\Filament\Pages\Traits\HasBackButton;
use App\Filament\Resources\DataKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDataKelas extends CreateRecord
{
   protected static string $resource = DataKelasResource::class;

   use HasBackButton;
    protected static ?string $title = 'Tambah Data Kelas';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data Kelas berhasil ditambahkan';
    }
}
