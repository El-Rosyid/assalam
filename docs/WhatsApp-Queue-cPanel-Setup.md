# Setup Queue Worker di cPanel untuk WhatsApp Broadcast

> **Panduan Praktis**: Cara menjalankan Laravel Queue Worker di shared hosting cPanel tanpa SSH access.

## 📋 Problem Statement

Sistem broadcast WhatsApp menggunakan **Laravel Queue** dengan database driver. Di local development, kita jalankan:

```bash
php artisan queue:work
```

Tapi di **shared hosting cPanel**:

-   ❌ Tidak ada SSH access
-   ❌ Tidak ada terminal persistent
-   ❌ Tidak bisa install Supervisor/Systemd
-   ⚠️ Worker harus jalan 24/7 untuk process queue jobs

**Solusi**: Gunakan **Cron Job** untuk menjalankan queue worker secara berkala.

---

## 🎯 Strategi untuk Shared Hosting

### Konsep: Queue Worker via Cron

Karena cPanel tidak support long-running process, kita gunakan strategi:

```
Cron Job (setiap menit)
    ↓
Run: php artisan queue:work --stop-when-empty --max-jobs=5
    ↓
Process max 5 jobs
    ↓
Worker stop otomatis
    ↓
Cron job akan start worker baru lagi (1 menit kemudian)
```

**Key Points**:

-   `--stop-when-empty`: Worker stop otomatis jika tidak ada job
-   `--max-jobs=5`: Process maksimal 5 jobs per run (prevent long-running)
-   Cron run **every minute** untuk memastikan jobs diproses cepat
-   Jika ada 100 jobs, akan diproses 5-5 per menit (selesai dalam ~20 menit)

---

## 🔧 Setup Step-by-Step di cPanel

### Step 1: Login ke cPanel

1. Buka browser: `https://yourdomain.com:2083` atau `https://yourdomain.com/cpanel`
2. Login dengan username & password cPanel

### Step 2: Buka Cron Jobs Menu

1. Di cPanel Dashboard, cari **"Cron Jobs"** (biasanya di section "Advanced")
2. Atau ketik "cron" di search box cPanel
3. Klik **Cron Jobs**

### Step 3: Setup Cron Job untuk Queue Worker

**A. Set Email untuk Notifikasi** (Optional tapi recommended)

```
Cron Email: admin@yourdomain.com
```

Klik **Update Email**

**B. Pilih Interval: Common Settings**

-   Pilih: **Every Minute ( \* \* \* \* \* )**

Atau manual set:

```
Minute:     *
Hour:       *
Day:        *
Month:      *
Weekday:    *
```

**C. Command to Run**

Masukkan command ini (untuk TK ABA Assalam):

```bash
/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1
```

**Penjelasan setiap parameter**:

-   `/usr/bin/php` → Path ke PHP binary di server
-   `/home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan` → **Full path ke file artisan** (dengan home directory)
-   `queue:work` → Command untuk process queue
-   `database` → Queue connection name (lihat config/queue.php)
-   `--stop-when-empty` → Worker stop otomatis jika tidak ada job (penting untuk shared hosting!)
-   `--tries=3` → Maksimal 3 percobaan jika job gagal
-   `--max-jobs=5` → Process maksimal 5 jobs per run (prevent timeout)
-   `>> /dev/null 2>&1` → Buang output (optional, hapus jika mau debugging)

**📁 Struktur Folder Lengkap Anda**:

```
/home/mhvpshnt/                              ← Home directory cPanel
└── domains/
    └── abaassalam.my.id/
        └── public_html/                     ← Document root
            ├── artisan                      ← FILE INI yang dipanggil cron job
            ├── .htaccess                    ← redirect web traffic ke public/
            ├── composer.json
            ├── app/
            ├── config/
            ├── database/
            ├── public/                      ← folder web accessible
            │   └── index.php                ← entry point website
            ├── routes/
            ├── storage/
            └── vendor/
```

**⚠️ PENTING - Full Path vs Relative Path**:

