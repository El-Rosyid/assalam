# Rekomendasi Refactoring Database - Sistem Manajemen Sekolah

## 📋 Executive Summary

**Status Saat Ini:** Database sudah memiliki foreign key, namun struktur masih bisa ditingkatkan untuk menjadi lebih profesional dan normalized.

**Masalah yang Ditemukan:**

1. ❌ Kolom `kelas` di `data_siswa` menggunakan **string** bukan foreign key
2. ❌ Tidak ada tabel **junction/pivot** untuk relasi many-to-many
3. ❌ Beberapa tabel **tidak normalized** (contoh: alamat, data orang tua)
4. ❌ Naming convention **inconsistent** (data_guru vs users)
5. ❌ Missing **audit trail** dan **soft deletes** yang konsisten
6. ❌ Tidak ada tabel **reference/lookup** untuk data master (agama, pekerjaan, dll)

**Goal Refactoring:**
✅ Database normalization (3NF minimum)
✅ Proper foreign key relationships
✅ Junction tables untuk many-to-many
✅ Lookup tables untuk data master
✅ Consistent naming convention
✅ Audit trail lengkap

---

## 🔍 Analisis Struktur Saat Ini

### Tabel-Tabel yang Ada:

```
┌─────────────────────────────────────────────────┐
│              TABEL UTAMA                        │
├─────────────────────────────────────────────────┤
│ users                                           │
│ data_guru                                       │
│ data_siswa                                      │
│ data_kelas                                      │
│ sekolah                                         │
│ academic_year                                   │
│ assessment_variables                            │
│ student_assessments                             │
│ student_assessment_details                      │
│ growth_records                                  │
│ attendance_records                              │
│ monthly_reports                                 │
└─────────────────────────────────────────────────┘
```

### Masalah Spesifik:

#### 1. **data_siswa.kelas (String, bukan Foreign Key)**

**Current:**

```php
Schema::create('data_siswa', function (Blueprint $table) {
    $table->string('kelas'); // ❌ String field
    // ... other fields
});
```

**Problem:**

-   Tidak ada referential integrity
-   Data bisa inconsistent ("Kelas A" vs "kelas a" vs "A")
-   Sulit join dengan tabel lain
-   Sulit query untuk analytics

**Should Be:**

```php
Schema::create('data_siswa', function (Blueprint $table) {
    $table->foreignId('kelas_id') // ✅ Foreign key
        ->constrained('kelas')
        ->onDelete('restrict'); // Tidak bisa hapus kelas yang masih ada siswa
});
```

#### 2. **Tidak Ada Many-to-Many Tables**

**Missing Scenarios:**

**A. Siswa bisa pindah kelas (historis):**

```
Siswa John:
- Semester 1 (2024) → Kelas A
- Semester 2 (2024) → Kelas B
- Semester 1 (2025) → Kelas A

Current problem:
- Kolom kelas di data_siswa hanya simpan 1 nilai
- Tidak ada history perpindahan kelas
```

**B. Guru bisa mengajar di multiple kelas:**

```
Guru Maria:
- Wali Kelas: Kelas A
- Mengajar: Kelas A, Kelas B, Kelas C

Current problem:
- data_kelas.walikelas_id hanya 1 guru
- Tidak ada relasi guru mengajar di kelas lain
```

**C. Siswa bisa punya multiple assessment per tahun:**

```
Sudah handled dengan student_assessments ✅
```

#### 3. **Data Tidak Normalized**

**data_siswa - Terlalu Banyak Kolom:**

```php
$table->string('nama_ayah');           // Should be separate table
$table->string('nama_ibu');            // Should be separate table
$table->string('pekerjaan_ayah');      // Should be reference table
$table->string('pekerjaan_ibu');       // Should be reference table
$table->string('no_telp_ortu_wali');   // Should be separate table
$table->string('email_ortu_wali');     // Should be separate table
$table->string('alamat');              // Should be separate table
$table->string('asal_sekolah');        // Should be reference table
```

**Problem:**

-   Redundansi: Jika 2 siswa kakak-adik, data ortu disimpan 2x
-   Update anomaly: Update data ayah harus update semua record
-   Tidak bisa track multiple kontak orang tua

#### 4. **Missing Lookup Tables**

**Data yang Seharusnya Jadi Master Table:**

```
- Agama (Islam, Kristen, Hindu, Buddha, Konghucu)
- Pekerjaan (PNS, Swasta, Wiraswasta, dll)
- Jenis Kelamin (L, P)
- Status Kepegawaian Guru
- Tingkat Kelas (TK A, TK B, dll)
- Provinsi, Kabupaten, Kecamatan, Desa
```

**Current:**

```php
$table->enum('agama', ['Islam', 'Kristen', 'Hindu', 'Buddha', 'Konghucu']); // ❌
$table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']); // ❌
```

