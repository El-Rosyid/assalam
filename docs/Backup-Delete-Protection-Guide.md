# Panduan Backup & Delete Protection

## ⚠️ PERINGATAN PENTING

### Data yang Terhapus Otomatis (Cascade Delete)

Sistem menggunakan **cascade delete** untuk menjaga integritas data. Artinya:

#### 1. **Hapus Siswa** → Data Terhapus:

-   ✅ User account siswa
-   ✅ Semua penilaian siswa (student_assessments)
-   ✅ Detail penilaian (student_assessment_details)
-   ✅ Catatan pertumbuhan (growth_records)
-   ✅ Catatan bulanan (monthly_reports)
-   ✅ Catatan kehadiran (attendance_records)

#### 2. **Hapus Guru** → Data Terhapus:

-   ✅ User account guru
-   ✅ Penilaian yang dia input
-   ✅ Catatan pertumbuhan yang dia input
-   ✅ Catatan kehadiran yang dia input
-   ⚠️ Kelas menjadi tanpa wali kelas (set null)

#### 3. **Hapus Kelas** → Data Terhapus:

-   ✅ Penilaian siswa di kelas itu
-   ✅ Catatan pertumbuhan kelas itu
-   ⚠️ Siswa menjadi tanpa kelas (set null)

#### 4. **Hapus Tahun Ajaran** → Data Terhapus:

-   ✅ SEMUA penilaian tahun itu
-   ✅ SEMUA catatan pertumbuhan tahun itu
-   ⚠️ **SANGAT BERBAHAYA!**

---

## 🛡️ SOLUSI PROTEKSI

### Opsi 1: Soft Delete (Recommended)

Data tidak benar-benar dihapus, hanya ditandai sebagai deleted.

#### Instalasi:

```bash
# 1. Jalankan migration soft delete
php artisan migrate

# 2. Update models untuk gunakan SoftDeletes
```

#### Keuntungan:

-   ✅ Data bisa di-restore
-   ✅ Audit trail lengkap
-   ✅ Aman dari kesalahan user

#### Update Model (Contoh untuk data_siswa):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class data_siswa extends Model
{
    use SoftDeletes; // Tambahkan ini

    protected $table = 'data_siswa';
    protected $guarded = [];

    // ...existing code
}
```

**File yang perlu ditambahkan SoftDeletes:**

-   `app/Models/data_siswa.php`
-   `app/Models/data_guru.php`
-   `app/Models/data_kelas.php`
-   `app/Models/student_assessment.php`
-   `app/Models/student_assessment_detail.php`
-   `app/Models/GrowthRecord.php`
-   `app/Models/monthly_reports.php`
-   `app/Models/AttendanceRecord.php`

---

### Opsi 2: Backup System

#### A. Manual Backup

```bash
# Backup tahun ajaran tertentu
php artisan backup:academic-year {year_id}

# Backup semua tahun ajaran
php artisan backup:academic-year --all

# Interactive mode (pilih dari menu)
php artisan backup:academic-year
```

#### B. Auto Backup (Scheduler)

Backup otomatis sudah dikonfigurasi:

-   **Setiap akhir bulan**: Tanggal 28 jam 23:00
-   **Akhir tahun ajaran**: 30 Juni jam 00:00

**Setup Scheduler (Production):**

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini:
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

#### C. Lokasi Backup

File backup tersimpan di:

```
storage/app/backups/academic-years/
```

Format nama file:

```
backup_2024_2025_2025-11-06_143022.sql
```

#### D. Restore dari Backup

```bash
# Manual restore via MySQL
mysql -u root -p database_name < storage/app/backups/academic-years/backup_file.sql

# Atau via Adminer/phpMyAdmin
# Import file SQL
```

---

## 📋 CHECKLIST SEBELUM HAPUS DATA

### ⚠️ WAJIB CEK SEBELUM HAPUS SISWA:

-   [ ] Apakah siswa sudah lulus/pindah?
-   [ ] Sudah backup data tahun ajaran?
-   [ ] Yakin tidak perlu data historis?
-   [ ] Sudah koordinasi dengan admin lain?

### ⚠️ WAJIB CEK SEBELUM HAPUS GURU:

-   [ ] Apakah guru sudah resign/pensiun?
-   [ ] Kelas yang dia ampu sudah di-assign ke guru lain?
-   [ ] Sudah backup data tahun ajaran?

### ⚠️ WAJIB CEK SEBELUM HAPUS KELAS:

-   [ ] Semua siswa sudah dipindah ke kelas lain?
-   [ ] Sudah backup data penilaian kelas?
-   [ ] Tahun ajaran sudah selesai?

### ⚠️ WAJIB CEK SEBELUM HAPUS TAHUN AJARAN:

-   [ ] **BACKUP WAJIB!**
-   [ ] Tahun ajaran benar-benar sudah selesai?
-   [ ] Semua laporan sudah dicetak?
-   [ ] Koordinasi dengan Kepala Sekolah?

---

## 🚨 EMERGENCY RECOVERY

### Jika Data Terhapus (dengan Soft Delete):

```bash
# Via Laravel Tinker
php artisan tinker

# Restore siswa
>>> App\Models\data_siswa::withTrashed()->find($id)->restore();

# Restore semua siswa yang dihapus hari ini
>>> App\Models\data_siswa::onlyTrashed()
       ->whereDate('deleted_at', today())
       ->restore();
```

### Jika Data Terhapus (tanpa Soft Delete):

```bash
# Restore dari backup terakhir
mysql -u root -p database_name < storage/app/backups/academic-years/latest_backup.sql
```

---

## 📊 MONITORING BACKUP

### Cek Ukuran Backup:

```bash
# List semua backup
ls -lh storage/app/backups/academic-years/

# Total ukuran backup
du -sh storage/app/backups/academic-years/
```

### Cleanup Backup Lama:

```bash
# Hapus backup lebih dari 1 tahun
find storage/app/backups/academic-years/ -name "*.sql" -mtime +365 -delete
```

---

## 🎯 BEST PRACTICES

### 1. **Akhir Tahun Ajaran:**

```bash
# Backup dulu sebelum mulai tahun ajaran baru
php artisan backup:academic-year --all

# Arsipkan ke external storage
cp -r storage/app/backups/academic-years/ /path/to/external/drive/
```

### 2. **Sebelum Maintenance:**

```bash
# Full database backup
php artisan backup:academic-year --all

# Backup database lengkap
mysqldump -u root -p database_name > full_backup_$(date +%Y%m%d).sql
```

### 3. **Rotasi Backup:**

-   Simpan backup bulanan: 12 bulan
-   Simpan backup tahunan: 5 tahun
-   Upload ke cloud storage (Google Drive/Dropbox)

---

## 📞 KONTAK DARURAT

Jika terjadi kehilangan data:

1. **STOP** - Jangan lakukan perubahan apapun
2. **BACKUP** - Backup database segera (meski sudah terhapus, masih bisa recovery)
3. **KONTAK** - Hubungi IT Support/Developer
4. **RESTORE** - Restore dari backup terakhir

---

## 🔧 TROUBLESHOOTING

### Backup Command Gagal:

```bash
# Cek permission
chmod -R 775 storage/app/backups

# Cek disk space
df -h

# Test manual backup
php artisan backup:academic-year 1
```

### Restore Gagal:

```bash
# Cek syntax SQL
mysql -u root -p database_name < backup.sql 2> error.log

# Lihat error
cat error.log
```

---

**Dibuat:** 6 November 2025  
**Versi:** 1.0.0  
**Update Terakhir:** 6 November 2025
