<?php

namespace App\Filament\Resources\StudentAssessmentResource\Pages;

use App\Filament\Resources\StudentAssessmentResource;
use App\Models\student_assessment;
use App\Models\student_assessment_detail;
use App\Models\assessment_variable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Tabs;

class InputStudentAssessment extends EditRecord
{
    protected static string $resource = StudentAssessmentResource::class;
    
    protected static ?string $title = 'Input Penilaian Siswa';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali ke Daftar')
                ->url(StudentAssessmentResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Siswa')
                    ->schema([
                        Placeholder::make('student_info')
                            ->label('')
                            ->content(function () {
                                $record = $this->getRecord();
                                $kelasInfo = $record->siswa->kelasInfo ?? null;
                                return new HtmlString("
                                    <div class='bg-blue-50 p-4 rounded-lg border border-blue-200'>
                                        <div class='grid grid-cols-2 gap-4'>
                                            <div><strong>Nama:</strong> {$record->siswa->nama_lengkap}</div>
                                            <div><strong>NIS:</strong> {$record->siswa->nis}</div>
                                            <div><strong>Kelas:</strong> " . ($kelasInfo ? $kelasInfo->nama_kelas : 'Belum ada kelas') . "</div>
                                            <div><strong>Semester:</strong> {$record->semester}</div>
                                        </div>
                                    </div>
                                ");
                            }),
                    ])
                    ->columns(1),
                    
                Section::make('Penilaian per Kriteria Assessment Variable')
                    ->description('Berikan penilaian untuk setiap kriteria perkembangan siswa berdasarkan Assessment Variable yang telah ditetapkan. Lengkapi dengan dokumentasi foto dan deskripsi observasi untuk setiap kriteria.')
                    ->schema([
                        Tabs::make('assessment_tabs')
                            ->tabs(function () {
                                $tabs = [];
                                $variables = assessment_variable::all();
                                
                                if ($variables->isEmpty()) {
                                    $tabs[] = Tabs\Tab::make('no_variables')
                                        ->label('Tidak Ada Data')
                                        ->schema([
                                            Placeholder::make('no_variables')
                                                ->label('')
                                                ->content(new HtmlString("
                                                    <div class='bg-yellow-50 p-4 rounded-lg border border-yellow-200'>
                                                        <p class='text-yellow-800'>⚠️ Belum ada Assessment Variable yang tersedia.</p>
                                                        <p class='text-sm text-yellow-600 mt-1'>Silakan hubungi administrator untuk menambahkan Assessment Variable terlebih dahulu.</p>
                                                    </div>
                                                "))
                                        ]);
                                    return $tabs;
                                }
                                
                                foreach ($variables as $index => $variable) {                                    
                                    $tabs[] = Tabs\Tab::make("variable_{$variable->id}")
                                        ->label($variable->name)                                        
                                        ->schema([
                                            Placeholder::make('variable_info')
                                                ->label('')
                                                ->content(function () use ($variable) {
                                                    return new HtmlString("
                                                        <div class='bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg border border-green-200 mb-6'>
                                                            <div class='flex items-center gap-3'>
                                                                
                                                                <div>
                                                                    <h3 class='text-xl font-bold text-green-800'>{$variable->name}</h3>                                                                    
                                                                    " . ($variable->dekripsi ? "<p class='text-sm text-gray-600 mt-1'>{$variable->dekripsi}</p>" : "") . "
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ");
                                                }),
                                            
                                            Hidden::make("details.{$variable->id}.assessment_variable_id")
                                                ->default($variable->id),
                                            
                                            FileUpload::make("details.{$variable->id}.images")
                                                ->label("Upload Gambar Dokumentasi")
                                                ->multiple()
                                                ->maxFiles(2)
                                                ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                                ->maxSize(2048) // 2MB
                                                ->directory('student-assessments')
                                                ->visibility('private')
                                                ->downloadable()
                                                ->previewable()
                                                ->helperText("Upload maksimal 2 foto dokumentasi untuk kriteria '{$variable->name}' (Format: JPG/PNG, Maksimal 2MB per file)"),
                                            
                                            Radio::make("details.{$variable->id}.rating")
                                                ->label("Tingkat Perkembangan")
                                                ->options([
                                                    'Berkembang Sesuai Harapan' => '✅ Berkembang Sesuai Harapan',
                                                    'Belum Berkembang' => '🔴 Belum Berkembang',
                                                    'Mulai Berkembang' => '🟡 Mulai Berkembang',
                                                    'Sudah Berkembang' => '🟢 Sudah Berkembang',
                                                ])
                                                ->reactive()
                                                ->afterStateUpdated(function (Forms\Set $set, $state) use ($variable) {
                                                    if ($state) {
                                                        $autoDescription = student_assessment_detail::getAutoDescription($state, $variable->id);
                                                        $set("details.{$variable->id}.description", $autoDescription);
                                                    }
                                                })
                                                ->helperText("Pilih tingkat perkembangan siswa pada kriteria '{$variable->name}'"),
                                            
                                            Textarea::make("details.{$variable->id}.description")
                                                ->label("Deskripsi & Observasi Guru")
                                                ->rows(4)
                                                ->placeholder("Tuliskan observasi dan catatan perkembangan siswa untuk kriteria '{$variable->name}'...")
                                                ->helperText("💡 Deskripsi akan otomatis terisi berdasarkan rating yang dipilih, namun dapat Anda edit sesuai observasi detail."),
                                        ]);
                                }
                                
                                return $tabs;
                            })
                            ->columnSpanFull()
                            ->persistTabInQueryString()
                    ]),
            ])
            ->columns(1);
    }
    
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan Penilaian'),
            $this->getCancelFormAction()
                ->url(StudentAssessmentResource::getUrl('index')),
        ];
    }
    
    protected function afterSave(): void
    {
        // Update status assessment berdasarkan completion
        $this->getRecord()->updateStatus();
        
        Notification::make()
            ->title('Penilaian berhasil disimpan!')
            ->success()
            ->send();
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure all assessment variables have details
        $record = $this->getRecord();
        $allVariables = assessment_variable::all();
        
        // Get existing details
        $existingDetails = $record->details()->get()->keyBy('assessment_variable_id');
        
        $data['details'] = [];
        
        foreach ($allVariables as $variable) {
            $existingDetail = $existingDetails->get($variable->id);
            
            if ($existingDetail) {
                // Use existing data
                $data['details'][$variable->id] = [
                    'assessment_variable_id' => $variable->id,
                    'rating' => $existingDetail->rating,
                    'description' => $existingDetail->description,
                    'images' => $existingDetail->images ? json_decode($existingDetail->images, true) : [],
                ];
            } else {
                // Create new empty detail
                $data['details'][$variable->id] = [
                    'assessment_variable_id' => $variable->id,
                    'rating' => null,
                    'description' => '',
                    'images' => [],
                ];
            }
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert details structure back to relationship format
        if (isset($data['details'])) {
            $record = $this->getRecord();
            
            foreach ($data['details'] as $variableId => $detail) {
                // Find or create detail record
                $detailRecord = student_assessment_detail::updateOrCreate(
                    [
                        'student_assessment_id' => $record->id,
                        'assessment_variable_id' => $variableId,
                    ],
                    [
                        'rating' => $detail['rating'],
                        'description' => $detail['description'],
                        'images' => !empty($detail['images']) ? json_encode($detail['images']) : null,
                    ]
                );
            }
            
            // Remove details from main data to avoid relationship conflicts
            unset($data['details']);
        }
        
        return $data;
    }
}