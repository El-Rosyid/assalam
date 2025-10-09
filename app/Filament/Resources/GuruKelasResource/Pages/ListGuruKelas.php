<?php

namespace App\Filament\Resources\GuruKelasResource\Pages;

use App\Filament\Resources\GuruKelasResource;
use Filament\Resources\Pages\ListRecords;

class ListGuruKelas extends ListRecords
{
    protected static string $resource = GuruKelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak ada action untuk create karena guru tidak bisa membuat kelas
        ];
    }

    public function getTitle(): string
    {
        return 'Kelas yang Diampu';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\GuruStatsOverview::class,
        ];
    }
}