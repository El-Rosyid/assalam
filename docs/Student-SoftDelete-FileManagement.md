# Student Soft Delete & File Management Documentation

## 📋 Overview

Sistem ini mengimplementasikan **Soft Delete** untuk data siswa, yang artinya:

-   Data siswa yang dihapus TIDAK langsung hilang permanent
-   Data dipindahkan ke "Recycle Bin" dan bisa dipulihkan
-   File uploads (foto, dokumen) tetap tersimpan selama di Recycle Bin
-   Setelah 90 hari, data otomatis dihapus permanent beserta file-nya

---

## 🔄 Workflow Penghapusan Data Siswa

### **1. Soft Delete (Hapus Biasa)**

**Ketika Admin klik "Hapus":**

```
✅ Data siswa di-mark sebagai "deleted"
✅ File tetap ada di storage
✅ Related data (assessment, attendance, etc) tetap ada
✅ Bisa dipulihkan kapan saja dalam 90 hari
✅ Tidak muncul di list utama (kecuali filter "Dengan Dihapus")
```

**Database:**

```sql
UPDATE data_siswa
SET deleted_at = NOW(),
    deleted_by = 1 -- User ID yang menghapus
WHERE nis = '12345';
```

---

### **2. Restore (Pulihkan)**

**Ketika Admin klik "Pulihkan":**

```
✅ Data siswa aktif kembali
✅ Muncul di list utama lagi
✅ File tetap utuh
✅ Related data tetap terhubung
```

**Database:**

```sql
UPDATE data_siswa
SET deleted_at = NULL,
    deleted_by = NULL
WHERE nis = '12345';
```

---

### **3. Force Delete (Hapus Permanent)**

**Ketika Admin klik "Hapus Permanent":**

```
❌ Data siswa dihapus PERMANENT dari database
❌ Semua file (foto, dokumen) TERHAPUS dari storage
⚠️ Related data di-handle sesuai konfigurasi:
   Option 1: Dihapus juga (CASCADE)
   Option 2: Tetap ada tapi log warning (default)
❌ TIDAK BISA dipulihkan lagi!
```

**Files yang Dihapus:**

-   `storage/app/public/siswa/foto/12345.jpg`
-   `storage/app/public/siswa/dokumen/akta_12345.pdf`
-   `storage/app/public/siswa/dokumen/kk_12345.pdf`
-   Dan file lainnya yang tercatat di database

---

### **4. Auto Cleanup (Setiap Minggu)**

**Cron Job berjalan otomatis:**

```bash
# Setiap Minggu, Minggu pukul 02:00
php artisan students:cleanup-deleted --days=90 --force
```

**Proses:**

```
1. Cari siswa yang sudah dihapus > 90 hari
2. Loop setiap siswa:
   ✅ Delete semua file uploads
   ✅ Handle related data
   ✅ Delete dari database permanent
3. Log hasil ke storage/logs/
4. Email notifikasi ke admin (jika ada error)
```

---

## 🎯 Cara Menggunakan

### **A. Dari Dashboard Filament**

#### **1. Soft Delete Siswa**

1. Buka **Data Siswa**
2. Pilih siswa yang ingin dihapus
3. Klik **Actions → Hapus**
4. Konfirmasi
5. ✅ Siswa dipindah ke Recycle Bin

#### **2. Lihat Siswa yang Dihapus**

1. Buka **Data Siswa**
2. Klik **Filter → Status Hapus**
3. Pilih **"Hanya Dihapus"**
4. List siswa yang sudah dihapus akan muncul

#### **3. Restore Siswa**

1. Filter: **"Hanya Dihapus"**
2. Pilih siswa yang ingin dipulihkan
3. Klik **Actions → Pulihkan**
4. ✅ Siswa aktif kembali

#### **4. Hapus Permanent**

1. Filter: **"Hanya Dihapus"**
2. Pilih siswa yang ingin dihapus permanent
3. Klik **Actions → Hapus Permanent**
4. Baca peringatan dengan teliti!
5. Konfirmasi
6. ❌ Data terhapus permanent

---

### **B. Via Command Line**

#### **1. Manual Cleanup (Dry Run)**

```bash
# Cek siswa yang akan dihapus (tanpa benar-benar menghapus)
php artisan students:cleanup-deleted --days=90

# Output:
# Finding students deleted more than 90 days ago...
# ┌───────────┬────────────────┬─────────────────┬───────────┐
# │ NIS       │ Nama           │ Deleted At      │ Days Ago  │
# ├───────────┼────────────────┼─────────────────┼───────────┤
# │ 12345     │ Ahmad          │ 01/09/2024 10:00│ 91 days   │
# │ 67890     │ Siti           │ 05/09/2024 14:30│ 87 days   │
# └───────────┴────────────────┴─────────────────┴───────────┘
# ⚠️  This is a DRY RUN. Use --force to actually delete.
```

#### **2. Manual Cleanup (Real Delete)**

```bash
# Hapus permanent siswa yang sudah > 90 hari
php artisan students:cleanup-deleted --days=90 --force

# Dengan konfirmasi interaktif
```

#### **3. Custom Days**

```bash
# Hapus yang sudah > 30 hari
php artisan students:cleanup-deleted --days=30 --force

# Hapus yang sudah > 180 hari
php artisan students:cleanup-deleted --days=180 --force
```

---

## ⚙️ Konfigurasi

### **1. File Columns untuk Cleanup**

Edit `app/Models/data_siswa.php`:

```php
protected function cleanupFiles(): void
{
    // Tambahkan kolom file yang ingin di-cleanup
    $fileColumns = [
        'foto_siswa',           // Foto profil
        'dokumen_akta',         // Akta kelahiran
        'dokumen_kk',           // Kartu Keluarga
        'dokumen_ijazah',       // Ijazah
        'dokumen_rapor',        // Rapor
        // ... tambahkan lainnya
    ];

    // ... cleanup logic
}
```

