<?php

namespace App\Filament\Resources\MonthlyReportSiswaResource\Pages;

use App\Filament\Pages\Traits\HasBackButton;
use App\Filament\Resources\MonthlyReportSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewMonthlyReportSiswa extends ViewRecord
{
    protected static string $resource = MonthlyReportSiswaResource::class;
     use HasBackButton;

    protected function getHeaderActions(): array
    {
        return [
            // Siswa tidak bisa edit atau delete
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        return 'Catatan ' . $months[$record->month] . ' ' . $record->year;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Informasi Metadata di atas
                Components\Section::make('Informasi Catatan')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('month')
                                    ->label('Bulan')
                                    ->formatStateUsing(function ($state) {
                                        $months = [
                                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                        ];
                                        return $months[$state] ?? '-';
                                    })
                                    ->badge()
                                    ->color('primary'),
                                    
                                Components\TextEntry::make('created_at')
                                    ->label('Dibuat pada tanggal')
                                    ->dateTime('d F Y')
                                    ->badge()
                                    ->color('info'),
                                    
                                Components\TextEntry::make('guru.nama_lengkap')
                                    ->label('Guru')
                                    ->default('Belum ada guru')
                                    ->icon('heroicon-o-user'),
                                    
                                Components\TextEntry::make('kelas.nama_kelas')
                                    ->label('Kelas')
                                    ->badge()
                                    ->color('success')
                                    ->default('Belum ada kelas'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                // Layout 2 Kolom: Foto & Catatan dalam 1 Section
                Components\Section::make('Catatan Perkembangan')
                    ->description('Foto kegiatan dan catatan dari guru')
                    ->schema([
                        Components\Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                // Kolom Kiri: Foto Kegiatan
                                Components\Group::make([
                                    Components\TextEntry::make('photos_label')
                                        ->label('Foto Kegiatan')
                                        ->hiddenLabel(false)
                                        ->default('')
                                        ->formatStateUsing(fn () => '')
                                        ->extraAttributes(['class' => 'font-semibold text-sm mb-2']),
                                        
                                    Components\ViewEntry::make('photos')
                                        ->label('')
                                        ->view('components.photo-gallery')
                                        ->viewData(fn ($record) => [
                                            'photos' => $record?->photos ?? []
                                        ]),
                                ])
                                ->columnSpan([
                                    'default' => 1,
                                    'md' => 1,
                                ]),
                                
                                // Kolom Kanan: Catatan Guru
                                Components\Group::make([
                                    Components\TextEntry::make('catatan_label')
                                        ->label('Catatan dari Guru')
                                        ->hiddenLabel(false)
                                        ->default('')
                                        ->formatStateUsing(fn () => '')
                                        ->extraAttributes(['class' => 'font-semibold text-sm mb-2']),
                                        
                                    Components\TextEntry::make('catatan')
                                        ->label('')
                                        ->default('Belum ada catatan dari guru')
                                        ->prose()
                                        ->html()
                                        ->formatStateUsing(fn ($state) => 
                                            $state 
                                                ? '<div style="white-space: pre-wrap; line-height: 1.8;">' . nl2br(e($state)) . '</div>'
                                                : '<div class="text-gray-500 italic">Belum ada catatan dari guru</div>'
                                        ),
                                ])
                                ->columnSpan([
                                    'default' => 1,
                                    'md' => 1,
                                ]),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}