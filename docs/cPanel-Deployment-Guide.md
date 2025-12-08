# 🚀 Panduan Deploy Laravel ke cPanel

## 🎯 **Sistem Manajemen Sekolah dengan WhatsApp Broadcast**

Aplikasi Laravel ini dilengkapi dengan:

-   📱 **WhatsApp Broadcast Otomatis** - Kirim pesan ke orang tua siswa secara massal
-   📊 **Growth Record Management** - Tracking perkembangan siswa
-   📄 **Report Card Generator** - Generate raport PDF otomatis
-   🗑️ **Soft Delete System** - Data recovery & automatic file cleanup
-   👥 **Multi-role Access** - Admin, Guru, Wali Kelas, Orang Tua

**Fitur Unggulan: WhatsApp Broadcast API**  
✅ Penjadwalan otomatis | ✅ Priority queue | ✅ Retry mechanism | ✅ Bulk import Excel

---

## 📋 **Prerequisites**

### Yang Harus Disiapkan di cPanel:

-   ✅ **PHP 8.1+** (recommended 8.1.10 sesuai development)
-   ✅ **MySQL Database** (sudah ada database & user)
-   ✅ **Composer** (tersedia di cPanel atau via SSH)
-   ✅ **SSH Access** (sangat direkomendasikan untuk deployment)
-   ✅ **Node.js & NPM** (untuk build assets jika diperlukan)

### Ekstensi PHP yang Diperlukan:

```
BCMath
Ctype
Fileinfo
JSON
Mbstring
OpenSSL
PDO
PDO_MySQL
Tokenizer
XML
GD (untuk image processing)
Zip (untuk composer)
```

---

## 🗂️ **Struktur Folder di cPanel**

```
/home/username/
├── public_html/          # Document root (hanya berisi index.php & assets)
│   ├── index.php        # Entry point dari Laravel public/
│   ├── .htaccess
│   ├── css/
│   ├── js/
│   ├── build/
│   └── storage -> symlink ke ../laravel/storage/app/public
│
└── laravel/              # Aplikasi Laravel (di luar public_html)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/          # Ini yang akan di-copy ke public_html
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    ├── artisan
    └── composer.json
```

**⚠️ PENTING**: Folder `laravel/` berada DI LUAR `public_html` untuk keamanan!

---

## 🛠️ **Metode Deployment**

Dokumentasi ini menyediakan **2 cara deployment**:

### **A. Via cPanel File Manager + Terminal (Tanpa SSH)** ⭐ RECOMMENDED untuk pemula

-   ✅ Lebih mudah dan visual
-   ✅ Tidak perlu skill command line
-   ✅ Edit file langsung di browser
-   ✅ Upload via File Manager
-   ⚠️ Agak lambat untuk file besar

**Panduan ini FOKUS di metode ini!**

### **B. Via SSH (Advanced)**

-   ✅ Lebih cepat untuk file besar
-   ✅ Full control dengan command line
-   ❌ Butuh SSH access
-   ❌ Perlu familiar dengan Linux commands

**Pilih metode sesuai kebutuhan Anda. Jika tidak ada SSH, ikuti cara A (File Manager).**

---

## 📦 **Step-by-Step Deployment**

### **1. Persiapan File untuk Upload**

#### A. Buat ZIP file untuk upload

```bash
# Di local development (Windows)
# Hapus folder yang tidak perlu diupload
rmdir /s /q node_modules
rmdir /s /q vendor
del .env

# Buat ZIP dari seluruh project
# Gunakan WinRAR, 7-Zip, atau PowerShell:
Compress-Archive -Path * -DestinationPath sekolah-app.zip
```

#### B. Files yang TIDAK perlu diupload:

```
node_modules/          # Install ulang di server
vendor/               # Install ulang via composer
.env                  # Buat baru di server
.git/                 # Tidak perlu
storage/logs/*.log    # Tidak perlu
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
backup_*.sql          # Backup database local
*.zip                 # File backup
```

