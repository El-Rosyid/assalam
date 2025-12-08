<?php

namespace App\Filament\Resources\CustomBroadcastResource\Pages;

use App\Filament\Resources\CustomBroadcastResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomBroadcast extends EditRecord
{
    protected static string $resource = CustomBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make()
                ->visible(fn () => $this->getRecord()->status === 'draft'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalculate total recipients jika target berubah
        $data['total_recipients'] = $this->calculateTotalRecipients($data);
        
        return $data;
    }

    private function calculateTotalRecipients(array $data): int
    {
        return match($data['target_type']) {
            'all' => \App\Models\data_siswa::count(),
            'class' => \App\Models\data_siswa::whereIn('kelas', $data['target_ids'] ?? [])->count(),
            'individual' => count($data['target_ids'] ?? []),
            default => 0,
        };
    }
}
