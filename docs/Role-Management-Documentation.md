# Dokumentasi Pembagian Role - Sistem Manajemen Sekolah

## Overview

Sistem ini menggunakan **Spatie Laravel Permission** untuk manajemen role dan permission. Terdapat 3 role utama dengan hak akses yang berbeda-beda.

---

## 🎭 Daftar Role

### 1. **Admin** (Super Admin)

**Deskripsi:** Role tertinggi dengan akses penuh ke seluruh sistem.

**Hak Akses:**

-   ✅ **Full Access** ke semua fitur dan menu
-   ✅ Manajemen data sekolah
-   ✅ Manajemen data guru
-   ✅ Manajemen data siswa
-   ✅ Manajemen data kelas
-   ✅ Manajemen tahun ajaran
-   ✅ Manajemen variabel penilaian (assessment variables)
-   ✅ Melihat semua penilaian siswa dari semua guru
-   ✅ Melihat semua catatan perkembangan bulanan
-   ✅ Melihat semua raport siswa
-   ✅ Manajemen user dan role

**Navigasi Menu:**

```
📊 Dashboard
├── 📚 Administrasi
│   ├── Data Sekolah
│   ├── Data Guru
│   ├── Data Siswa
│   ├── Data Kelas
│   └── Tahun Ajaran
├── 📝 Penilaian
│   ├── Variabel Penilaian
│   ├── Penilaian Siswa (semua kelas)
│   └── Catatan Perkembangan Bulanan (semua)
├── 📄 Raport
│   └── Raport Siswa (semua)
└── 👥 Users & Roles
    ├── Users
    └── Roles & Permissions
```

**Implementasi di Code:**

```php
// Di Resource canViewAny()
public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user && $user->hasRole('admin');
}
```

---

### 2. **Guru** (Teacher)

**Deskripsi:** Guru yang menjadi wali kelas dengan akses terbatas untuk kelasnya sendiri.

**Hak Akses:**

-   ✅ Melihat dashboard statistik kelasnya
-   ✅ Melihat data siswa **di kelasnya sendiri**
-   ✅ Input dan edit penilaian siswa **di kelasnya sendiri**
-   ✅ Input dan edit catatan perkembangan bulanan siswa **di kelasnya sendiri**
-   ✅ Input data pertumbuhan siswa **di kelasnya sendiri**
-   ✅ Input data kehadiran siswa **di kelasnya sendiri**
-   ✅ Melihat raport siswa **di kelasnya sendiri**
-   ❌ Tidak bisa akses data administrasi
-   ❌ Tidak bisa akses data guru lain
-   ❌ Tidak bisa akses penilaian kelas lain

**Navigasi Menu:**

```
📊 Dashboard
├── 📝 Penilaian
│   ├── Penilaian Siswa (kelas sendiri)
│   ├── Catatan Perkembangan (kelas sendiri)
│   ├── Data Pertumbuhan (kelas sendiri)
│   └── Kehadiran Siswa (kelas sendiri)
└── 📄 Raport
    └── Raport Siswa (kelas sendiri)
```

**Filter di Code:**

```php
// Contoh di StudentAssessmentResource
->modifyQueryUsing(function (Builder $query) {
    $user = auth()->user();
    if ($user && $user->guru) {
        $query->whereHas('kelas', function ($kelasQuery) use ($user) {
            $kelasQuery->where('walikelas_id', $user->guru->id);
        });
    }
    return $query;
})

// Check akses
public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user && $user->guru; // Cek apakah user memiliki relasi guru
}
```

---

### 3. **Siswa** (Student)

**Deskripsi:** Siswa dengan akses read-only untuk melihat data pribadinya sendiri.

**Hak Akses:**

-   ✅ Melihat dashboard pribadi
-   ✅ Melihat raport sendiri **read-only**
-   ✅ Melihat catatan perkembangan bulanan sendiri **read-only**
-   ✅ Melihat data pertumbuhan sendiri **read-only**
-   ✅ Melihat data kehadiran sendiri **read-only**
-   ✅ Klik foto untuk memperbesar (lightbox)
-   ❌ Tidak bisa edit atau hapus apapun
-   ❌ Tidak bisa akses data siswa lain
-   ❌ Tidak bisa akses data administrasi

**Navigasi Menu:**

```
📊 Dashboard Saya
└── 👤 Siswa
    ├── Raport Saya
    ├── Catatan Perkembangan Saya
    ├── Pertumbuhan Saya
    └── Kehadiran Saya
```

**Filter di Code:**

```php
// Contoh di MonthlyReportSiswaResource
->modifyQueryUsing(function (Builder $query) {
    $user = Auth::user();
    if ($user && $user->siswa) {
        return $query->where('data_siswa_id', $user->siswa->id);
    }
    return $query->whereRaw('1 = 0'); // Empty query jika bukan siswa
})

// Disable actions
public static function canCreate(): bool { return false; }
public static function canEdit($record): bool { return false; }
public static function canDelete($record): bool { return false; }

// Only show if siswa
public static function shouldRegisterNavigation(): bool
{
    $user = Auth::user();
    return $user && $user->siswa;
}
```

---

## 🔗 Relasi User dengan Role

### Struktur Database

**Tabel `users`:**

```sql
- id
- name
- username (unique)
- email
- password
- avatar (nullable)
```

**Relasi ke Data:**

-   **Admin:** Tidak punya relasi khusus, hanya role 'admin'
-   **Guru:** Punya relasi `user->guru` via `data_guru.user_id`
-   **Siswa:** Punya relasi `user->siswa` via `data_siswa.user_id`