---

### **2. Upload & Extract di cPanel**

#### Via File Manager (Recommended - Tanpa SSH):

1. **Login ke cPanel**
2. **cPanel → File Manager**
3. Enable "Show Hidden Files": **Settings** (kanan atas) → centang **"Show Hidden Files (dotfiles)"** → Save
4. Navigate ke `/home/username/` (home directory)
5. **Upload** → Pilih `sekolah-app.zip`
6. Tunggu upload selesai (progress bar hijau)
7. Right-click file ZIP → **Extract**
8. Pilih folder tujuan: `/home/username/`
9. Click **Extract File(s)**
10. Setelah selesai, rename folder hasil extract menjadi `laravel`
    - Right-click folder → **Rename** → ketik `laravel`

**⚠️ Tips Upload File Besar:**

-   Jika ZIP > 100MB, pertimbangkan upload via FTP (FileZilla/WinSCP)
-   Atau split ZIP menjadi beberapa part menggunakan 7-Zip
-   Increase upload limit: cPanel → MultiPHP INI Editor → `upload_max_filesize = 256M`

#### Via SSH (Alternative - jika tersedia):

```bash
# Login via SSH
ssh username@yourdomain.com

# Upload via SCP dari komputer lokal
# scp sekolah-app.zip username@yourdomain.com:~/

# Extract di server
cd ~
unzip sekolah-app.zip -d laravel
```

---

### **3. Setup Database di cPanel**

#### A. Buat Database & User:

1. **cPanel → MySQL Databases**
2. **Create Database**: `username_sekolah`
3. **Create User**: `username_admin` dengan password kuat
4. **Add User to Database**: Pilih user & database, beri **ALL PRIVILEGES**

#### B. Import Database:

**Via phpMyAdmin**:

```
1. cPanel → phpMyAdmin
2. Pilih database username_sekolah
3. Tab "Import"
4. Choose File: backup_before_tier1_optimization_20251118.sql
5. Click "Go"
```

**Via SSH (lebih cepat untuk database besar)**:

```bash
# Upload SQL file dulu via SCP
scp backup_before_tier1_optimization_20251118.sql username@yourdomain.com:~/

# Import via command line
mysql -u username_admin -p username_sekolah < backup_before_tier1_optimization_20251118.sql
```

---

### **4. Konfigurasi Environment (.env)**

#### Via File Manager cPanel (Recommended - Tanpa SSH):

1. **cPanel → File Manager**
2. Navigate ke folder `laravel/`
3. **Pastikan "Show Hidden Files" sudah enabled** (Settings → Show Hidden Files)
4. Copy file `.env.example` → Right-click → **Copy**
5. Paste dengan nama `.env`
6. Right-click file `.env` → **Edit** atau **Code Editor**
7. Edit konfigurasi berikut:

#### Via SSH (Alternative):

```bash
cd ~/laravel
cp .env.example .env
nano .env  # atau vi .env
```

#### Konfigurasi Penting di `.env`:

```env
APP_NAME="Sistem Sekolah"
APP_ENV=production
APP_KEY=  # Akan di-generate nanti
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_sekolah
DB_USERNAME=username_admin
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# WhatsApp Configuration
FONNTE_TOKEN=your-fonnte-token
```

**⚠️ PENTING**:

-   `APP_DEBUG=false` di production!
-   Password database yang kuat
-   `APP_URL` sesuai domain Anda

---

### **5. Install Dependencies & Optimization**

#### Via cPanel Terminal (Recommended - Tanpa SSH):

1. **cPanel → Terminal** (atau Advanced → Terminal)
2. Jalankan commands berikut satu per satu:

