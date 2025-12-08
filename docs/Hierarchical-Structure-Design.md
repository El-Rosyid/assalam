# Analisis Struktur Berjenjang Linear (Single Path Hierarchy)

## Konsep: Tabel Berjenjang Satu Jalur

Struktur berjenjang yang satu jalur adalah konsep menghubungkan tabel-tabel dalam database secara linear dan terstruktur sehingga hanya ada **satu jalur hubungan** dari atas ke bawah:

**Sekolah → Tahun Ajaran → Kelas → Wali Kelas (Guru) → Siswa**

Tidak ada percabangan rumit, setiap level hanya punya satu parent dan bisa punya banyak child.

---

# Struktur yang Sudah Ada (EXISTING)

# Struktur yang Sudah Ada (EXISTING)

## Level 1: Sekolah (Root)

```
Tabel: sekolah
Primary Key: id
Relationship: One (hanya 1 sekolah)
```

**Kolom:**

-   `id` - Primary Key
-   `nama_sekolah` - Nama TK
-   `npsn` - Nomor Pokok Sekolah Nasional
-   `alamat` - Alamat lengkap
-   `telepon` - Nomor telepon
-   `email` - Email sekolah
-   `logo` - Logo sekolah

**Status Saat Ini:** ✅ **SUDAH BENAR** - Tabel independent tanpa FK

---

## Level 2: Tahun Ajaran

```
Tabel: academic_year
Primary Key: tahun_ajaran_id
Parent: sekolah (implicit - semua tahun ajaran untuk 1 sekolah)
Relationship: One sekolah → Many tahun ajaran
```

**Kolom:**

-   `tahun_ajaran_id` - Primary Key (INT)
-   `year` - Tahun ajaran (misal: "2024/2025")
-   `semester` - "Ganjil" atau "Genap"
-   `is_active` - Boolean (hanya 1 yang aktif)
-   `start_date` - Tanggal mulai
-   `end_date` - Tanggal selesai

**Status Saat Ini:** ✅ **SUDAH BENAR** - Tidak perlu FK ke sekolah karena single school

**Yang Perlu Ditambahkan:**

```sql
-- Optional: Jika mau explicit relationship
ALTER TABLE academic_year
ADD COLUMN sekolah_id INT UNSIGNED DEFAULT 1,
ADD FOREIGN KEY (sekolah_id) REFERENCES sekolah(id) ON DELETE CASCADE;
```

---

## Level 3: Kelas

```
Tabel: data_kelas
Primary Key: kelas_id
Parent: academic_year (MISSING!)
Parent: data_guru via walikelas_id
Relationship: One tahun ajaran → Many kelas
              One guru → Many kelas (as wali kelas)
```

**Kolom Existing:**

-   `kelas_id` - Primary Key (INT)
-   `nama_kelas` - Nama kelas (misal: "Kelas A", "Kelas B")
-   `walikelas_id` - FK ke `data_guru.guru_id`
-   `tingkat` - Tingkat kelas (untuk TK biasanya A/B)
-   `kapasitas` - Kapasitas maksimal siswa

**⚠️ MASALAH:** Tidak ada kolom `tahun_ajaran_id` atau `academic_year_id`!

**Yang Perlu Ditambahkan:**

```sql
ALTER TABLE data_kelas
ADD COLUMN tahun_ajaran_id INT UNSIGNED NOT NULL,
ADD FOREIGN KEY (tahun_ajaran_id) REFERENCES academic_year(tahun_ajaran_id) ON DELETE CASCADE;

-- Index untuk query cepat
CREATE INDEX idx_kelas_tahun_ajaran ON data_kelas(tahun_ajaran_id);
CREATE INDEX idx_kelas_walikelas ON data_kelas(walikelas_id);
```

---

## Level 4: Guru/Wali Kelas

```
Tabel: data_guru
Primary Key: guru_id
Relationship: One guru → Many kelas (via walikelas_id di data_kelas)
```

**Kolom Existing:**

