# Auto Cleanup Schedule - File & Data Management

## 📅 Timeline Otomatis

```
┌─────────────────────────────────────────────────────────────────┐
│                    LIFECYCLE SISWA DIHAPUS                      │
└─────────────────────────────────────────────────────────────────┘

Hari 0: [SOFT DELETE]
├─ Admin klik tombol "Hapus"
├─ Data siswa DISEMBUNYIKAN (deleted_at = now())
├─ File TETAP ADA ✅
├─ Related data TETAP ADA ✅
└─ Status: "Di Recycle Bin"

Hari 1-89: [GRACE PERIOD]
├─ Data bisa dipulihkan kapan saja
├─ File masih aman di storage
├─ Muncul di filter "Only Trashed"
└─ Bisa restore via Admin Panel

Hari 90: [AUTO FORCE DELETE]
├─ 🤖 Cron job: model:prune (jam 03:00 pagi)
├─ Data PERMANENT DELETE dari database
├─ File OTOMATIS TERHAPUS 🗑️
│  └─ foto_siswa, dokumen_akta, dokumen_kk, dokumen_ijazah
├─ Related data tetap ada (audit trail)
└─ TIDAK BISA dipulihkan lagi ❌

Setiap Minggu: [ORPHAN CLEANUP]
├─ 🤖 Cron job: storage:cleanup-orphan-files (Minggu 02:00)
├─ Scan file yang tidak ada pemiliknya
├─ Hapus file hasil upload gagal/corrupt
└─ Optimasi storage usage
```

## ⚙️ Scheduled Tasks

### 1. Auto-Prune Siswa (Daily)

**Command:**

```bash
php artisan model:prune --model="App\Models\data_siswa"
```

**Schedule:**

-   **Waktu:** Setiap hari jam 03:00 pagi
-   **Target:** Siswa yang sudah >90 hari di recycle bin
-   **Action:**
    -   Force delete dari database
    -   Hapus semua file (foto + 4 dokumen)
    -   Cleanup related data
    -   Log ke `storage/logs/laravel.log`

**Implementasi:**

```php
// app/Console/Kernel.php
$schedule->command('model:prune', ['--model' => \App\Models\data_siswa::class])
    ->daily()
    ->at('03:00')
    ->withoutOverlapping()
    ->runInBackground();
```

**Model Prunable:**

```php
// app/Models/data_siswa.php
use Illuminate\Database\Eloquent\Prunable;

public function prunable()
{
    // Siswa yang sudah >90 hari di recycle bin
    return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(90));
}

protected function pruning()
{
    // Cleanup files sebelum permanent delete
    $this->cleanupFiles();
    $this->cleanupRelatedData();

    Log::info("Student auto-pruned (>90 days in recycle bin)", [
        'nis' => $this->nis,
        'nama' => $this->nama_lengkap,
    ]);
}
```

### 2. Orphan File Cleanup (Weekly)

**Command:**

```bash
php artisan storage:cleanup-orphan-files
```

**Schedule:**

-   **Waktu:** Setiap Minggu (Minggu) jam 02:00 pagi
-   **Target:** File di storage yang tidak ada owner-nya
-   **Source:**
    -   Upload gagal
    -   Data corrupt
    -   Manual delete tanpa proper cleanup
    -   Migrasi/import yang error

**Implementasi:**

```php
// app/Console/Kernel.php
$schedule->command('storage:cleanup-orphan-files')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping()
    ->runInBackground();
```

**File yang Dicek:**

```
storage/app/public/
├── siswa/foto/
├── siswa/akta/
├── siswa/kk/
└── siswa/ijazah/
```

## 🔧 Manual Testing

### Test Auto-Prune (Dry Run)

```bash
# Preview siswa yang akan di-prune
php artisan model:prune --model="App\Models\data_siswa" --pretend

# Output:
# INFO  No prunable [App\Models\data_siswa] records found.
# (Jika tidak ada yang >90 hari)
```

### Test Orphan Cleanup

```bash
# Preview file yang akan dihapus
php artisan storage:cleanup-orphan-files --dry-run

# Hapus actual (setelah backup!)
php artisan storage:cleanup-orphan-files
```

### Check Scheduled Tasks

```bash
# Lihat semua scheduled tasks
php artisan schedule:list

# Output:
# ┌─────────────────────────────────────────────────────────────┐
# │ 03:00 ............... model:prune --model="App\Models\..." │
# │ 02:00 (Sundays) ..... storage:cleanup-orphan-files         │
# └─────────────────────────────────────────────────────────────┘
```

### Run Scheduler Manually

```bash
# Test run semua scheduled tasks
php artisan schedule:run

# Test specific command
php artisan schedule:test
```

## 🚀 Aktivasi di Production

### cPanel Setup

**1. Edit Cron Job Existing:**

```bash
# File: /home/mhvpshnt/domains/abaassalam.my.id/public_html

# Existing cron (queue worker)
* * * * * /opt/alt/php82/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1

# Tambahkan: Scheduler (jalankan setiap menit, Laravel akan handle schedule internal)
* * * * * /opt/alt/php82/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan schedule:run >> /dev/null 2>&1
```

**2. Atau Manual Setup (Alternative):**

```bash
# Auto-prune setiap hari jam 3 pagi
0 3 * * * /opt/alt/php82/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan model:prune --model="App\Models\data_siswa" >> /dev/null 2>&1

# Orphan cleanup setiap Minggu jam 2 pagi
0 2 * * 0 /opt/alt/php82/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan storage:cleanup-orphan-files >> /dev/null 2>&1
```

**Recommended:** Gunakan `schedule:run` karena lebih flexible dan semua schedule terpusat di Kernel.php.

