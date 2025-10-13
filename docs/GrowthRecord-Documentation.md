# Dokumentasi Sistem Growth Record

## Overview

Sistem Growth Record adalah fitur baru untuk mencatat dan memantau pertumbuhan fisik siswa secara bulanan. Sistem ini memungkinkan wali kelas untuk mengukur dan mencatat berbagai parameter pertumbuhan siswa.

## Fitur Utama

### 1. Parameter Pengukuran

-   **Lingkar Kepala** (cm)
-   **Lingkar Lengan** (cm)
-   **Berat Badan** (kg)
-   **Tinggi Badan** (cm)
-   **BMI** (otomatis dihitung)

### 2. Akses & Autorisasi

-   Hanya **Wali Kelas** yang dapat mengakses data siswa di kelasnya
-   Guru lain tidak dapat melihat data kelas lain
-   Auto-fill data guru dan tahun akademik saat input

### 3. Interface & Navigasi

-   **Inline Editing**: Edit langsung di tabel tanpa modal
-   **Bulk Generation**: Generate record untuk semua siswa sekaligus
-   **Filter Month/Year**: Filter berdasarkan bulan dan tahun
-   **Search**: Pencarian berdasarkan nama siswa

### 4. Validasi Data

-   Unique constraint: 1 record per siswa per bulan per tahun
-   Validasi range nilai (tidak boleh negatif)
-   Required fields untuk semua parameter

## Struktur Database

### Tabel: growth_records

```sql
- id (primary key)
- data_siswa_id (foreign key)
- data_guru_id (foreign key)
- data_kelas_id (foreign key)
- academic_year_id (foreign key)
- month (1-12)
- year (YYYY)
- lingkar_kepala (decimal)
- lingkar_lengan (decimal)
- berat_badan (decimal)
- tinggi_badan (decimal)
- created_at, updated_at
```

### Unique Constraint

Kombinasi (data_siswa_id, month, year) harus unik.

## Cara Penggunaan

### 1. Akses Menu

-   Login sebagai Wali Kelas
-   Pilih menu "Growth Records" di sidebar

### 2. Generate Bulk Records

-   Klik tombol "Generate Monthly Records"
-   Pilih bulan dan tahun
-   Sistem akan create record kosong untuk semua siswa di kelas

### 3. Input Data

-   Klik langsung pada cell di tabel untuk edit
-   Masukkan nilai pengukuran
-   Tekan Enter atau klik di luar cell untuk save
-   BMI akan otomatis dihitung

### 4. Filter & Search

-   Gunakan filter Month/Year di header tabel
-   Search box untuk cari siswa tertentu
-   Reset filter untuk lihat semua data

## Model Relationships

### GrowthRecord Model

```php
- belongsTo(DataSiswa::class, 'data_siswa_id')
- belongsTo(DataGuru::class, 'data_guru_id')
- belongsTo(DataKelas::class, 'data_kelas_id')
- belongsTo(AcademicYear::class, 'academic_year_id')
```

### Accessors

-   `getBmiAttribute()`: Otomatis hitung BMI dari berat/tinggi

### Scopes

-   `forWaliKelas($guruId)`: Filter hanya kelas wali
-   `forMonth($month, $year)`: Filter bulan/tahun tertentu

## File yang Terlibat

1. **Migration**: `database/migrations/2025_10_12_135332_create_growth_records_table.php`
2. **Model**: `app/Models/GrowthRecord.php`
3. **Resource**: `app/Filament/Resources/GrowthRecordResource.php`
4. **Pages**:
    - `app/Filament/Resources/GrowthRecordResource/Pages/ListGrowthRecords.php`
    - `app/Filament/Resources/GrowthRecordResource/Pages/CreateGrowthRecord.php`

## Catatan Penting

-   Sistem menggunakan Filament v3 untuk admin interface
-   Inline editing menggunakan TextInputColumn
-   Bulk generation menggunakan batch insert untuk performa
-   Data terintegrasi dengan sistem akademik yang sudah ada

---

_Dokumentasi dibuat pada tanggal pembuatan sistem Growth Record_