-   `guru_id` - Primary Key (INT)
-   `nama_lengkap` - Nama lengkap guru
-   `nip` - Nomor Induk Pegawai (nullable)
-   `email` - Email guru
-   `no_telp` - Nomor telepon
-   `alamat` - Alamat
-   `status` - Status kepegawaian

**Status Saat Ini:** ✅ **SUDAH BENAR** - Independent, linked dari data_kelas

---

## Level 5: Siswa (Leaf/Ujung)

```
Tabel: data_siswa
Primary Key: nis (Natural Key)
Parent: data_kelas via kelas
Relationship: One kelas → Many siswa
```

**Kolom Existing:**

-   `nis` - Primary Key (INT, 3-4 digit)
-   `nisn` - NISN nasional (nullable)
-   `nama_lengkap` - Nama siswa
-   `kelas` - FK ke `data_kelas.kelas_id`
-   `tempat_lahir` - Tempat lahir
-   `tanggal_lahir` - Tanggal lahir
-   `jenis_kelamin` - L/P
-   `alamat` - Alamat
-   `no_telp_ortu_wali` - Nomor telepon orang tua
-   `status` - Status siswa (aktif/lulus/pindah)

**Status Saat Ini:** ✅ **SUDAH BENAR** - Ada FK `kelas` ke `data_kelas.kelas_id`

---

# Visualisasi Hierarki Linear

```
┌─────────────────┐
│    SEKOLAH      │ Level 1 (Root)
│  (sekolah.id)   │
└────────┬────────┘
         │ implicit (1 school system)
         ▼
┌─────────────────────────┐
│    TAHUN AJARAN         │ Level 2
│ (academic_year.         │
│  tahun_ajaran_id)       │
└────────┬────────────────┘
         │ ⚠️ MISSING FK!
         ▼
┌─────────────────────────┐
│       KELAS             │ Level 3
│ (data_kelas.kelas_id)   │
│   + walikelas_id ────┐  │
└────────┬─────────────┼──┘
         │             │
         │             ▼
         │      ┌─────────────┐
         │      │    GURU     │ Level 4
         │      │ (guru_id)   │
         │      └─────────────┘
         │ FK: kelas
         ▼
┌──────────────────┐
│     SISWA        │ Level 5 (Leaf)
│ (data_siswa.nis) │
└──────────────────┘
```

---

# Path Query Examples

## 1. Dari Sekolah ke Siswa (Top-Down)

```sql
SELECT
    s.nama_sekolah,
    ay.year AS tahun_ajaran,
    ay.semester,
    k.nama_kelas,
    g.nama_lengkap AS wali_kelas,
    siswa.nis,
    siswa.nama_lengkap AS nama_siswa
FROM sekolah s
CROSS JOIN academic_year ay  -- Implicit karena 1 sekolah
INNER JOIN data_kelas k ON k.tahun_ajaran_id = ay.tahun_ajaran_id  -- AKAN DITAMBAHKAN
INNER JOIN data_guru g ON k.walikelas_id = g.guru_id
INNER JOIN data_siswa siswa ON siswa.kelas = k.kelas_id
WHERE ay.is_active = TRUE
ORDER BY k.nama_kelas, siswa.nama_lengkap;
```

## 2. Dari Siswa ke Sekolah (Bottom-Up)

```sql
SELECT
    siswa.nis,
    siswa.nama_lengkap,
    k.nama_kelas,
    g.nama_lengkap AS wali_kelas,
    ay.year AS tahun_ajaran,
    s.nama_sekolah
FROM data_siswa siswa
INNER JOIN data_kelas k ON siswa.kelas = k.kelas_id
INNER JOIN data_guru g ON k.walikelas_id = g.guru_id
INNER JOIN academic_year ay ON k.tahun_ajaran_id = ay.tahun_ajaran_id  -- AKAN DITAMBAHKAN
CROSS JOIN sekolah s
WHERE siswa.nis = 210;
```

## 3. Semua Siswa dalam Tahun Ajaran Aktif