-   **Full Path** (untuk cron job): `/home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan` ✅
-   **Relative Path** (jika ada default): `domains/abaassalam.my.id/public_html/artisan` ✅
-   cPanel kadang sudah set default working directory ke `/home/mhvpshnt`
-   Web traffic via browser → redirect ke `/public` oleh `.htaccess`
-   Cron job via CLI → langsung akses `/artisan` dari root Laravel

**D. Klik "Add New Cron Job"**

---

## 📐 Cara Menemukan Path yang Benar

### 1. Path ke PHP Binary

**Via cPanel File Manager**:

1. Buka **Terminal** di cPanel (jika tersedia)
2. Jalankan: `which php` atau `whereis php`
3. Akan muncul path seperti:
    - `/usr/bin/php`
    - `/usr/local/bin/php`
    - `/opt/cpanel/ea-php81/root/usr/bin/php` (jika multiple PHP version)

**Via PHP Info** (jika tidak ada terminal):

1. Buat file `info.php` di public_html:
    ```php
    <?php phpinfo(); ?>
    ```
2. Akses via browser: `yourdomain.com/info.php`
3. Cari "PHP Path" atau "Loaded Configuration File"
4. **Hapus file ini setelah selesai** (security risk!)

**Common Paths**:

```bash
/usr/bin/php                           # Standard Linux
/usr/local/bin/php                     # Alternative
/opt/cpanel/ea-php81/root/usr/bin/php  # cPanel MultiPHP (PHP 8.1)
/opt/cpanel/ea-php82/root/usr/bin/php  # cPanel MultiPHP (PHP 8.2)
```

### 2. Path ke Laravel Artisan

**Via cPanel File Manager**:

1. Buka **File Manager**
2. Navigate ke folder Laravel
3. Klik kanan pada `artisan` → **Copy** → Lihat full path

**Common Paths**:

```bash
# Standard cPanel (username: cpanelusername)
/home/cpanelusername/public_html/artisan

# Jika Laravel di subfolder
/home/cpanelusername/public_html/sekolah/artisan

# Jika Laravel di luar public_html (best practice)
/home/cpanelusername/laravel/artisan

# Untuk TK ABA Assalam (struktur domains/)
/home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan
# dengan .htaccess redirect: RewriteRule ^(.*)$ public/$1 [L]

# Relative path (jika cPanel default ke home directory)
domains/abaassalam.my.id/public_html/artisan
```

**Cara Cek**:

1. Replace `username` dengan username cPanel Anda
2. Replace path sesuai struktur folder

---

## 🧪 Testing Cron Job

### Test 1: Cek Syntax Command

Buat test command dulu:

```bash
/usr/bin/php /home/username/public_html/artisan queue:work --help
```

Jika berhasil, akan muncul help text. Jika error, berarti path salah.

### Test 2: Jalankan Manual (via cPanel Terminal)

Jika cPanel punya Terminal:

```bash
cd /home/mhvpshnt/domains/abaassalam.my.id/public_html
php artisan queue:work database --stop-when-empty --max-jobs=1
```

Seharusnya:

-   Jika ada job: akan process 1 job, lalu stop
-   Jika tidak ada job: langsung stop dengan message "No jobs available"

**Jika tidak ada Terminal**, test via endpoint temporary (lihat section "Manual Test via Browser" di bawah).

### Test 3: Monitor Cron Execution

**A. Via Cron Job Logs**

```bash
# Ubah command untuk save log (sementara untuk testing)
/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /home/mhvpshnt/logs/queue-worker.log 2>&1
```

Lalu check file `/home/mhvpshnt/logs/queue-worker.log` via File Manager.

**Note**: Pastikan folder `logs` sudah dibuat di `/home/mhvpshnt/logs/`

**B. Via Laravel Logs**

```bash
# Check file: storage/logs/laravel.log
tail -f storage/logs/laravel.log
```

Cari log seperti:

```
[2024-12-01 10:15:00] local.INFO: WhatsApp sent successfully {"broadcast_id":123,...}
```