```bash
cd ~/laravel

# Install Composer dependencies (optimized for production)
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# ⚠️ PENTING: Verifikasi konfigurasi path dulu!
php artisan tinker --execute="echo 'Public Path: ' . public_path() . PHP_EOL;"
# Harus menunjuk ke: /home/username/public_html

# Clear all existing caches first
php artisan optimize:clear
php artisan filament:optimize-clear

# ⚡ FILAMENT OPTIMIZATION (PENTING!)
php artisan filament:optimize
# Ini akan:
# ✅ Cache semua Filament components (Resources, Pages, Widgets)
# ✅ Cache Blade Icons
# ✅ Meningkatkan performance loading dashboard secara signifikan

# ⚡ LARAVEL OPTIMIZATION
php artisan optimize
# Ini akan:
# ✅ Cache config files
# ✅ Cache routes
# ✅ Cache events
# ✅ Optimize autoloader

# Cache views
php artisan view:cache

# Setup storage permissions
chmod -R 775 storage bootstrap/cache
```

#### Via cPanel Terminal (jika SSH tidak tersedia):

Gunakan **Terminal** di cPanel, lalu jalankan command yang sama.

**⚠️ PENTING - Kapan Menjalankan Optimasi:**

-   ✅ **SELALU** jalankan `filament:optimize` dan `optimize` di production
-   ✅ Jalankan setiap kali deploy update code baru
-   ❌ **JANGAN** jalankan di local development (akan block auto-discovery)

**🔄 Clear Cache (jika ada masalah):**

```bash
php artisan filament:optimize-clear  # Clear Filament cache
php artisan optimize:clear           # Clear Laravel cache
```

---

### **6. Setup Public Files**

#### A. Copy public folder ke public_html:

**Via File Manager cPanel:**

1. **cPanel → File Manager**
2. Navigate ke folder `laravel/`
3. Right-click folder `public` → **Copy**
4. Navigate ke `/home/username/`
5. **Paste** folder
6. Rename hasil paste menjadi `public_html`
    - Jika sudah ada `public_html` lama:
        - Rename `public_html` → `public_html_backup`
        - Baru rename `public` → `public_html`

**Via SSH (Alternative):**

```bash
# Backup public_html existing (jika ada)
cd ~
mv public_html public_html_backup

# Copy Laravel public folder
cp -R laravel/public public_html
```

---

#### B. Edit `public_html/index.php`:

**Via File Manager cPanel:**

1. **cPanel → File Manager**
2. Navigate ke `public_html/`
3. Right-click `index.php` → **Edit** atau **Code Editor**
4. Cari baris ini:

```php
// DARI (baris ~17 dan ~31):
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

5. **Ubah menjadi:**

```php
// MENJADI:
require __DIR__.'/../laravel/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel/bootstrap/app.php';
```

6. **Save Changes** (Ctrl+S atau tombol Save)

**Via SSH (jika tersedia):**

```bash
cd ~/public_html
nano index.php
# Edit dan save
```

#### C. Konfigurasi Path di Laravel (PENTING!)

**Edit `~/laravel/bootstrap/app.php`** untuk memberitahu Laravel lokasi public folder yang baru.

**Via File Manager cPanel:**

1. **cPanel → File Manager**
2. Navigate ke `laravel/bootstrap/`
3. Right-click `app.php` → **Edit** atau **Code Editor**
4. Lihat struktur file yang ada

**Via SSH (jika tersedia):**

```bash
cd ~/laravel/bootstrap
nano app.php
```

**Tambahkan konfigurasi sesuai versi Laravel:**

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// ⚙️ KONFIGURASI PATH UNTUK STRUKTUR TERPISAH
// Set custom public path karena public_html terpisah dari aplikasi
$app->usePublicPath(realpath(__DIR__.'/../../public_html'));

return $app;
```

**Atau jika menggunakan Laravel 10 style lama (`bootstrap/app.php` versi lama):**

```php
<?php

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// ⚙️ Set custom public path
$app->bind('path.public', function() {
    return realpath(base_path('/../public_html'));
});

// ... rest of the file
```

