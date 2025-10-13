<?php

namespace App\Filament\Resources\ReportCardResource\Pages;

use App\Filament\Resources\ReportCardResource;
use Filament\Resources\Pages\ListRecords;

class ListReportCards extends ListRecords
{
    protected static string $resource = ReportCardResource::class;
    
    public function getTitle(): string
    {
        return 'Cetak Raport';
    }
    
    public function getHeading(): string
    {
        return 'Cetak Raport Siswa';
    }
    
    public function getSubheading(): ?string
    {
        return 'Pilih kelas untuk melihat daftar siswa dan mencetak raport';
    }
}
