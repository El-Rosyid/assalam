<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataKelasResource\Pages;
use App\Filament\Resources\DataKelasResource\RelationManagers;
use App\Models\academic_year;
use App\Models\data_guru;
use App\Models\data_kelas;
use App\Models\DataKelas;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Livewire\wrap;

class DataKelasResource extends Resource
{
    protected static ?string $model = data_kelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Data Kelas';
    protected static ?string $pluralLabel = 'Data Kelas';
    protected static ?string $navigationGroup = 'Administrasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tambah Data Kelas')
                    ->schema([
                        Select::make('tingkat')
                            ->required()
                            ->label('Kelas')
                            ->placeholder('--pilih kelas--')
                            ->options([
                                1 => 'Kelas A (TK A)',
                                2 => 'Kelas B (TK B)',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Auto-fill nama_kelas berdasarkan tingkat
                                $nama = $state == 1 ? 'Kelas A' : ($state == 2 ? 'Kelas B' : '');
                                $set('nama_kelas', $nama);
                            })
                            ->searchable(),
                        TextInput::make('nama_kelas')
                            ->label('Nama Kelas')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Otomatis terisi dari tingkat yang dipilih'),
                        Select::make('walikelas_id')
                            ->required()
                            ->label('Wali Kelas')
                            ->placeholder('--pilih wali kelas--')
                            ->options(function () {
                                return data_guru::where('status', 'Aktif')
                                    ->orderBy('nama_lengkap')
                                    ->get()
                                    ->mapWithKeys(function ($guru) {
                                        // Tampilkan NIP/NUPTK jika ada, jika tidak ada tampilkan "Guru Honor"
                                        $identifier = '';
                                        if ($guru->nip) {
                                            $identifier = ' - NIP: ' . $guru->nip;
                                        } elseif ($guru->nuptk) {
                                            $identifier = ' - NUPTK: ' . $guru->nuptk;
                                        } else {
                                            $identifier = ' (Guru Honor)';
                                        }
                                        return [$guru->guru_id => $guru->nama_lengkap . $identifier];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('tahun_ajaran_id')
                            ->required()
                            ->label('Tahun Ajaran')
                            ->placeholder('--pilih tahun ajaran--')
                            ->options(function () {
                                return academic_year::orderBy('year', 'desc')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        return [$item->tahun_ajaran_id => $item->year . ' - ' . $item->semester];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kelas_id')
                    ->label('ID Kelas')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->tingkat == 1 ? 'success' : 'info'),
                TextColumn::make('walikelas.nama_lengkap')
                    ->label('Wali Kelas')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->wrap()
                    ->description(fn ($record) => $record->walikelas ? $record->walikelas->nip
                        ? 'NIP: ' . $record->walikelas->nip : 'Tidak ada NIP' : 'Tidak ada wali kelas'),
                 TextColumn::make('siswa_count')
                    ->label('Jumlah Siswa')
                    ->counts('siswa')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('tahunAjaran.year')
                    ->label('Tahun Ajaran')
                    ->default('-')
                    ->description(fn ($record) => $record->tahunAjaran?->semester),
                ])
            ->filters([
                SelectFilter::make('tingkat')
                    ->label('Filter Kelas')
                    ->options([
                        1 => 'Kelas A (TK A)',
                        2 => 'Kelas B (TK B)',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('lihatDetailSiswa')
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
                        
                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                    
                EditAction::make()
                    ->color('warning'),
                DeleteAction::make()
                    ->color('danger')
                    ->before(function ($record) {
                        // Cek apakah ada siswa di kelas ini
                        if ($record->siswa()->count() > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Tidak dapat menghapus kelas')
                                ->body('Masih ada ' . $record->siswa()->count() . ' siswa di kelas ini. Pindahkan siswa terlebih dahulu.')
                                ->send();
                            
                            return false; // Batalkan penghapusan
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('pindahSiswa')
                        ->label('Pindahkan Semua Siswa')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->form([
                            Select::make('kelas_tujuan')
                                ->label('Pindah ke Kelas')
                                ->placeholder('--pilih kelas tujuan--')
                                ->options(function () {
                                    return data_kelas::orderBy('nama_kelas')
                                        ->get()
                                        ->mapWithKeys(function ($kelas) {
                                            return [$kelas->kelas_id => $kelas->nama_kelas . ' (Tingkat ' . $kelas->tingkat . ')'];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->helperText('Pilih kelas tujuan untuk memindahkan semua siswa dari kelas yang dipilih'),
                        ])
                        ->action(function (array $data, $records) {
                            $kelasTujuan = $data['kelas_tujuan'];
                            $totalSiswa = 0;
                            $namaKelasAsal = [];
                            
                            foreach ($records as $kelas) {
                                $jumlahSiswa = $kelas->siswa()->count();
                                if ($jumlahSiswa > 0) {
                                    // Pindahkan semua siswa ke kelas tujuan
                                    $kelas->siswa()->update(['kelas' => $kelasTujuan]);
                                    $totalSiswa += $jumlahSiswa;
                                    $namaKelasAsal[] = $kelas->nama_kelas;
                                }
                            }
                            
                            if ($totalSiswa > 0) {
                                $kelasTujuanNama = data_kelas::find($kelasTujuan)->nama_kelas;
                                Notification::make()
                                    ->success()
                                    ->title('Siswa Berhasil Dipindahkan!')
                                    ->body("$totalSiswa siswa dari kelas " . implode(', ', $namaKelasAsal) . " telah dipindahkan ke kelas $kelasTujuanNama.")
                                    ->duration(8000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Tidak Ada Siswa')
                                    ->body('Tidak ada siswa yang dapat dipindahkan dari kelas yang dipilih.')
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Pindahkan Semua Siswa')
                        ->modalDescription('Apakah Anda yakin ingin memindahkan semua siswa dari kelas yang dipilih ke kelas tujuan?')
                        ->modalSubmitActionLabel('Ya, Pindahkan'),
                        
                    BulkAction::make('lihatSiswa')
                        ->label('Lihat Siswa di Kelas')
                        ->icon('heroicon-o-user-group')
                        ->color('info')
                        ->action(function ($records) {
                            $totalSiswa = 0;
                            $detailKelas = [];
                            
                            foreach ($records as $kelas) {
                                $jumlahSiswa = $kelas->siswa()->count();
                                $totalSiswa += $jumlahSiswa;
                                $detailKelas[] = $kelas->nama_kelas . ': ' . $jumlahSiswa . ' siswa';
                            }
                            
                            Notification::make()
                                ->info()
                                ->title('Informasi Siswa per Kelas')
                                ->body('Total: ' . $totalSiswa . ' siswa<br>' . implode('<br>', $detailKelas))
                                ->duration(10000)
                                ->send();
                        }),

                    BulkAction::make('gantiTingkat')
                        ->label('Ganti Tingkat Kelas')
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('success')
                        ->form([
                            Select::make('tingkat_baru')
                                ->label('Tingkat Baru')
                                ->placeholder('--pilih tingkat baru--')
                                ->options([
                                    1 => 'TK Kelas A',
                                    2 => 'TK Kelas B',
                                ])
                                ->required()
                                ->helperText('Pilih tingkat baru untuk kelas yang dipilih'),
                        ])
                        ->action(function (array $data, $records) {
                            $tingkatBaru = $data['tingkat_baru'];
                            $namaKelasBaru = $tingkatBaru == 1 ? 'Kelas A' : 'Kelas B';
                            $namaKelas = [];
                            $jumlahKelas = count($records);
                            
                            foreach ($records as $kelas) {
                                // Update tingkat DAN nama_kelas agar konsisten
                                $kelas->update([
                                    'tingkat' => $tingkatBaru,
                                    'nama_kelas' => $namaKelasBaru
                                ]);
                                $namaKelas[] = $namaKelasBaru;
                            }
                            
                            $tingkatLabel = $tingkatBaru == 1 ? 'Kelas A (TK A)' : 'Kelas B (TK B)';
                            
                            Notification::make()
                                ->success()
                                ->title('Tingkat Kelas Berhasil Diubah!')
                                ->body("$jumlahKelas kelas telah diubah ke $tingkatLabel.")
                                ->duration(8000)
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ganti Tingkat Kelas')
                        ->modalDescription('Apakah Anda yakin ingin mengubah tingkat untuk kelas yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Ubah Tingkat'),
                        
                    BulkAction::make('gantiWaliKelas')
                        ->label('Ganti Wali Kelas')
                        ->icon('heroicon-o-user-circle')
                        ->color('primary')
                        ->form([
                            Select::make('walikelas_baru')
                                ->label('Wali Kelas Baru')
                                ->placeholder('--pilih wali kelas baru--')
                                ->options(function () {
                                    return data_guru::where('status', 'Aktif')
                                        ->orderBy('nama_lengkap')
                                        ->get()
                                        ->mapWithKeys(function ($guru) {
                                            $identifier = '';
                                            if ($guru->nip) {
                                                $identifier = ' - NIP: ' . $guru->nip;
                                            } elseif ($guru->nuptk) {
                                                $identifier = ' - NUPTK: ' . $guru->nuptk;
                                            } else {
                                                $identifier = ' (Guru Honor)';
                                            }
                                            return [$guru->guru_id => $guru->nama_lengkap . $identifier];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->helperText('Pilih guru yang akan menjadi wali kelas baru'),
                        ])
                        ->action(function (array $data, $records) {
                            $waliKelasBaru = $data['walikelas_baru'];
                            $namaKelas = [];
                            $jumlahKelas = count($records);
                            
                            foreach ($records as $kelas) {
                                $kelas->update(['walikelas_id' => $waliKelasBaru]);
                                $namaKelas[] = $kelas->nama_kelas;
                            }
                            
                            $namaGuru = data_guru::find($waliKelasBaru)->nama_lengkap;
                            
                            Notification::make()
                                ->success()
                                ->title('Wali Kelas Berhasil Diubah!')
                                ->body("$jumlahKelas kelas (" . implode(', ', $namaKelas) . ") sekarang diampu oleh $namaGuru.")
                                ->duration(8000)
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ganti Wali Kelas')
                        ->modalDescription('Apakah Anda yakin ingin mengganti wali kelas untuk kelas yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Ganti Wali Kelas'),
                    
                    BulkAction::make('Ganti Tahun Ajaran')
                        ->label('Ganti Tahun Ajaran')
                        ->icon('heroicon-o-calendar')
                        ->color('secondary')
                        ->form([
                            Select::make('tahun_ajaran_baru')
                                ->label('Tahun Ajaran Baru')
                                ->placeholder('--pilih tahun ajaran baru--')
                                ->options(function () {
                                    return academic_year::orderBy('year', 'desc')
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            return [$item->tahun_ajaran_id => $item->year . ' - ' . $item->semester];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->helperText('Pilih tahun ajaran baru untuk kelas yang dipilih'),
                        ])
                        ->action(function (array $data, $records) {
                            $tahunAjaranBaru = $data['tahun_ajaran_baru'];
                            $namaKelas = [];
                            $jumlahKelas = count($records);

                            foreach ($records as $kelas) {
                                $kelas->update(['tahun_ajaran_id' => $tahunAjaranBaru]);
                                $namaKelas[] = $kelas->nama_kelas;
                            }

                            $tahunAjaranObj = academic_year::find($tahunAjaranBaru);
                            $namaTahunAjaran = $tahunAjaranObj->year;

                            Notification::make()
                                ->success()
                                ->title('Tahun Ajaran Berhasil Diubah!')
                                ->body("$jumlahKelas kelas (" . implode(', ', $namaKelas) . ") sekarang menggunakan tahun ajaran $namaTahunAjaran.")
                                ->duration(8000)
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ganti Tahun Ajaran')
                        ->modalDescription('Apakah Anda yakin ingin mengganti tahun ajaran untuk kelas yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Ganti Tahun Ajaran'),
                    ])
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataKelas::route('/'),
            'create' => Pages\CreateDataKelas::route('/create'),
            'edit' => Pages\EditDataKelas::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {        
        $user = auth()->user();
        return $user && $user->hasRole('admin');
    }
}
