# WhatsApp Broadcast untuk Monthly Report

Dokumentasi fitur WhatsApp broadcast otomatis ketika guru menyelesaikan catatan perkembangan bulanan siswa.

## 📋 Daftar Isi

-   [Overview](#overview)
-   [Cara Kerja](#cara-kerja)
-   [Konfigurasi](#konfigurasi)
-   [Format Pesan](#format-pesan)
-   [Testing](#testing)
-   [Troubleshooting](#troubleshooting)

## Overview

Fitur ini memungkinkan sistem secara otomatis mengirimkan notifikasi WhatsApp kepada orang tua/wali siswa ketika guru menyelesaikan catatan perkembangan bulanan.

### Fitur Utama:

-   ✅ Notifikasi otomatis ke orang tua/wali siswa
-   ✅ Queue system dengan 3x retry otomatis
-   ✅ Logging lengkap untuk tracking
-   ✅ Validasi nomor telepon otomatis
-   ✅ Notifikasi admin untuk kegagalan pengiriman
-   ✅ Toggle on/off via environment variable
-   ✅ Skip otomatis untuk draft atau catatan kosong

## Cara Kerja

### Flow Diagram:

```
Guru Input Laporan → Simpan
    ↓
Observer Detect (created/updated)
    ↓
Validasi Catatan (not empty, not draft)
    ↓
Check Already Sent (skip jika sudah)
    ↓
Validasi Nomor Telepon
    ↓
Create Broadcast Record (status: pending)
    ↓
Dispatch Job ke Queue
    ↓
Queue Process (3x retry jika gagal)
    ↓
Fonnte API Send WhatsApp
    ↓
Update Status (sent/failed)
    ↓
Log ke Database + Notify Admin (jika gagal)
```

### Trigger Conditions:

WhatsApp hanya akan dikirim jika:

1. ✅ Catatan tidak kosong (`catatan` field tidak null/empty)
2. ✅ Catatan bukan draft (tidak mengandung kata "draft")
3. ✅ Belum pernah dikirim sebelumnya untuk laporan yang sama
4. ✅ Siswa memiliki nomor telepon yang valid
5. ✅ Fitur broadcast aktif (`WHATSAPP_BROADCAST_ENABLED=true`)

## Konfigurasi

### 1. Environment Variables

Tambahkan ke file `.env`:

```bash
# Fonnte API Token (wajib)
FONNTE_API_TOKEN=your_actual_token_here

# Enable/Disable Broadcast Feature
WHATSAPP_BROADCAST_ENABLED=true
```

### 2. Queue Configuration

Untuk production, gunakan queue driver seperti `database` atau `redis`:

```bash
# Di .env
QUEUE_CONNECTION=database
```

Jika menggunakan `database` queue, jalankan migration terlebih dahulu:

```bash
php artisan queue:table
php artisan migrate
```

### 3. Jalankan Queue Worker

Untuk memproses job di background:

```bash
# Development
php artisan queue:work

# Production (dengan supervisor)
php artisan queue:work --queue=default --tries=3 --timeout=60
```

## Format Pesan

Pesan WhatsApp yang dikirim akan mengikuti format berikut:

```
Assalamualaikum Yth. Orang Tua/Wali dari *[Nama Siswa]*

Laporan bulan *[Bulan Tahun]*:

[Isi Catatan dari Guru]

Informasi lebih lanjut: [Link Website Sekolah]

Terima kasih atas perhatiannya.

_Pesan otomatis dari [Nama Sekolah]_
```

### Contoh Pesan Real:

```
Assalamualaikum Yth. Orang Tua/Wali dari *Ahmad Fauzi*

Laporan bulan *Oktober 2025*:

Ahmad menunjukkan perkembangan yang baik bulan ini.
Nilai matematika meningkat dan kehadiran 100%.
Tetap semangat belajar!

Informasi lebih lanjut: https://sekolah-example.id

Terima kasih atas perhatiannya.

_Pesan otomatis dari SD Negeri 1 Jakarta_
```

## Testing

### 1. Test Manual (Dry Run)

Untuk preview pesan tanpa mengirim:

```bash
php artisan test:whatsapp-broadcast

# Atau test dengan ID spesifik
php artisan test:whatsapp-broadcast 123
```

Command ini akan menampilkan:

-   ✅ Status fitur (enabled/disabled)
-   📄 Detail monthly report
-   👤 Data siswa
-   📱 Nomor telepon (asli & tervalidasi)
-   📝 Preview pesan lengkap

### 2. Test Real Send

Saat menjalankan command test, sistem akan meminta konfirmasi sebelum benar-benar mengirim:

```bash
php artisan test:whatsapp-broadcast

# Output:
# ...
# Apakah Anda ingin mengirim WhatsApp ini? (REAL SEND) (yes/no) [no]:
# > yes  # Ketik 'yes' untuk send real

# ✅ WhatsApp berhasil dikirim!
# 📊 Response dari Fonnte:
# {
#   "status": true,
#   "message": "Message sent"
# }
```

### 3. Check Logs

Cek database untuk tracking:

```sql
-- Lihat semua broadcast log
SELECT * FROM monthly_report_broadcasts ORDER BY created_at DESC;

-- Filter by status
SELECT * FROM monthly_report_broadcasts WHERE status = 'failed';
SELECT * FROM monthly_report_broadcasts WHERE status = 'sent';
SELECT * FROM monthly_report_broadcasts WHERE status = 'pending';

-- Cek retry count
SELECT id, phone_number, retry_count, error_message
FROM monthly_report_broadcasts
WHERE retry_count > 0;
```

### 4. Monitor Queue

```bash
# Lihat failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Flush failed jobs
php artisan queue:flush
```

## Troubleshooting

### ❌ WhatsApp tidak terkirim

**Cek 1: Fitur Enabled?**

```bash
# Di .env
WHATSAPP_BROADCAST_ENABLED=true  # Pastikan true
```

**Cek 2: Queue Running?**

```bash
# Pastikan queue worker berjalan
php artisan queue:work
```

**Cek 3: Token Valid?**

```bash
# Test API token dengan curl
curl -X POST https://api.fonnte.com/send \
  -H "Authorization: YOUR_TOKEN" \
  -d "target=628123456789" \
  -d "message=Test"
```

**Cek 4: Catatan Valid?**

-   Catatan tidak boleh kosong
-   Catatan tidak boleh mengandung kata "draft"

**Cek 5: Nomor Telepon Valid?**

-   Siswa harus punya nomor di field `no_telp_ortu_wali`
-   Format nomor akan dikonversi otomatis (0812 → 62812)

### ❌ Custom Broadcast Status Stuck di "Pending" (Kasus Kemarin)

**Gejala:**

-   Broadcast dibuat tapi status tetap "pending" berhari-hari
-   Jobs ada di database tapi tidak diproses
-   Meski token sudah diganti di `.env`, masih error

**Root Cause:**

1. **Config Cache** - Service membaca token dari cache, bukan langsung dari `.env`
2. **Queue Worker Not Running** - Jobs menumpuk tanpa diproses
3. **Invalid Token** - API Fonnte menolak dengan token lama

**Solusi:**

```bash
# 1. Clear dan rebuild config cache
php artisan config:clear
php artisan config:cache

# 2. Pastikan queue worker berjalan
php artisan queue:work --max-jobs=1 --max-time=58

# 3. Atau jika ada pending jobs, dispatch ulang
php artisan queue:retry all

# 4. Check status
SELECT * FROM custom_broadcast_logs WHERE status = 'pending';
```

**Prevention:**
✅ Setup Cron Job di cPanel (lihat Production Setup)
✅ Monitor logs regularly
✅ Test token sebelum deploy

---

### ❌ Notifikasi Admin Tidak Muncul

Cek role admin:

```sql
-- Pastikan ada user dengan role super_admin
SELECT u.name, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE r.name = 'super_admin';
```

### 📊 Monitoring Production

#### **Option 1: Setup Supervisor untuk Queue Worker (Linux/Dedicated Server)**

File: `/etc/supervisor/conf.d/laravel-worker.conf`

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Setelah membuat file, jalankan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

#### **Option 2: Setup di cPanel (Shared Hosting)**

**Penting:** cPanel tidak support daemon process secara native. Solusi terbaik adalah menggunakan **Cron Job**:

**A. Setup Cron Job untuk Queue Processing**

1. Login ke cPanel
2. Buka **Cron Jobs** (biasanya di Advanced section)
3. Buat cron job baru:

```bash
# Jalankan setiap menit
* * * * * cd /home/username/public_html/sekolah && php artisan queue:work --max-jobs=1 --max-time=58 >> /dev/null 2>&1
```

**Penjelasan:**

-   `* * * * *` = setiap menit
-   `--max-jobs=1` = process 1 job per run
-   `--max-time=58` = timeout 58 detik (aman dalam cron 1 menit)
-   `>> /dev/null 2>&1` = suppress output

**B. Setup untuk Monitoring**

Buat cron job monitoring (jalankan setiap jam):

```bash
# Jalankan setiap jam
0 * * * * cd /home/username/public_html/sekolah && php artisan queue:work --daemon --sleep=5 --tries=3 --timeout=60 >> storage/logs/worker.log 2>&1 &
```

**C. Alternatif: Laravel Scheduler**

Edit `routes/console.php` atau `app/Console/Kernel.php`:

```php
// Di app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('queue:work --max-jobs=1 --max-time=58')
        ->everyMinute()
        ->withoutOverlapping()
        ->onFailure(function () {
            // Notify admin jika queue worker gagal
            Log::error('Queue worker failed');
        });
}
```

Kemudian setup cron untuk scheduler (jalankan setiap menit):

```bash
* * * * * cd /home/username/public_html/sekolah && php artisan schedule:run >> /dev/null 2>&1
```

---

#### **Option 3: Setup di cPanel dengan Daemon Process (Jika Available)**

Beberapa cPanel support custom daemon via `.cpanel.yml`:

File: `.cpanel.yml`

```yaml
---
deployment:
    tasks:
        - export DEPLOYPATH=/home/username/public_html/sekolah
        - /bin/bash $DEPLOYPATH/scripts/deploy.sh

features:
    - cPanel
    - Whitelabel
    - Autoupdate
    - Webmail

background_jobs:
    - name: "Laravel Queue Worker"
      command: "/usr/bin/php /home/username/public_html/sekolah/artisan queue:work --daemon --sleep=5 --tries=3"
      user: "username"
      enabled: true
```

Hubungi hosting provider untuk memastikan fitur ini available.

---

#### **Rekomendasi Setup di cPanel:**

✅ **Best Practice:**

1. Gunakan **Cron Job + Scheduler** (Option 2C)
2. Set `--max-jobs=1 --max-time=58` untuk safety
3. Combine dengan config-based token (sudah diimplementasi)
4. Monitor via Laravel logs

⚠️ **Hindari:**

-   Daemon process permanent (usually tidak support di shared hosting)
-   Long-running processes (cPanel akan timeout)

---

**Log Files:**

```bash
# Laravel log
tail -f storage/logs/laravel.log

# Cron execution log (jika setup)
grep CRON /var/log/syslog
```

## Database Schema

Tabel `monthly_report_broadcasts` menyimpan:

| Column            | Type      | Description                      |
| ----------------- | --------- | -------------------------------- |
| id                | bigint    | Primary key                      |
| monthly_report_id | bigint    | FK ke monthly_reports            |
| data_siswa_id     | bigint    | FK ke data_siswa                 |
| phone_number      | string    | Nomor tervalidasi (format 62xxx) |
| message           | text      | Isi pesan WhatsApp               |
| status            | enum      | pending / sent / failed          |
| response          | text      | Response JSON dari Fonnte API    |
| error_message     | text      | Error message jika gagal         |
| retry_count       | integer   | Jumlah retry (max 3)             |
| sent_at           | timestamp | Waktu berhasil terkirim          |
| timestamps        | -         | created_at, updated_at           |

## File-File Terkait

```
app/
├── Models/
│   └── MonthlyReportBroadcast.php    # Model untuk log broadcast
├── Services/
│   └── WhatsAppNotificationService.php  # Service wrapper Fonnte API
├── Jobs/
│   └── SendMonthlyReportWhatsAppJob.php  # Queue job untuk async send
├── Observers/
│   └── MonthlyReportObserver.php     # Observer trigger broadcast
└── Console/Commands/
    └── TestWhatsAppBroadcast.php     # Command testing

database/migrations/
└── 2025_10_27_000001_create_monthly_report_broadcasts_table.php
```

## Support

Untuk pertanyaan atau issue, silakan:

1. Cek log file di `storage/logs/laravel.log`
2. Cek database `monthly_report_broadcasts` untuk tracking
3. Test manual dengan command `php artisan test:whatsapp-broadcast`

---

**Dibuat:** 27 Oktober 2025  
**Versi:** 1.0.0
