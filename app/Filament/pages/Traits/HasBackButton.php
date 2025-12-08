<?php

namespace App\Filament\Pages\Traits;

use Filament\Actions\Action;

trait HasBackButton
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->url($this->getResource()::getUrl('index'))
                ->button()
                ->color('gray'),
        ];
    }
}