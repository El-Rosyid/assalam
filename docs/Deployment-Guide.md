# 🚀 Panduan Deployment - TK ABA ASSALAM System

## Prasyarat

### Server Requirements

-   PHP 8.2 atau lebih tinggi
-   MySQL 8.0 atau lebih tinggi
-   Composer
-   Node.js & NPM
-   Git
-   Web server (Apache/Nginx)

### Extensions PHP yang Diperlukan

```bash
php -m | grep -E "BCMath|Ctype|Fileinfo|JSON|Mbstring|OpenSSL|PDO|Tokenizer|XML|cURL|GD|ZIP"
```

### Persiapan Server

```bash
# Install PHP extensions (Ubuntu/Debian)
sudo apt-get install php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js & NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

---

## 📋 Langkah-langkah Deployment

### 1️⃣ Persiapan Lokal (Development)

#### A. Jalankan Pre-Deployment Check

```bash
bash scripts/pre-deploy-check.sh
```

#### B. Commit & Push Perubahan

```bash
git add .
git commit -m "Ready for deployment"
git push origin main
```

---

### 2️⃣ Setup di Server (Production)

#### A. Clone Repository

```bash
cd /var/www/
sudo git clone https://github.com/El-Rosyid/assalam.git
cd assalam
```

#### B. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/assalam
sudo chmod -R 755 /var/www/assalam
sudo chmod -R 775 /var/www/assalam/storage
sudo chmod -R 775 /var/www/assalam/bootstrap/cache
```

#### C. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

#### D. Setup Environment

```bash
# Copy .env.example ke .env
cp .env.example .env

# Edit .env untuk production
nano .env
```

**Konfigurasi `.env` Production:**

```dotenv
APP_NAME="TK ABA ASSALAM"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sekolah_production
DB_USERNAME=sekolah_user
DB_PASSWORD=STRONG_PASSWORD_HERE

QUEUE_CONNECTION=database

# WhatsApp Settings (Production)
WHATSAPP_API_URL=https://api.fonnte.com
WHATSAPP_API_TOKEN=YOUR_PRODUCTION_TOKEN

# Mail Settings (if using)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
```

#### E. Generate Application Key

```bash
php artisan key:generate
```

#### F. Run Migrations & Seeders

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=UserSeeder --force
```

#### G. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

### 3️⃣ Web Server Configuration

#### Apache (.htaccess sudah ada di public/)

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/assalam/public

    <Directory /var/www/assalam/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/assalam_error.log
    CustomLog ${APACHE_LOG_DIR}/assalam_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/assalam/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Enable Site & Restart

```bash
# Apache
sudo a2ensite assalam.conf
sudo a2enmod rewrite
sudo systemctl restart apache2

# Nginx
sudo ln -s /etc/nginx/sites-available/assalam /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

### 4️⃣ Setup Queue Worker (Penting untuk WhatsApp Broadcast!)

#### A. Install Supervisor

```bash
sudo apt-get install supervisor
```

#### B. Buat Konfigurasi Supervisor

```bash
sudo nano /etc/supervisor/conf.d/assalam-worker.conf
```

**Isi file:**

```ini
[program:assalam-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/assalam/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/assalam/storage/logs/worker.log
stopwaitsecs=3600
```

#### C. Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start assalam-worker:*

# Check status
sudo supervisorctl status assalam-worker:*
```

---

### 5️⃣ Setup SSL (HTTPS) - Recommended

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache  # Apache
# OR
sudo apt-get install certbot python3-certbot-nginx   # Nginx

# Generate SSL Certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com  # Apache
# OR
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com   # Nginx

# Auto-renewal test
sudo certbot renew --dry-run
```

---

## 🔄 Update/Deploy Perubahan Baru

Setelah deployment pertama, gunakan script deploy untuk update:

```bash
cd /var/www/assalam
bash deploy.sh
```

Script ini akan otomatis:

1. ✅ Masuk maintenance mode
2. ✅ Pull code terbaru
3. ✅ Update dependencies
4. ✅ Build assets
5. ✅ Clear & cache config
6. ✅ Run migrations
7. ✅ Restart queue workers
8. ✅ Keluar dari maintenance mode

---

## 🔍 Monitoring & Maintenance

### Check Logs

```bash
# Application logs
tail -f /var/www/assalam/storage/logs/laravel.log

# Queue worker logs
tail -f /var/www/assalam/storage/logs/worker.log

# Web server logs
tail -f /var/log/apache2/assalam_error.log  # Apache
tail -f /var/log/nginx/error.log            # Nginx
```

### Monitor Queue

```bash
# Check queue status
php artisan queue:work --once --verbose

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Backup Database

```bash
# Manual backup
mysqldump -u sekolah_user -p sekolah_production > backup_$(date +%Y%m%d_%H%M%S).sql

# Setup automatic backup (crontab)
crontab -e
# Add line: 0 2 * * * mysqldump -u sekolah_user -p'PASSWORD' sekolah_production > /backups/db_$(date +\%Y\%m\%d).sql
```

### Clear Cache (if needed)

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🚨 Troubleshooting

### Error: "500 Internal Server Error"

1. Check logs: `tail -f storage/logs/laravel.log`
2. Check permissions: `sudo chmod -R 775 storage bootstrap/cache`
3. Clear cache: `php artisan cache:clear`

### Error: "Storage link not found"

```bash
php artisan storage:link
```

### Queue Jobs Not Processing

```bash
# Restart queue workers
sudo supervisorctl restart assalam-worker:*

# OR manually
php artisan queue:restart
php artisan queue:work --daemon
```

### WhatsApp Broadcast Not Sending

1. Check `.env`: `WHATSAPP_API_TOKEN` valid?
2. Check queue: `php artisan queue:work --once --verbose`
3. Check logs: `storage/logs/laravel.log`
4. Test API: `php artisan tinker` → `\App\Models\CustomBroadcast::first()->sendToQueue()`

---

## 📞 Support

Jika ada masalah deployment, hubungi developer atau buka issue di repository.

**Repository:** https://github.com/El-Rosyid/assalam

---

## ✅ Post-Deployment Checklist

-   [ ] Website dapat diakses via domain
-   [ ] SSL/HTTPS aktif (recommended)
-   [ ] Login admin berhasil
-   [ ] Database terisi dengan data seed
-   [ ] Queue worker berjalan (check: `sudo supervisorctl status`)
-   [ ] WhatsApp broadcast dapat dikirim
-   [ ] PDF report dapat digenerate
-   [ ] Backup database sudah dijadwalkan
-   [ ] Monitoring logs sudah disetup

---

**Last Updated:** December 8, 2025