```sql
SELECT
    ay.year,
    ay.semester,
    k.nama_kelas,
    COUNT(siswa.nis) AS jumlah_siswa
FROM academic_year ay
INNER JOIN data_kelas k ON k.tahun_ajaran_id = ay.tahun_ajaran_id  -- AKAN DITAMBAHKAN
LEFT JOIN data_siswa siswa ON siswa.kelas = k.kelas_id
WHERE ay.is_active = TRUE
GROUP BY ay.tahun_ajaran_id, k.kelas_id
ORDER BY k.nama_kelas;
```

---

# Migration Plan: Menambahkan Link yang Hilang

## Migration: Add tahun_ajaran_id to data_kelas

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add column without constraint first
        Schema::table('data_kelas', function (Blueprint $table) {
            $table->unsignedInteger('tahun_ajaran_id')->nullable()->after('kelas_id');
        });

        // Step 2: Populate with active academic year
        $activeYear = DB::table('academic_year')->where('is_active', true)->first();

        if ($activeYear) {
            DB::table('data_kelas')->update([
                'tahun_ajaran_id' => $activeYear->tahun_ajaran_id
            ]);
        }

        // Step 3: Make it NOT NULL and add foreign key
        Schema::table('data_kelas', function (Blueprint $table) {
            $table->unsignedInteger('tahun_ajaran_id')->nullable(false)->change();
            $table->foreign('tahun_ajaran_id')
                  ->references('tahun_ajaran_id')
                  ->on('academic_year')
                  ->onDelete('cascade');

            $table->index('tahun_ajaran_id', 'idx_kelas_tahun_ajaran');
        });
    }

    public function down(): void
    {
        Schema::table('data_kelas', function (Blueprint $table) {
            $table->dropForeign(['tahun_ajaran_id']);
            $table->dropIndex('idx_kelas_tahun_ajaran');
            $table->dropColumn('tahun_ajaran_id');
        });
    }
};
```

---

# Benefits of Linear Hierarchy

## ✅ Keuntungan

1. **Simple & Clear**: Tidak ada relationship yang rumit atau circular
2. **Easy to Query**: Query path dari root ke leaf sangat straightforward
3. **Performance**: Index pada FK membuat query cepat
4. **Data Integrity**: CASCADE constraints menjaga konsistensi
5. **Easy to Maintain**: Mudah dipahami developer baru
6. **Scalable**: Mudah ditambah level jika perlu (misal: Jurusan)

## ✅ Query Patterns yang Mudah

-   **Top-Down**: Dari sekolah → siswa (untuk laporan aggregate)
-   **Bottom-Up**: Dari siswa → sekolah (untuk detail siswa)
-   **Horizontal**: Semua siswa dalam kelas yang sama
-   **Time-based**: Filter berdasarkan tahun ajaran

---

# Data Child Tables (Transactional)

Tabel-tabel yang tergantung pada hierarki utama:

## Terkait Siswa (Level 5)

```
student_assessments    → siswa_nis → data_siswa.nis
student_assessment_details → penilaian_id → student_assessments
growth_records        → siswa_nis → data_siswa.nis
attendance_records    → siswa_nis → data_siswa.nis
monthly_reports       → siswa_nis → data_siswa.nis
```

## Terkait Kelas (Level 3)

```
attendance_records    → kelas (via siswa)
monthly_reports       → kelas (via siswa)
```

## Terkait Guru (Level 4)

```
growth_records        → data_guru_id → data_guru.guru_id
monthly_reports       → data_guru_id → data_guru.guru_id
```

**Status:** ✅ Semua child tables sudah benar menggunakan natural/semantic keys

---

# Implementation Checklist

## Phase 1: Fix Missing Link ⚠️ PRIORITY

-   [ ] Create migration untuk `data_kelas.tahun_ajaran_id`
-   [ ] Run migration (akan populate dengan tahun ajaran aktif)

## Phase 1: Fix Missing Link ⚠️ PRIORITY

-   [ ] Create migration untuk `data_kelas.tahun_ajaran_id`
-   [ ] Run migration (akan populate dengan tahun ajaran aktif)
-   [ ] Update model `data_kelas` dengan relationship `belongsTo(academic_year)`
-   [ ] Test query hierarki lengkap

## Phase 2: Update Filament Resources

-   [ ] Update `DataKelasResource` - tambah field tahun_ajaran_id
-   [ ] Update forms/tables yang terkait kelas
-   [ ] Test CRUD kelas dengan tahun ajaran

## Phase 3: Add Optional sekolah_id (if multi-school in future)

-   [ ] Add `sekolah_id` to `academic_year` table
-   [ ] Add `sekolah_id` to `data_guru` table
-   [ ] Add `sekolah_id` to `data_kelas` table

---

# Summary: Single Path Hierarchy

**Jalur Utama (Main Path):**

```
Sekolah (1)
  ↓
