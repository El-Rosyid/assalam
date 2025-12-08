# ✅ PRE-DEPLOYMENT CHECKLIST

Cek satu per satu sebelum deploy:

## 1. Git Status

```powershell
git status
```

-   [ ] Tidak ada uncommitted changes
-   [ ] Semua file sudah di-commit

## 2. Branch

```powershell
git branch
```

-   [ ] Berada di branch `main`

## 3. Environment File

```powershell
Get-Content .env | Select-String "APP_KEY=base64:"
Get-Content .env | Select-String "APP_ENV"
Get-Content .env | Select-String "APP_DEBUG"
```

-   [ ] APP_KEY sudah diset
-   [ ] Untuk production: APP_ENV=production
-   [ ] Untuk production: APP_DEBUG=false

## 4. Database Connection

```powershell
php artisan migrate:status
```

-   [ ] Bisa connect ke database
-   [ ] Migrations sudah dijalankan

## 5. Storage Permissions (di server nanti)

-   [ ] `storage/` writable
-   [ ] `bootstrap/cache/` writable

## 6. Composer

```powershell
composer validate
```

-   [ ] composer.json valid

## 7. Build Assets

```powershell
Test-Path public\build
```

-   [ ] Assets sudah di-build (`npm run build`)

## 8. Queue Configuration

```powershell
Get-Content .env | Select-String "QUEUE_CONNECTION"
```

-   [ ] QUEUE_CONNECTION diset (database/sync/redis)
-   [ ] Jika database: siapkan queue worker

## 9. Push ke Repository

```powershell
git push origin main
```

-   [ ] Semua perubahan sudah di-push

## 10. Server Preparation

-   [ ] Server sudah siap (VPS/hosting)
-   [ ] Domain sudah pointing ke server
-   [ ] Database production sudah dibuat
-   [ ] .env production sudah dikonfigurasi
-   [ ] Web server (Apache/Nginx) sudah dikonfigurasi
-   [ ] SSL certificate sudah diinstall (recommended)

---

## 🚀 Ready to Deploy!

Jika semua checklist ✅, lanjut ke server dan run:

```bash
bash deploy.sh
```

Atau ikuti panduan lengkap di: `docs/Deployment-Guide.md`
