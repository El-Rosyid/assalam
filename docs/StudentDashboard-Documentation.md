# Dashboard & View Siswa - Update Documentation

**Tanggal:** 27 Oktober 2025

## 📋 Overview Perubahan

Implementasi dashboard khusus siswa dan perbaikan tampilan detail catatan perkembangan bulanan dengan layout 2 kolom.

## ✨ Fitur Baru

### 1. Dashboard Siswa (`StudentDashboard`)

Dashboard khusus untuk role siswa dengan komponen:

#### Widget Profil Siswa (`StudentProfileWidget`)

-   **Lokasi**: `app/Filament/Widgets/StudentProfileWidget.php`
-   **View**: `resources/views/filament/widgets/student-profile-widget.blade.php`
-   **Fitur**:
    -   Foto profil (placeholder dengan initial nama)
    -   Data identitas: NIS, NISN, Nama Lengkap, Kelas
    -   Data pribadi: Jenis Kelamin, Tempat/Tanggal Lahir, Agama
    -   Data orang tua: Nama Ayah dan Ibu
    -   Design responsif dengan grid layout
    -   Color-coded cards untuk setiap info

#### Widget Grafik Pertumbuhan (`StudentGrowthChartWidget`)

-   **Lokasi**: `app/Filament/Widgets/StudentGrowthChartWidget.php`
-   **Fitur**:
    -   Line chart menampilkan data tinggi dan berat badan
    -   Filter: Tinggi Badan saja, Berat Badan saja, atau Keduanya
    -   Data diambil dari tabel `growth_records`
    -   Dual Y-axis saat menampilkan keduanya
    -   Timeline otomatis (bulan + tahun)
    -   Interactive tooltip

### 2. Perbaikan View Detail Monthly Report

**File**: `app/Filament/Resources/MonthlyReportSiswaResource.php`

#### Perubahan Layout:

-   **Sebelumnya**: Layout vertikal (info di atas, textarea catatan, foto di bawah)
-   **Sekarang**: Layout 2 kolom horizontal
    -   **Kolom Kiri**: Section "Foto Kegiatan" dengan photo gallery
    -   **Kolom Kanan**: Section "Catatan dari Guru" dengan format prose

#### Detail Perubahan:

1. Header info (Bulan, Tahun, Guru, Kelas) dalam Grid 4 kolom
2. Main content dalam Grid 2 kolom
3. Foto gallery tetap menggunakan component `photo-gallery.blade.php` yang sudah ada
4. Catatan ditampilkan dengan styling prose (whitespace preserved)
5. Fallback message jika catatan kosong

### 3. Photo Gallery Component

**File**: `resources/views/components/photo-gallery.blade.php`

-   Menggunakan `Storage::url($photo)` untuk akses file
-   Grid responsif (2 cols mobile, 3 cols tablet, 4 cols desktop)
-   Hover effect dengan overlay
-   Modal preview untuk gambar full-size
-   Close modal: click outside, escape key, atau tombol close
-   Fallback UI jika tidak ada foto

## 🔧 Files Created/Modified

### Created:

```
app/
├── Filament/
│   ├── Pages/
│   │   └── StudentDashboard.php
│   └── Widgets/
│       ├── StudentProfileWidget.php
│       └── StudentGrowthChartWidget.php
resources/views/
├── filament/
│   ├── pages/
│   │   └── student-dashboard.blade.php
│   └── widgets/
│       └── student-profile-widget.blade.php
docs/
└── StudentDashboard-Documentation.md (this file)
```

### Modified:

```
app/Filament/Resources/MonthlyReportSiswaResource.php (form method)
```

## 🎯 Akses & Permissions

### StudentDashboard

-   **URL**: `/admin` (untuk siswa akan redirect ke StudentDashboard)
-   **Access**: Hanya user dengan relasi `siswa` (checked via `Auth::user()->siswa`)
-   **Navigation**: Muncul di sidebar hanya untuk siswa

### Widgets

-   **StudentProfileWidget**: Auto-visible di dashboard siswa
-   **StudentGrowthChartWidget**: Auto-visible di dashboard siswa
-   Keduanya memiliki `canView()` check untuk memastikan hanya siswa yang bisa akses

## 📊 Data Sources

### StudentProfileWidget

**Model**: `data_siswa`
**Fields**:

-   nama_lengkap, nis, nisn
-   jenis_kelamin, tanggal_lahir, tempat_lahir, agama
-   nama_ayah, nama_ibu, alamat
-   kelas (via relationship `kelasInfo`)

### StudentGrowthChartWidget

**Model**: `GrowthRecord`
**Fields**:

-   data_siswa_id (filter by logged-in siswa)
-   month, year (for x-axis labels)
-   tinggi_badan, berat_badan (for y-axis data)

### MonthlyReportSiswaResource (View)

**Model**: `monthly_reports`
**Fields**:

-   month, year, catatan, photos
-   Relationships: siswa, guru, kelas

## 🧪 Testing Checklist

-   [ ] Login sebagai siswa
-   [ ] Akses dashboard → verify profil widget tampil
-   [ ] Check data profil: NIS, NISN, nama, kelas, dll
-   [ ] Verify grafik pertumbuhan tampil (jika ada data GrowthRecord)
-   [ ] Test filter grafik: Tinggi, Berat, Keduanya
-   [ ] Akses "Catatan Perkembangan Saya" dari menu
-   [ ] Klik "Lihat Detail" pada salah satu record
-   [ ] Verify layout 2 kolom: foto kiri, catatan kanan
-   [ ] Click foto → verify modal preview buka
-   [ ] Close modal → ESC key, click outside, tombol close
-   [ ] Test responsive: mobile, tablet, desktop

## 🐛 Known Issues & Notes

1. **Foto Profil**: Saat ini menggunakan placeholder dengan initial nama. Untuk foto real, perlu:

    - Tambah field `foto` di tabel `data_siswa`
    - Update widget untuk load foto dari storage
    - Fallback ke initial jika foto tidak ada

2. **Storage Public Link**: Pastikan symlink storage sudah dibuat:

    ```bash
    php artisan storage:link
    ```

3. **Growth Records**: Jika siswa belum punya data pertumbuhan, chart akan kosong dengan message "No data available"

4. **Dashboard Default**: Jika ingin StudentDashboard jadi default untuk siswa, perlu config di `PanelProvider`:

    ```php
    ->pages([
        \App\Filament\Pages\StudentDashboard::class,
    ])

    ```

## 💡 Future Enhancements

1. **Upload Foto Profil**: Allow siswa upload foto profil sendiri
2. **More Widgets**:
    - Widget attendance summary (kehadiran)
    - Widget latest monthly reports
    - Widget academic achievements
3. **Interactive Chart**: Add zoom, pan, export to image
4. **Print Report**: Button untuk print/export profil + growth chart ke PDF
5. **Comparison**: Compare growth dengan rata-rata kelas/nasional

## 🔗 Related Documentation

-   [GrowthRecord Documentation](./GrowthRecord-Documentation.md)
-   [MonthlyReport Documentation](./MonthlyReport-Documentation.md)
-   [WhatsApp Broadcast Documentation](./WhatsApp-Broadcast-Documentation.md)

---

**Created by**: AI Assistant  
**Version**: 1.0.0  
**Last Updated**: 27 Oktober 2025
