<?php

namespace App\Filament\Resources\MonthlyReportSiswaResource\Pages;

use App\Filament\Resources\MonthlyReportSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListMonthlyReportSiswas extends ListRecords
{
    protected static string $resource = MonthlyReportSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Siswa tidak bisa create
        ];
    }

    public function getTitle(): string
    {
        $user = Auth::user();
        if ($user && $user->siswa) {
            return 'Catatan Perkembangan - ' . $user->siswa->nama_lengkap;
        }
        return 'Catatan Perkembangan Saya';
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }
}