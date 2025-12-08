# Student Restore & Storage Cleanup Guide

## Overview

Sistem menggunakan **Soft Delete** untuk data siswa. Data yang dihapus tidak langsung hilang, tapi dipindahkan ke "Recycle Bin" dan bisa dipulihkan dalam **90 hari**.

## Cara Restore Data Siswa

### 1. Via Admin Panel (Filament)

**Step 1: Aktifkan Filter Trashed**

1. Buka menu **Siswa → Data Siswa**
2. Klik dropdown filter **Trashed** di atas tabel
3. Pilih **Only Trashed** untuk melihat siswa yang dihapus

**Step 2: Restore Data**
Ada 2 cara:

-   **Individual:** Klik icon **🔄 Pulihkan** di baris siswa yang ingin dipulihkan
-   **Bulk Restore:** Centang beberapa siswa → Pilih **Restore** dari dropdown actions

**Step 3: Verifikasi**

-   Filter kembali ke **Without Trashed** (default)
-   Data siswa seharusnya muncul kembali dengan semua file utuh

### 2. Via Tinker (Manual)

```bash
# Lihat siswa yang dihapus
php artisan tinker
>>> App\Models\data_siswa::onlyTrashed()->get(['nis', 'nama_lengkap', 'deleted_at']);

# Restore siswa spesifik by NIS
>>> App\Models\data_siswa::onlyTrashed()->where('nis', '210')->restore();

# Restore semua siswa yang dihapus
>>> App\Models\data_siswa::onlyTrashed()->restore();
```

### 3. Via Database Query (Emergency)

```sql
-- Lihat siswa yang dihapus
SELECT nis, nama_lengkap, deleted_at
FROM data_siswa
WHERE deleted_at IS NOT NULL;

-- Restore siswa spesifik
UPDATE data_siswa
SET deleted_at = NULL
WHERE nis = '210';

-- Restore semua siswa
UPDATE data_siswa
SET deleted_at = NULL
WHERE deleted_at IS NOT NULL;
```

## Status Data Siswa

### Statistik

```bash
php artisan tinker
>>> echo "Aktif: " . App\Models\data_siswa::count();
>>> echo "Di Recycle Bin: " . App\Models\data_siswa::onlyTrashed()->count();
>>> echo "Total All: " . App\Models\data_siswa::withTrashed()->count();
```

**Current Status (5 Des 2025):**

-   Siswa aktif: **2**
-   Di recycle bin: **5**
-   Total: **7**

### Lifecycle Data Siswa

```
┌─────────────┐
│ Data Aktif  │ ← Normal state, semua fitur tersedia
└──────┬──────┘
       │ [Hapus] (Soft Delete)
       ↓
┌─────────────┐
│ Recycle Bin │ ← Data disembunyikan, file TETAP ADA
└──────┬──────┘
       │ [Pulihkan]         [Hapus Permanent]
       ↓                              ↓
┌─────────────┐              ┌────────────────┐
│ Data Aktif  │              │ PERMANENT      │
│ (Restored)  │              │ File TERHAPUS  │
└─────────────┘              └────────────────┘
```

## Penjelasan: Kenapa File Masih Ada?

### Soft Delete vs Force Delete

**1. Soft Delete (Default)** - Tombol **[Hapus]** biasa

-   Data siswa **hanya ditandai** dengan timestamp `deleted_at`
-   **File TIDAK dihapus** (foto, akta, KK, ijazah tetap ada)
-   **Related data TIDAK dihapus** (assessments, growth records, attendance)
-   Tujuan: Bisa dipulihkan jika ada kesalahan atau perubahan keputusan
-   Periode grace: **90 hari** (configurable)

**2. Force Delete (Permanent)** - Tombol **[Hapus Permanent]**

-   Data siswa **dihapus dari database**
-   **File OTOMATIS TERHAPUS** via model event `deleting()`
-   **Related data tetap ada** (for audit trail)
-   Tidak bisa dipulihkan
-   Memerlukan konfirmasi ekstra

### Implementasi di Model