#### D. Update `.env` untuk Asset URLs (Opsional tapi Recommended):

**Via File Manager cPanel:**

1. **cPanel → File Manager**
2. Navigate ke folder `laravel/`
3. Right-click `.env` → **Edit**
    - **⚠️ Catatan:** File `.env` mungkin hidden, enable "Show Hidden Files (dotfiles)" di Settings
4. Scroll ke bagian bawah atau cari section yang sesuai

**Via SSH:**

```bash
cd ~/laravel
nano .env
```

**Tambahkan/update baris ini:**

```env
# Asset URL Configuration
ASSET_URL=https://yourdomain.com
APP_URL=https://yourdomain.com

# Filesystem
FILESYSTEM_DISK=public
```

#### C. Setup Storage Link:

```bash
cd ~/laravel
php artisan storage:link

# Atau manual jika command gagal:
cd ~/public_html
ln -s ../laravel/storage/app/public storage

# Verifikasi symlink
ls -la storage
# Should show: storage -> ../laravel/storage/app/public
```

#### E. Test Path Configuration:

```bash
cd ~/laravel
php artisan tinker
```

Jalankan di tinker:

```php
// Test public path
echo public_path();
// Should show: /home/username/public_html

// Test storage path
echo storage_path();
// Should show: /home/username/laravel/storage

// Test base path
echo base_path();
// Should show: /home/username/laravel

// Test asset URL
echo asset('css/app.css');
// Should show: https://yourdomain.com/css/app.css

exit
```

---

### **7. Setup .htaccess di public_html**

Buat/edit `public_html/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Disable directory browsing
Options -Indexes

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP settings (sesuaikan dengan kebutuhan)
<IfModule mod_php8.c>
    php_value upload_max_filesize 64M
    php_value post_max_size 64M
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 256M
</IfModule>
```

---

### **8. Enable OPcache (Performance Boost 2-3x)**

OPcache menyimpan compiled PHP code di memory untuk performance maksimal.

#### Via cPanel → Select PHP Version:

1. **cPanel → Select PHP Version** atau **MultiPHP INI Editor**
2. **Enable OPcache** dengan setting berikut:

```ini
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

#### Verifikasi OPcache Enabled:

```bash
php -i | grep opcache
# Atau buat file phpinfo.php di public_html:
<?php phpinfo(); ?>
# Lalu akses: https://yourdomain.com/phpinfo.php
# Cari section "Zend OPcache"
# ⚠️ HAPUS file ini setelah cek!
```

**📊 Expected Performance Improvement:**

-   Dashboard load: **2-3x lebih cepat**
-   API response: **30-50% lebih cepat**
-   Memory usage: **Lebih efisien**

---

### **9. Build Assets (Optional jika ada perubahan)**

Jika Anda mengubah CSS/JS di resources:

```bash
# Di local, build production assets
npm install
npm run build

# Upload folder public/build ke public_html/build
# Via SCP atau File Manager cPanel
```

---

### **10. Setup Queue Worker (PENTING untuk Notifications!)**

Sistem ini menggunakan **Queue & Scheduled Tasks** untuk:

-   📱 WhatsApp broadcast otomatis
-   🗑️ Auto-cleanup data terhapus
-   💾 Backup otomatis

**HANYA PERLU 1 CRON JOB ini:**

#### A. Via Cron Job (Recommended untuk cPanel):

**cPanel → Cron Jobs**, tambahkan **HANYA SATU** ini:

```bash
* * * * * cd ~/laravel && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Apa yang Jalan Otomatis:**

```
✅ whatsapp:send-scheduled    → Setiap menit
✅ whatsapp:retry-failed      → Setiap 5 menit
✅ students:cleanup-deleted   → Setiap Minggu (Minggu 02:00)
✅ backup:academic-year       → Setiap akhir bulan
✅ queue:work                 → Process antrian WA
```

