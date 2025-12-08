<?php

namespace App\Filament\Resources\CustomBroadcastResource\Pages;

use App\Filament\Resources\CustomBroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomBroadcasts extends ListRecords
{
    protected static string $resource = CustomBroadcastResource::class;

    protected static ?string $title = 'Riwayat Broadcast';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Broadcast Baru')
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => static::getResource()::getUrl('index'))
                ->color('primary'),
        ];
    }
}
