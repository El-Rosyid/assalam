<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuruKelasResource\Pages;
use App\Models\data_kelas;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class GuruKelasResource extends Resource
{
    protected static ?string $model = data_kelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Guru Kelas';
    protected static ?string $pluralLabel = 'Kelas yang Diampu';
    protected static ?string $navigationGroup = 'Guru';

    public static function getEloquentQuery(): Builder
    {
        // Hanya tampilkan kelas yang diampu oleh guru yang sedang login
        $user = auth()->user();
        $guru = $user->guru ?? null;
        
        if (!$guru) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty result
        }
        
        return parent::getEloquentQuery()
            ->where('walikelas_id', $guru->guru_id) // Gunakan guru_id, bukan id
            ->with(['walikelas', 'tahunAjaran']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Form tidak diperlukan karena hanya read-only
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_kelas')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->sortable(),
                TextColumn::make('tahunAjaran.year')
                    ->label('Tahun Ajaran')
                    ->default('-')
                    ->description(fn ($record) => $record->tahunAjaran?->semester),
                TextColumn::make('siswa_count')
                    ->label('Jumlah Siswa')
                    ->counts('siswa')
                    ->badge(),
                TextColumn::make('walikelas.nama_lengkap')
                    ->label('Wali Kelas')
                    ->default('Tidak Ada'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('lihatDetailSiswa')
                    ->label('Lihat Siswa')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'Daftar Siswa - ' . $record->nama_kelas)
                    ->modalContent(function ($record) {
                        $siswa = $record->siswa()->orderBy('nama_lengkap')->get();
                        
                        if ($siswa->isEmpty()) {
                            return view('filament::components.modal.content', [
                                'content' => '<p class="text-gray-600">Belum ada siswa di kelas ini.</p>'
                            ]);
                        }
                        
                        $content = '<div class="space-y-2">';
                        $content .= '<p class="font-semibold">Total: ' . $siswa->count() . ' siswa</p>';
                        $content .= '<div class="border rounded-lg overflow-hidden">';
                        $content .= '<table class="w-full divide-y divide-gray-200">';
                        $content .= '<thead class="bg-gray-50"><tr>';
                        $content .= '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>';
                        $content .= '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Lengkap</th>';
                        $content .= '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">NISN</th>';
                        $content .= '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">JK</th>';
                        $content .= '</tr></thead><tbody class="divide-y divide-gray-200">';
                        
                        foreach ($siswa as $index => $s) {
                            $content .= '<tr class="hover:bg-gray-50">';
                            $content .= '<td class="px-4 py-2 text-sm">' . ($index + 1) . '</td>';
                            $content .= '<td class="px-4 py-2 text-sm font-medium">' . $s->nama_lengkap . '</td>';
                            $content .= '<td class="px-4 py-2 text-sm">' . ($s->nisn ?? '-') . '</td>';
                            $content .= '<td class="px-4 py-2 text-sm">' . $s->jenis_kelamin . '</td>';
                            $content .= '</tr>';
                        }
                        $content .= '</tbody></table></div></div>';
                        
                        return new HtmlString($content);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->bulkActions([
                // Tidak ada bulk action untuk guru
            ])
            ->emptyStateHeading('Tidak Ada Kelas')
            ->emptyStateDescription('Anda belum ditugaskan sebagai wali kelas manapun.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuruKelas::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Guru tidak bisa create kelas
    }

    public static function canEdit($record): bool
    {
        return false; // Guru tidak bisa edit kelas
    }

    public static function canDelete($record): bool
    {
        return false; // Guru tidak bisa delete kelas
    }

    public static function canViewAny(): bool
    {
        // Only wali kelas can access
        $user = auth()->user();
        return $user && $user->guru;
    }
}