**Screenshot Setup di cPanel:**

1. Login cPanel
2. Cari "Cron Jobs" di search bar
3. **Common Settings**: Custom
4. **Minute**: _(every minute)_
5. **Hour**: _(every hour)_
6. **Day**: _(every day)_
7. **Month**: _(every month)_
8. **Weekday**: _(every weekday)_
9. **Command**: `cd ~/laravel && /usr/bin/php artisan schedule:run >> /dev/null 2>&1`
10. Klik "Add New Cron Job"

**✅ DONE! Satu cron job ini mengatur SEMUA scheduled tasks!**

---

#### Alternative: Separate Cron Jobs (Tidak Recommended)

Jika ingin kontrol manual (lebih ribet):

```bash
# WhatsApp broadcast - setiap menit
* * * * * cd ~/laravel && /usr/bin/php artisan whatsapp:send-scheduled >> /dev/null 2>&1

# Retry failed - setiap 5 menit
*/5 * * * * cd ~/laravel && /usr/bin/php artisan whatsapp:retry-failed >> /dev/null 2>&1

# Cleanup deleted - setiap Minggu jam 02:00
0 2 * * 0 cd ~/laravel && /usr/bin/php artisan students:cleanup-deleted --days=90 --force >> /dev/null 2>&1

# Queue worker
* * * * * cd ~/laravel && /usr/bin/php artisan queue:work --stop-when-empty --max-time=60 >> /dev/null 2>&1
```

❌ **TIDAK DISARANKAN** - susah maintenance!

#### B. Via Supervisor (jika ada akses root):

Buat file `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /home/username/laravel/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=username
numprocs=2
redirect_stderr=true
stdout_logfile=/home/username/laravel/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

### **11. File Permissions (Security)**

```bash
# Set permission yang aman
cd ~/laravel

# Folders
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# Writable directories (PENTING untuk Filament cache!)
chmod -R 775 storage bootstrap/cache

# Artisan executable
chmod +x artisan

# Protect sensitive files
chmod 600 .env

# Public folder
cd ~/public_html
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
```

---

### **12. Testing Deployment**

#### A. Check Database Connection:

```bash
cd ~/laravel
php artisan tinker --execute="echo 'DB Connected: ' . (DB::connection()->getPdo() ? 'YES' : 'NO');"
```

#### B. Check Optimizations:

```bash
# Check Filament cache exists
ls -la bootstrap/cache/filament/panels/

# Check Laravel caches
php artisan route:list  # Should load from cache
php artisan config:show app.name  # Should show cached config

# Check OPcache status
php -i | grep "opcache.enable"
```

#### C. Test Notification System:

```bash
php artisan test:notifications --type=all
php artisan queue:work --stop-when-empty
php artisan check:notifications
```

#### D. Performance Testing:

**Sebelum optimasi vs Setelah:**

-   Dashboard load time: Cek dengan browser DevTools (Network tab)
-   Expected: **50-70% faster** setelah `filament:optimize`
-   Memory usage: Cek `php artisan about`

#### E. Browser Testing:

1. Akses `https://yourdomain.com`
2. Login dengan kredensial dari database
3. Test fitur-fitur utama:
    - Dashboard loading (should be significantly faster!)
    - Data siswa/guru
    - Input nilai/assessment
    - Growth records
    - Monthly reports
    - Notification bell icon
4. **Clear browser cache** jika assets tidak load

---

## 🔒 **Security Checklist**

-   [ ] `.env` file dengan `APP_DEBUG=false`
-   [ ] Database password yang kuat
-   [ ] File permissions correct (755/644)
-   [ ] `.env` permission 600
-   [ ] Folder `laravel/` di luar `public_html`
-   [ ] SSL Certificate installed (HTTPS)
-   [ ] Disable directory listing
-   [ ] Regular backup schedule
-   [ ] OPcache enabled
-   [ ] Filament optimized (`filament:optimize` executed)
-   [ ] Update Laravel & dependencies regularly