**Problem:**

-   Sulit extend (kalau ada agama baru harus alter table)
-   Tidak bisa multilingual
-   Tidak bisa track status active/inactive

---

## 🎯 Rekomendasi Struktur Database Baru

### A. Entity Relationship Diagram (ERD) Profesional

```
┌─────────────────────────────────────────────────────────────────┐
│                    CORE ENTITIES                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  users ←─────┐                                                 │
│              │                                                  │
│  roles ←─────┼── role_user (pivot)                            │
│              │                                                  │
│  permissions │                                                  │
│              │                                                  │
│  ┌───────────┴───────────┐                                     │
│  │                       │                                     │
│  ▼                       ▼                                     │
│  guru                  siswa                                    │
│  │                       │                                     │
│  │                       │                                     │
│  │                       ├──► orang_tua (parent_student pivot) │
│  │                       │                                     │
│  │                       ├──► alamat_siswa (addresses)         │
│  │                       │                                     │
│  │                       └──► kelas_siswa (class_student pivot)│
│  │                                 │                            │
│  │                                 ▼                            │
│  │                             kelas                            │
│  │                             │ │                              │
│  └─────────────────────────────┘ │                              │
│         (walikelas_id)            │                              │
│                                   ├──► tahun_ajaran             │
│                                   │                              │
│                                   └──► tingkat (ref table)      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                 ASSESSMENT ENTITIES                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  penilaian (assessments)                                        │
│  ├──► penilaian_detail (assessment_details)                    │
│  │     ├──► variabel_penilaian (assessment_variables)          │
│  │     └──► penilaian_foto (assessment_photos)                 │
│  │                                                              │
│  ├──► data_pertumbuhan (growth_records)                        │
│  └──► data_kehadiran (attendance_records)                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                   REFERENCE/LOOKUP TABLES                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ref_agama                                                      │
│  ref_pekerjaan                                                  │
│  ref_jenis_kelamin                                              │
│  ref_status_kepegawaian                                         │
│  ref_tingkat_kelas                                              │
│  ref_provinsi                                                   │
│  ref_kabupaten                                                  │
│  ref_kecamatan                                                  │
│  ref_desa                                                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔧 Detail Refactoring

### 1. **Tabel Users & Authentication**

**Struktur Baru:**

```php
// users - Keep as is (sudah bagus)
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('username')->unique();
    $table->string('email')->unique();
    $table->string('password');
    $table->string('avatar')->nullable();
    $table->enum('user_type', ['admin', 'guru', 'siswa', 'orang_tua']);
    $table->boolean('is_active')->default(true);
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});

// Relasi polymorphic untuk guru/siswa
// users.userable_type = 'App\Models\Guru'
// users.userable_id = guru.id
```

---

### 2. **Tabel Guru (Teacher)**

**Struktur Baru:**

```php
Schema::create('guru', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique(); // UUID untuk security
    $table->foreignId('user_id')
        ->constrained('users')
        ->onDelete('cascade');

    // Data Pribadi
    $table->string('nip', 50)->unique();
    $table->string('nuptk', 50)->unique()->nullable();
    $table->string('nama_lengkap');
    $table->string('tempat_lahir');
    $table->date('tanggal_lahir');

    // Reference Tables
    $table->foreignId('jenis_kelamin_id')
        ->constrained('ref_jenis_kelamin');
    $table->foreignId('agama_id')
        ->constrained('ref_agama');
    $table->foreignId('status_kepegawaian_id')
        ->nullable()
        ->constrained('ref_status_kepegawaian');

    // Contact
    $table->string('email')->unique()->nullable();
    $table->string('telepon', 20)->nullable();

    // Employment
    $table->date('tanggal_masuk')->nullable();
    $table->date('tanggal_keluar')->nullable();
    $table->enum('status', ['aktif', 'cuti', 'pensiun', 'resign'])->default('aktif');

    // Audit Trail
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('nip');
    $table->index('status');
    $table->index(['tanggal_masuk', 'tanggal_keluar']);
});

