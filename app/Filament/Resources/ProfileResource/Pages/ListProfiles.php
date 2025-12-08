<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for profile
        ];
    }

    public function mount(): void
    {
        // Automatically redirect to edit the current user's profile
        $user = auth()->user();
        $this->redirect(ProfileResource::getUrl('edit', ['record' => $user->user_id]));
    }
}