```php
// app/Models/data_siswa.php

protected static function boot()
{
    parent::boot();

    static::deleting(function ($siswa) {
        // Cek apakah force delete atau soft delete
        if ($siswa->isForceDeleting()) {
            // PERMANENT DELETE - Cleanup everything
            $siswa->cleanupFiles();        // Hapus semua file
            $siswa->cleanupRelatedData();  // Handle related data
        }
        // Soft delete - TIDAK cleanup apapun
    });
}

protected function cleanupFiles(): void
{
    $fileColumns = ['foto_siswa', 'dokumen_akta', 'dokumen_kk', 'dokumen_ijazah'];

    foreach ($fileColumns as $column) {
        if (!empty($this->$column)) {
            $filePath = str_replace(['storage/', '/storage/'], '', $this->$column);

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                Log::info("Deleted file: {$filePath} for student NIS: {$this->nis}");
            }
        }
    }
}
```

### Kenapa File Tidak Dihapus Saat Soft Delete?

**Alasan 1: Data Recovery**

-   Admin bisa restore siswa kapan saja dalam 90 hari
-   Jika file sudah terhapus, restore jadi tidak lengkap
-   Orang tua akan komplain jika foto/dokumen anak hilang

**Alasan 2: Storage Cost vs Risk**

-   File foto rata-rata 200KB-500KB per siswa
-   Total: ~2MB per siswa (foto + 3 dokumen)
-   5 siswa di recycle bin = ~10MB (masih sangat kecil)
-   Cost storage sangat murah vs risk kehilangan data

**Alasan 3: Audit Trail**

-   Kadang butuh cek data historis siswa yang sudah pindah/lulus
-   File dokumen penting untuk verifikasi alumni

## Storage Cleanup: Hapus File Orphan

### Apa itu Orphan Files?

File di storage yang **tidak ada pemiliknya** (siswa sudah di-force delete atau data corrupt).

### Command: Storage Cleanup

**1. Dry Run (Preview Mode)**

```bash
php artisan storage:cleanup-orphan-files --dry-run
```

Output:

```
🔍 DRY RUN MODE - No files will be deleted
Starting orphan file cleanup...
Found 7 files in use by 7 students
Found 12 orphan files (2.4 MB)

┌───┬────────────────────────────────┬────────┐
│ # │ File Path                      │ Size   │
├───┼────────────────────────────────┼────────┤
│ 1 │ siswa/foto/old_photo_123.jpg   │ 324 KB │
│ 2 │ siswa/akta/deleted_siswa.pdf   │ 1.2 MB │
└───┴────────────────────────────────┴────────┘

🔍 DRY RUN: These files would be deleted in actual run.
```

**2. Actual Cleanup (Permanent Delete)**

```bash
php artisan storage:cleanup-orphan-files
```

Output:

```
⚠️  CLEANUP MODE - Files will be permanently deleted!
Are you sure you want to continue? (yes/no) [no]:
> yes

Starting orphan file cleanup...
Found 7 files in use by 7 students
Found 12 orphan files (2.4 MB)

 12/12 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

✅ Cleanup completed!
   Deleted: 12 files
   Freed space: 2.4 MB
```

### Kapan Harus Cleanup?

**Jalankan Cleanup Jika:**

1. Setelah force delete banyak siswa
2. Setelah cleanup recycle bin (siswa di-hard delete)
3. Setelah migrasi/import data yang gagal
4. Storage usage terlalu tinggi

**Best Practice:**

-   Jalankan `--dry-run` dulu untuk preview
-   Lakukan cleanup setelah **backup database**
-   Jalankan setiap **3-6 bulan** atau setelah end semester
-   Dokumentasikan file yang dihapus (sudah auto-log ke `storage/logs/laravel.log`)

### Scheduled Cleanup (Optional)

Tambahkan ke `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Auto cleanup orphan files setiap 3 bulan (first day)
    $schedule->command('storage:cleanup-orphan-files')
        ->quarterly()
        ->at('02:00')
        ->emailOutputOnFailure('admin@abaassalam.my.id');
}
```

## Best Practices

### 1. Soft Delete Management

**Retention Policy:**

-   Siswa aktif: Unlimited
-   Recycle bin: **90 hari** (otomatis di-force delete setelahnya)
-   Force delete: Manual only (dengan konfirmasi)

**Auto-Cleanup Task (Recommended):**

```php
// app/Console/Kernel.php
$schedule->command('model:prune')
    ->daily()
    ->at('03:00'); // Hapus permanent siswa yang sudah >90 hari di recycle bin
```

### 2. Storage Management

**Directory Structure:**

```
storage/app/public/
├── siswa/
│   ├── foto/          # Max 2MB per file
│   ├── akta/          # Max 5MB per file
│   ├── kk/            # Max 5MB per file
│   └── ijazah/        # Max 5MB per file
├── custom-broadcasts/
│   ├── images/        # Broadcast images
│   └── documents/     # Broadcast PDFs/docs
└── monthly-reports/   # Auto-deleted after 7 days
```

