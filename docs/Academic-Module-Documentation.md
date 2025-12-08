# Dokumentasi Implementasi Modul Akademik

## 📋 Daftar Isi

1. [Overview Modul Akademik](#overview-modul-akademik)
2. [Pengelolaan Data Master](#pengelolaan-data-master)
3. [Input Laporan Bulanan](#input-laporan-bulanan)
4. [Sistem Penilaian](#sistem-penilaian)
5. [Cetak Rapor PDF](#cetak-rapor-pdf)
6. [Filter Data Berdasarkan Role](#filter-data-berdasarkan-role)
7. [Technical Implementation](#technical-implementation)

---

## Overview Modul Akademik

Modul Akademik adalah sistem inti dari aplikasi manajemen sekolah yang mencakup:

### 🎯 Fitur Utama

```
┌─────────────────────────────────────────────────┐
│           MODUL AKADEMIK                        │
├─────────────────────────────────────────────────┤
│                                                 │
│  1️⃣  PENGELOLAAN DATA MASTER                   │
│      ├── Data Guru (CRUD)                      │
│      ├── Data Siswa (CRUD)                     │
│      └── Data Kelas (CRUD)                     │
│                                                 │
│  2️⃣  INPUT LAPORAN BULANAN                     │
│      ├── Generate Records Per Kelas            │
│      ├── Upload Foto Kegiatan                  │
│      └── Catatan Perkembangan                  │
│                                                 │
│  3️⃣  SISTEM PENILAIAN                          │
│      ├── Variabel Penilaian                    │
│      ├── Assessment Detail (Rating + Deskripsi)│
│      ├── Upload Foto Per Aspek                 │
│      └── Status Tracking (Belum/Sebagian/Selesai)│
│                                                 │
│  4️⃣  CETAK RAPOR PDF                           │
│      ├── Generate PDF dengan DomPDF            │
│      ├── Template Cover + Content              │
│      ├── Data Penilaian + Pertumbuhan          │
│      └── Role-Based Access (Guru & Siswa)      │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## Pengelolaan Data Master

### 1️⃣ Data Guru (CRUD)

**Resource:** `app/Filament/Resources/DataGuruResource.php`

**Model:** `app/Models/data_guru.php`

#### Struktur Tabel:

```sql
CREATE TABLE data_guru (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_lengkap VARCHAR(255),
    nip VARCHAR(50) UNIQUE,
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P'),
    agama VARCHAR(50),
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    status_kepegawaian VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Fitur CRUD:

```php
// Create - Form Schema
Forms\Components\TextInput::make('nama_lengkap')
    ->required()
    ->maxLength(255),
Forms\Components\TextInput::make('nip')
    ->label('NIP')
    ->unique(ignoreRecord: true),
Forms\Components\DatePicker::make('tanggal_lahir'),
Forms\Components\Select::make('jenis_kelamin')
    ->options(['L' => 'Laki-laki', 'P' => 'Perempuan']),

// Read - Table Columns
Tables\Columns\TextColumn::make('nama_lengkap')
    ->searchable()
    ->sortable(),
Tables\Columns\TextColumn::make('nip')
    ->searchable(),

// Update - Edit Action (default Filament)
// Delete - Delete Action (default Filament)
```

#### Relasi:

```php
// Model: data_guru.php
public function kelas()
{
    return $this->hasMany(data_kelas::class, 'walikelas_id');
}

public function assessments()
{
    return $this->hasMany(student_assessment::class, 'data_guru_id');
}

public function monthlyReports()
{
    return $this->hasMany(monthly_reports::class, 'data_guru_id');
}

public function user()
{
    return $this->hasOne(User::class, 'guru_id');
}
```

---

### 2️⃣ Data Siswa (CRUD)

**Resource:** `app/Filament/Resources/DataSiswaResource.php`

**Model:** `app/Models/data_siswa.php`

#### Struktur Tabel:

```sql
CREATE TABLE data_siswa (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_lengkap VARCHAR(255),
    nisn VARCHAR(50) UNIQUE,
    nis VARCHAR(50),
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P'),
    agama VARCHAR(50),
    alamat TEXT,
    nama_ayah VARCHAR(255),
    nama_ibu VARCHAR(255),
    telepon_orangtua VARCHAR(20),
    kelas BIGINT,
    status ENUM('aktif', 'nonaktif', 'lulus', 'pindah'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (kelas) REFERENCES data_kelas(id)
);
```

#### Fitur CRUD:

```php
// Create - Form Schema
Forms\Components\Section::make('Data Pribadi')
    ->schema([
        Forms\Components\TextInput::make('nama_lengkap')
            ->required(),
        Forms\Components\TextInput::make('nisn')
            ->label('NISN')
            ->unique(ignoreRecord: true),
        Forms\Components\TextInput::make('nis')
            ->label('NIS'),
        Forms\Components\DatePicker::make('tanggal_lahir'),
    ]),

Forms\Components\Section::make('Data Orang Tua')
    ->schema([
        Forms\Components\TextInput::make('nama_ayah'),
        Forms\Components\TextInput::make('nama_ibu'),
        Forms\Components\TextInput::make('telepon_orangtua'),
    ]),

Forms\Components\Section::make('Data Akademik')
    ->schema([
        Forms\Components\Select::make('kelas')
            ->relationship('kelasInfo', 'nama_kelas')
            ->searchable()
            ->preload(),
        Forms\Components\Select::make('status')
            ->options([
                'aktif' => 'Aktif',
                'nonaktif' => 'Non-Aktif',
                'lulus' => 'Lulus',
                'pindah' => 'Pindah',
            ])
            ->default('aktif'),
    ]),

// Read - Table with Badge Status
Tables\Columns\TextColumn::make('nama_lengkap')
    ->searchable()
    ->sortable(),
Tables\Columns\TextColumn::make('nisn')
    ->searchable(),
Tables\Columns\TextColumn::make('kelasInfo.nama_kelas')
    ->label('Kelas')
    ->badge()
    ->color('primary'),
Tables\Columns\BadgeColumn::make('status')
    ->colors([
        'success' => 'aktif',
        'danger' => 'nonaktif',
        'warning' => 'lulus',
        'gray' => 'pindah',
    ]),
```

#### Relasi:

```php
// Model: data_siswa.php
public function kelasInfo()
{
    return $this->belongsTo(data_kelas::class, 'kelas');
}

public function studentAssessments()
{
    return $this->hasMany(student_assessment::class, 'data_siswa_id');
}

public function growthRecords()
{
    return $this->hasMany(GrowthRecord::class, 'data_siswa_id');
}

public function attendanceRecords()
{
    return $this->hasMany(AttendanceRecord::class, 'data_siswa_id');
}

public function monthlyReports()
{
    return $this->hasMany(monthly_reports::class, 'data_siswa_id');
}

public function user()
{
    return $this->hasOne(User::class, 'siswa_id');
}
```

---

### 3️⃣ Data Kelas (CRUD)

**Resource:** `app/Filament/Resources/DataKelasResource.php`

**Model:** `app/Models/data_kelas.php`

#### Struktur Tabel:

```sql
CREATE TABLE data_kelas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_kelas VARCHAR(100),
    walikelas_id BIGINT,
    tahun_ajaran_id BIGINT,
    tingkat VARCHAR(10),
    created_at TIMESTAMP,

    FOREIGN KEY (walikelas_id) REFERENCES data_guru(id),
    FOREIGN KEY (tahun_ajaran_id) REFERENCES academic_year(id)
);
```

#### Fitur CRUD:

```php
// Create - Form Schema
Forms\Components\TextInput::make('nama_kelas')
    ->required()
    ->maxLength(100)
    ->placeholder('Contoh: Kelas A, TK A, TK B'),

Forms\Components\Select::make('walikelas_id')
    ->label('Wali Kelas')
    ->relationship('waliKelas', 'nama_lengkap')
    ->searchable()
    ->preload()
    ->required(),

Forms\Components\Select::make('tahun_ajaran_id')
    ->label('Tahun Ajaran')
    ->relationship('tahunAjaran', 'year')
    ->getOptionLabelFromRecordUsing(fn ($record) =>
        $record->year . ' - ' . $record->semester
    )
    ->default(function () {
        return \App\Models\academic_year::where('is_active', true)->first()?->id;
    }),

Forms\Components\Select::make('tingkat')
    ->options([
        'TK A' => 'TK A',
        'TK B' => 'TK B',
    ]),

// Read - Table with Counts
Tables\Columns\TextColumn::make('nama_kelas')
    ->searchable()
    ->sortable(),
Tables\Columns\TextColumn::make('waliKelas.nama_lengkap')
    ->label('Wali Kelas'),
Tables\Columns\TextColumn::make('siswa_count')
    ->label('Jumlah Siswa')
    ->getStateUsing(function ($record) {
        return \App\Models\data_siswa::where('kelas', $record->id)->count();
    })
    ->badge()
    ->color('info'),
```

#### Relasi:

```php
// Model: data_kelas.php
public function waliKelas()
{
    return $this->belongsTo(data_guru::class, 'walikelas_id');
}

public function tahunAjaran()
{
    return $this->belongsTo(academic_year::class, 'tahun_ajaran_id');
}

public function siswa()
{
    return $this->hasMany(data_siswa::class, 'kelas');
}

public function assessments()
{
    return $this->hasMany(student_assessment::class, 'data_kelas_id');
}

public function monthlyReports()
{
    return $this->hasMany(monthly_reports::class, 'data_kelas_id');
}
```

---

## Input Laporan Bulanan

### 📝 Monthly Reports Resource

**Resource:** `app/Filament/Resources/MonthlyReportResource.php`

**Model:** `app/Models/monthly_reports.php`

#### Struktur Tabel:

```sql
CREATE TABLE monthly_reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    data_siswa_id BIGINT NOT NULL,
    data_guru_id BIGINT NOT NULL,
    data_kelas_id BIGINT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    catatan TEXT,
    photos JSON,
    status ENUM('draft', 'final') DEFAULT 'draft',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE KEY unique_siswa_month (data_siswa_id, month, year),
    FOREIGN KEY (data_siswa_id) REFERENCES data_siswa(id),
    FOREIGN KEY (data_guru_id) REFERENCES data_guru(id),
    FOREIGN KEY (data_kelas_id) REFERENCES data_kelas(id)
);
```

### 🔄 Generate Records Bulk

#### Fitur Generate Otomatis:

**Action Button:**

```php
// File: MonthlyReportResource.php
protected function getHeaderActions(): array
{
    return [
        Action::make('generate_records')
            ->label('Generate Catatan Bulan Ini')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Generate Catatan Perkembangan')
            ->modalDescription('Generate catatan untuk semua siswa di kelas yang Anda ajar untuk bulan ini.')
            ->action(function () {
                $user = Auth::user();
                if (!$user || !$user->guru) {
                    Notification::make()
                        ->title('Error')
                        ->body('User tidak memiliki data guru.')
                        ->danger()
                        ->send();
                    return;
                }

                $currentMonth = date('n');
                $currentYear = date('Y');

                // Generate records menggunakan model method
                $records = monthly_reports::generateSpecificMonthRecords(
                    $currentMonth,
                    $user->guru->id
                );

                if (count($records) > 0) {
                    monthly_reports::insert($records);

                    Notification::make()
                        ->title('Berhasil!')
                        ->body(count($records) . ' catatan berhasil di-generate.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Info')
                        ->body('Semua catatan sudah ada atau tidak ada siswa.')
                        ->info()
                        ->send();
                }
            }),
    ];
}
```

#### Model Method:

```php
// File: app/Models/monthly_reports.php
public static function generateSpecificMonthRecords($month, $guruId)
{
    // Get all classes where this guru is wali kelas
    $kelasList = data_kelas::where('walikelas_id', $guruId)->get();

    $records = [];

    foreach ($kelasList as $kelas) {
        // Get students in this class
        $siswaList = data_siswa::where('kelas', $kelas->id)->get();

        foreach ($siswaList as $siswa) {
            // Check if record already exists
            $existingRecord = self::where('data_siswa_id', $siswa->id)
                ->where('month', $month)
                ->where('year', date('Y'))
                ->first();

            if (!$existingRecord) {
                $records[] = [
                    'data_guru_id' => $guruId,
                    'data_kelas_id' => $kelas->id,
                    'data_siswa_id' => $siswa->id,
                    'month' => $month,
                    'year' => date('Y'),
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    return $records;
}
```

### 📷 Upload Foto Kegiatan

#### Form Schema:

```php
Forms\Components\FileUpload::make('photos')
    ->label('Foto Kegiatan')
    ->multiple()
    ->image()
    ->maxFiles(5)
    ->maxSize(2048)
    ->directory('monthly-reports')
    ->imageEditor()
    ->imageEditorAspectRatios([
        '16:9',
        '4:3',
        '1:1',
    ])
    ->helperText('Upload maksimal 5 foto (maks 2MB per foto)')
    ->columnSpanFull(),
```

#### Display di Table:

```php
Tables\Columns\TextColumn::make('photos')
    ->label('Foto')
    ->getStateUsing(function ($record) {
        $photos = $record->photos;

        if (is_string($photos)) {
            $photos = json_decode($photos, true);
        }

        if (!$photos || !is_array($photos) || count($photos) == 0) {
            return 'Belum ada foto';
        }

        $validPhotos = array_filter($photos, fn($photo) => !empty($photo));

        if (count($validPhotos) == 0) {
            return 'Belum ada foto';
        }

        return count($validPhotos) . ' foto';
    })
    ->badge()
    ->color(function ($record) {
        $photos = $record->photos;

        if (is_string($photos)) {
            $photos = json_decode($photos, true);
        }

        if (!$photos || !is_array($photos) || count($photos) == 0) {
            return 'gray';
        }

        $validPhotos = array_filter($photos, fn($photo) => !empty($photo));

        return count($validPhotos) > 0 ? 'success' : 'gray';
    }),
```

### ✍️ Catatan Perkembangan

#### Form Input:

```php
Forms\Components\Textarea::make('catatan')
    ->label('Catatan Perkembangan')
    ->rows(6)
    ->maxLength(1000)
    ->placeholder('Tuliskan catatan perkembangan anak dalam bulan ini...')
    ->helperText('Deskripsikan perkembangan anak dari aspek kognitif, sosial-emosional, dan fisik-motorik.')
    ->columnSpanFull(),

Forms\Components\Select::make('status')
    ->label('Status')
    ->options([
        'draft' => 'Draft (Masih bisa diedit)',
        'final' => 'Final (Tidak bisa diedit lagi)',
    ])
    ->default('draft')
    ->required()
    ->helperText('Pastikan data sudah benar sebelum mengubah ke Final'),
```

#### View di Infolist:

```php
// File: ViewMonthlyReportSiswa.php
Components\TextEntry::make('catatan')
    ->label('Catatan dari Guru')
    ->default('Belum ada catatan dari guru')
    ->prose()
    ->html()
    ->formatStateUsing(fn ($state) =>
        $state
            ? '<div style="white-space: pre-wrap; line-height: 1.8;">' . nl2br(e($state)) . '</div>'
            : '<div class="text-gray-500 italic">Belum ada catatan dari guru</div>'
    ),
```

---

## Sistem Penilaian

### 📊 Student Assessment

**Resource:** `app/Filament/Resources/StudentAssessmentResource.php`

**Model:** `app/Models/student_assessment.php`

#### Struktur Tabel:

```sql
CREATE TABLE student_assessments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    data_siswa_id BIGINT NOT NULL,
    data_guru_id BIGINT NOT NULL,
    data_kelas_id BIGINT NOT NULL,
    academic_year_id BIGINT NOT NULL,
    semester ENUM('Ganjil', 'Genap') NOT NULL,
    status ENUM('belum_dinilai', 'sebagian', 'selesai') DEFAULT 'belum_dinilai',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (data_siswa_id) REFERENCES data_siswa(id),
    FOREIGN KEY (data_guru_id) REFERENCES data_guru(id),
    FOREIGN KEY (data_kelas_id) REFERENCES data_kelas(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_year(id)
);

CREATE TABLE student_assessment_details (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_assessment_id BIGINT NOT NULL,
    assessment_variable_id BIGINT NOT NULL,
    rating VARCHAR(10),
    description TEXT,
    images JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (student_assessment_id) REFERENCES student_assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_variable_id) REFERENCES assessment_variables(id)
);
```

### 📋 Variabel Penilaian

**Resource:** `app/Filament/Resources/AssessmentVariableResource.php`

#### Contoh Data:

```php
assessment_variables:
1. Nilai Agama dan Moral
2. Fisik-Motorik (Motorik Kasar)
3. Fisik-Motorik (Motorik Halus)
4. Kognitif (Belajar dan Pemecahan Masalah)
5. Kognitif (Berfikir Logis)
6. Kognitif (Berfikir Simbolik)
7. Bahasa (Memahami Bahasa)
8. Bahasa (Mengungkapkan Bahasa)
9. Bahasa (Keaksaraan)
10. Sosial-Emosional (Kesadaran Diri)
11. Sosial-Emosional (Rasa Tanggung Jawab)
12. Sosial-Emosional (Perilaku Prososial)
13. Seni (Eksplorasi)
14. Seni (Ekspresi)
```

### 🎯 Assessment Detail dengan Foto

#### Form Schema (Relation Manager):

```php
// File: AssessmentDetailRelationManager.php
public function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Select::make('assessment_variable_id')
                ->label('Aspek Penilaian')
                ->relationship('assessmentVariable', 'name')
                ->required()
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('rating')
                ->label('Rating')
                ->options([
                    'BB' => 'BB (Belum Berkembang)',
                    'MB' => 'MB (Mulai Berkembang)',
                    'BSH' => 'BSH (Berkembang Sesuai Harapan)',
                    'BSB' => 'BSB (Berkembang Sangat Baik)',
                ])
                ->required(),

            Forms\Components\Textarea::make('description')
                ->label('Deskripsi Perkembangan')
                ->rows(4)
                ->required()
                ->maxLength(500)
                ->helperText('Jelaskan perkembangan anak pada aspek ini'),

            Forms\Components\FileUpload::make('images')
                ->label('Foto Kegiatan')
                ->multiple()
                ->image()
                ->maxFiles(3)
                ->maxSize(2048)
                ->directory('assessment-images')
                ->imageEditor()
                ->helperText('Upload maksimal 3 foto dokumentasi (maks 2MB per foto)')
                ->columnSpanFull(),
        ]);
}
```

### 🔄 Auto Update Status

#### Model Method:

```php
// File: app/Models/student_assessment.php
public function updateStatus()
{
    $totalVariables = assessment_variable::count();
    $completedDetails = $this->details()
        ->whereNotNull('rating')
        ->count();

    if ($completedDetails == 0) {
        $this->status = 'belum_dinilai';
        $this->completed_at = null;
    } elseif ($completedDetails < $totalVariables) {
        $this->status = 'sebagian';
        $this->completed_at = null;
    } else {
        $this->status = 'selesai';
        $this->completed_at = now();
    }

    $this->save();
}
```

#### Observer:

```php
// File: app/Observers/StudentAssessmentDetailObserver.php
class StudentAssessmentDetailObserver
{
    public function created(student_assessment_detail $detail)
    {
        $detail->studentAssessment->updateStatus();
    }

    public function updated(student_assessment_detail $detail)
    {
        $detail->studentAssessment->updateStatus();
    }

    public function deleted(student_assessment_detail $detail)
    {
        $detail->studentAssessment->updateStatus();
    }
}
```

---

## Cetak Rapor PDF

### 📄 PDF Generation Logic

**Controller:** `app/Http/Controllers/RaportController.php`

**Route:** `routes/web.php`

```php
Route::get('/view-raport/{siswa}', [RaportController::class, 'viewPDFInline'])
    ->name('view.raport.inline');
```

### 🔧 Implementasi Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\data_siswa;
use App\Models\sekolah;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class RaportController extends Controller
{
    /**
     * View PDF inline in browser
     */
    public function viewPDFInline($siswaId)
    {
        try {
            // 1. Validasi siswa exists
            $siswa = data_siswa::with([
                'kelasInfo',
                'kelasInfo.waliKelas',
                'growthRecords',
                'attendanceRecords'
            ])->find($siswaId);

            if (!$siswa) {
                return response("Siswa tidak ditemukan", 404);
            }

            // 2. Get sekolah data
            $sekolah = sekolah::first();

            // 3. Get kepala sekolah dari data sekolah
            $kepalaSekolah = (object)[
                'nama' => $sekolah->kepala_sekolah ?? 'Nama Kepala Sekolah',
                'nip' => $sekolah->nip_kepala_sekolah ?? '.....................'
            ];

            // 4. Get academic year (ambil yang active atau dari session)
            $academicYearId = session('selected_academic_year_id')
                ?? \App\Models\academic_year::where('is_active', true)->first()?->id;

            $academicYear = \App\Models\academic_year::find($academicYearId)
                ?? \App\Models\academic_year::where('is_active', true)->first();

            if (!$academicYear) {
                return response(
                    "Tahun ajaran tidak ditemukan. Silakan aktifkan tahun ajaran terlebih dahulu.",
                    404
                );
            }

            // 5. Get all assessment variables
            $assessmentVariables = \App\Models\assessment_variable::orderBy('name')->get();

            // 6. Get assessment data dengan filter academic_year_id
            $assessments = \App\Models\student_assessment::where('data_siswa_id', $siswaId)
                ->where('academic_year_id', $academicYear->id)
                ->with(['details.assessmentVariable', 'academicYear', 'guru'])
                ->orderBy('semester', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            // 7. Get wali kelas
            $waliKelas = $siswa->kelasInfo?->waliKelas ?? null;

            // 8. Format semester untuk display: "1 (Ganjil)" atau "2 (Genap)"
            $semester = $academicYear->semester == 'Ganjil' ? '1 (Ganjil)' : '2 (Genap)';

            // 9. Prepare data array untuk view
            $data = [
                'siswa' => $siswa,
                'sekolah' => $sekolah,
                'kelasInfo' => $siswa->kelasInfo,
                'waliKelas' => $waliKelas,
                'kepalaSekolah' => $kepalaSekolah,
                'academicYear' => $academicYear,
                'semester' => $semester,
                'assessments' => $assessments,
                'assessmentVariables' => $assessmentVariables
            ];

            // 10. Generate PDF dengan DomPDF
            $pdf = Pdf::loadView('pdf.cover-pages', $data)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                    'fontDir' => storage_path('fonts/'),
                    'fontCache' => storage_path('fonts/'),
                    'tempDir' => sys_get_temp_dir(),
                    'chroot' => realpath(base_path()),
                    'enable_font_subsetting' => false,
                    'pdf_backend' => 'CPDF',
                    'dpi' => 96,
                ]);

            $filename = 'raport-' . str_replace(' ', '-', strtolower($siswa->nama_lengkap)) . '.pdf';

            // 11. Return inline PDF response untuk browser viewing
            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', 'public, must-revalidate, max-age=0')
                ->header('Pragma', 'public')
                ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');

        } catch (\Exception $e) {
            \Log::error("PDF inline view failed: " . $e->getMessage());

            return response()->json([
                'error' => 'Gagal menampilkan PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
```

### 📝 Template PDF

**File:** `resources/views/pdf/cover-pages.blade.php`

#### Struktur Template:

```html
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <title>Raport - {{ $siswa->nama_lengkap ?? 'Siswa' }}</title>
        <style>
            @page {
                size: A4;
                margin: 33mm;
            }
            body {
                font-family: "DejaVu Sans", sans-serif;
                font-size: 12pt;
                line-height: 1.5;
            }
            /* ... styles ... */
        </style>
    </head>
    <body>
        <!-- HALAMAN 1: COVER PAGE -->
        <div class="cover">
            @if(!empty($logoSrc))
            <img class="logo" src="{{ $logoSrc }}" alt="Logo Sekolah" />
            @endif

            <div class="title">LAPORAN CAPAIAN PERKEMBANGAN ANAK DIDIK</div>

            <!-- Data Sekolah -->
            <table class="info-table">
                <tr>
                    <td>NAMA SEKOLAH</td>
                    <td>:</td>
                    <td>{{ $sekolah->nama_sekolah ?? 'TK ABA ASSALAM' }}</td>
                </tr>
                <!-- ... more school info ... -->
            </table>

            <!-- Student Name Box -->
            <div class="student-name-box">
                <div class="name">
                    {{ strtoupper($siswa->nama_lengkap ?? '-') }}
                </div>
            </div>
        </div>

        <div class="page-break"></div>

        <!-- HALAMAN 2+: ASSESSMENT DETAILS -->
        @foreach($assessmentVariables as $index => $variable)
        <table class="assessment-table">
            <tr>
                <td colspan="2">{{ $variable->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="assessment-photo-section">
                    <!-- Display photos grid -->
                </td>
                <td class="assessment-description">
                    {{ $detail->description ?? 'Belum ada penilaian' }}
                </td>
            </tr>
        </table>

        @if(($index + 1) % 2 === 0)
        <div class="page-break"></div>
        @endif @endforeach

        <!-- HALAMAN AKHIR: GROWTH, ATTENDANCE, SIGNATURES -->
        <table>
            <tr>
                <th>PERTUMBUHAN</th>
                <th>KEHADIRAN</th>
            </tr>
            <tr>
                <td>Berat Badan: {{ $latestGrowth->weight ?? '-' }} kg</td>
                <td>Sakit: {{ $totalSakit }} hari</td>
            </tr>
        </table>

        <!-- Signatures -->
        <table>
            <tr>
                <td>Kepala Sekolah<br />{{ $kepalaSekolah->nama }}</td>
                <td>Guru Kelas<br />{{ $guruName }}</td>
            </tr>
        </table>
    </body>
</html>
```

### 🎨 DomPDF Configuration

**Config File:** `config/dompdf.php`

```php
return [
    'show_warnings' => false,
    'public_path' => public_path(),
    'convert_entities' => true,
    'options' => [
        'font_dir' => storage_path('fonts/'),
        'font_cache' => storage_path('fonts/'),
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),
        'enable_font_subsetting' => false,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_font' => 'serif',
        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => true,
        'enable_remote' => true,
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],
];
```

---

## Filter Data Berdasarkan Role

### 🔐 Role-Based Filtering Implementation

#### 1️⃣ **Guru - Filter Kelas yang Diajar**

**File:** `app/Filament/Resources/MonthlyReportResource.php`

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            $user = Auth::user();

            if ($user && $user->guru) {
                // Filter hanya kelas dimana user adalah wali kelas
                return $query->whereHas('kelas', function ($kelasQuery) use ($user) {
                    $kelasQuery->where('walikelas_id', $user->guru->id);
                })
                ->with(['siswa', 'guru', 'kelas'])
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc');
            }

            // Jika bukan guru, return query kosong
            return $query->whereRaw('1 = 0');
        })
        // ... columns, actions, etc ...
}
```

**Penjelasan:**

-   `$user->guru` → Relasi User ke data_guru
-   `whereHas('kelas')` → Filter berdasarkan relasi kelas
-   `where('walikelas_id', $user->guru->id)` → Hanya kelas dimana user adalah wali kelas
-   `whereRaw('1 = 0')` → Return empty jika bukan guru

#### 2️⃣ **Guru - Filter Assessment**

**File:** `app/Filament/Resources/StudentAssessmentResource.php`

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            $user = auth()->user();

            if ($user && $user->guru) {
                // Group by academic_year_id and semester with aggregations
                $query->select([
                    'academic_year_id',
                    'semester',
                    DB::raw('COUNT(DISTINCT data_siswa_id) as total_siswa'),
                    DB::raw("SUM(CASE WHEN status = 'belum_dinilai' THEN 1 ELSE 0 END) as belum_dinilai_count"),
                    DB::raw("SUM(CASE WHEN status = 'sebagian' THEN 1 ELSE 0 END) as sebagian_count"),
                    DB::raw("SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai_count"),
                    DB::raw('MIN(id) as id')
                ])
                ->whereHas('kelas', function ($kelasQuery) use ($user) {
                    $kelasQuery->where('walikelas_id', $user->guru->id);
                })
                ->groupBy('academic_year_id', 'semester')
                ->orderBy('academic_year_id', 'desc')
                ->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')");
            }

            return $query;
        })
        // ... columns, actions, etc ...
}
```

**Penjelasan:**

-   `COUNT(DISTINCT data_siswa_id)` → Hitung siswa unik
-   `SUM(CASE WHEN ...)` → Agregasi per status
-   `groupBy('academic_year_id', 'semester')` → Group data per tahun ajaran & semester
-   `whereHas('kelas')` → Filter hanya kelas yang diajar

#### 3️⃣ **Siswa - Filter Data Diri Sendiri**

**File:** `app/Filament/Resources/MonthlyReportSiswaResource.php`

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            $user = Auth::user();

            if ($user && $user->siswa) {
                // Filter hanya catatan untuk siswa yang sedang login
                return $query->where('data_siswa_id', $user->siswa->id)
                    ->with(['siswa', 'guru', 'kelas'])
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc');
            }

            // Jika bukan siswa, return query kosong
            return $query->whereRaw('1 = 0');
        })
        // ... columns, actions, etc ...
}
```

**Penjelasan:**

-   `$user->siswa` → Relasi User ke data_siswa
-   `where('data_siswa_id', $user->siswa->id)` → Hanya data siswa sendiri
-   Siswa tidak bisa melihat data siswa lain

#### 4️⃣ **Kepala Sekolah - Akses Semua Data**

**File:** `app/Filament/Resources/ReportCardResource.php`

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            $user = auth()->user();

            if ($user && $user->guru) {
                // Check if user is kepala sekolah
                $isKepalaSekolah = \App\Models\sekolah::where(
                    'kepala_sekolah',
                    $user->guru->id
                )->exists();

                if (!$isKepalaSekolah) {
                    // If not kepala sekolah, only show classes where user is wali kelas
                    $query->where('walikelas_id', $user->guru->id);
                }
                // If kepala sekolah, no filter (show all classes)
            }

            return $query;
        })
        // ... columns, actions, etc ...
}
```

**Penjelasan:**

-   `sekolah::where('kepala_sekolah', $user->guru->id)` → Cek apakah user adalah kepala sekolah
-   Jika kepala sekolah → Tidak ada filter (lihat semua)
-   Jika bukan → Filter hanya kelas yang diajar

---

## Technical Implementation

### 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────┐
│              FRONTEND (Filament)                │
├─────────────────────────────────────────────────┤
│  Resource Classes (DataGuruResource, etc)       │
│  ├── form() → Create/Edit Forms                │
│  ├── table() → List with Columns & Actions     │
│  └── modifyQueryUsing() → Role-Based Filtering │
└─────────────────────┬───────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────┐
│              BACKEND (Laravel)                  │
├─────────────────────────────────────────────────┤
│  Models (data_guru, data_siswa, etc)           │
│  ├── Relationships (hasMany, belongsTo)        │
│  ├── Casts (photos => array)                   │
│  └── Helper Methods (generateRecords, etc)     │
│                                                 │
│  Controllers (RaportController)                │
│  └── viewPDFInline() → PDF Generation Logic    │
│                                                 │
│  Services (ReportCardService - future)         │
│  └── Complex business logic isolation          │
└─────────────────────┬───────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────┐
│              DATABASE (MySQL)                   │
├─────────────────────────────────────────────────┤
│  Tables:                                        │
│  ├── data_guru                                  │
│  ├── data_siswa                                 │
│  ├── data_kelas                                 │
│  ├── monthly_reports                            │
│  ├── student_assessments                        │
│  ├── student_assessment_details                 │
│  ├── assessment_variables                       │
│  ├── growth_records                             │
│  └── attendance_records                         │
└─────────────────────────────────────────────────┘
```

### 🔄 Data Flow: Generate PDF

```
User Click "Lihat PDF"
    ↓
Route: /view-raport/{siswa}
    ↓
RaportController::viewPDFInline($siswaId)
    ↓
1. Load Siswa with Relations
    ↓
2. Get Sekolah Data
    ↓
3. Get Active Academic Year
    ↓
4. Load Assessment Variables
    ↓
5. Load Student Assessments (filtered by academic_year_id)
    ↓
6. Prepare Data Array
    ↓
7. Pdf::loadView('pdf.cover-pages', $data)
    ↓
8. Set Options (paper, font, etc)
    ↓
9. Return Inline PDF Response
    ↓
Browser Display PDF
```

### 📦 Dependencies

**Composer:**

```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "filament/filament": "^3.0",
        "barryvdh/laravel-dompdf": "^2.0",
        "spatie/laravel-permission": "^5.0"
    }
}
```

**Installation Commands:**

```bash
# Install Filament
composer require filament/filament:"^3.0"
php artisan filament:install --panels

# Install DomPDF
composer require barryvdh/laravel-dompdf

# Install Spatie Permission
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### 🔑 Key Features Summary

| Feature           | Technology               | Status      |
| ----------------- | ------------------------ | ----------- |
| CRUD Operations   | Filament Resources       | ✅ Complete |
| Role-Based Access | Spatie Permission        | ✅ Complete |
| File Upload       | Filament FileUpload      | ✅ Complete |
| PDF Generation    | DomPDF                   | ✅ Complete |
| Bulk Operations   | Model Static Methods     | ✅ Complete |
| Inline Editing    | Filament TextInputColumn | ✅ Complete |
| Filters & Search  | Filament Table Filters   | ✅ Complete |
| Relationships     | Eloquent Relations       | ✅ Complete |

---

## Best Practices

### ✅ Do's:

1. **Selalu gunakan `modifyQueryUsing()`** untuk role-based filtering
2. **Cast JSON columns** di model (`'photos' => 'array'`)
3. **Eager load relations** dengan `with()` untuk performa
4. **Validasi input** di form schema (required, maxLength, etc)
5. **Generate bulk records** dengan model static methods
6. **Handle exceptions** di controller dengan try-catch
7. **Use transactions** untuk operasi multi-table
8. **Clear cache** setelah perubahan config

### ❌ Don'ts:

1. Jangan query data di loop (N+1 problem)
2. Jangan hardcode user ID di query
3. Jangan skip validation untuk speed
4. Jangan expose sensitive data di API/routes
5. Jangan lupa foreign key constraints
6. Jangan upload file tanpa validasi size/type

---

## Troubleshooting

### ❌ **Problem: Query terlalu lambat**

**Solution:**

```php
// Add indexes to frequently queried columns
Schema::table('monthly_reports', function (Blueprint $table) {
    $table->index(['data_siswa_id', 'month', 'year']);
    $table->index('data_guru_id');
    $table->index('data_kelas_id');
});

// Use eager loading
$reports = monthly_reports::with(['siswa', 'guru', 'kelas'])->get();
```

### ❌ **Problem: PDF generation fails**

**Solution:**

```bash
# Check DomPDF config
php artisan config:clear
php artisan cache:clear

# Check file permissions
chmod -R 755 storage/fonts
chmod -R 755 storage/app/public

# Check image paths (use absolute paths)
$logoSrc = storage_path('app/public/' . $logoPath);
```

### ❌ **Problem: Photos tidak muncul di tabel**

**Solution:**

```php
// Use getStateUsing instead of formatStateUsing
Tables\Columns\TextColumn::make('photos')
    ->getStateUsing(function ($record) {
        $photos = $record->photos; // Auto-cast by model
        return count($photos) . ' foto';
    })


```

---

## Changelog

### Version 1.0 (November 2025)

-   ✅ CRUD Data Guru, Siswa, Kelas
-   ✅ Generate Laporan Bulanan bulk
-   ✅ Upload foto kegiatan (max 5)
-   ✅ Sistem penilaian dengan 14 aspek
-   ✅ Assessment detail dengan foto
-   ✅ Auto update status penilaian
-   ✅ Generate PDF rapor dengan DomPDF
-   ✅ Role-based filtering (Guru, Siswa, Kepala Sekolah)
-   ✅ Inline PDF viewer di browser
-   ✅ Template rapor lengkap dengan tanda tangan

---

**Dokumentasi dibuat:** November 13, 2025  
**Terakhir diupdate:** November 13, 2025  
**Versi:** 1.0  
**Author:** System Documentation