---

## 🔧 **Troubleshooting Common Issues**

### **1. "Target class [App\Models\Sekolah] does not exist"**

**Cause:** Case sensitivity di Linux (cPanel)

Windows: `sekolah.php` = `Sekolah.php` (case-insensitive)
Linux: `sekolah.php` ≠ `Sekolah.php` (case-sensitive) ❌

**Fix:**

```bash
# Pastikan nama file dan class sama (PascalCase)
# File: app/Models/Sekolah.php
# Class: class Sekolah extends Model

# Regenerate autoload
composer dump-autoload
php artisan optimize:clear
```

**📖 Detail:** Lihat [Case-Sensitivity-Fix-Sekolah-Model.md](Case-Sensitivity-Fix-Sekolah-Model.md)

### **2. "500 Internal Server Error"**

**Check error log:**

```bash
tail -f ~/laravel/storage/logs/laravel.log
```

**Common causes:**

-   Wrong file permissions (fix: `chmod -R 775 storage bootstrap/cache`)
-   Missing APP_KEY (fix: `php artisan key:generate`)
-   Wrong .env configuration
-   PHP version mismatch
-   Case sensitivity issues (models, controllers)

### **3. "404 Not Found" untuk semua routes**

**Fix .htaccess:**

-   Pastikan mod_rewrite enabled di cPanel
-   Check `public_html/.htaccess` ada dan correct
-   Check `AllowOverride All` di Apache config

### **4. CSS/JS tidak load**

**Fix asset paths:**

```bash
php artisan storage:link
php artisan config:clear
php artisan cache:clear
```

### **5. Database connection error**

**Check:**

-   Database name, username, password di .env
-   Database user has privileges
-   DB_HOST correct (biasanya `localhost` di cPanel)

### **5. Notification tidak muncul**

**Check queue:**

```bash
# Check jobs table
php artisan tinker --execute="echo 'Pending jobs: ' . DB::table('jobs')->count();"

# Process queue
php artisan queue:work --stop-when-empty

# Check cron job running
crontab -l
```

### **6. "Class not found" errors**

**Rebuild autoload & clear caches:**

```bash
composer dump-autoload
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan filament:optimize
php artisan optimize
```

### **7. Permission denied errors**

**Fix ownership:**

```bash
# Pastikan files owned by correct user
chown -R username:username ~/laravel
chown -R username:username ~/public_html

# Fix cache folder permissions
chmod -R 775 ~/laravel/storage ~/laravel/bootstrap/cache
```

### **8. Assets/CSS/JS path salah atau 404**

**Symptoms:** CSS tidak load, asset() mengarah ke path yang salah

**Fix:**

```bash
# 1. Verifikasi public path configuration
cd ~/laravel
php artisan tinker --execute="echo 'Public: ' . public_path() . PHP_EOL; echo 'Asset: ' . asset('test.css') . PHP_EOL;"

# 2. Jika path salah, pastikan bootstrap/app.php sudah di-update
nano bootstrap/app.php
# Tambahkan: $app->usePublicPath(realpath(__DIR__.'/../../public_html'));

# 3. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 4. Rebuild optimization
php artisan optimize
```

### **9. Symlink storage tidak bekerja**

**Via cPanel Terminal:**

```bash
# Hapus symlink lama
rm ~/public_html/storage

# Buat ulang manual
ln -s ~/laravel/storage/app/public ~/public_html/storage

# Verifikasi
ls -la ~/public_html/storage
# Should show: storage -> /home/username/laravel/storage/app/public
```

**Via File Manager (Alternative):**

1. Hapus folder/link `storage` di `public_html/` jika ada
2. Gunakan Terminal di cPanel untuk membuat symlink (command di atas)
3. Atau minta bantuan hosting support untuk create symlink

**Test:**

