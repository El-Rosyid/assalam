# Data Deletion & Image Cleanup - Complete Guide

## 📋 Overview

Dokumentasi ini menjelaskan bagaimana sistem menangani penghapusan data dan cleanup file/gambar untuk berbagai skenario.

---

## 🎯 Skenario Penghapusan Data

### **Skenario 1: Siswa Dihapus**

#### **A. Soft Delete (Hapus Biasa)**

**Apa yang Terjadi:**

```
✅ Data siswa di-mark sebagai "deleted" (deleted_at terisi)
✅ Semua data terkait TETAP ADA:
   - Student Assessments (nilai + gambar)
   - Growth Records
   - Attendance Records
✅ Gambar di storage TETAP ADA
✅ Bisa dipulihkan (restore) kapan saja dalam 90 hari
```

**Database Query:**

```sql
-- Soft delete siswa
UPDATE data_siswa
SET deleted_at = NOW(), deleted_by = 1
WHERE nis = '12345';

-- Data terkait TIDAK berubah
SELECT * FROM student_assessments WHERE siswa_nis = '12345'; -- ✅ Masih ada
SELECT * FROM growth_records WHERE siswa_nis = '12345'; -- ✅ Masih ada
```

**Filament UI:**

```
Actions → Hapus
  ↓
Konfirmasi: "Data akan dipindahkan ke Recycle Bin"
  ↓
✅ Siswa hilang dari list utama
✅ Muncul di filter "Hanya Dihapus"
✅ Bisa di-restore
```

---

#### **B. Force Delete (Hapus Permanent)**

**Apa yang Terjadi:**

```
❌ Data siswa DIHAPUS PERMANENT dari database
❌ Semua foto siswa TERHAPUS dari storage:
   - foto_siswa
   - dokumen_akta
   - dokumen_kk
   - dokumen_ijazah

❌ CASCADE DELETE - Semua data terkait DIHAPUS:

   1. Student Assessments:
      ❌ Header assessment dihapus
      ❌ Detail assessment dihapus
      ❌ SEMUA GAMBAR di assessment dihapus

   2. Growth Records:
      ❌ Semua record pertumbuhan dihapus

   3. Attendance Records:
      ❌ Semua record kehadiran dihapus

❌ TIDAK BISA dipulihkan!
```

**Code Flow:**

```php
// 1. User klik "Hapus Permanent" di Filament
$siswa->forceDelete();

// 2. Model boot event triggered
data_siswa::forceDeleting(function($siswa) {

    // 3. Cleanup files
    $siswa->cleanupFiles();
    // → Delete foto_siswa, dokumen_akta, dll

    // 4. Cleanup related data
    $siswa->cleanupRelatedData();

    // 4a. Delete assessments with images
    foreach ($siswa->studentAssessments as $assessment) {
        $assessment->forceDelete();

        // Triggers student_assessment::forceDeleting
        foreach ($assessment->details as $detail) {
            // Delete images array
            foreach ($detail->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $assessment->details()->forceDelete();
    }

    // 4b. Delete growth records
    $siswa->growthRecords()->forceDelete();

    // 4c. Delete attendance
    $siswa->attendanceRecords()->forceDelete();
});

// 5. Finally delete siswa from database
```

**Log Output:**

```log
[2024-12-01 10:00:00] info: Deleted file: siswa/foto/12345.jpg
[2024-12-01 10:00:01] info: Deleted assessment image: assessments/12345_01.jpg
[2024-12-01 10:00:01] info: Deleted assessment image: assessments/12345_02.jpg
[2024-12-01 10:00:02] info: StudentAssessmentDetail force deleted {detail_id: 100}
[2024-12-01 10:00:02] info: StudentAssessment force deleted {penilaian_id: 50, siswa_nis: 12345}
[2024-12-01 10:00:03] info: GrowthRecord force deleted {id: 200, siswa_nis: 12345}
[2024-12-01 10:00:04] warning: Student being permanently deleted with related data
[2024-12-01 10:00:05] info: Related data cleaned up for student: 12345
```

---

### **Skenario 2: Assessment Dihapus (Tanpa Hapus Siswa)**

#### **A. Soft Delete Assessment**

**Apa yang Terjadi:**

```
✅ Assessment di-mark sebagai deleted
✅ Detail assessment (nilai) TETAP ADA
✅ Gambar di storage TETAP ADA
✅ Siswa TETAP AKTIF
✅ Bisa dipulihkan
```

**Use Case:**

```
Guru salah input assessment semester 1, ingin hapus dan buat ulang.
→ Soft delete dulu
→ Kalau perlu, bisa restore nanti
→ Atau biarkan auto-cleanup setelah 90 hari
```