**File Size Limits:**

-   Foto siswa: 2MB (JPEG/PNG)
-   Dokumen: 5MB (PDF/DOC/DOCX)
-   Total per siswa: ~12MB max
-   Broadcast: 5MB per file

### 3. Backup Strategy

**Database Backup:**

```bash
# Full backup dengan soft-deleted data
php artisan db:backup --include-soft-deletes

# Backup data aktif saja
php artisan db:backup
```

**Storage Backup:**

```bash
# Sync storage ke backup server
rsync -avz storage/app/public/ backup-server:/backups/storage/
```

## Troubleshooting

### Issue: Tidak Bisa Restore Siswa

**Symptom:**

```
Error: Unable to restore student NIS 210
```

**Solution:**

```bash
# Cek apakah data masih ada
php artisan tinker
>>> App\Models\data_siswa::withTrashed()->where('nis', '210')->first();

# Jika null, berarti sudah di-force delete (permanent)
# Restore dari database backup

# Jika data ada, cek constraint
>>> App\Models\data_siswa::onlyTrashed()->where('nis', '210')->restore();
```

### Issue: File Tidak Muncul Setelah Restore

**Symptom:**
Siswa berhasil di-restore tapi foto/dokumen tidak muncul.

**Solution:**

```bash
# Cek path file di database
php artisan tinker
>>> $siswa = App\Models\data_siswa::where('nis', '210')->first();
>>> echo $siswa->foto_siswa;

# Cek apakah file ada di storage
>>> Storage::disk('public')->exists(str_replace('storage/', '', $siswa->foto_siswa));

# Jika false, restore file dari backup
cp backup/siswa/foto/210.jpg storage/app/public/siswa/foto/
php artisan storage:link
```

### Issue: Storage Penuh

**Symptom:**

```
Error: disk quota exceeded
```

**Solution:**

```bash
# 1. Cek usage
du -sh storage/app/public/*

# 2. Cleanup orphan files
php artisan storage:cleanup-orphan-files --dry-run
php artisan storage:cleanup-orphan-files

# 3. Force delete siswa lama di recycle bin (>90 hari)
php artisan model:prune

# 4. Cleanup old logs
php artisan log:clear --keep-last=30

# 5. Compress old files (optional)
find storage/app/public/siswa/foto -name "*.jpg" -mtime +365 -exec mogrify -quality 70 {} \;
```

## Security Considerations

### Access Control

**Restore Permission:**

-   ✅ Super Admin: Full access (restore, force delete)
-   ✅ Admin: Soft delete & restore only
-   ❌ Guru: View only (no delete/restore)
-   ❌ Wali Murid: No access

**File Access:**

-   Soft deleted: File tetap accessible via direct URL (by design)
-   Force deleted: File otomatis terhapus
-   Backup: Simpan di server terpisah dengan encryption

### Audit Trail

**Logging Events:**

```php
// Automatic logging di app/Models/data_siswa.php

// Soft delete
Log::info("Student soft deleted", ['nis' => $siswa->nis, 'nama' => $siswa->nama_lengkap]);

// Restore
Log::info("Student restored", ['nis' => $siswa->nis, 'nama' => $siswa->nama_lengkap]);

// Force delete
Log::warning("Student permanently deleted with related data", [
    'nis' => $siswa->nis,
    'related_data' => $relatedCount
]);

// File cleanup
Log::info("Deleted file: {$filePath} for student NIS: {$this->nis}");
```

**Check Logs:**

```bash
tail -f storage/logs/laravel.log | grep "Student"
```

## Summary

### Restore Data Siswa

1. **Admin Panel:** Filter → Only Trashed → Klik Restore
2. **Tinker:** `data_siswa::onlyTrashed()->where('nis', 'XXX')->restore()`
3. **SQL:** `UPDATE data_siswa SET deleted_at = NULL WHERE nis = 'XXX'`

### File Storage

-   **Soft Delete:** File TIDAK terhapus (by design)
-   **Force Delete:** File OTOMATIS terhapus
-   **Orphan Cleanup:** `php artisan storage:cleanup-orphan-files`

### Best Practice

-   Backup sebelum force delete
-   Jalankan cleanup setiap 3-6 bulan
-   Monitor storage usage
-   Dokumentasikan setiap force delete

---

**Last Updated:** 5 Desember 2025  
**Author:** AI Assistant  
**Version:** 1.0
