<?php

namespace App\Filament\Resources\CustomBroadcastResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Detail Pengiriman';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('siswa_nis')
            ->columns([
                Tables\Columns\TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->description(fn ($record) => $record->siswa->kelasInfo?->nama_kelas),
                
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Nomor WhatsApp')
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($record): string => $record->status_badge),
                
                Tables\Columns\TextColumn::make('retry_count')
                    ->label('Percobaan')
                    ->badge()
                    ->color('info')
                    ->visible(fn ($record) => $record && $record->retry_count > 0),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record?->error_message)
                    ->visible(fn ($record) => $record && !empty($record->error_message)),                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Waktu Kirim')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                    ]),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_message')
                    ->label('Lihat Pesan')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->modalHeading('Isi Pesan yang Dikirim')
                    ->modalContent(fn ($record) => view('filament.custom.message-preview', [
                        'message' => $record->message,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