// Alamat Guru (One-to-Many)
Schema::create('alamat_guru', function (Blueprint $table) {
    $table->id();
    $table->foreignId('guru_id')
        ->constrained('guru')
        ->onDelete('cascade');

    $table->enum('jenis', ['domisili', 'ktp', 'surat_menyurat'])->default('domisili');
    $table->text('alamat_lengkap');
    $table->string('rt', 10)->nullable();
    $table->string('rw', 10)->nullable();

    // Reference Tables
    $table->foreignId('desa_id')->constrained('ref_desa');
    $table->foreignId('kecamatan_id')->constrained('ref_kecamatan');
    $table->foreignId('kabupaten_id')->constrained('ref_kabupaten');
    $table->foreignId('provinsi_id')->constrained('ref_provinsi');
    $table->string('kode_pos', 10)->nullable();

    $table->boolean('is_primary')->default(false);
    $table->timestamps();

    $table->index('guru_id');
    $table->index('jenis');
});
```

---

### 3. **Tabel Siswa (Student)**

**Struktur Baru:**

```php
Schema::create('siswa', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('user_id')
        ->constrained('users')
        ->onDelete('cascade');

    // Data Pribadi
    $table->string('nisn', 50)->unique();
    $table->string('nis', 50)->unique();
    $table->string('nama_lengkap');
    $table->string('nama_panggilan')->nullable();
    $table->string('tempat_lahir');
    $table->date('tanggal_lahir');

    // Reference Tables
    $table->foreignId('jenis_kelamin_id')
        ->constrained('ref_jenis_kelamin');
    $table->foreignId('agama_id')
        ->constrained('ref_agama');

    // Family Info
    $table->integer('anak_ke')->nullable();
    $table->integer('jumlah_saudara_kandung')->nullable();
    $table->integer('jumlah_saudara_tiri')->nullable();

    // Academic Info
    $table->string('asal_sekolah')->nullable();
    $table->date('tanggal_diterima');
    $table->foreignId('kelas_diterima_id')
        ->nullable()
        ->constrained('kelas');

    // Status
    $table->enum('status', [
        'aktif',
        'cuti',
        'pindah',
        'lulus',
        'keluar',
        'wafat'
    ])->default('aktif');
    $table->text('keterangan_status')->nullable();

    // Audit Trail
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('nisn');
    $table->index('nis');
    $table->index('status');
    $table->index('tanggal_diterima');
});

// Alamat Siswa (One-to-Many)
Schema::create('alamat_siswa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');

    $table->enum('jenis', ['domisili', 'kk', 'surat_menyurat'])->default('domisili');
    $table->text('alamat_lengkap');
    $table->string('rt', 10)->nullable();
    $table->string('rw', 10)->nullable();

    $table->foreignId('desa_id')->constrained('ref_desa');
    $table->foreignId('kecamatan_id')->constrained('ref_kecamatan');
    $table->foreignId('kabupaten_id')->constrained('ref_kabupaten');
    $table->foreignId('provinsi_id')->constrained('ref_provinsi');
    $table->string('kode_pos', 10)->nullable();

    $table->boolean('is_primary')->default(false);
    $table->timestamps();

    $table->index('siswa_id');
});
```

---

### 4. **Tabel Orang Tua (Parent) - NEW**

**Struktur Baru (Normalized):**

```php
// Tabel Orang Tua
Schema::create('orang_tua', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('user_id')
        ->nullable()
        ->constrained('users')
        ->onDelete('set null');

    // Data Pribadi
    $table->string('nik', 20)->unique()->nullable();
    $table->string('nama_lengkap');
    $table->string('tempat_lahir')->nullable();
    $table->date('tanggal_lahir')->nullable();

    // Reference Tables
    $table->foreignId('jenis_kelamin_id')
        ->constrained('ref_jenis_kelamin');
    $table->foreignId('agama_id')
        ->nullable()
        ->constrained('ref_agama');
    $table->foreignId('pekerjaan_id')
        ->nullable()
        ->constrained('ref_pekerjaan');
    $table->foreignId('pendidikan_terakhir_id')
        ->nullable()
        ->constrained('ref_pendidikan');

    // Contact
    $table->string('telepon', 20)->nullable();
    $table->string('email')->nullable();

    // Financial
    $table->decimal('penghasilan_bulanan', 15, 2)->nullable();

    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index('nik');
    $table->index('nama_lengkap');
});

// Pivot Table: Siswa - Orang Tua (Many-to-Many)
Schema::create('siswa_orang_tua', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');
    $table->foreignId('orang_tua_id')
        ->constrained('orang_tua')
        ->onDelete('cascade');

    // Jenis Hubungan
    $table->enum('hubungan', [
        'ayah_kandung',
        'ibu_kandung',
        'ayah_tiri',
        'ibu_tiri',
        'wali',
        'kakek',
        'nenek',
        'paman',
        'bibi',
        'lainnya'
    ]);

    $table->boolean('is_primary_contact')->default(false);
    $table->boolean('can_pickup')->default(true); // Boleh jemput anak
    $table->boolean('emergency_contact')->default(false);
    $table->integer('urutan_kontak')->default(1); // Prioritas kontak

    $table->timestamps();

    // Unique constraint: 1 siswa 1 hubungan hanya 1 record
    $table->unique(['siswa_id', 'orang_tua_id', 'hubungan']);
    $table->index('siswa_id');
    $table->index('orang_tua_id');
});

