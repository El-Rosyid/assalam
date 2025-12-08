# Dokumentasi Fitur Cetak Raport

## 📋 Daftar Isi

1. [Overview Sistem](#overview-sistem)
2. [Role-Based Access](#role-based-access)
3. [Flow Cetak Raport](#flow-cetak-raport)
4. [Template PDF](#template-pdf)
5. [Struktur File](#struktur-file)
6. [Panduan Penggunaan](#panduan-penggunaan)

---

## Overview Sistem

Sistem cetak raport memiliki **3 tampilan berbeda** berdasarkan role pengguna:

### 🔑 Role-Based Views

| Role      | Resource                    | Navigation        | Deskripsi                                                 |
| --------- | --------------------------- | ----------------- | --------------------------------------------------------- |
| **Admin** | -                           | ❌ Tidak ada      | Admin tidak memiliki akses direct ke cetak raport         |
| **Guru**  | `ReportCardResource`        | ✅ "Cetak Raport" | Guru (wali kelas) dapat mencetak raport siswa di kelasnya |
| **Siswa** | `StudentReportCardResource` | ✅ "Raport Saya"  | Siswa dapat melihat raport mereka sendiri                 |

---

## Role-Based Access

### 1️⃣ **Admin** - Tidak Ada Akses Cetak Raport

Admin fokus pada manajemen sistem dan tidak memiliki menu cetak raport. Ini sesuai dengan pembagian tugas dimana:

-   Admin mengelola master data (guru, siswa, kelas, tahun ajaran)
-   Guru yang melakukan input penilaian dan cetak raport
-   Siswa yang melihat raport mereka

### 2️⃣ **Guru** - ReportCardResource

**Navigation:**

```
📁 Penilaian
  └── 📄 Cetak Raport
```

**Access Control:**

```php
// File: app/Filament/Resources/ReportCardResource.php

public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user && $user->guru; // Hanya guru yang punya akses
}
```

**Filtering Data:**

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            $user = auth()->user();
            if ($user && $user->guru) {
                // Kepala sekolah bisa lihat semua kelas
                $isKepalaSekolah = \App\Models\sekolah::where('kepala_sekolah', $user->guru->id)->exists();

                if (!$isKepalaSekolah) {
                    // Wali kelas hanya bisa lihat kelasnya sendiri
                    $query->where('walikelas_id', $user->guru->id);
                }
            }
            return $query;
        })
}
```

**Tampilan Tabel Guru:**

-   Daftar kelas yang diajar
-   Nama kelas
-   Nama wali kelas
-   Jumlah siswa per kelas

**Actions:**

-   **Detail** → Lihat daftar siswa di kelas tersebut

### 3️⃣ **Siswa** - StudentReportCardResource

**Navigation:**

```
📁 Siswa
  └── 🎓 Raport Saya
```

**Access Control:**

```php
// File: app/Filament/Resources/StudentReportCardResource.php

public static function canViewAny(): bool
{
    $user = Auth::user();
    return $user && $user->siswa; // Hanya siswa yang punya akses
}

public static function shouldRegisterNavigation(): bool
{
    $user = Auth::user();
    return $user && $user->siswa; // Menu hanya muncul untuk siswa
}
```

**Filtering Data:**

```php
->modifyQueryUsing(function (Builder $query) {
    $user = Auth::user();
    if ($user && $user->siswa) {
        // Siswa hanya bisa lihat data diri sendiri
        return $query->where('id', $user->siswa->id);
    }

    // Jika bukan siswa, return empty
    return $query->whereRaw('1 = 0');
})
```

**Tampilan Tabel Siswa:**

-   Nama siswa (diri sendiri)
-   NISN
-   Kelas
-   Wali kelas
-   Badge status penilaian (jumlah penilaian)
-   Badge status pertumbuhan (jumlah record)
-   Badge status kehadiran (jumlah record)

**Actions:**

-   **Lihat PDF** → Buka raport dalam format PDF

---

## Flow Cetak Raport

### 📊 Diagram Alur

```
┌─────────────┐
│   LOGIN     │
└──────┬──────┘
       │
   ┌───▼────────────────────────────────────┐
   │  Role Check: Guru atau Siswa?          │
   └───┬────────────────────┬───────────────┘
       │                    │
   ┌───▼─────┐         ┌────▼────────┐
   │  GURU   │         │   SISWA     │
   └───┬─────┘         └────┬────────┘
       │                    │
   ┌───▼──────────────┐ ┌───▼─────────────────┐
   │ ReportCardResource│ │StudentReportCard    │
   │ (Daftar Kelas)    │ │Resource             │
   └───┬───────────────┘ └───┬─────────────────┘
       │                     │
   ┌───▼───────────────┐     │
   │ Klik "Detail"     │     │
   └───┬───────────────┘     │
       │                     │
   ┌───▼───────────────┐ ┌───▼─────────────────┐
   │ReportCardStudents │ │ Tabel Raport Siswa  │
   │(Daftar Siswa)     │ │ (Data Diri Sendiri) │
   └───┬───────────────┘ └───┬─────────────────┘
       │                     │
       │   Klik "Lihat PDF"  │
       └─────────┬───────────┘
                 │
         ┌───────▼────────┐
         │ RaportController│
         │ viewPDFInline() │
         └───────┬─────────┘
                 │
         ┌───────▼────────┐
         │  Generate PDF   │
         │ cover-pages.blade│
         └───────┬─────────┘
                 │
         ┌───────▼────────┐
         │  View PDF in   │
         │    Browser     │
         └────────────────┘
```

### 🔄 Alur Detail

#### **Untuk Guru:**

1. Login sebagai guru
2. Menu "Penilaian" → "Cetak Raport" muncul
3. Tampil daftar kelas yang diajar (atau semua kelas jika kepala sekolah)
4. Klik tombol **"Detail"** pada kelas tertentu
5. Pindah ke halaman `ReportCardStudents` → daftar siswa di kelas tersebut
6. Klik tombol **"Lihat PDF"** pada siswa tertentu
7. PDF raport dibuka di tab baru

#### **Untuk Siswa:**

1. Login sebagai siswa
2. Menu "Siswa" → "Raport Saya" muncul
3. Tampil data diri siswa dengan badge status (penilaian, pertumbuhan, kehadiran)
4. Klik tombol **"Lihat PDF"**
5. PDF raport dibuka di tab baru

---

## Template PDF

### 📄 File Template

Ada **2 file template** untuk raport PDF:

#### 1. `cover-pages.blade.php` - Template Utama (Digunakan)

**Path:** `resources/views/pdf/cover-pages.blade.php`

**Struktur Halaman:**

```
┌─────────────────────────────────────┐
│         HALAMAN 1: COVER            │
│  ┌───────────────────────────────┐  │
│  │ Logo Sekolah                  │  │
│  │ "LAPORAN CAPAIAN PERKEMBANGAN │  │
│  │    ANAK DIDIK"                │  │
│  │                               │  │
│  │ Informasi Sekolah:            │  │
│  │ - Nama Sekolah                │  │
│  │ - NPSN                        │  │
│  │ - Alamat                      │  │
│  │ - Desa/Kelurahan              │  │
│  │ - Kecamatan                   │  │
│  │ - Kabupaten                   │  │
│  │ - Provinsi                    │  │
│  │                               │  │
│  │ ┌───────────────────────────┐ │  │
│  │ │ NAMA MURID                │ │  │
│  │ │  [Nama Siswa Uppercase]   │ │  │
│  │ └───────────────────────────┘ │  │
│  │ NOMOR INDUK / NISN: XXXXX    │  │
│  │                               │  │
│  │ TK ABA ASSALAM                │  │
│  │ BREBES                        │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│    HALAMAN 2+: PENILAIAN ASPEK      │
│  ┌───────────────────────────────┐  │
│  │ Header (fixed per halaman):   │  │
│  │ Nama: [...]    Kelas: [...]   │  │
│  │ NIS: [...]     Semester: [...] │  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ Aspek Penilaian 1             │  │
│  ├─────────────┬─────────────────┤  │
│  │ Foto        │ Deskripsi       │  │
│  │ Kegiatan    │ Perkembangan    │  │
│  │ (Grid 2x2   │ [Text]          │  │
│  │  atau 1 img)│                 │  │
│  └─────────────┴─────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ Aspek Penilaian 2             │  │
│  ├─────────────┬─────────────────┤  │
│  │ Foto        │ Deskripsi       │  │
│  │ Kegiatan    │ Perkembangan    │  │
│  └─────────────┴─────────────────┘  │
│  [Page break setiap 2 aspek]     │  │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  HALAMAN AKHIR: RINGKASAN & TTD     │
│  ┌───────────────────────────────┐  │
│  │ PERTUMBUHAN  │ KEHADIRAN      │  │
│  ├──────────────┼────────────────┤  │
│  │ BB: XX kg    │ Sakit: X hari  │  │
│  │ TB: XX cm    │ Izin: X hari   │  │
│  └──────────────┴────────────────┘  │
│                                     │
│  Brebes, [Tanggal Penerimaan]      │
│                                     │
│  Mengetahui,           Guru Kelas, │
│  Kepala Sekolah                    │
│                                     │
│  [Nama Kepsek]        [Nama Guru]  │
│  NIP/NBM: XXXXX                    │
│  ┌───────────────────────────────┐ │
│  │ REFLEKSI ORANG TUA            │ │
│  │ ............................. │ │
│  │ ............................. │ │
│  │ ............................. │ │
│  └───────────────────────────────┘ │
│              Paraf Orang Tua       │
│              ...................   │
└─────────────────────────────────────┘
```

#### 2. `raport-content.blade.php` - Template Alternatif (Tidak Digunakan)

**Path:** `resources/views/pdf/raport-content.blade.php`

Template ini lebih sederhana dengan format tabel biasa. **Tidak digunakan di sistem saat ini.**

---

## Struktur File

### 📁 File dan Direktori

```
app/
├── Filament/
│   └── Resources/
│       ├── ReportCardResource.php              # Resource untuk guru
│       │   └── Pages/
│       │       ├── ListReportCards.php         # Halaman daftar kelas
│       │       └── ReportCardStudents.php      # Halaman daftar siswa per kelas
│       │
│       └── StudentReportCardResource.php       # Resource untuk siswa
│           └── Pages/
│               └── ListStudentReportCards.php  # Halaman raport siswa
│
├── Http/
│   └── Controllers/
│       └── RaportController.php                # Controller generate PDF
│
└── Models/
    ├── data_siswa.php                          # Model siswa
    ├── data_kelas.php                          # Model kelas
    ├── student_assessment.php                  # Model penilaian
    ├── student_assessment_detail.php           # Model detail penilaian
    ├── assessment_variable.php                 # Model variabel penilaian
    ├── GrowthRecord.php                        # Model pertumbuhan
    ├── AttendanceRecord.php                    # Model kehadiran
    └── academic_year.php                       # Model tahun ajaran

resources/
└── views/
    └── pdf/
        ├── cover-pages.blade.php               # ✅ Template PDF utama (digunakan)
        └── raport-content.blade.php            # ❌ Template alternatif (tidak digunakan)

routes/
└── web.php                                     # Route untuk view PDF
```

---

## Panduan Penggunaan

### 👨‍🏫 **Untuk Guru (Wali Kelas)**

#### Langkah 1: Akses Menu Cetak Raport

1. Login dengan akun guru
2. Klik menu **"Penilaian"** di sidebar
3. Klik submenu **"Cetak Raport"**

#### Langkah 2: Pilih Kelas

-   Tampil daftar kelas yang Anda ajar
-   **Jika Anda Kepala Sekolah:** Semua kelas akan tampil
-   **Jika Anda Wali Kelas:** Hanya kelas Anda yang tampil

#### Langkah 3: Lihat Daftar Siswa

1. Klik tombol **"Detail"** pada kelas yang ingin dicetak raportnya
2. Tampil daftar siswa di kelas tersebut dengan informasi:
    - Nama lengkap
    - NIS
    - Jumlah penilaian
    - Jumlah record pertumbuhan
    - Jumlah record kehadiran

#### Langkah 4: Cetak Raport PDF

1. Klik tombol **"Lihat PDF"** di baris siswa yang ingin dicetak
2. PDF akan terbuka di tab baru browser
3. Dari browser, Anda bisa:
    - Melihat raport secara online
    - Download PDF (Ctrl+S atau icon download)
    - Print PDF (Ctrl+P)

#### Langkah 5: Kembali ke Daftar Kelas

-   Klik tombol **"Kembali ke Daftar Kelas"** di header

---

### 🎓 **Untuk Siswa**

#### Langkah 1: Akses Menu Raport

1. Login dengan akun siswa
2. Klik menu **"Siswa"** di sidebar
3. Klik submenu **"Raport Saya"**

#### Langkah 2: Lihat Status Raport

Tampil informasi:

-   Nama lengkap Anda
-   NISN
-   Kelas
-   Wali kelas
-   **Badge Status:**
    -   🟢 Hijau = Ada data
    -   🔴 Merah = Belum ada data

#### Langkah 3: Buka Raport PDF

1. Klik tombol **"Lihat PDF"**
2. PDF raport Anda akan terbuka di tab baru
3. Anda bisa download atau print raport

---

## Technical Details

### 🔧 Controller: RaportController

**File:** `app/Http/Controllers/RaportController.php`

**Method:** `viewPDFInline($siswaId)`

**Flow Proses:**

```php
1. Validasi siswa exists
   ↓
2. Load relasi: kelasInfo, waliKelas, growthRecords, attendanceRecords
   ↓
3. Get data sekolah (logo, alamat, kepala sekolah)
   ↓
4. Get tahun ajaran aktif (dari session atau is_active=true)
   ↓
5. Get semua assessment variables (aspek penilaian)
   ↓
6. Get assessment data siswa untuk tahun ajaran aktif
   ↓
7. Format semester: "1 (Ganjil)" atau "2 (Genap)"
   ↓
8. Prepare data array untuk view
   ↓
9. Generate PDF dengan DomPDF
   ↓
10. Return PDF inline (buka di browser)
```

**Route:**

```php
Route::get('/view-raport/{siswa}', [RaportController::class, 'viewPDFInline'])
    ->name('view.raport.inline');
```

**DomPDF Config:**

```php
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
```

---

### 📝 Data yang Ditampilkan di PDF

#### **Cover Page (Halaman 1):**

-   Logo sekolah (jika ada)
-   Judul: "LAPORAN CAPAIAN PERKEMBANGAN ANAK DIDIK"
-   Data sekolah:
    -   Nama sekolah
    -   NPSN
    -   Alamat lengkap (desa, kecamatan, kabupaten, provinsi)
-   Nama siswa (uppercase, dalam box)
-   NIS/NISN siswa

#### **Halaman Penilaian (Halaman 2+):**

-   **Header (setiap halaman):**

    -   Nama siswa
    -   NIS
    -   Kelas
    -   Semester (format: "1 (Ganjil)" atau "2 (Genap)")

-   **Setiap Aspek Penilaian:**
    -   Nama aspek penilaian
    -   Foto kegiatan (grid 2x2 untuk multiple, atau 1 foto besar)
    -   Deskripsi perkembangan
    -   **Page break setiap 2 aspek** (agar tidak terpotong)

#### **Halaman Ringkasan (Halaman Akhir):**

-   **Pertumbuhan:**

    -   Berat badan terakhir (kg)
    -   Tinggi badan terakhir (cm)

-   **Kehadiran:**

    -   Jumlah hari sakit
    -   Jumlah hari izin

-   **Tanda Tangan:**

    -   Kepala Sekolah (nama + NIP/NBM)
    -   Guru Kelas (nama)
    -   Tanggal penerimaan raport

-   **Refleksi Orang Tua:**
    -   Kotak kosong untuk diisi manual
    -   Tempat paraf orang tua

---

### 🎨 Styling PDF

**Format:** A4 Portrait

**Margin:** 33mm semua sisi

**Font:** DejaVu Sans (mendukung karakter Indonesia)

**Warna:**

-   Background abu-abu muda (#f9f9f9) untuk header tabel
-   Border hitam (2px solid) untuk kotak penting
-   Border dashed (#ccc) untuk area "belum ada data"

**Layout:**

-   Cover page: Center alignment
-   Header: Fixed di setiap halaman (kiri-kanan)
-   Tabel penilaian: 25% foto, 75% deskripsi
-   Footer: 2 kolom (kepala sekolah & guru)

---

### 🔐 Authorization

#### **ReportCardResource (Guru):**

```php
canViewAny() {
    return auth()->user()->guru !== null;
}

canCreate() = false
canEdit() = false
canDelete() = false
canView() = false
```

#### **StudentReportCardResource (Siswa):**

```php
canViewAny() {
    return auth()->user()->siswa !== null;
}

canView($record) {
    return auth()->user()->siswa->id === $record->id;
}

canCreate() = false
canEdit() = false
canDelete() = false
```

#### **ReportCardStudents Page (Detail Siswa):**

```php
mount() {
    $isKepalaSekolah = Sekolah::where('kepala_sekolah', $user->guru->id)->exists();

    if (!$isKepalaSekolah && $record->walikelas_id !== $user->guru->id) {
        abort(403); // Forbidden jika bukan kepala sekolah dan bukan wali kelas
    }
}
```

---

### 📊 Query Optimization

#### **Count Penilaian:**

```php
Tables\Columns\TextColumn::make('assessments_count')
    ->getStateUsing(function (data_siswa $record) {
        $count = student_assessment::where('data_siswa_id', $record->id)->count();
        return $count . ' penilaian';
    })
```

#### **Count Pertumbuhan:**

```php
Tables\Columns\TextColumn::make('growth_records_count')
    ->getStateUsing(function (data_siswa $record) {
        $count = GrowthRecord::where('data_siswa_id', $record->id)->count();
        return $count . ' record';
    })
```

#### **Display Kehadiran:**

```php
Tables\Columns\TextColumn::make('attendance_records_count')
    ->getStateUsing(function (data_siswa $record) {
        $attendance = AttendanceRecord::where('data_siswa_id', $record->id)->first();
        if ($attendance) {
            $total = ($attendance->alfa ?? 0) + ($attendance->ijin ?? 0) + ($attendance->sakit ?? 0);
            return $total > 0 ? $total . ' absen' : 'Hadir';
        }
        return 'Belum ada data';
    })
```

---

## Troubleshooting

### ❌ **Problem: PDF tidak muncul**

**Solusi:**

1. Pastikan tahun ajaran sudah diaktifkan
2. Cek data siswa sudah punya penilaian
3. Cek logo sekolah path-nya valid
4. Clear cache Laravel: `php artisan cache:clear`

### ❌ **Problem: Foto tidak muncul di PDF**

**Solusi:**

1. Pastikan foto tersimpan di `storage/app/public/`
2. Jalankan: `php artisan storage:link`
3. Cek path foto di database benar
4. DomPDF memerlukan path absolut: `storage_path('app/public/' . $photo)`

### ❌ **Problem: Guru tidak bisa lihat kelas**

**Solusi:**

1. Pastikan user sudah di-link ke `data_guru` (relasi `User->guru`)
2. Cek `data_kelas.walikelas_id` sudah diisi dengan `data_guru.id`
3. Atau set guru sebagai kepala sekolah di tabel `sekolah`

### ❌ **Problem: Siswa tidak bisa lihat raport**

**Solusi:**

1. Pastikan user sudah di-link ke `data_siswa` (relasi `User->siswa`)
2. Pastikan siswa sudah punya kelas (`data_siswa.kelas` not null)
3. Pastikan ada penilaian untuk siswa tersebut

### ❌ **Problem: Semester tidak sesuai**

**Solusi:**

1. Cek tahun ajaran aktif: `academic_year.is_active = true`
2. Pastikan `academic_year.semester` = 'Ganjil' atau 'Genap'
3. Format otomatis di controller: "1 (Ganjil)" atau "2 (Genap)"

---

## Future Enhancements

### 🚀 **Fitur yang Bisa Ditambahkan:**

1. **Bulk Download PDF**

    - Download raport semua siswa di 1 kelas sekaligus
    - Generate ZIP file berisi semua PDF

2. **Email Raport**

    - Kirim raport langsung ke email orang tua
    - Notifikasi otomatis saat raport sudah siap

3. **Preview Before Print**

    - Preview raport sebelum generate PDF final
    - Edit minor detail langsung dari preview

4. **Raport Comparison**

    - Bandingkan raport semester 1 vs semester 2
    - Grafik perkembangan siswa

5. **Digital Signature**

    - Tanda tangan digital kepala sekolah & guru
    - QR code untuk validasi keaslian raport

6. **Template Customization**

    - Admin bisa edit template raport
    - Multiple template design options

7. **Raport History**
    - Archive raport per semester
    - Download raport semester lalu

---

## Best Practices

### ✅ **Do's:**

-   Selalu aktifkan tahun ajaran sebelum cetak raport
-   Pastikan semua penilaian sudah complete (status = 'selesai')
-   Upload foto dengan resolusi cukup (min 800x600px)
-   Isi data pertumbuhan dan kehadiran secara rutin
-   Test print 1 raport dulu sebelum print massal

### ❌ **Don'ts:**

-   Jangan hapus tahun ajaran yang sudah ada raport
-   Jangan edit data siswa saat PDF sedang di-generate
-   Jangan upload foto terlalu besar (max 2MB per foto)
-   Jangan cetak raport sebelum semua data lengkap

---

## Changelog

### Version 1.0 (November 2025)

-   ✅ Initial release
-   ✅ Role-based access (Guru & Siswa)
-   ✅ PDF generation with DomPDF
-   ✅ Cover page dengan logo sekolah
-   ✅ Multiple assessment dengan foto grid
-   ✅ Pertumbuhan dan kehadiran otomatis
-   ✅ Tanda tangan kepala sekolah & guru
-   ✅ Format semester: "1 (Ganjil)" / "2 (Genap)"
-   ✅ Responsive inline PDF viewer

---

## Contact & Support

Untuk pertanyaan atau issue terkait fitur cetak raport, silakan:

1. Baca dokumentasi ini terlebih dahulu
2. Cek troubleshooting section
3. Hubungi admin sistem jika masih ada kendala

---

**Dokumentasi dibuat:** November 12, 2025  
**Terakhir diupdate:** November 12, 2025  
**Versi:** 1.0