**C. Via Database**

Cek table `jobs`:

```sql
SELECT COUNT(*) FROM jobs;
```

-   Jika ada pending jobs tapi tidak berkurang setiap menit → cron tidak jalan
-   Jika berkurang 5-5 per menit → cron berjalan normal

---

## 📊 Monitoring Queue Status

### Via Filament Admin Panel

Buat widget atau page untuk monitoring (optional):

```php
// Di Filament Resource atau Widget
public function getQueueStats(): array
{
    return [
        'pending_jobs' => DB::table('jobs')->count(),
        'failed_jobs' => DB::table('failed_jobs')->count(),
        'pending_broadcasts' => MonthlyReportBroadcast::pending()->count(),
        'sent_broadcasts' => MonthlyReportBroadcast::sent()->count(),
        'failed_broadcasts' => MonthlyReportBroadcast::failed()->count(),
    ];
}
```

### Via Database Query (Langsung di cPanel phpMyAdmin)

```sql
-- Check pending jobs
SELECT COUNT(*) as pending_jobs FROM jobs;

-- Check failed jobs
SELECT COUNT(*) as failed_jobs FROM failed_jobs;

-- Check broadcast status
SELECT
    status,
    COUNT(*) as total,
    MAX(updated_at) as last_update
FROM monthly_report_broadcasts
GROUP BY status;

-- Check recent failures
SELECT
    siswa_nis,
    error_message,
    retry_count,
    updated_at
FROM monthly_report_broadcasts
WHERE status = 'failed'
ORDER BY updated_at DESC
LIMIT 10;
```

---

## 🔧 Troubleshooting Common Issues

### Issue 1: Cron Job Tidak Jalan

**Symptom**: Jobs tetap pending di database, tidak berkurang

**Debug Steps**:

1. **Cek Cron Log** (jika enabled email notification):

    - Check email yang di-set di Cron Email
    - Akan ada email error jika cron fail

2. **Test Command Manual**:

    ```bash
    # Via cPanel Terminal atau SSH (jika ada)
    cd /home/username/public_html
    php artisan queue:work database --stop-when-empty --max-jobs=1 -vvv
    ```

    Error messages:

    - `command not found` → Path PHP salah
    - `could not open input file` → Path artisan salah
    - `Class not found` → Composer autoload issue

3. **Fix Common Path Issues**:

    ```bash
    # Try different PHP paths
    /usr/bin/php
    /usr/local/bin/php
    /opt/cpanel/ea-php81/root/usr/bin/php
    php  # Sometimes works in cPanel
    ```

4. **Check File Permissions**:
    ```bash
    # Via File Manager atau Terminal
    chmod 755 artisan
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    ```

### Issue 2: "No such file or directory"

**Error**:

```
/usr/bin/php: No such file or directory: /home/username/public_html/artisan
```

**Solution**:

```bash
# Gunakan absolute path yang benar
# Cek via File Manager > Properties > Full Path
/home/actualusername/public_html/artisan

# ATAU cek via whoami (jika ada terminal)
whoami  # akan tampilkan username yang benar
```

### Issue 3: "Class not found" atau "Composer autoload"

**Error**:

```
Class 'App\Jobs\SendMonthlyReportWhatsAppJob' not found
```

**Solution**:

```bash
# Re-generate autoload files
cd /home/username/public_html
composer dump-autoload

# Atau via cron, tambahkan sebelum queue:work
cd /home/username/public_html && composer dump-autoload && php artisan queue:work...
```

### Issue 4: Jobs Stuck in "Reserved" State

**Symptom**: Jobs di table `jobs` punya `reserved_at` tapi tidak berubah

**Cause**: Worker crashed sebelum selesai process job

**Solution**:

```sql
-- Clear stuck jobs (via phpMyAdmin)
UPDATE jobs SET reserved_at = NULL WHERE reserved_at IS NOT NULL;

-- Or via Laravel Tinker (jika ada SSH)
php artisan tinker
>>> DB::table('jobs')->update(['reserved_at' => null]);
```