// Alamat Orang Tua
Schema::create('alamat_orang_tua', function (Blueprint $table) {
    $table->id();
    $table->foreignId('orang_tua_id')
        ->constrained('orang_tua')
        ->onDelete('cascade');

    $table->text('alamat_lengkap');
    $table->foreignId('desa_id')->constrained('ref_desa');
    $table->foreignId('kecamatan_id')->constrained('ref_kecamatan');
    $table->foreignId('kabupaten_id')->constrained('ref_kabupaten');
    $table->foreignId('provinsi_id')->constrained('ref_provinsi');
    $table->string('kode_pos', 10)->nullable();

    $table->boolean('is_primary')->default(false);
    $table->timestamps();
});
```

**Keuntungan:**

-   ✅ 1 orang tua bisa punya banyak anak (tidak redundan)
-   ✅ Data ortu cukup input 1x untuk kakak-adik
-   ✅ Support wali, orang tua tiri, dll
-   ✅ Multiple contact person per siswa
-   ✅ Track emergency contact

---

### 5. **Tabel Kelas (Class) - IMPROVED**

**Struktur Baru:**

```php
Schema::create('kelas', function (Blueprint $table) {
    $table->id();
    $table->string('kode_kelas', 20)->unique(); // TKA-2024-1
    $table->string('nama_kelas'); // Kelas A

    // Reference
    $table->foreignId('tingkat_id')
        ->constrained('ref_tingkat_kelas'); // TK A, TK B
    $table->foreignId('tahun_ajaran_id')
        ->constrained('tahun_ajaran')
        ->onDelete('restrict');

    // Wali Kelas
    $table->foreignId('walikelas_id')
        ->nullable()
        ->constrained('guru')
        ->onDelete('set null');

    // Capacity
    $table->integer('kapasitas_maksimal')->default(20);
    $table->integer('kapasitas_terisi')->default(0); // Auto-update via trigger/observer

    // Room Info
    $table->string('ruang_kelas')->nullable();

    // Status
    $table->enum('status', ['aktif', 'nonaktif', 'diarsipkan'])->default('aktif');

    // Audit Trail
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index('kode_kelas');
    $table->index('tahun_ajaran_id');
    $table->index('status');
});

// Pivot Table: Siswa - Kelas (Many-to-Many with History)
Schema::create('kelas_siswa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');
    $table->foreignId('kelas_id')
        ->constrained('kelas')
        ->onDelete('cascade');
    $table->foreignId('tahun_ajaran_id')
        ->constrained('tahun_ajaran');

    // Period
    $table->enum('semester', ['Ganjil', 'Genap']);
    $table->date('tanggal_masuk');
    $table->date('tanggal_keluar')->nullable();

    // Status
    $table->enum('status', [
        'aktif',
        'pindah_kelas',
        'naik_kelas',
        'tinggal_kelas',
        'keluar'
    ])->default('aktif');
    $table->text('keterangan')->nullable();

    // Attendance Stats (denormalization for performance)
    $table->integer('total_hadir')->default(0);
    $table->integer('total_sakit')->default(0);
    $table->integer('total_izin')->default(0);
    $table->integer('total_alfa')->default(0);

    $table->timestamps();

    // Unique: 1 siswa 1 kelas 1 semester
    $table->unique(['siswa_id', 'kelas_id', 'tahun_ajaran_id', 'semester']);
    $table->index('siswa_id');
    $table->index('kelas_id');
    $table->index('status');
});

