<?php

namespace App\Filament\Pages\Traits;

use Filament\Actions\Action;

trait HasBackButton
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->icon('heroicon-o-arrow-left')
                 ->label('Kembali')
                ->url(static::getResource()::getUrl())
                ->color('gray')
                ->button(),
        ];
    }
}