### Model Relationship

**User.php:**

```php
public function guru()
{
    return $this->hasOne(data_guru::class, 'user_id');
}

public function siswa()
{
    return $this->hasOne(data_siswa::class, 'user_id');
}
```

**data_guru.php:**

```php
public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

public function kelasAsWali()
{
    return $this->hasMany(data_kelas::class, 'walikelas_id');
}
```

**data_siswa.php:**

```php
public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

public function kelas()
{
    return $this->belongsTo(data_kelas::class, 'kelas', 'id');
}
```

---

## 🛡️ Pattern Implementasi Role

### 1. **Menampilkan Menu Berdasarkan Role**

**Di Resource:**

```php
public static function shouldRegisterNavigation(): bool
{
    $user = Auth::user();

    // Hanya untuk admin
    return $user && $user->hasRole('admin');

    // Atau hanya untuk guru
    return $user && $user->guru;

    // Atau hanya untuk siswa
    return $user && $user->siswa;
}
```

### 2. **Filter Data Berdasarkan Role**

**Query Scope:**

```php
// Di Resource table()
->modifyQueryUsing(function (Builder $query) {
    $user = auth()->user();

    if ($user->hasRole('admin')) {
        // Admin lihat semua
        return $query;
    }

    if ($user->guru) {
        // Guru hanya lihat kelasnya
        return $query->whereHas('kelas', function ($q) use ($user) {
            $q->where('walikelas_id', $user->guru->id);
        });
    }

    if ($user->siswa) {
        // Siswa hanya lihat datanya sendiri
        return $query->where('data_siswa_id', $user->siswa->id);
    }

    return $query->whereRaw('1 = 0'); // Empty jika tidak ada role
})
```

### 3. **Disable Actions untuk Role Tertentu**

```php
// Di Resource
public static function canCreate(): bool
{
    $user = auth()->user();

    // Hanya admin yang bisa create
    return $user && $user->hasRole('admin');

    // Atau siswa tidak bisa create
    if ($user->siswa) return false;

    return true;
}

public static function canEdit($record): bool
{
    $user = auth()->user();

    // Siswa tidak bisa edit
    if ($user->siswa) return false;

    // Guru hanya bisa edit data kelasnya
    if ($user->guru) {
        return $record->kelas->walikelas_id === $user->guru->id;
    }

    return true;
}
```

---

## 📋 Checklist Implementasi Role di Resource Baru

Saat membuat Resource baru, pastikan mengimplementasikan:

-   [ ] **shouldRegisterNavigation()** - Tampilkan menu sesuai role
-   [ ] **canViewAny()** - Cek apakah role bisa akses resource
-   [ ] **canCreate()** - Cek apakah role bisa create
-   [ ] **canEdit()** - Cek apakah role bisa edit
-   [ ] **canDelete()** - Cek apakah role bisa delete
-   [ ] **modifyQueryUsing()** - Filter data sesuai role
-   [ ] **navigationGroup** - Kelompokkan menu sesuai role

---

## 🔧 Setup Role untuk User Baru

### Via Tinker:

```php
// Assign role admin
$user = User::find(1);
$user->assignRole('admin');

// Assign role guru (pastikan sudah ada di data_guru)
$user = User::find(2);
$user->assignRole('guru');
$guru = data_guru::where('user_id', $user->id)->first();

// Assign role siswa (pastikan sudah ada di data_siswa)
$user = User::find(3);
$user->assignRole('siswa');
$siswa = data_siswa::where('user_id', $user->id)->first();
```

### Via Seeder:

```php
// database/seeders/RoleSeeder.php
Role::create(['name' => 'admin']);
Role::create(['name' => 'guru']);
Role::create(['name' => 'siswa']);
```

---

## 🎯 Best Practices

1. **Selalu cek role di awal method**

    ```php
    $user = auth()->user();
    if (!$user || !$user->hasRole('admin')) {
        abort(403, 'Unauthorized');
    }
    ```

2. **Gunakan Policy untuk logic kompleks**

    ```php
    php artisan make:policy StudentAssessmentPolicy
    ```

3. **Log aktivitas penting**

    ```php
    Log::info('Admin deleted class', [
        'user_id' => auth()->id(),
        'class_id' => $record->id
    ]);
    ```

4. **Gunakan Gate untuk permission granular**
    ```php
    Gate::define('view-all-assessments', function ($user) {
        return $user->hasRole('admin');
    });
    ```

---

## 📞 Troubleshooting

**Q: Guru tidak bisa melihat kelasnya?**

-   Cek apakah `user->guru` relationship terisi
-   Cek apakah `data_kelas.walikelas_id` sesuai dengan `data_guru.id`

**Q: Siswa tidak bisa melihat raportnya?**

-   Cek apakah `user->siswa` relationship terisi
-   Cek apakah `data_siswa.user_id` sesuai dengan user yang login

**Q: Menu tidak muncul untuk role tertentu?**

-   Cek method `shouldRegisterNavigation()` di Resource
-   Cek apakah user sudah di-assign role dengan benar

---

## 📚 Referensi

-   [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/)
-   [Filament Authorization](https://filamentphp.com/docs/3.x/panels/users#authorization)
-   [Laravel Gates & Policies](https://laravel.com/docs/10.x/authorization)

---

**Last Updated:** November 12, 2025  
**Version:** 1.0  
**Author:** AI Assistant