// Guru Mengajar (Many-to-Many)
Schema::create('kelas_guru', function (Blueprint $table) {
    $table->id();
    $table->foreignId('guru_id')
        ->constrained('guru')
        ->onDelete('cascade');
    $table->foreignId('kelas_id')
        ->constrained('kelas')
        ->onDelete('cascade');
    $table->foreignId('mata_pelajaran_id')
        ->nullable()
        ->constrained('ref_mata_pelajaran');

    $table->enum('jenis', ['wali_kelas', 'pengajar', 'pendamping']);
    $table->timestamps();

    $table->unique(['guru_id', 'kelas_id', 'mata_pelajaran_id']);
});
```

**Keuntungan:**

-   ✅ History perpindahan kelas siswa tersimpan
-   ✅ 1 siswa bisa di multiple kelas (kalo pindah)
-   ✅ Track kapan masuk, kapan keluar kelas
-   ✅ Guru bisa mengajar di banyak kelas
-   ✅ Support wali kelas + guru pengajar

---

### 6. **Tabel Tahun Ajaran (Academic Year) - IMPROVED**

```php
Schema::create('tahun_ajaran', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique(); // 2024/2025-GANJIL
    $table->string('nama'); // Tahun Ajaran 2024/2025
    $table->year('tahun_mulai');
    $table->year('tahun_selesai');
    $table->enum('semester', ['Ganjil', 'Genap']);

    // Dates
    $table->date('tanggal_mulai');
    $table->date('tanggal_selesai');
    $table->date('tanggal_pembagian_raport')->nullable();
    $table->date('tanggal_penerimaan_raport')->nullable();

    // Status
    $table->boolean('is_active')->default(false); // Only 1 can be active
    $table->enum('status', ['draft', 'aktif', 'selesai', 'diarsipkan'])->default('draft');

    // Statistics (denormalized for performance)
    $table->integer('total_kelas')->default(0);
    $table->integer('total_siswa')->default(0);
    $table->integer('total_guru')->default(0);

    $table->timestamps();
    $table->softDeletes();

    $table->index('is_active');
    $table->index('status');
    $table->index(['tahun_mulai', 'tahun_selesai']);
});
```

---

### 7. **Reference Tables (Lookup)**

```php
// Jenis Kelamin
Schema::create('ref_jenis_kelamin', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 10)->unique(); // L, P
    $table->string('nama'); // Laki-laki, Perempuan
    $table->string('nama_en')->nullable(); // Male, Female
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Agama
Schema::create('ref_agama', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique(); // ISLAM, KRISTEN, dll
    $table->string('nama'); // Islam, Kristen Protestan, dll
    $table->string('nama_en')->nullable();
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Pekerjaan
Schema::create('ref_pekerjaan', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique();
    $table->string('nama');
    $table->text('deskripsi')->nullable();
    $table->enum('kategori', ['formal', 'informal', 'profesional', 'lainnya'])->nullable();
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Pendidikan
Schema::create('ref_pendidikan', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique(); // SD, SMP, SMA, S1, S2, S3
    $table->string('nama');
    $table->integer('level')->default(0); // Untuk sorting
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Tingkat Kelas
Schema::create('ref_tingkat_kelas', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique(); // TKA, TKB
    $table->string('nama'); // TK A, TK B
    $table->text('deskripsi')->nullable();
    $table->integer('level')->default(0);
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Status Kepegawaian
Schema::create('ref_status_kepegawaian', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique(); // PNS, PPPK, GTY, GTT
    $table->string('nama'); // PNS, PPPK, Guru Tetap Yayasan, dll
    $table->text('deskripsi')->nullable();
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Provinsi
Schema::create('ref_provinsi', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 10)->unique(); // 33 (Jawa Tengah)
    $table->string('nama');
    $table->timestamps();
});

// Kabupaten
Schema::create('ref_kabupaten', function (Blueprint $table) {
    $table->id();
    $table->foreignId('provinsi_id')->constrained('ref_provinsi');
    $table->string('kode', 10)->unique(); // 33.29 (Brebes)
    $table->string('nama');
    $table->timestamps();

    $table->index('provinsi_id');
});

// Kecamatan
Schema::create('ref_kecamatan', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kabupaten_id')->constrained('ref_kabupaten');
    $table->string('kode', 15)->unique();
    $table->string('nama');
    $table->timestamps();

    $table->index('kabupaten_id');
});

// Desa
Schema::create('ref_desa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kecamatan_id')->constrained('ref_kecamatan');
    $table->string('kode', 20)->unique();
    $table->string('nama');
    $table->timestamps();

    $table->index('kecamatan_id');
});
```

---

### 8. **Assessment Tables - IMPROVED**

```php
// Penilaian (Main Assessment)
Schema::create('penilaian', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    // Relations
    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');
    $table->foreignId('kelas_id')
        ->constrained('kelas')
        ->onDelete('restrict');
    $table->foreignId('guru_id')
        ->constrained('guru')
        ->onDelete('restrict');
    $table->foreignId('tahun_ajaran_id')
        ->constrained('tahun_ajaran')
        ->onDelete('restrict');

    // Period
    $table->enum('semester', ['Ganjil', 'Genap']);

    // Status
    $table->enum('status', [
        'draft',
        'in_progress',
        'review',
        'final',
        'published'
    ])->default('draft');

    // Timestamps
    $table->timestamp('mulai_dinilai_at')->nullable();
    $table->timestamp('selesai_dinilai_at')->nullable();
    $table->timestamp('published_at')->nullable();

    // Stats (denormalized)
    $table->integer('total_aspek')->default(0);
    $table->integer('aspek_dinilai')->default(0);
    $table->decimal('progress_persen', 5, 2)->default(0);

    // Audit Trail
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->foreignId('published_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    // Unique constraint
    $table->unique(['siswa_id', 'tahun_ajaran_id', 'semester']);
    $table->index(['tahun_ajaran_id', 'semester']);
    $table->index('status');
});

