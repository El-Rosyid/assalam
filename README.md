# 🏫 Sistem Manajemen Sekolah

Sistem informasi manajemen sekolah berbasis Laravel 10 + Filament v3 untuk sekolah di Indonesia.

## ✨ Fitur Utama

-   👨‍🎓 **Manajemen Siswa** - Data lengkap siswa dengan foto
-   👨‍🏫 **Manajemen Guru** - Data guru dan wali kelas
-   📚 **Manajemen Kelas** - Organisasi kelas per tahun ajaran
-   📝 **Penilaian Siswa** - Input dan tracking nilai akademik
-   📊 **Laporan Bulanan** - Laporan per kelas per bulan
-   📈 **Catatan Pertumbuhan** - Tracking tinggi, berat, BMI siswa
-   🔔 **Sistem Notifikasi** - Real-time notifications untuk admin
-   📄 **Raport PDF** - Generate raport dengan DomPDF
-   💬 **WhatsApp Broadcast** - Kirim laporan via WhatsApp (Fonnte)

## 🛠️ Tech Stack

-   **Laravel 10.x** - PHP Framework
-   **Filament v3.2** - Admin Panel
-   **MySQL** - Database
-   **DomPDF** - PDF Generation
-   **Spatie Permission** - Role Management
-   **Livewire v3** - Reactive Components
-   **Vite** - Asset Bundling

## 📋 Requirements

-   PHP 8.1+
-   MySQL 5.7+ / MariaDB 10.3+
-   Composer
-   Node.js & NPM
-   Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD

## 🚀 Quick Start (Development)

```bash
# Clone repository
git clone https://github.com/El-Rosyid/assalam.git
cd assalam

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database di .env
DB_DATABASE=sekolah
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed data (optional)
php artisan db:seed

# Build assets
npm run dev

# Start server
php artisan serve
```

Akses aplikasi di: `http://localhost:8000/admin`

## 📦 Production Deployment

### Quick Deploy:

```bash
# Upload files ke server
# Lalu jalankan:
chmod +x deploy.sh
./deploy.sh
```

### Manual Deploy:

Ikuti panduan lengkap di: **[docs/cPanel-Deployment-Guide.md](docs/cPanel-Deployment-Guide.md)**

### ⚡ Performance Optimization:

```bash
# PENTING: Jalankan di production!
php artisan filament:optimize
php artisan optimize
php artisan view:cache
```

**Expected performance:** Dashboard 50-70% lebih cepat!

Lihat detail: **[docs/Filament-Optimization-Summary.md](docs/Filament-Optimization-Summary.md)**

## 📚 Dokumentasi

### Deployment & Setup:

-   [cPanel Deployment Guide](docs/cPanel-Deployment-Guide.md) - Panduan deploy ke cPanel
-   [Filament Optimization](docs/Filament-Optimization-Summary.md) - Optimasi production

### Features:

-   [Growth Record System](docs/GrowthRecord-Documentation.md) - Sistem catatan pertumbuhan
-   [Report Card System](docs/Report-Card-Documentation.md) - Sistem raport
-   [Notification System](docs/Notification-System-Debug-Fix.md) - Sistem notifikasi
-   [WhatsApp Broadcast](docs/WhatsApp-Broadcast-Documentation.md) - Broadcast WhatsApp
-   [Monthly Report System](docs/MonthlyReportSystem-Implementation.md) - Laporan bulanan

### Database & Architecture:

-   [Database Refactoring](docs/Database-Refactoring-Recommendation.md)
-   [Hierarchical Structure](docs/Hierarchical-Structure-Design.md)
-   [Database Optimization](docs/Database-Optimization-Complete-Plan.md)

### Technical:

-   [Role Management](docs/Role-Management-Documentation.md)
-   [Foreign Key Best Practices](docs/Foreign-Key-Best-Practices.md)
-   [File Upload Implementation](docs/Filament-FileUpload-Implementation.md)

## 🔑 Default Login

Setelah migration & seeding:

```
Email: admin@sekolah.com
Password: password
```

⚠️ **Ganti password di production!**

## 🧪 Testing

```bash
# Run tests
php artisan test

# Test specific feature
php artisan test --filter=GrowthRecordNotification

# Test notifications
php artisan test:notifications --type=all
```

## 📊 Monitoring & Maintenance

```bash
# Check notifications
php artisan check:notifications

# Check database
php artisan db:show

# Process queue
php artisan queue:work

# Clear caches (development)
php artisan optimize:clear
php artisan filament:optimize-clear
```

## 🛡️ Security

-   ✅ Role-based access control (Spatie Permission)
-   ✅ Laravel folder di luar public_html (production)
-   ✅ CSRF protection
-   ✅ SQL injection prevention (Eloquent ORM)
-   ✅ XSS protection
-   ✅ Secure file uploads

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License

Proprietary - © 2025 Sistem Sekolah. All rights reserved.

## 👥 Team

-   **Developer** - [El-Rosyid](https://github.com/El-Rosyid)
-   **AI Assistant** - GitHub Copilot

## 🆘 Support

-   📧 Email: support@sekolah.com
-   📖 Documentation: [docs/](docs/)
-   🐛 Issues: [GitHub Issues](https://github.com/El-Rosyid/assalam/issues)

---

**Built with ❤️ using Laravel & Filament**

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>
