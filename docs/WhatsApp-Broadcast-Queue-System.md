# WhatsApp Broadcast Queue System - Dokumentasi Teknis

> **Catatan**: Dokumentasi ini menjelaskan sistem broadcast WhatsApp yang menggunakan Laravel Queue untuk mengirim laporan bulanan ke orang tua siswa.

## 📋 Daftar Isi

-   [Overview Sistem](#overview-sistem)
-   [Arsitektur Queue](#arsitektur-queue)
-   [Komponen Sistem](#komponen-sistem)
-   [Flow Pengiriman Pesan](#flow-pengiriman-pesan)
-   [Konfigurasi](#konfigurasi)
-   [Cara Kerja Queue](#cara-kerja-queue)
-   [Monitoring & Troubleshooting](#monitoring--troubleshooting)

---

## 🎯 Overview Sistem

Sistem broadcast WhatsApp pada TK ABA Assalam menggunakan **Laravel Queue System** dengan database driver untuk mengirim notifikasi WhatsApp kepada orang tua siswa tentang laporan perkembangan bulanan anak mereka.

### Fitur Utama

✅ **Asynchronous Processing** - Kirim pesan di background tanpa menghambat UI  
✅ **Auto Retry** - 3x percobaan otomatis jika gagal  
✅ **Error Handling** - Notifikasi admin jika ada kegagalan  
✅ **Phone Validation** - Validasi nomor telepon otomatis  
✅ **Logging** - Log lengkap untuk debugging

### Tech Stack

-   **Queue Driver**: Database (jobs table)
-   **WhatsApp Provider**: Fonnte API
-   **Framework**: Laravel 10.x
-   **Worker**: Laravel Queue Worker (php artisan queue:work)

---

## 🏗️ Arsitektur Queue

```
┌─────────────────────────────────────────────────────────────────┐
│                    BROADCAST INITIATION                         │
│  (Admin Creates/Sends Monthly Report via Resource)             │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                 BROADCAST PREPARATION                           │
│  1. Get all students with valid phone numbers                  │
│  2. Create MonthlyReportBroadcast records (status=pending)     │
│  3. Dispatch jobs to queue                                      │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                     JOBS TABLE                                  │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │  Job ID │ Queue │ Payload │ Attempts │ Available At    │  │
│  ├─────────┼───────┼─────────┼──────────┼─────────────────┤  │
│  │  1      │ default│ {...}   │    0     │ now()          │  │
│  │  2      │ default│ {...}   │    0     │ now()          │  │
│  │  3      │ default│ {...}   │    0     │ now()          │  │
│  └─────────────────────────────────────────────────────────┘  │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│               LARAVEL QUEUE WORKER                              │
│  php artisan queue:work --tries=3 --backoff=60                 │
│                                                                 │
│  ┌───────────────────────────────────────────────────────┐    │
│  │  Process jobs one by one from database                │    │
│  │  - Execute SendMonthlyReportWhatsAppJob                │    │
│  │  - If fail: retry after 60 seconds                    │    │
│  │  - Max 3 attempts                                      │    │
│  └───────────────────────────────────────────────────────┘    │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│           SendMonthlyReportWhatsAppJob::handle()                │
│                                                                 │
│  1. Get broadcast record                                        │
│  2. Validate phone number                                       │
│  3. Format message                                              │
│  4. Send via Fonnte API                                         │
│  5. Update broadcast status (sent/failed)                       │
└────────────────────┬────────────────────────────────────────────┘
                     │
        ┌────────────┴───────────┐
        │                        │
        ▼                        ▼
┌──────────────┐        ┌──────────────┐
│   SUCCESS    │        │    FAILED    │
│              │        │              │
│ markAsSent() │        │ markAsFailed()│
│ Log success  │        │ Auto retry    │
│              │        │ (max 3x)      │
└──────────────┘        └───────┬───────┘
                                │
                                ▼
                        ┌──────────────────┐
                        │  After 3 fails:  │
                        │  Notify Admin    │
                        └──────────────────┘
```

---

## 🧩 Komponen Sistem

### 1. Models

#### **MonthlyReportBroadcast** (`app/Models/MonthlyReportBroadcast.php`)

```php
// Menyimpan log pengiriman WhatsApp per siswa
protected $fillable = [
    'monthly_report_id',  // ID laporan bulanan
    'siswa_nis',          // NIS siswa
    'phone_number',       // Nomor telepon tujuan
    'message',            // Isi pesan
    'status',             // pending|sent|failed
    'response',           // Response dari API
    'error_message',      // Pesan error (jika ada)
    'retry_count',        // Jumlah percobaan ulang
    'sent_at'            // Waktu terkirim
];
```

**Status Flow**:

```
pending → sent      (berhasil)
pending → failed    (gagal setelah 3 retry)
```

**Methods**:

-   `markAsSent($response)` - Update status menjadi sent
-   `markAsFailed($error)` - Update status menjadi failed + increment retry_count

### 2. Jobs

#### **SendMonthlyReportWhatsAppJob** (`app/Jobs/SendMonthlyReportWhatsAppJob.php`)

**Properties**:

```php
public $tries = 3;      // Maksimal 3 percobaan
public $backoff = 60;   // Tunggu 60 detik antar retry
```

**Constructor**:

```php
__construct(int $monthlyReportId, int $broadcastId)
```

**Main Methods**:

```php
handle(WhatsAppNotificationService $whatsappService): void
```

-   Get broadcast record dari database
-   Validasi monthly report exists
-   Validasi nomor telepon (format Indonesia)
-   Format pesan WhatsApp
-   Kirim via Fonnte API
-   Update status (sent/failed)
-   Log hasil pengiriman

```php
failed(\Throwable $exception): void
```

-   Dipanggil setelah 3x percobaan gagal
-   Mark broadcast as failed
-   Kirim notifikasi ke admin (WhatsAppFailedNotification)
-   Log error lengkap

### 3. Services

#### **WhatsAppNotificationService** (`app/Services/WhatsAppNotificationService.php`)

**Methods**:

```php
formatMonthlyReportMessage(monthly_reports $monthlyReport): string
```

Format pesan dengan template:

```
🔔 *Notifikasi TK ABA Assalam*

Assalamualaikum Yth. Orang Tua/Wali dari *[Nama Siswa]*

Laporan perkembangan ananda *[Nama Siswa]*
Laporan bulan *[Bulan] [Tahun]* telah tersedia

Silahkan login ke sistem untuk melihat detail laporan.
Informasi lebih lanjut: [Website Sekolah]

Terima kasih atas perhatiannya.

_Pesan otomatis dari [Nama Sekolah]_
```

```php
validatePhoneNumber(?string $phoneNumber): ?string
```

Validasi & normalisasi nomor:

-   Remove non-numeric characters
-   Convert `0812...` → `62812...`
-   Add `62` prefix if missing
-   Validate minimum length (10 digits after country code)
-   Return `null` if invalid

```php
sendWhatsApp(string $phoneNumber, string $message): array
```

Kirim via Fonnte API:

```php
// Request
POST https://api.fonnte.com/send
Headers: Authorization: [FONNTE_TOKEN]
Body: {
    "target": "62812...",
    "message": "...",
    "countryCode": "62"
}

// Response
[
    'success' => true|false,
    'response' => [...],  // API response data
    'error' => '...'      // Error message (if failed)
]
```

### 4. Notifications

#### **InvalidPhoneNumberNotification**

Dikirim ke admin jika nomor telepon siswa invalid.

#### **WhatsAppFailedNotification**

Dikirim ke admin setelah 3x percobaan gagal.

**Target**: User dengan role `super_admin` atau `admin`

---

## 🔄 Flow Pengiriman Pesan

### Step-by-Step Process

#### **1. Admin Membuat/Mengirim Laporan Bulanan**

```php
// Di MonthlyReportBroadcastResource atau Action
public function sendBroadcast($recordId)
{
    $monthlyReport = monthly_reports::find($recordId);

    // Get siswa dengan nomor telepon valid
    $siswas = data_siswa::whereNotNull('telepon_ortu')
                        ->where('kelas', $monthlyReport->kelas_id)
                        ->get();

    foreach ($siswas as $siswa) {
        // 1. Create broadcast record
        $broadcast = MonthlyReportBroadcast::create([
            'monthly_report_id' => $monthlyReport->id,
            'siswa_nis' => $siswa->nis,
            'phone_number' => $siswa->telepon_ortu,
            'message' => '', // Will be formatted in job
            'status' => 'pending',
            'retry_count' => 0
        ]);

        // 2. Dispatch job ke queue
        SendMonthlyReportWhatsAppJob::dispatch(
            $monthlyReport->id,
            $broadcast->id
        );
    }
}
```

#### **2. Job Masuk ke Database Queue**

Job disimpan di table `jobs`:

```sql
INSERT INTO jobs (
    queue,
    payload,
    attempts,
    reserved_at,
    available_at,
    created_at
) VALUES (
    'default',
    '{"job":"...","data":{...}}', -- serialized job data
    0,
    NULL,
    NOW(),
    NOW()
);
```

#### **3. Queue Worker Mengambil Job**

```bash
# Queue worker berjalan di background
php artisan queue:work --tries=3 --backoff=60
```

Worker mengambil job dari database:

```sql
SELECT * FROM jobs
WHERE queue = 'default'
  AND reserved_at IS NULL
  AND available_at <= NOW()
ORDER BY id ASC
LIMIT 1
```

#### **4. Job Di-Execute**

```php
// Inside SendMonthlyReportWhatsAppJob::handle()
public function handle(WhatsAppNotificationService $whatsappService): void
{
    // 1. Get broadcast record
    $broadcast = MonthlyReportBroadcast::find($this->broadcastId);

    // 2. Get monthly report
    $monthlyReport = monthly_reports::find($this->monthlyReportId);

    // 3. Validate phone
    $validPhone = $whatsappService->validatePhoneNumber($broadcast->phone_number);
    if (!$validPhone) {
        $broadcast->markAsFailed('Nomor telepon tidak valid');
        // Notify admin...
        return;
    }

    // 4. Format message
    $message = $whatsappService->formatMonthlyReportMessage($monthlyReport);

    // 5. Send WhatsApp
    $result = $whatsappService->sendWhatsApp($validPhone, $message);

    // 6. Update status
    if ($result['success']) {
        $broadcast->markAsSent($result['response']);
        Log::info('WhatsApp sent', [...]);
    } else {
        $broadcast->markAsFailed($result['error']);
        Log::error('WhatsApp failed', [...]);

        // Throw exception untuk trigger retry
        if ($this->attempts() >= $this->tries) {
            throw new \Exception($result['error']);
        }
    }
}
```

#### **5a. Jika Berhasil**

```php
// Update broadcast record
$broadcast->update([
    'status' => 'sent',
    'response' => json_encode($apiResponse),
    'sent_at' => now()
]);

// Log success
Log::info('WhatsApp sent successfully', [
    'broadcast_id' => $broadcastId,
    'phone' => $phoneNumber,
    'siswa' => $siswa->nama
]);

// Job selesai, dihapus dari jobs table
DELETE FROM jobs WHERE id = ?;
```

#### **5b. Jika Gagal (Retry)**

```php
// Update broadcast record
$broadcast->update([
    'error_message' => $errorMessage,
    'retry_count' => $broadcast->retry_count + 1
]);

// Job dikembalikan ke queue dengan delay
UPDATE jobs SET
    attempts = attempts + 1,
    reserved_at = NULL,
    available_at = NOW() + 60 -- backoff 60 seconds
WHERE id = ?;
```

#### **5c. Jika Gagal Setelah 3x (Final Failure)**

```php
// Method failed() dipanggil
public function failed(\Throwable $exception): void
{
    $broadcast->markAsFailed($exception->getMessage());

    // Notify admin
    $adminUsers = User::role(['super_admin', 'admin'])->get();
    foreach ($adminUsers as $admin) {
        $admin->notify(new WhatsAppFailedNotification(
            $siswa->nama,
            $exception->getMessage(),
            $this->tries
        ));
    }

    // Job dipindahkan ke failed_jobs table
    INSERT INTO failed_jobs (
        connection,
        queue,
        payload,
        exception,
        failed_at
    ) VALUES (...);

    DELETE FROM jobs WHERE id = ?;
}
```

---

## ⚙️ Konfigurasi

### 1. Environment Variables

```env
# File: .env

# Fonnte WhatsApp API
FONNTE_API_TOKEN=your-fonnte-api-token-here
FONNTE_URL=https://api.fonnte.com/send

# Queue Configuration
QUEUE_CONNECTION=database

# Admin Email for Notifications
ADMIN_EMAIL=admin@tkassalam.sch.id
```

### 2. Queue Configuration

```php
// File: config/queue.php

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,  // seconds
        'after_commit' => false,
    ],
],
```

### 3. Database Tables

#### **jobs** (queue storage)

```sql
CREATE TABLE `jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `attempts` tinyint unsigned NOT NULL,
    `reserved_at` int unsigned DEFAULT NULL,
    `available_at` int unsigned NOT NULL,
    `created_at` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
);
```

#### **failed_jobs** (failed queue storage)

```sql
CREATE TABLE `failed_jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `uuid` varchar(255) NOT NULL,
    `connection` text NOT NULL,
    `queue` text NOT NULL,
    `payload` longtext NOT NULL,
    `exception` longtext NOT NULL,
    `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
);
```

#### **monthly_report_broadcasts** (broadcast log)

```sql
CREATE TABLE `monthly_report_broadcasts` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `monthly_report_id` bigint unsigned NOT NULL,
    `siswa_nis` char(10) NOT NULL,
    `phone_number` varchar(20) NOT NULL,
    `message` text,
    `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `response` text,
    `error_message` text,
    `retry_count` int NOT NULL DEFAULT '0',
    `sent_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
);
```

---

## 🚀 Cara Kerja Queue

### Starting Queue Worker

**Development (Manual)**:

```bash
# Run worker dengan output verbose
php artisan queue:work --verbose --tries=3 --backoff=60

# Run dengan timeout (stop after 1 hour)
php artisan queue:work --timeout=60 --max-time=3600

# Process single job (for testing)
php artisan queue:work --once
```

**Production (Supervisor/Systemd)**:

#### Option 1: Supervisor (Recommended for VPS)

```ini
# File: /etc/supervisor/conf.d/laravel-worker.conf

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/laravel/artisan queue:work database --tries=3 --backoff=60 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/laravel/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*

# Check status
sudo supervisorctl status
```

#### Option 2: Systemd (Alternative for VPS)

```ini
# File: /etc/systemd/system/laravel-queue-worker.service

[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/laravel
ExecStart=/usr/bin/php artisan queue:work database --tries=3 --backoff=60 --max-time=3600
Restart=always
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl enable laravel-queue-worker
sudo systemctl start laravel-queue-worker
sudo systemctl status laravel-queue-worker
```

#### Option 3: Cron Job (For Shared Hosting/cPanel)

```bash
# File: crontab -e
# Run queue worker every minute, process max 5 jobs
* * * * * cd /path/to/laravel && php artisan queue:work --stop-when-empty --tries=3 --max-jobs=5 >> /dev/null 2>&1
```

⚠️ **Catatan untuk Shared Hosting**:

-   `--stop-when-empty`: Worker akan berhenti otomatis jika tidak ada job
-   `--max-jobs=5`: Process max 5 jobs per run (prevent long-running process)
-   Cron akan start worker baru setiap menit jika ada job

---

## 📊 Monitoring & Troubleshooting

### 1. Check Queue Status

```bash
# Via Artisan
php artisan queue:monitor database

# Via Database
mysql> SELECT queue, COUNT(*) as pending_jobs,
       MAX(attempts) as max_attempts
       FROM jobs
       GROUP BY queue;
```

### 2. Check Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Retry specific failed job
php artisan queue:retry [job-id]

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs (permanent delete)
php artisan queue:flush
```

### 3. Check Broadcast Status

```php
// Via Tinker
php artisan tinker

>>> MonthlyReportBroadcast::count()
>>> MonthlyReportBroadcast::pending()->count()
>>> MonthlyReportBroadcast::sent()->count()
>>> MonthlyReportBroadcast::failed()->count()

>>> MonthlyReportBroadcast::failed()->get(['siswa_nis', 'error_message', 'retry_count'])
```

### 4. Check Logs

```bash
# Laravel log
tail -f storage/logs/laravel.log | grep -i whatsapp

# Worker log (if using supervisor)
tail -f storage/logs/worker.log

# Filter by broadcast ID
grep "broadcast_id: 123" storage/logs/laravel.log
```

### 5. Common Issues & Solutions

#### Issue: "No query results for model [monthly_reports]"

**Cause**: Monthly report sudah dihapus sebelum job diproses  
**Solution**: Add null check di job handle()

```php
if (!$monthlyReport) {
    $broadcast->markAsFailed('Laporan bulanan tidak ditemukan');
    return;
}
```

#### Issue: "Nomor telepon tidak valid"

**Cause**: Format nomor salah atau kosong  
**Solution**:

-   Validasi nomor saat input data siswa
-   Update nomor di data siswa yang bermasalah

```php
// Check invalid phones
data_siswa::whereNull('telepon_ortu')
          ->orWhere('telepon_ortu', '')
          ->get(['nis', 'nama_lengkap', 'telepon_ortu']);
```

#### Issue: "Connection timeout" dari Fonnte

**Cause**: Koneksi internet VPS bermasalah  
**Solution**:

-   Check koneksi: `curl https://api.fonnte.com/send`
-   Increase HTTP timeout di WhatsAppNotificationService

```php
$response = Http::timeout(30)  // increase from default 5s
               ->withHeaders([...])
               ->post(...);
```

#### Issue: Jobs stuck di "reserved" state

**Cause**: Worker mati tanpa melepas job  
**Solution**:

```bash
# Clear stuck jobs
php artisan queue:restart

# Or manually
UPDATE jobs SET reserved_at = NULL WHERE reserved_at IS NOT NULL;
```

#### Issue: Too many failed jobs

**Cause**: API key invalid / expired  
**Solution**:

1. Check API key di .env: `FONNTE_API_TOKEN`
2. Test manually:

```bash
curl -X POST https://api.fonnte.com/send \
     -H "Authorization: YOUR_API_KEY" \
     -d "target=62812XXX" \
     -d "message=Test"
```

3. Retry failed jobs setelah fix: `php artisan queue:retry all`

---

## 🔐 Best Practices

### 1. Error Handling

```php
// Always check relationships exist
if (!$broadcast || !$monthlyReport) {
    Log::error('Missing data', [...]);
    return; // Exit gracefully
}

// Validate before processing
if (!$validPhone) {
    $broadcast->markAsFailed('Invalid phone');
    // Notify admin for data correction
    return;
}
```

### 2. Logging

```php
// Log with context
Log::info('WhatsApp sent', [
    'broadcast_id' => $id,
    'siswa_nis' => $nis,
    'phone' => $phone,
    'message_length' => strlen($message)
]);

// Log errors with stack trace
Log::error('API call failed', [
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString()
]);
```

### 3. Rate Limiting

```php
// Add delay between jobs (in Resource)
foreach ($siswas as $index => $siswa) {
    SendMonthlyReportWhatsAppJob::dispatch(...)
        ->delay(now()->addSeconds($index * 2)); // 2 seconds apart
}
```

### 4. Monitoring

```php
// Set up alerts for failed jobs
if (DB::table('failed_jobs')->count() > 10) {
    // Send alert to admin
    Notification::route('mail', env('ADMIN_EMAIL'))
        ->notify(new HighFailureRateAlert());
}
```

---

## 📚 Related Documentation

-   [WhatsApp Broadcast Documentation](WhatsApp-Broadcast-Documentation.md) - Panduan admin & user
-   [WhatsApp Broadcast Flowchart](WhatsApp-Broadcast-Flowchart.md) - Flowchart visual
-   [Laravel Queue Documentation](https://laravel.com/docs/10.x/queues) - Official docs
-   [Fonnte API Documentation](https://fonnte.com/api) - WhatsApp API provider

---

## 🆘 Support

Jika ada masalah dengan sistem broadcast WhatsApp:

1. **Check Logs**: `storage/logs/laravel.log`
2. **Check Queue**: `php artisan queue:monitor database`
3. **Check Failed Jobs**: `php artisan queue:failed`
4. **Test API**: Manual test via curl/Postman
5. **Contact**: IT Support TK ABA Assalam

---

**Last Updated**: December 2024  
**Version**: 1.0.0  
**Author**: IT Team TK ABA Assalam