// Penilaian Detail
Schema::create('penilaian_detail', function (Blueprint $table) {
    $table->id();
    $table->foreignId('penilaian_id')
        ->constrained('penilaian')
        ->onDelete('cascade');
    $table->foreignId('variabel_penilaian_id')
        ->constrained('variabel_penilaian')
        ->onDelete('restrict');

    // Rating
    $table->string('rating', 10)->nullable(); // BB, MB, BSH, BSB
    $table->text('deskripsi')->nullable();
    $table->text('rekomendasi')->nullable(); // Saran untuk orang tua

    // Photos (akan pindah ke tabel terpisah)
    $table->json('foto_kegiatan')->nullable(); // Temporary, akan migrate

    // Status
    $table->enum('status', ['belum', 'draft', 'final'])->default('belum');
    $table->timestamp('dinilai_at')->nullable();

    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();

    // Unique: 1 penilaian 1 variabel
    $table->unique(['penilaian_id', 'variabel_penilaian_id']);
    $table->index('penilaian_id');
});

// Foto Penilaian (Separate table for photos)
Schema::create('penilaian_foto', function (Blueprint $table) {
    $table->id();
    $table->foreignId('penilaian_detail_id')
        ->constrained('penilaian_detail')
        ->onDelete('cascade');

    $table->string('file_path');
    $table->string('file_name');
    $table->string('mime_type')->nullable();
    $table->integer('file_size')->nullable(); // in bytes
    $table->integer('urutan')->default(0);
    $table->text('keterangan')->nullable();

    $table->timestamps();

    $table->index('penilaian_detail_id');
    $table->index('urutan');
});

// Variabel Penilaian (Assessment Variables)
Schema::create('variabel_penilaian', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique(); // NAM-01, FM-01, dll
    $table->string('nama');
    $table->text('deskripsi')->nullable();

    // Kategori
    $table->foreignId('kategori_penilaian_id')
        ->nullable()
        ->constrained('kategori_penilaian');

    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index('kode');
    $table->index('is_active');
});

// Kategori Penilaian (NEW)
Schema::create('kategori_penilaian', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 20)->unique(); // NAM, FM, KOG, BHS, SE, SENI
    $table->string('nama'); // Nilai Agama dan Moral, Fisik Motorik, dll
    $table->text('deskripsi')->nullable();
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

---

### 9. **Growth & Attendance - IMPROVED**

```php
// Data Pertumbuhan
Schema::create('data_pertumbuhan', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');
    $table->foreignId('tahun_ajaran_id')
        ->constrained('tahun_ajaran');

    // Period
    $table->tinyInteger('bulan'); // 1-12
    $table->year('tahun');

    // Measurements
    $table->decimal('berat_badan', 5, 2)->nullable(); // kg
    $table->decimal('tinggi_badan', 5, 2)->nullable(); // cm
    $table->decimal('lingkar_kepala', 5, 2)->nullable(); // cm
    $table->decimal('lingkar_lengan', 5, 2)->nullable(); // cm

    // Calculated
    $table->decimal('bmi', 5, 2)->nullable(); // Auto-calculated
    $table->string('kategori_bmi')->nullable(); // Underweight, Normal, Overweight

    // Notes
    $table->text('catatan')->nullable();

    // Measured by
    $table->foreignId('diukur_oleh')
        ->nullable()
        ->constrained('users');
    $table->timestamp('diukur_pada')->nullable();

    $table->timestamps();

    // Unique constraint
    $table->unique(['siswa_id', 'bulan', 'tahun']);
    $table->index(['siswa_id', 'tahun', 'bulan']);
});

// Data Kehadiran (Per Semester)
Schema::create('data_kehadiran', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');
    $table->foreignId('kelas_id')
        ->constrained('kelas')
        ->onDelete('restrict');
    $table->foreignId('tahun_ajaran_id')
        ->constrained('tahun_ajaran');
    $table->enum('semester', ['Ganjil', 'Genap']);

    // Attendance Stats
    $table->integer('hadir')->default(0);
    $table->integer('sakit')->default(0);
    $table->integer('izin')->default(0);
    $table->integer('alfa')->default(0);
    $table->integer('total_hari_efektif')->default(0);

    // Calculated
    $table->decimal('persentase_kehadiran', 5, 2)->default(0);

    $table->timestamps();

    // Unique constraint
    $table->unique(['siswa_id', 'tahun_ajaran_id', 'semester']);
    $table->index('siswa_id');
});

// Detail Kehadiran Harian (Optional, untuk tracking detail)
Schema::create('kehadiran_harian', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');
    $table->foreignId('kelas_id')
        ->constrained('kelas');
    $table->date('tanggal');

    $table->enum('status', ['hadir', 'sakit', 'izin', 'alfa', 'libur']);
    $table->time('jam_masuk')->nullable();
    $table->time('jam_keluar')->nullable();
    $table->text('keterangan')->nullable();

    $table->foreignId('dicatat_oleh')
        ->nullable()
        ->constrained('users');

    $table->timestamps();

    // Unique constraint
    $table->unique(['siswa_id', 'tanggal']);
    $table->index(['siswa_id', 'tanggal']);
    $table->index('status');
});
```

