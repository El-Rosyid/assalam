<?php

namespace App\Filament\Resources\DataSiswaResource\Pages;

use App\Filament\Resources\DataSiswaResource;
use Filament\Actions;
use App\Filament\Pages\Traits\HasBackButton;
use Filament\Resources\Pages\EditRecord;

class EditDataSiswa extends EditRecord
{
    use HasBackButton;
    protected static ?string $title = 'Edit Data Siswa';
    protected static string $resource = DataSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