Tahun Ajaran (Many)
  ↓
Kelas (Many) ← Wali Kelas/Guru
  ↓
Siswa (Many)
```

**Yang Sudah Benar:**

-   ✅ Sekolah table (independent)
-   ✅ Academic year table (independent)
-   ✅ Data guru table (independent)
-   ✅ Data siswa table (FK ke kelas)
-   ✅ Semua transactional tables (FK ke siswa/guru/kelas)

**Yang Perlu Diperbaiki:**

-   ⚠️ **data_kelas** perlu kolom `tahun_ajaran_id` untuk complete path

**Prinsip:**

1. **One parent per level** - Tidak ada multiple parents
2. **Clear path** - Dari root ke leaf jelas
3. **Cascade delete** - Hapus parent, child ikut terhapus
4. **Index all FKs** - Performance query optimal
5. **Natural/Semantic keys** - Sudah diterapkan (nis, guru_id, kelas_id)

---

# Comparison: Linear vs Complex Hierarchy

## Linear (Yang Kita Gunakan) ✅

```
Sekolah → Tahun Ajaran → Kelas → Siswa
- Simple queries
- Easy maintenance
- Clear data flow
- Perfect untuk TK/SD
```

## Complex (Yang TIDAK Kita Butuhkan) ❌

```
            ┌→ Wakil Kurikulum
Kepala ─────┼→ Wakil Kesiswaan
Sekolah     ├→ Koordinator A ──→ Guru A ──→ Kelas A
            └→ Koordinator B ──→ Guru B ──→ Kelas B