---

### 10. **Monthly Reports - IMPROVED**

```php
// Laporan Bulanan
Schema::create('laporan_bulanan', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->onDelete('cascade');
    $table->foreignId('kelas_id')
        ->constrained('kelas')
        ->onDelete('restrict');
    $table->foreignId('guru_id')
        ->constrained('guru')
        ->onDelete('restrict');
    $table->foreignId('tahun_ajaran_id')
        ->constrained('tahun_ajaran');

    // Period
    $table->tinyInteger('bulan'); // 1-12
    $table->year('tahun');

    // Content
    $table->text('catatan_perkembangan')->nullable();
    $table->text('catatan_kesehatan')->nullable();
    $table->text('kegiatan_unggulan')->nullable();
    $table->text('rekomendasi')->nullable();

    // Status
    $table->enum('status', ['draft', 'review', 'final', 'published'])->default('draft');
    $table->timestamp('published_at')->nullable();

    // Seen by parent
    $table->boolean('sudah_dibaca_ortu')->default(false);
    $table->timestamp('dibaca_ortu_at')->nullable();

    // Audit Trail
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->foreignId('published_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    // Unique constraint
    $table->unique(['siswa_id', 'bulan', 'tahun']);
    $table->index(['tahun_ajaran_id', 'bulan', 'tahun']);
    $table->index('status');
});

// Foto Laporan Bulanan
Schema::create('laporan_bulanan_foto', function (Blueprint $table) {
    $table->id();
    $table->foreignId('laporan_bulanan_id')
        ->constrained('laporan_bulanan')
        ->onDelete('cascade');

    $table->string('file_path');
    $table->string('file_name');
    $table->string('mime_type')->nullable();
    $table->integer('file_size')->nullable();
    $table->text('keterangan')->nullable();
    $table->integer('urutan')->default(0);

    $table->timestamps();

    $table->index('laporan_bulanan_id');
    $table->index('urutan');
});
```

---

## 📊 Migration Strategy

### Step-by-Step Migration Plan:

#### **Phase 1: Preparation (1-2 weeks)**

```bash
1. Backup database
2. Create new branch: feature/database-refactoring
3. Document current data mapping
4. Create seeder untuk reference tables
5. Test migrations di local environment
```

#### **Phase 2: Create New Tables (1 week)**

```bash
1. Create all ref_* tables
2. Seed master data
3. Create new tables tanpa foreign key dulu
4. Test migrations rollback
```

#### **Phase 3: Data Migration (2-3 weeks)**

```bash
1. Migrate data_guru → guru + alamat_guru
2. Migrate data_siswa → siswa + alamat_siswa
3. Extract parent data → orang_tua + siswa_orang_tua
4. Migrate data_kelas → kelas + kelas_siswa + kelas_guru
5. Migrate student_assessments → penilaian + penilaian_detail
6. Migrate monthly_reports → laporan_bulanan
7. Migrate growth_records → data_pertumbuhan
8. Migrate attendance_records → data_kehadiran
```

#### **Phase 4: Add Foreign Keys (1 week)**

```bash
1. Add all foreign key constraints
2. Test referential integrity
3. Create indexes untuk performa
```

#### **Phase 5: Update Code (2-3 weeks)**

```bash
1. Update all Models
2. Update all Resources
3. Update all Controllers
4. Update all Queries
5. Update all Views/Blade
```

#### **Phase 6: Testing (2 weeks)**

```bash
1. Unit tests
2. Integration tests
3. Performance tests
4. UAT dengan client
```

#### **Phase 7: Deployment (1 week)**

```bash
1. Backup production database
2. Schedule downtime
3. Run migrations
4. Deploy new code
5. Monitor errors
6. Rollback plan ready
```

**Total Timeline: 10-13 weeks**

---

## 🎯 Quick Wins (Prioritas Tinggi)

Jika tidak bisa refactor semuanya sekaligus, prioritaskan ini:

### Priority 1: Critical Issues (2-3 weeks)

1. ✅ Fix `data_siswa.kelas` string → foreign key `kelas_id`
2. ✅ Create reference tables (agama, jenis_kelamin, pekerjaan)
3. ✅ Normalize parent data → table `orang_tua`
4. ✅ Add proper indexes

### Priority 2: Performance (1-2 weeks)

1. ✅ Add composite indexes untuk query yang sering
2. ✅ Denormalize statistics fields
3. ✅ Add caching layer

### Priority 3: Features (2-3 weeks)

1. ✅ Implement `kelas_siswa` pivot (history)
2. ✅ Implement `kelas_guru` pivot (multiple teachers)
3. ✅ Separate photo tables