### **2. Related Data Handling**

Edit `app/Models/data_siswa.php`:

```php
protected function cleanupRelatedData(): void
{
    // Option 1: DELETE CASCADE (hapus semua)
    $this->studentAssessments()->delete();
    $this->growthRecords()->delete();
    $this->attendanceRecords()->delete();

    // Option 2: KEEP DATA (default - hanya log warning)
    // Biarkan commented out jika ingin keep data
}
```

### **3. Auto Cleanup Schedule**

Edit `app/Console/Kernel.php`:

```php
// Ubah jadwal cleanup
$schedule->command('students:cleanup-deleted --days=90 --force')
    ->weekly()           // Bisa: daily(), weekly(), monthly()
    ->sundays()          // Hari: mondays(), tuesdays(), etc
    ->at('02:00');       // Jam: '02:00', '23:30', etc
```

### **4. Email Notification**

Update `.env`:

```env
ADMIN_EMAIL=admin@sekolah.com
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

---

## 🔒 Security & Permissions

### **Role-Based Access**

```php
// Di DataSiswaResource.php sudah ada permission check
public static function canDelete(Model $record): bool
{
    // Hanya admin yang bisa delete
    return auth()->user()->hasRole('admin');
}

public static function canForceDelete(Model $record): bool
{
    // Hanya super_admin yang bisa force delete
    return auth()->user()->hasRole('super_admin');
}
```

### **Audit Trail**

Setiap penghapusan tercatat:

```php
// Di database
deleted_at  => timestamp kapan dihapus
deleted_by  => user ID yang menghapus

// Di log files
storage/logs/laravel.log
[2024-12-01 10:00:00] info: Student soft deleted {nis: 12345, by: admin@school.com}
[2024-12-01 10:05:00] info: Student restored {nis: 12345, by: admin@school.com}
[2024-12-01 11:00:00] warning: Student force deleted {nis: 12345, files: 5, by: superadmin@school.com}
```

---

## 📊 Database Structure

### **Migration:**

```sql
ALTER TABLE data_siswa
ADD COLUMN deleted_at TIMESTAMP NULL,
ADD COLUMN deleted_by BIGINT UNSIGNED NULL,
ADD INDEX idx_deleted_at (deleted_at),
ADD FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;
```

### **Queries:**

```sql
-- Active students only (default)
SELECT * FROM data_siswa WHERE deleted_at IS NULL;

-- Soft deleted students only
SELECT * FROM data_siswa WHERE deleted_at IS NOT NULL;

-- All students (including deleted)
SELECT * FROM data_siswa;

-- Students deleted > 90 days ago
SELECT * FROM data_siswa
WHERE deleted_at IS NOT NULL
AND deleted_at <= DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 🚀 Deployment Steps

### **1. Run Migration**

```bash
cd ~/laravel
php artisan migrate

# Output:
# Migrating: 2024_12_01_add_soft_deletes_to_data_siswa
# Migrated:  2024_12_01_add_soft_deletes_to_data_siswa (50.23ms)
```

### **2. Setup Cron Job**

**cPanel → Cron Jobs:**

```bash
# Laravel Scheduler (includes student cleanup)
* * * * * cd ~/laravel && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### **3. Test Soft Delete**

```bash
# Test di local dulu
php artisan tinker

# Soft delete
$siswa = App\Models\data_siswa::find('12345');
$siswa->delete(); // Soft delete

# Check
$siswa->trashed(); // true

# Restore
$siswa->restore();

# Force delete
$siswa->forceDelete(); // Permanent!
```

### **4. Verify Auto Cleanup**

```bash
# Test manual
php artisan students:cleanup-deleted --days=0 --force

# Check logs
tail -f storage/logs/laravel.log
```

---

## ⚠️ Important Notes

### **DO's:**

-   ✅ Selalu backup database sebelum force delete
-   ✅ Verifikasi data yang akan dihapus permanent
-   ✅ Test di local environment dulu
-   ✅ Monitor log files secara berkala
-   ✅ Inform user sebelum auto cleanup

### **DON'Ts:**

-   ❌ Jangan force delete tanpa backup
-   ❌ Jangan set cleanup days < 30 hari
-   ❌ Jangan disable soft deletes tanpa migrasi data
-   ❌ Jangan hapus manual dari database langsung

---

## 🐛 Troubleshooting

### **Problem: Files tidak terhapus**

**Solution:**

```bash
# Check storage permissions
chmod -R 775 storage/app/public

# Check file paths di database
SELECT foto_siswa, dokumen_akta FROM data_siswa WHERE nis = '12345';

# Manual cleanup
php artisan tinker
Storage::disk('public')->exists('siswa/foto/12345.jpg');
Storage::disk('public')->delete('siswa/foto/12345.jpg');
```

### **Problem: Related data orphan**

**Solution:**

```php
// Update cleanupRelatedData() di model
protected function cleanupRelatedData(): void
{
    // Enable CASCADE delete
    $this->studentAssessments()->delete();
    $this->growthRecords()->delete();
    $this->attendanceRecords()->delete();
}
```

### **Problem: Cron job tidak jalan**

**Solution:**

```bash
# Check cron logs
tail -f storage/logs/laravel.log

# Manual test
php artisan schedule:run

# Check cron configuration
crontab -l
```

---

## 📞 Support

Jika ada masalah atau pertanyaan:

1. Check log files: `storage/logs/laravel.log`
2. Run diagnostic: `php artisan about`
3. Contact: admin@sekolah.com

---

**Last Updated:** December 1, 2024  
**Version:** 1.0.0  
**Author:** GitHub Copilot