## 📊 Monitoring & Logging

### Check Logs

```bash
# Lihat log auto-prune
tail -f storage/logs/laravel.log | grep "auto-pruned"

# Output contoh:
# [2025-12-05 03:00:15] local.INFO: Student auto-pruned (>90 days in recycle bin) {"nis":"2103040009","nama":"tama","deleted_at":"2025-09-06 10:25:30"}
```

### Storage Usage

```bash
# Cek ukuran storage siswa
du -sh storage/app/public/siswa/*

# Output contoh:
# 12M  storage/app/public/siswa/foto
# 8.5M storage/app/public/siswa/akta
# 6.2M storage/app/public/siswa/kk
# 4.1M storage/app/public/siswa/ijazah
```

### Database Stats

```bash
php artisan tinker
>>> echo "Aktif: " . App\Models\data_siswa::count();
>>> echo "Recycle Bin: " . App\Models\data_siswa::onlyTrashed()->count();
>>> echo "Total: " . App\Models\data_siswa::withTrashed()->count();
```

## ⚠️ Important Notes

### File TIDAK Otomatis Hilang Setelah Soft Delete

**Pertanyaan:** "File penggalan masih tersisa di storage, apa akan hilang setelah 90 hari?"

**Jawaban:**

-   ❌ **TIDAK otomatis hilang** saat soft delete
-   ✅ **OTOMATIS hilang** setelah 90 hari via auto-prune
-   ✅ **OTOMATIS hilang** saat force delete manual
-   ✅ **OTOMATIS hilang** saat weekly orphan cleanup (jika tidak ada owner)

**Timeline:**

```
Hari 0-89  : File AMAN, data bisa di-restore lengkap
Hari 90+   : File TERHAPUS otomatis via cron job
Manual     : File langsung terhapus jika force delete
```

### Backup Strategy

**Sebelum Auto-Prune Berjalan:**

1. Backup database (include soft deleted):

    ```bash
    php artisan db:backup --include-soft-deletes
    ```

2. Backup storage:

    ```bash
    tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public/siswa/
    ```

3. Simpan backup >90 hari untuk compliance

### Recovery Setelah Auto-Prune

Jika siswa sudah di-auto-prune (>90 hari) dan perlu dipulihkan:

1. **Restore Database:**

    ```sql
    # Dari backup SQL
    INSERT INTO data_siswa VALUES (...);
    ```

2. **Restore Files:**

    ```bash
    # Extract dari backup
    tar -xzf storage_backup_20250906.tar.gz
    ```

3. **Re-link di database**

## 🔐 Security & Compliance

### Audit Trail

Auto-prune mencatat setiap penghapusan:

```
[2025-12-05 03:00:15] local.INFO: Student auto-pruned
    nis: 2103040009
    nama: tama
    deleted_at: 2025-09-06 10:25:30
    files_deleted: ["siswa/foto/2103040009.jpg", "siswa/akta/2103040009.pdf"]
```

### Data Retention Policy

-   **Active Students:** Unlimited
-   **Soft Deleted:** 90 days (recoverable)
-   **Pruned Students:** Related data retained for audit
-   **Backups:** 1 year minimum

### GDPR Compliance

Untuk fully delete data (GDPR "Right to be Forgotten"):

1. Soft delete siswa
2. Tunggu auto-prune (90 hari) ATAU force delete manual
3. Related data (assessments, attendance) juga bisa di-anonymize jika perlu

## 📝 Configuration

### Ubah Retention Period

Edit `app/Models/data_siswa.php`:

```php
public function prunable()
{
    // Default: 90 hari
    // Ubah jadi 180 hari:
    return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(180));

    // Atau 30 hari:
    return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30));
}
```

### Disable Auto-Prune

Comment di `app/Console/Kernel.php`:

```php
// $schedule->command('model:prune', ['--model' => \App\Models\data_siswa::class])
//     ->daily()
//     ->at('03:00');
```

### Change Schedule Time

```php
// Dari jam 3 pagi → jam 1 siang
->at('13:00')

// Dari daily → weekly (Sabtu)
->weekly()
->saturdays()
->at('03:00')
```

## ✅ Summary

### Auto-Cleanup Features

| Feature            | Schedule              | Target                | Action                    |
| ------------------ | --------------------- | --------------------- | ------------------------- |
| **Auto-Prune**     | Daily 03:00           | Siswa >90 hari di bin | Force delete + hapus file |
| **Orphan Cleanup** | Weekly 02:00 (Minggu) | File tanpa owner      | Hapus file orphan         |
| **Queue Worker**   | Every minute          | Jobs pending          | Process WhatsApp, etc     |

### File Deletion Logic

| Trigger               | File Status             | Recovery                       |
| --------------------- | ----------------------- | ------------------------------ |
| Soft Delete           | ✅ Tetap ada            | ✅ Auto-recover saat restore   |
| Force Delete (Manual) | ❌ Langsung terhapus    | ❌ Restore dari backup         |
| Auto-Prune (90 hari)  | ❌ Otomatis terhapus    | ❌ Restore dari backup         |
| Orphan Cleanup        | ❌ Terhapus jika orphan | ❌ Tidak perlu (memang sampah) |

### Best Practice

1. ✅ **Backup sebelum production:** Database + Storage weekly
2. ✅ **Monitor logs:** Check auto-prune activity monthly
3. ✅ **Test di local:** Run `--pretend` dulu
4. ✅ **Inform users:** Beritahu admin tentang 90-day policy
5. ✅ **Document decisions:** Catat alasan force delete manual

---

**Last Updated:** 5 Desember 2025  
**Version:** 1.0  
**Status:** ✅ Active & Scheduled