### Priority 4: Nice to Have (ongoing)

1. ✅ Full address normalization
2. ✅ Audit trail everywhere
3. ✅ Soft deletes everywhere

---

## 📝 Sample Migration Code

### Example: Fix data_siswa.kelas

```php
// Migration: fix_siswa_kelas_foreign_key.php
public function up()
{
    Schema::table('data_siswa', function (Blueprint $table) {
        // 1. Add new column
        $table->foreignId('kelas_id')
            ->nullable()
            ->after('kelas')
            ->constrained('data_kelas')
            ->onDelete('restrict');
    });

    // 2. Migrate data dari string ke foreign key
    $siswaList = DB::table('data_siswa')->get();
    foreach ($siswaList as $siswa) {
        if (!empty($siswa->kelas)) {
            $kelas = DB::table('data_kelas')
                ->where('nama_kelas', $siswa->kelas)
                ->orWhere('id', $siswa->kelas)
                ->first();

            if ($kelas) {
                DB::table('data_siswa')
                    ->where('id', $siswa->id)
                    ->update(['kelas_id' => $kelas->id]);
            }
        }
    }

    // 3. Drop old column (after verify data)
    // Schema::table('data_siswa', function (Blueprint $table) {
    //     $table->dropColumn('kelas');
    // });
}
```

---

## ✅ Benefits Setelah Refactoring

### Data Integrity:

-   ✅ Referential integrity terjaga dengan foreign keys
-   ✅ Tidak ada data orphan
-   ✅ Cascading delete/update works properly

### Performance:

-   ✅ Query lebih cepat dengan proper indexes
-   ✅ JOIN tables optimal
-   ✅ Denormalization pada data yang tepat

### Maintainability:

-   ✅ Code lebih clean dan readable
-   ✅ ERD lebih jelas dan terstruktur
-   ✅ Mudah onboarding developer baru

### Scalability:

-   ✅ Mudah extend dengan fitur baru
-   ✅ Support multi-tenant (jika diperlukan)
-   ✅ Ready untuk API/mobile app

### Professional:

-   ✅ Database design mengikuti best practices
-   ✅ Normalized sampai 3NF
-   ✅ Proper naming convention
-   ✅ Complete audit trail

---

## 🚨 Risks & Mitigation

### Risk 1: Data Loss

**Mitigation:**

-   Multiple backups sebelum migration
-   Test di staging environment dulu
-   Rollback plan yang jelas
-   Migration bisa di-rollback

### Risk 2: Downtime Lama

**Mitigation:**

-   Migration dilakukan step-by-step
-   Schedule di luar jam kerja
-   Blue-green deployment strategy
-   Parallel run old & new system

### Risk 3: Breaking Changes

**Mitigation:**

-   Maintain backward compatibility
-   Phased rollout
-   Feature flags
-   Comprehensive testing

### Risk 4: Performance Degradation

**Mitigation:**

-   Load testing sebelum deploy
-   Monitor query performance
-   Add indexes as needed
-   Query optimization

---

## 📚 Resources & References

### Database Design Best Practices:

-   Database Normalization (1NF, 2NF, 3NF)
-   Foreign Key Constraints
-   Indexing Strategies
-   Denormalization When Needed

### Laravel Specific:

-   [Laravel Migrations Documentation](https://laravel.com/docs/migrations)
-   [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
-   [Database Seeding](https://laravel.com/docs/seeding)

### Tools:

-   **DB Designer:** dbdiagram.io, draw.io
-   **Migration Tools:** Laravel Shift, Doctrine Migrations
-   **Testing:** Laravel Dusk, PHPUnit

---

## 🎉 Conclusion

Database refactoring ini akan membuat sistem Anda:

1. **Lebih Profesional** - Struktur yang jelas dan terorganisir
2. **Lebih Scalable** - Mudah dikembangkan di masa depan
3. **Lebih Maintainable** - Code yang clean dan readable
4. **Lebih Performant** - Query yang optimal dengan indexes
5. **Lebih Reliable** - Data integrity terjaga

**Rekomendasi saya:**

-   Mulai dari **Quick Wins Priority 1** dulu (2-3 minggu)
-   Lihat impact-nya dan feedback dari team
-   Lanjutkan ke phase berikutnya bertahap
-   Jangan rush, quality > speed

**Next Steps:**

1. Review dokumen ini dengan team
2. Pilih approach: Big Bang atau Incremental
3. Setup timeline dan milestone
4. Mulai dengan backup dan testing environment
5. Execute phase by phase

---

**Dokumentasi dibuat:** November 14, 2025  
**Versi:** 1.0  
**Status:** Rekomendasi untuk Review

**Contact:** Diskusikan dengan lead developer dan DBA sebelum eksekusi.