---

#### **B. Force Delete Assessment**

**Apa yang Terjadi:**

```
❌ Assessment header dihapus permanent
❌ Semua detail assessment dihapus
❌ SEMUA GAMBAR dokumentasi TERHAPUS dari storage
✅ Siswa TETAP AKTIF (tidak terhapus)
```

**Code Flow:**

```php
// User force delete assessment
$assessment->forceDelete();

// Triggers boot event
student_assessment::forceDeleting(function($assessment) {
    // Loop all details
    foreach ($assessment->details as $detail) {
        // Delete images
        if ($detail->images) {
            foreach ($detail->images as $image) {
                Storage::disk('public')->delete($image);
                // File: storage/app/public/assessments/photo_123.jpg → DELETED
            }
        }
    }

    // Delete all details
    $assessment->details()->forceDelete();
});
```

**Contoh:**

```
Assessment ID: 50
├── Detail 1: Rating + 3 gambar
│   ├── assessments/siswa_12345_var1_01.jpg → ❌ DELETED
│   ├── assessments/siswa_12345_var1_02.jpg → ❌ DELETED
│   └── assessments/siswa_12345_var1_03.jpg → ❌ DELETED
│
├── Detail 2: Rating + 2 gambar
│   ├── assessments/siswa_12345_var2_01.jpg → ❌ DELETED
│   └── assessments/siswa_12345_var2_02.jpg → ❌ DELETED
│
└── TOTAL: 5 gambar terhapus permanent
```

---

### **Skenario 3: Growth Record Dihapus**

#### **A. Hapus Record 1 Bulan (Generate Bulan)**

**Apa yang Terjadi:**

```
❌ Semua growth records untuk bulan tersebut & kelas tersebut DIHAPUS
✅ Record bulan lain TETAP ADA
✅ Siswa TETAP AKTIF
```

**Contoh:**

```
Wali Kelas generate ulang bulan Januari karena ada kesalahan:

1. Hapus generate bulan Januari
   → DELETE FROM growth_records
     WHERE data_kelas_id = 1
     AND month = 1
     AND year = 2024;

   Result:
   ❌ Record Januari untuk semua siswa di kelas 1 terhapus
   ✅ Record Februari, Maret, dst tetap ada

2. Generate ulang
   → Buat record kosong baru untuk bulan Januari
```

**⚠️ Note:**

-   Growth Record TIDAK ada gambar/file upload
-   Hanya data numerik (berat, tinggi, lingkar kepala/lengan)
-   Jadi tidak ada file cleanup

---

#### **B. Soft Delete Growth Record Individual**

```
✅ Record specific siswa + bulan di-mark deleted
✅ Bisa dipulihkan
✅ Auto cleanup setelah 90 hari
```

---

### **Skenario 4: Monthly Report Dihapus**

**Monthly Report biasanya adalah generated report (PDF), bukan data di database.**

Jika Anda punya model `MonthlyReport`:

```php
// Add SoftDeletes
use SoftDeletes;

protected static function boot() {
    parent::boot();

    static::forceDeleting(function($report) {
        // Delete PDF file
        if ($report->pdf_path) {
            Storage::disk('public')->delete($report->pdf_path);
        }
    });
}
```

---

## 🔧 Konfigurasi & Customization

### **1. Enable/Disable CASCADE Delete**

Di `app/Models/data_siswa.php`:

```php
protected function cleanupRelatedData(): void
{
    // OPTION A: CASCADE DELETE (default - enabled)
    foreach ($this->studentAssessments as $assessment) {
        $assessment->forceDelete(); // Will delete images
    }
    $this->growthRecords()->forceDelete();
    $this->attendanceRecords()->forceDelete();

    // OPTION B: KEEP DATA (comment out lines above)
    // Just log warning, don't delete
    Log::warning("Student deleted with existing data", [...]);
}
```

---

### **2. Tambah File Columns untuk Cleanup**

Di `app/Models/data_siswa.php`:

```php
protected function cleanupFiles(): void
{
    $fileColumns = [
        'foto_siswa',
        'dokumen_akta',
        'dokumen_kk',
        'dokumen_ijazah',
        'dokumen_rapor',         // ← Add new columns here
        'dokumen_kesehatan',     // ← Add new columns here
        // ... more
    ];

    foreach ($fileColumns as $column) {
        if ($this->$column) {
            Storage::disk('public')->delete($this->$column);
        }
    }
}
```

---

### **3. Test File Cleanup**

```bash
php artisan tinker
```