-   Upload file dari dashboard
-   Check apakah file muncul di `laravel/storage/app/public/`

### **10. Tidak bisa lihat file .env atau .htaccess (hidden files)**

**Via File Manager:**

1. **File Manager → Settings** (kanan atas)
2. Centang **"Show Hidden Files (dotfiles)"**
3. Click **Save**
4. Refresh halaman
5. File `.env`, `.htaccess`, `.gitignore` akan terlihat

### **11. Upload ZIP file terlalu besar (error)**

**Solutions:**

**A. Split ZIP file:**

```bash
# Di Windows, split jadi beberapa part
# Gunakan 7-Zip: Right-click → 7-Zip → Add to archive → Split to volumes (50MB each)
```

**B. Upload via FTP/SFTP:**

-   Gunakan FileZilla atau WinSCP
-   Connect ke server: Host, Username, Password dari cPanel
-   Upload file langsung tanpa ZIP

**C. Increase upload limit (via php.ini):**

-   cPanel → MultiPHP INI Editor
-   Pilih domain
-   Ubah: `upload_max_filesize = 256M` dan `post_max_size = 256M`

### **12. Slow dashboard after update**

**Re-run optimizations via cPanel Terminal:**

```bash
cd ~/laravel
php artisan filament:optimize-clear
php artisan filament:optimize
php artisan optimize
php artisan view:cache
```

---

## 📊 **Maintenance Commands**

### Daily/Weekly Tasks:

```bash
# Clear old logs (keep last 7 days)
find ~/laravel/storage/logs -name "*.log" -mtime +7 -delete

# Clear old notifications (30 days)
php artisan tinker --execute="DB::table('notifications')->where('created_at', '<', now()->subDays(30))->delete();"

# Optimize application
php artisan optimize
```

### Backup Database:

```bash
# Manual backup
mysqldump -u username_admin -p username_sekolah > ~/backups/sekolah_$(date +%Y%m%d).sql

# Via cron (daily at 2 AM)
0 2 * * * mysqldump -u username_admin -pYOURPASSWORD username_sekolah > ~/backups/sekolah_$(date +\%Y\%m\%d).sql
```

### Update Application:

```bash
cd ~/laravel

# Backup first!
mysqldump -u username_admin -p username_sekolah > ~/backup_before_update.sql

# Pull latest code (if using git)
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# ⚡ RE-OPTIMIZE AFTER UPDATE (PENTING!)
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan filament:optimize
php artisan optimize
php artisan view:cache

# Test queue worker
php artisan queue:restart
```

**📝 Note:** Setiap kali update code, WAJIB jalankan ulang optimization commands!

---

## 📱 **Post-Deployment Checklist**

### ✅ **Basic Functionality**

-   [ ] Website accessible via domain
-   [ ] SSL working (HTTPS)
-   [ ] Login functioning
-   [ ] Database connected
-   [ ] All pages loading

### ✅ **Optimization Verified**

-   [ ] `filament:optimize` executed successfully
-   [ ] `php artisan optimize` executed successfully
-   [ ] Cache files exist in `bootstrap/cache/filament/`
-   [ ] OPcache enabled (check `php -i | grep opcache`)
-   [ ] Dashboard loads **2-3x faster** than before optimization

### ✅ **Features Working**

-   [ ] Assets (CSS/JS/images) loading
-   [ ] File uploads working
-   [ ] Notifications appearing in bell icon
-   [ ] Queue worker running (cron job active)
-   [ ] PDF generation working
-   [ ] WhatsApp integration working
-   [ ] Email sending working (if used)

### ✅ **Maintenance Setup**

-   [ ] Backup cron jobs setup
-   [ ] Queue worker cron job active
-   [ ] Error monitoring setup
-   [ ] Log rotation configured

### ✅ **Performance Metrics**