### Issue 5: "Queue connection [database] not configured"

**Error**:

```
Queue connection [database] not configured
```

**Solution**:

```bash
# Check .env
QUEUE_CONNECTION=database

# Check config/queue.php sudah ada connection 'database'

# Clear config cache
php artisan config:clear
```

### Issue 6: Cron Berjalan Tapi Jobs Tidak Terkirim

**Check List**:

1. **Cek API Key Fonnte**:

    ```bash
    # Via .env
    FONNTE_API_TOKEN=xxxxx

    # Test manual via curl
    curl -X POST https://api.fonnte.com/send \
         -H "Authorization: YOUR_TOKEN" \
         -d "target=628123456789" \
         -d "message=Test"
    ```

2. **Cek Nomor Telepon Siswa**:

    ```sql
    SELECT nis, nama_lengkap, telepon_ortu
    FROM data_siswa
    WHERE telepon_ortu IS NULL OR telepon_ortu = '';
    ```

3. **Cek Laravel Logs**:

    ```bash
    tail -100 storage/logs/laravel.log | grep -i error
    ```

4. **Cek Failed Jobs**:
    ```sql
    SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
    ```

---

## ⚙️ Configuration Best Practices

### 1. Optimal Cron Settings untuk Shared Hosting

```bash
# RECOMMENDED: Every minute, max 5 jobs (TK ABA Assalam)
* * * * * /usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1
```

**Why**:

-   `Every minute`: Balance antara cepat & resource usage
-   `--max-jobs=5`: Prevent long-running process (shared hosting limit biasanya 60-300 seconds)
-   `--tries=3`: Auto retry for transient errors
-   `>> /dev/null 2>&1`: Suppress output (prevent email spam)

### 2. Alternative: Every 5 Minutes (Low Traffic)

Jika broadcast tidak urgent (acceptable delay 5 menit):

```bash
# Every 5 minutes, max 25 jobs
*/5 * * * * /usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=25 >> /dev/null 2>&1
```

### 3. With Logging (for Debugging)

```bash
# Save logs untuk debugging (TK ABA Assalam)
* * * * * /usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /home/mhvpshnt/logs/queue-worker.log 2>&1
```

**⚠️ Warning**: Log file akan membesar cepat, **gunakan hanya untuk debugging**, lalu disable setelah selesai.

### 4. Prevent Duplicate Workers (Safety)

Jika cPanel tidak handle overlapping cron dengan baik:

```bash
# Add flock untuk prevent duplicate worker (TK ABA Assalam)
* * * * * /usr/bin/flock -n /tmp/queue-worker.lock /usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5
```

`flock` memastikan hanya 1 worker jalan per waktu.

---

## 🎯 Complete Setup Example

### Scenario: TK ABA Assalam di cPanel Hosting

**Informasi Server**:

-   cPanel Username: `mhvpshnt`
-   Home Directory: `/home/mhvpshnt/`
-   Domain: `abaassalam.my.id`
-   Laravel location: `/home/mhvpshnt/domains/abaassalam.my.id/public_html/`
-   Web root redirect: `.htaccess` redirect ke `/public` folder
-   PHP version: 8.1 atau 8.2 (sesuai server)

**Struktur Folder Lengkap**:

```
/home/mhvpshnt/                                 ← Home directory
└── domains/
    └── abaassalam.my.id/
        └── public_html/                        ← Document root
            ├── .htaccess                       ← redirect ke public/
            ├── artisan                         ← Laravel CLI (untuk cron job)
            ├── composer.json
            ├── app/
            ├── config/
            ├── database/
            ├── public/                         ← web accessible
            │   └── index.php                   ← entry point
            ├── storage/
            └── vendor/
```

**Cron Job Setting di cPanel**:

```
Minute:     *
Hour:       *
Day:        *
Month:      *
Weekday:    *

Command:
/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1
```

**Atau jika cPanel sudah set default directory ke `/home/mhvpshnt`**, gunakan relative path:

```bash
/usr/bin/php domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1
```

**Jika PHP version specific diperlukan**:

```bash
# PHP 8.1
/opt/cpanel/ea-php81/root/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1

# PHP 8.2
/opt/cpanel/ea-php82/root/usr/bin/php /home/mhvpshnt/domains/abaassalam.my.id/public_html/artisan queue:work database --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1
```

**Email untuk Notifikasi**: `admin@abaassalam.my.id`

**Testing**:

1. Create test broadcast via admin panel
2. Wait 1 minute
3. Check `monthly_report_broadcasts` table → status should change to `sent`
4. Check Laravel log: `storage/logs/laravel.log` → should see "WhatsApp sent successfully"

---

## 📚 Additional Resources

### Dokumentasi Terkait:

-   [WhatsApp Broadcast Queue System](WhatsApp-Broadcast-Queue-System.md) - Dokumentasi teknis queue
-   [WhatsApp Broadcast Documentation](WhatsApp-Broadcast-Documentation.md) - User guide
-   [cPanel Deployment Guide](cPanel-Deployment-Guide.md) - General deployment guide

### Laravel Queue Documentation:

-   [Laravel Queue - Working With Jobs](https://laravel.com/docs/10.x/queues#running-the-queue-worker)
-   [Laravel Queue - Supervisor Configuration](https://laravel.com/docs/10.x/queues#supervisor-configuration)

### cPanel Resources:

-   [cPanel Cron Jobs Documentation](https://docs.cpanel.net/cpanel/advanced/cron-jobs/)
-   [MultiPHP Manager](https://docs.cpanel.net/cpanel/software/multiphp-manager/) - PHP version management

---

## 🆘 Support & Troubleshooting

### If Setup Not Working:

1. **Contact Hosting Provider**:

    - Tanyakan: Path PHP binary yang benar
    - Tanyakan: Execution time limit untuk cron job
    - Tanyakan: Apakah ada firewall yang block API call ke fonnte.com

2. **Test Components Separately**:

    ```bash
    # Test 1: PHP can run
    /usr/bin/php -v

    # Test 2: Artisan accessible
    /usr/bin/php /home/username/public_html/artisan list

    # Test 3: Queue:work command exists
    /usr/bin/php /home/username/public_html/artisan queue:work --help

    # Test 4: API connection
    curl https://api.fonnte.com/send
    ```

3. **Enable Debug Mode** (temporarily):

    ```env
    # .env
    APP_DEBUG=true
    LOG_LEVEL=debug
    ```

    Then check: `storage/logs/laravel.log`

4. **Manual Test via Browser** (create test endpoint):

    ```php
    // routes/web.php (temporary for testing)
    Route::get('/test-queue', function() {
        Artisan::call('queue:work', [
            'connection' => 'database',
            '--stop-when-empty' => true,
            '--max-jobs' => 1
        ]);
        return Artisan::output();
    });
    ```

    Access: `yourdomain.com/test-queue`  
    **Delete this route after testing!**

---

## ✅ Checklist Final

Sebelum go-live, pastikan:

-   [ ] Cron job sudah dibuat di cPanel
-   [ ] Path PHP binary sudah benar
-   [ ] Path Laravel artisan sudah benar
-   [ ] Interval set ke **every minute** (`* * * * *`)
-   [ ] Command include `--stop-when-empty` dan `--max-jobs=5`
-   [ ] Email notification sudah di-set
-   [ ] Test broadcast berhasil terkirim
-   [ ] Check logs tidak ada error
-   [ ] Monitoring dashboard berfungsi
-   [ ] API key Fonnte sudah valid
-   [ ] Nomor telepon siswa sudah di-validasi

---

**Setup Selesai!** 🎉

Queue worker akan berjalan otomatis setiap menit via cron job. Broadcast WhatsApp akan terkirim secara asynchronous tanpa perlu buka terminal atau SSH.

---

**Last Updated**: December 2024  
**Version**: 1.0.0  
**Author**: IT Team TK ABA Assalam