```php
// Test single file delete
Storage::disk('public')->exists('siswa/foto/12345.jpg');
Storage::disk('public')->delete('siswa/foto/12345.jpg');

// Test assessment image cleanup
$assessment = App\Models\student_assessment::find(1);
$assessment->forceDelete(); // Will delete all images

// Check logs
tail -f storage/logs/laravel.log
```

---

## 📊 Database Structure

### **Tables with Soft Deletes:**

```sql
-- data_siswa
deleted_at TIMESTAMP NULL
deleted_by BIGINT NULL (FK to users.user_id)

-- student_assessments
deleted_at TIMESTAMP NULL
deleted_by BIGINT NULL

-- student_assessment_details
deleted_at TIMESTAMP NULL
deleted_by BIGINT NULL

-- growth_records
deleted_at TIMESTAMP NULL
deleted_by BIGINT NULL

-- attendance_records
deleted_at TIMESTAMP NULL
deleted_by BIGINT NULL
```

---

## 🎯 Best Practices

### **DO's:**

✅ **Selalu backup sebelum force delete**

```bash
mysqldump -u root -p sekolah > backup_before_delete.sql
```

✅ **Test di local dulu**

```php
// Test force delete
$siswa = data_siswa::withTrashed()->find('12345');
$siswa->forceDelete();

// Check if files deleted
Storage::disk('public')->exists('siswa/foto/12345.jpg'); // Should be false
```

✅ **Monitor logs untuk cleanup**

```bash
tail -f storage/logs/laravel.log | grep "Deleted"
```

✅ **Inform user tentang cascade delete**

```php
// Di Filament Resource
DeleteAction::make()
    ->modalDescription('⚠️ PERHATIAN: Menghapus siswa akan MENGHAPUS SEMUA:
    • Foto & dokumen siswa
    • Assessment (nilai + gambar dokumentasi)
    • Growth records
    • Attendance records

    Data TIDAK BISA dipulihkan!')
```

---

### **DON'Ts:**

❌ **Jangan force delete tanpa warning**

❌ **Jangan disable cascade tanpa pertimbangan**

```php
// BAD: Related data jadi orphan
$siswa->forceDelete();
// Assessment masih ada tapi siswa_nis tidak valid
```

❌ **Jangan manual delete file tanpa update database**

```bash
# BAD
rm storage/app/public/siswa/foto/*
# Database masih reference file yang sudah tidak ada
```

---

## 🐛 Troubleshooting

### **Problem: Files tidak terhapus saat force delete**

**Debug:**

```php
// Check model boot registered
php artisan tinker
$siswa = App\Models\data_siswa::find('12345');
$siswa->forceDelete();

// Check logs
tail -f storage/logs/laravel.log
// Should see: "Deleted file: ..."
```

**Solution:**

```php
// Make sure boot method is called
protected static function boot()
{
    parent::boot(); // ← MUST call parent first!

    static::forceDeleting(function($siswa) {
        $siswa->cleanupFiles();
    });
}
```

---

### **Problem: Assessment images masih ada setelah force delete**

**Check:**

```php
// Verify boot event
student_assessment::forceDeleting(function($assessment) {
    Log::info("Force deleting assessment"); // Add log
    foreach ($assessment->details as $detail) {
        Log::info("Images: " . json_encode($detail->images));
    }
});
```

**Common Issues:**

1. `images` column kosong (NULL) → Check data
2. Path salah → Check `Storage::disk('public')->exists($path)`
3. Permission → Check `storage/app/public/` writable

---

## 📞 Summary

| Aksi                            | Siswa   | Assessment | Growth Record | Gambar  | Restore? |
| ------------------------------- | ------- | ---------- | ------------- | ------- | -------- |
| **Soft Delete Siswa**           | Deleted | Tetap      | Tetap         | Tetap   | ✅ Ya    |
| **Force Delete Siswa**          | ❌ Gone | ❌ Gone    | ❌ Gone       | ❌ Gone | ❌ No    |
| **Soft Delete Assessment**      | Tetap   | Deleted    | Tetap         | Tetap   | ✅ Ya    |
| **Force Delete Assessment**     | Tetap   | ❌ Gone    | Tetap         | ❌ Gone | ❌ No    |
| **Delete Growth Record (bulk)** | Tetap   | Tetap      | ❌ Gone       | N/A     | ❌ No    |

**Key Takeaways:**

1. **Soft Delete = Reversible** (90 hari)
2. **Force Delete = Permanent + Cascade + File Cleanup**
3. **Always Backup** sebelum force delete
4. **Monitor Logs** untuk verify cleanup

---

**Last Updated:** December 1, 2024  
**Version:** 2.0.0  
**Author:** GitHub Copilot
