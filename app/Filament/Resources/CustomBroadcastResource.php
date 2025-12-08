<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomBroadcastResource\Pages;
use App\Models\CustomBroadcast;
use App\Models\data_kelas;
use App\Models\data_siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomBroadcastResource extends Resource
{
    protected static ?string $model = CustomBroadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $navigationLabel = 'Pesan WhatsApp';
    
    protected static ?string $navigationGroup = 'WhatsApp';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                 Forms\Components\Section::make('Target Penerima')
                    ->description('Pilih siapa yang akan menerima pesan WhatsApp ini')
                    ->schema([                                                
                        Forms\Components\Radio::make('target_type')
                            ->label('Kirim Ke:')
                            ->options([
                                'all' => 'Semua Siswa',
                                'class' => 'Per Kelas',
                                'individual' => 'Per Siswa (Individual)',
                            ])
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->reactive()                            
                            ->afterStateUpdated(fn ($state, callable $set) => $set('target_ids', null)),

                        Forms\Components\Select::make('target_ids')
                            ->label('Pilih Kelas')
                            ->options(data_kelas::pluck('nama_kelas', 'kelas_id'))
                            ->multiple()
                            ->searchable()
                            ->visible(fn (callable $get) => $get('target_type') === 'class')
                            ->required(fn (callable $get) => $get('target_type') === 'class')
                            ->helperText(fn (callable $get) => 
                            $get('target_ids') 
                            ? 'Total: ' . data_siswa::whereIn('kelas', (array)$get('target_ids'))->count() . ' siswa' 
                            : 'Pilih kelas terlebih dahulu'
                        ),
                        
                        Forms\Components\Select::make('target_ids')
                        ->label('Pilih Siswa')
                        ->options(function () {
                            return data_siswa::with('kelasInfo')
                                    ->get()
                                    ->mapWithKeys(fn ($siswa) => [
                                        $siswa->nis => "{$siswa->nama_lengkap} ({$siswa->kelasInfo?->nama_kelas}) - {$siswa->no_telp_ortu_wali}"
                                    ]);
                                })
                                ->multiple()
                                ->searchable()
                                ->visible(fn (callable $get) => $get('target_type') === 'individual')
                                ->required(fn (callable $get) => $get('target_type') === 'individual')
                                ->helperText(fn (callable $get) => 
                                $get('target_ids') 
                                ? count((array)$get('target_ids')) . ' siswa dipilih' 
                                : 'Pilih siswa yang akan menerima broadcast'
                            ),
                                                    
                        Forms\Components\Placeholder::make('total_info')
                            ->label('Informasi')
                            ->content(fn (callable $get) => 
                                $get('target_type') === 'all' 
                                    ? '✅ Broadcast akan dikirim ke SEMUA siswa (' . data_siswa::count() . ' siswa)'
                                    : 'Pilih target penerima di atas'
                            )
                            ->visible(fn (callable $get) => $get('target_type') === 'all'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Isi Pesan')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Pesan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Pengumuman Libur Semester')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('message')
                            ->label('Isi Pesan')
                            ->helperText('Tuliskan isi pesan saja. Format formal sudah ditambahkan otomatis.')
                            ->required()
                            ->rows(6)
                            ->placeholder('Contoh: Sekolah akan libur pada tanggal 15-20 Desember 2025...')
                            ->columnSpanFull(),
                        ])
                    ->columns(1),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                                
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul') 
                    ->searchable()
                    ->description(fn (CustomBroadcast $record): string => $record->created_at->format('d M Y, H:i')),
                
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'success',
                        'class' => 'warning',
                        'individual' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => '👥 Semua',
                        'class' => '🏫 Per Kelas',
                        'individual' => '👤 Individual',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Penerima')
                    ->formatStateUsing(fn (CustomBroadcast $record): string => 
                        "{$record->sent_count}/{$record->total_recipients}" . 
                        ($record->failed_count > 0 ? " ({$record->failed_count} gagal)" : '')
                    )
                    ->description(fn (CustomBroadcast $record): string => 
                        $record->total_recipients > 0 
                            ? "{$record->progress_percentage}% selesai" 
                            : '-'
                    ),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (CustomBroadcast $record): string => $record->status_badge),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sending' => 'Mengirim',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                    ]),
                
                Tables\Filters\SelectFilter::make('target_type')
                    ->label('Target')
                    ->options([
                        'all' => 'Semua Siswa',
                        'class' => 'Per Kelas',
                        'individual' => 'Per Siswa',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (CustomBroadcast $record) => "Detail: {$record->title}")
                    ->modalContent(fn (CustomBroadcast $record) => view('filament.modals.custom-broadcast-detail', ['record' => $record]))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                    
                Tables\Actions\EditAction::make()
                    ->visible(fn (CustomBroadcast $record): bool => $record->status === 'draft'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (CustomBroadcast $record): bool => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            CustomBroadcastResource\RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\CreateCustomBroadcast::route('/'),
            'history' => Pages\ListCustomBroadcasts::route('/history'),
            'edit' => Pages\EditCustomBroadcast::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only admin and super_admin can access this resource
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }
}   