- Multiple parents
- Complex approval flows
- Matrix organization
- Overkill untuk TK
```

**Kesimpulan:** Struktur linear sudah sangat tepat untuk sistem TK. Hanya perlu tambah 1 kolom (`tahun_ajaran_id` di `data_kelas`) untuk lengkap!

```sql
CREATE TABLE `jabatan` (
  `jabatan_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_jabatan` VARCHAR(100) NOT NULL,
  `kode_jabatan` VARCHAR(20) UNIQUE NOT NULL,
  `tingkat` TINYINT NOT NULL COMMENT '1=Kepala Sekolah, 2=Wakil, 3=Koordinator, 4=Guru, 5=Staff',
  `deskripsi` TEXT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data jabatan
INSERT INTO `jabatan` VALUES
(1, 'Kepala Sekolah', 'KEPSEK', 1, 'Pemimpin tertinggi sekolah', 1, NOW(), NOW()),
(2, 'Wakil Kepala Sekolah Kurikulum', 'WAKA_KUR', 2, 'Wakil kepala bidang kurikulum', 1, NOW(), NOW()),
(3, 'Wakil Kepala Sekolah Kesiswaan', 'WAKA_SIS', 2, 'Wakil kepala bidang kesiswaan', 1, NOW(), NOW()),
(4, 'Wakil Kepala Sekolah Sarana Prasarana', 'WAKA_SAR', 2, 'Wakil kepala bidang sarana prasarana', 1, NOW(), NOW()),
(5, 'Koordinator Kelas', 'KOOR_KELAS', 3, 'Koordinator tingkat kelas', 1, NOW(), NOW()),
(6, 'Wali Kelas', 'WALIKELAS', 4, 'Guru wali kelas', 1, NOW(), NOW()),
(7, 'Guru Mata Pelajaran', 'GURU_MAPEL', 4, 'Guru pengajar mata pelajaran', 1, NOW(), NOW()),
(8, 'Staff Administrasi', 'STAFF_ADM', 5, 'Staff administrasi sekolah', 1, NOW(), NOW()),
(9, 'Staff Perpustakaan', 'STAFF_PERPUS', 5, 'Staff perpustakaan', 1, NOW(), NOW());
```

## 2. Tabel: `guru_jabatan` (Penugasan Jabatan ke Guru)

Menghubungkan guru dengan jabatan (many-to-many dengan periode)

```sql
CREATE TABLE `guru_jabatan` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `guru_id` INT UNSIGNED NOT NULL COMMENT 'FK ke data_guru.guru_id',
  `jabatan_id` INT UNSIGNED NOT NULL COMMENT 'FK ke jabatan.jabatan_id',
  `tahun_ajaran_id` INT UNSIGNED NULL COMMENT 'FK ke academic_year.tahun_ajaran_id',
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `sk_number` VARCHAR(50) NULL COMMENT 'Nomor SK pengangkatan',
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  FOREIGN KEY (`guru_id`) REFERENCES `data_guru`(`guru_id`) ON DELETE CASCADE,
  FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan`(`jabatan_id`) ON DELETE CASCADE,
  FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `academic_year`(`tahun_ajaran_id`) ON DELETE SET NULL,

  INDEX `idx_guru_active` (`guru_id`, `is_active`),
  INDEX `idx_tahun_ajaran` (`tahun_ajaran_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 3. Tabel: `organizational_hierarchy` (Struktur Organisasi Berjenjang)

Menyimpan struktur hierarki dengan adjacency list pattern

```sql
CREATE TABLE `organizational_hierarchy` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `guru_jabatan_id` INT UNSIGNED NOT NULL COMMENT 'FK ke guru_jabatan.id',
  `parent_id` INT UNSIGNED NULL COMMENT 'FK ke organizational_hierarchy.id (atasan langsung)',
  `level` TINYINT NOT NULL DEFAULT 1 COMMENT 'Tingkat hierarki: 1=top, 2=sub, dst',
  `tahun_ajaran_id` INT UNSIGNED NULL COMMENT 'FK ke academic_year.tahun_ajaran_id',
  `urutan` TINYINT DEFAULT 0 COMMENT 'Urutan tampilan dalam level yang sama',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  FOREIGN KEY (`guru_jabatan_id`) REFERENCES `guru_jabatan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `organizational_hierarchy`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `academic_year`(`tahun_ajaran_id`) ON DELETE SET NULL,

  UNIQUE KEY `unique_position_per_year` (`guru_jabatan_id`, `tahun_ajaran_id`),
  INDEX `idx_parent` (`parent_id`),
  INDEX `idx_level` (`level`),
  INDEX `idx_tahun_ajaran_active` (`tahun_ajaran_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 4. Tabel: `hierarchy_path` (Materialized Path untuk Query Cepat)

Menyimpan path lengkap untuk query hierarki yang lebih cepat

```sql
CREATE TABLE `hierarchy_path` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `hierarchy_id` INT UNSIGNED NOT NULL COMMENT 'FK ke organizational_hierarchy.id',
  `ancestor_id` INT UNSIGNED NOT NULL COMMENT 'FK ke organizational_hierarchy.id',
  `depth` TINYINT NOT NULL DEFAULT 0 COMMENT '0=diri sendiri, 1=parent langsung, 2=grandparent, dst',
  `path` VARCHAR(500) NULL COMMENT 'Path lengkap: 1/2/5/7',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  FOREIGN KEY (`hierarchy_id`) REFERENCES `organizational_hierarchy`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ancestor_id`) REFERENCES `organizational_hierarchy`(`id`) ON DELETE CASCADE,

  UNIQUE KEY `unique_path` (`hierarchy_id`, `ancestor_id`),
  INDEX `idx_hierarchy` (`hierarchy_id`),
  INDEX `idx_ancestor_depth` (`ancestor_id`, `depth`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 5. Tabel: `tugas_delegasi` (Pendelegasian Tugas Berjenjang)

Menyimpan tugas yang didelegasikan dari atasan ke bawahan

```sql
CREATE TABLE `tugas_delegasi` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pemberi_tugas_id` INT UNSIGNED NOT NULL COMMENT 'FK ke guru_jabatan.id (atasan)',
  `penerima_tugas_id` INT UNSIGNED NOT NULL COMMENT 'FK ke guru_jabatan.id (bawahan)',
  `judul_tugas` VARCHAR(200) NOT NULL,
  `deskripsi` TEXT NULL,
  `deadline` DATETIME NULL,
  `prioritas` ENUM('rendah', 'sedang', 'tinggi', 'urgent') DEFAULT 'sedang',
  `status` ENUM('pending', 'progress', 'review', 'selesai', 'dibatalkan') DEFAULT 'pending',
  `tanggal_selesai` DATETIME NULL,
  `catatan_selesai` TEXT NULL,
  `file_lampiran` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  FOREIGN KEY (`pemberi_tugas_id`) REFERENCES `guru_jabatan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`penerima_tugas_id`) REFERENCES `guru_jabatan`(`id`) ON DELETE CASCADE,

  INDEX `idx_penerima_status` (`penerima_tugas_id`, `status`),
  INDEX `idx_pemberi` (`pemberi_tugas_id`),
  INDEX `idx_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 6. Tabel: `approval_flow` (Alur Persetujuan Berjenjang)

Menyimpan alur approval untuk berbagai dokumen/permintaan

```sql
CREATE TABLE `approval_flow` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `jenis_dokumen` VARCHAR(50) NOT NULL COMMENT 'misal: cuti, ijin, pengajuan_dana, dll',
  `dokumen_id` INT UNSIGNED NOT NULL COMMENT 'ID dari tabel dokumen terkait',
  `pengaju_id` INT UNSIGNED NOT NULL COMMENT 'FK ke guru_jabatan.id',
  `current_approver_id` INT UNSIGNED NULL COMMENT 'FK ke guru_jabatan.id (yang sedang review)',
  `level_approval` TINYINT NOT NULL DEFAULT 1,
  `max_level` TINYINT NOT NULL DEFAULT 3,
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  FOREIGN KEY (`pengaju_id`) REFERENCES `guru_jabatan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`current_approver_id`) REFERENCES `guru_jabatan`(`id`) ON DELETE SET NULL,

  INDEX `idx_dokumen` (`jenis_dokumen`, `dokumen_id`),
  INDEX `idx_current_approver` (`current_approver_id`, `status`),
  INDEX `idx_pengaju` (`pengaju_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 7. Tabel: `approval_history` (Riwayat Approval)

Menyimpan history setiap step approval

```sql
CREATE TABLE `approval_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `approval_flow_id` INT UNSIGNED NOT NULL,
  `approver_id` INT UNSIGNED NOT NULL COMMENT 'FK ke guru_jabatan.id',
  `level` TINYINT NOT NULL,
  `action` ENUM('pending', 'approved', 'rejected', 'forwarded') NOT NULL,
  `catatan` TEXT NULL,
  `action_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,

  FOREIGN KEY (`approval_flow_id`) REFERENCES `approval_flow`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approver_id`) REFERENCES `guru_jabatan`(`id`) ON DELETE CASCADE,

  INDEX `idx_approval_flow` (`approval_flow_id`),
  INDEX `idx_approver` (`approver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Contoh Query Penting

### 1. Mendapatkan Seluruh Bawahan dari Kepala Sekolah

```sql
-- Menggunakan recursive CTE
WITH RECURSIVE subordinates AS (
  SELECT h.id, h.guru_jabatan_id, h.parent_id, h.level, 1 as depth
  FROM organizational_hierarchy h
  WHERE h.parent_id IS NULL -- Kepala Sekolah
    AND h.is_active = TRUE

  UNION ALL

  SELECT h.id, h.guru_jabatan_id, h.parent_id, h.level, s.depth + 1
  FROM organizational_hierarchy h
  INNER JOIN subordinates s ON h.parent_id = s.id
  WHERE h.is_active = TRUE
)
SELECT s.*, gj.guru_id, g.nama_lengkap, j.nama_jabatan
FROM subordinates s
JOIN guru_jabatan gj ON s.guru_jabatan_id = gj.id
JOIN data_guru g ON gj.guru_id = g.guru_id
JOIN jabatan j ON gj.jabatan_id = j.jabatan_id
ORDER BY s.depth, s.level;
```

### 2. Mendapatkan Chain of Command (Atasan Berjenjang)

```sql
-- Mendapat semua atasan dari seorang guru
SELECT hp.depth, h.id, gj.guru_id, g.nama_lengkap, j.nama_jabatan
FROM hierarchy_path hp
JOIN organizational_hierarchy h ON hp.ancestor_id = h.id
JOIN guru_jabatan gj ON h.guru_jabatan_id = gj.id
JOIN data_guru g ON gj.guru_id = g.guru_id
JOIN jabatan j ON gj.jabatan_id = j.jabatan_id
WHERE hp.hierarchy_id = ? -- ID hierarchy dari guru
  AND hp.depth > 0 -- Tidak termasuk diri sendiri
ORDER BY hp.depth DESC; -- Dari terdekat ke terjauh
```

### 3. Mendapat Approval Flow dengan Pejabat Terkait

```sql
SELECT af.*,
       pengaju.nama_lengkap as nama_pengaju,
       j_pengaju.nama_jabatan as jabatan_pengaju,
       approver.nama_lengkap as nama_approver_saat_ini,
       j_approver.nama_jabatan as jabatan_approver
FROM approval_flow af
JOIN guru_jabatan gj_pengaju ON af.pengaju_id = gj_pengaju.id
JOIN data_guru pengaju ON gj_pengaju.guru_id = pengaju.guru_id
JOIN jabatan j_pengaju ON gj_pengaju.jabatan_id = j_pengaju.jabatan_id
LEFT JOIN guru_jabatan gj_approver ON af.current_approver_id = gj_approver.id
LEFT JOIN data_guru approver ON gj_approver.guru_id = approver.guru_id
LEFT JOIN jabatan j_approver ON gj_approver.jabatan_id = j_approver.jabatan_id
WHERE af.status = 'pending'
ORDER BY af.created_at DESC;
```

## Keuntungan Desain Ini

1. **Flexible Hierarchy**: Support multiple levels dan mudah diubah
2. **Historical Data**: Bisa track perubahan jabatan per tahun ajaran
3. **Fast Queries**: hierarchy_path mempercepat query hierarki kompleks
4. **Approval System**: Support workflow approval berjenjang
5. **Delegation Tracking**: Track tugas yang didelegasikan
6. **Audit Trail**: Semua perubahan tercatat dengan timestamp

## Relationship ke Tabel Existing

-   `data_guru.guru_id` → `guru_jabatan.guru_id`
-   `academic_year.tahun_ajaran_id` → `guru_jabatan.tahun_ajaran_id`
-   `data_kelas.walikelas_id` → bisa linked ke `guru_jabatan` untuk wali kelas

## Migration Steps

1. Create `jabatan` table
2. Create `guru_jabatan` table
3. Create `organizational_hierarchy` table
4. Create `hierarchy_path` table
5. Create `tugas_delegasi` table
6. Create `approval_flow` table
7. Create `approval_history` table
8. Populate initial data (jabatan)
9. Create triggers untuk auto-populate `hierarchy_path`
