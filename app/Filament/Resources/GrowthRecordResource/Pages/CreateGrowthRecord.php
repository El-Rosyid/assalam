<?php

namespace App\Filament\Resources\GrowthRecordResource\Pages;

use App\Filament\Resources\GrowthRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGrowthRecord extends CreateRecord
{
    protected static string $resource = GrowthRecordResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user && $user->guru) {
            $data['data_guru_id'] = $user->guru->id;
            
            // Auto-fill kelas from siswa
            if (isset($data['data_siswa_id'])) {
                $siswa = \App\Models\data_siswa::find($data['data_siswa_id']);
                if ($siswa) {
                    $data['data_kelas_id'] = $siswa->kelas;
                }
            }
        }
        
        return $data;
    }
}