-   [ ] Dashboard load: **< 2 seconds** (first load)
-   [ ] Dashboard load: **< 1 second** (subsequent loads)
-   [ ] PHP memory usage: **< 128MB** per request
-   [ ] No 500/404 errors in logs

---

## 🆘 **Support & Resources**

### Laravel Documentation:

-   https://laravel.com/docs/10.x/deployment
-   https://laravel.com/docs/10.x/queues

### Filament Documentation:

-   https://filamentphp.com/docs/3.x/panels/installation
-   https://filamentphp.com/docs/3.x/panels/deployment

### cPanel Documentation:

-   Check dengan hosting provider untuk specific guides

---

## ⚡ **Quick Reference - Optimization Commands**

### First Deploy:

```bash
cd ~/laravel
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan filament:optimize
php artisan optimize
php artisan view:cache
```

### Update/Redeploy:

```bash
cd ~/laravel
git pull origin main  # or upload files
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan filament:optimize
php artisan optimize
php artisan view:cache
php artisan queue:restart
```

### Clear All Caches (troubleshooting):

```bash
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan cache:clear
php artisan view:clear
```

### Performance Check:

```bash
# Check Filament cache
ls -la bootstrap/cache/filament/panels/

# Check OPcache
php -i | grep "opcache.enable"

# Check Laravel caches
php artisan about
```

---

## 🎉 **Deployment Successful!**

Jika semua langkah diikuti dengan benar, aplikasi Laravel Anda seharusnya sudah berjalan dengan baik di cPanel dengan **performance optimal**.

**Testing final**:

1. Login sebagai admin
2. Check notification bell (should load fast!)
3. Test semua fitur utama
4. Verify dashboard load time < 2 seconds

**🔥 Tips**:

-   Simpan dokumentasi ini dan backup `.env` file di tempat aman!
-   Jalankan `filament:optimize` setiap kali update code
-   Monitor performance dengan browser DevTools
-   Setup error monitoring (Sentry/Bugsnag) untuk production

---

## 📚 **Related Documentation**

Setelah deployment selesai, baca dokumentasi berikut untuk fitur-fitur sistem:

### **WhatsApp Broadcast System:**

-   📱 [`WhatsApp-Cron-Job-Setup.md`](./WhatsApp-Cron-Job-Setup.md) - Setup cron job untuk WA API (5 menit)
-   🎨 [`WhatsApp-Cron-Visual-Guide.md`](./WhatsApp-Cron-Visual-Guide.md) - Visual guide dengan screenshot ASCII
-   📡 [`WhatsApp-Broadcast-Documentation.md`](./WhatsApp-Broadcast-Documentation.md) - Dokumentasi lengkap fitur broadcast

### **Data Management:**

-   🗑️ [`Student-SoftDelete-FileManagement.md`](./Student-SoftDelete-FileManagement.md) - Soft delete & file cleanup system
-   📊 [`Data-Deletion-Image-Cleanup-Guide.md`](./Data-Deletion-Image-Cleanup-Guide.md) - Penjelasan cascade deletion & cleanup

### **Academic Features:**

-   📝 [`GrowthRecord-Documentation.md`](./GrowthRecord-Documentation.md) - Growth record management
-   📋 [`Report-Card-Documentation.md`](./Report-Card-Documentation.md) - Report card generation
-   📊 [`MonthlyReportSystem-Implementation.md`](./MonthlyReportSystem-Implementation.md) - Monthly report system

### **Development & Maintenance:**

-   🔧 [`Database-Optimization-Complete-Plan.md`](./Database-Optimization-Complete-Plan.md) - Database optimization
-   🔐 [`Role-Management-Documentation.md`](./Role-Management-Documentation.md) - User roles & permissions
-   📁 [`File-Cleanup-Report.md`](./File-Cleanup-Report.md) - File cleanup procedures

---

**Last Updated:** December 1, 2024  
**Version:** 3.0.0 (Updated with WhatsApp & Soft Delete features)
