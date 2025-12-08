<?php

namespace App\Filament\Resources\MonthlyReportBroadcastResource\Pages;

use App\Filament\Resources\MonthlyReportBroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyReportBroadcasts extends ListRecords
{
    protected static string $resource = MonthlyReportBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
