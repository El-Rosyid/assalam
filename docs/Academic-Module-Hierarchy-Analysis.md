# Analisis Linear Hierarchy: Modul Akademik

## 📋 Overview

Modul akademik mencakup 4 area utama:

1. **Student Assessments** (Penilaian Siswa)
2. **Growth Records** (Catatan Pertumbuhan)
3. **Attendance Records** (Catatan Kehadiran)
4. **Monthly Reports** (Catatan Perkembangan Bulanan)

---

## 🔍 Current Structure Analysis

### 1. Student Assessments (Penilaian Siswa)

**Tabel:** `student_assessments`

```
Kolom:
- penilaian_id (PK)
- siswa_nis (FK → data_siswa.nis)
- semester ('Ganjil' atau 'Genap')
- status (belum_dinilai/sebagian/selesai)
- completed_at
```

**Linear Hierarchy Path:**

```
Sekolah → Tahun Ajaran → Kelas → Siswa → Penilaian
                ↓                           ↑
            Semester                   (siswa_nis)
```

**⚠️ MASALAH:**

-   ❌ **Tidak ada `tahun_ajaran_id`** - Hanya ada `semester`
-   ❌ **Tidak ada `data_kelas_id`** - Harus query via siswa.kelas
-   ✅ Ada `siswa_nis` yang benar
-   🟡 Semester tersimpan sebagai string, bukan relasi ke tahun ajaran

**Impact:**

-   Sulit filter penilaian per tahun ajaran spesifik
-   Tidak bisa langsung query "semua penilaian kelas A tahun 2025/2026"
-   Ambiguitas: semester "Ganjil" tahun berapa?

---

### 2. Growth Records (Catatan Pertumbuhan)

**Tabel:** `growth_records`

```
Kolom:
- pertumbuhan_id (PK)
- siswa_nis (FK → data_siswa.nis)
- data_guru_id (FK → data_guru.guru_id)
- data_kelas_id (FK → data_kelas.kelas_id)
- month (1-12)
- year (2025, 2026, etc)
- lingkar_kepala, lingkar_lengan, berat_badan, tinggi_badan
```

**Linear Hierarchy Path:**

```
Sekolah → Tahun Ajaran → Kelas → Guru → Siswa → Growth Record
                ↓          ↓       ↓       ↓
            (implicit)  kelas_id guru_id siswa_nis
```

**✅ BAIK:**

-   ✅ Ada `siswa_nis` (Level 5)
-   ✅ Ada `data_kelas_id` (Level 3)
-   ✅ Ada `data_guru_id` (Level 4)
-   ✅ Ada `year` dan `month` untuk time filtering

**⚠️ BISA LEBIH BAIK:**

-   🟡 Tidak ada `tahun_ajaran_id` explicit
-   🟡 Year tersimpan sebagai integer (2025), bukan FK ke academic_year
-   🟡 Harus assume year + month → tahun ajaran tertentu

**Status:** ⭐ **CUKUP BAIK** - Sudah mendekati linear hierarchy lengkap

---

### 3. Attendance Records (Catatan Kehadiran)

**Tabel:** `attendance_records`

```
Kolom:
- id (PK)
- siswa_nis (FK → data_siswa.nis)
- data_guru_id (FK → data_guru.guru_id)
- data_kelas_id (FK → data_kelas.kelas_id)
- alfa, ijin, sakit
```

**Linear Hierarchy Path:**

```
Sekolah → Tahun Ajaran → Kelas → Guru → Siswa → Attendance
                ↓          ↓       ↓       ↓
            (implicit)  kelas_id guru_id siswa_nis
```

**✅ BAIK:**

-   ✅ Ada `siswa_nis` (Level 5)
-   ✅ Ada `data_kelas_id` (Level 3)
-   ✅ Ada `data_guru_id` (Level 4)

**⚠️ MASALAH:**

-   ❌ **Tidak ada period** (bulan/tahun/tahun ajaran)
-   ❌ **Tidak ada `tahun_ajaran_id`**
-   ❌ Asumsi: 1 siswa = 1 attendance record total (akumulatif)?

**Status:** 🟡 **KURANG LENGKAP** - Tidak ada dimensi waktu!

---

### 4. Monthly Reports (Catatan Perkembangan Bulanan)

**Tabel:** `monthly_reports`

```
Kolom:
- id (PK)
- siswa_nis (FK → data_siswa.nis)
- data_guru_id (FK → data_guru.guru_id)
- data_kelas_id (FK → data_kelas.kelas_id)
- month (1-12)
- year (2025, 2026, etc)
- catatan
- photos (JSON)
- status (draft/final)
```

**Linear Hierarchy Path:**

```
Sekolah → Tahun Ajaran → Kelas → Guru → Siswa → Monthly Report
                ↓          ↓       ↓       ↓
            (implicit)  kelas_id guru_id siswa_nis
```

**✅ SANGAT BAIK:**

-   ✅ Ada `siswa_nis` (Level 5)
-   ✅ Ada `data_kelas_id` (Level 3)
-   ✅ Ada `data_guru_id` (Level 4)
-   ✅ Ada `month` dan `year` untuk time filtering

**⚠️ BISA LEBIH BAIK:**

-   🟡 Tidak ada `tahun_ajaran_id` explicit
-   🟡 Year tersimpan sebagai integer (2025), bukan FK

**Status:** ⭐⭐ **PALING BAIK** - Sudah lengkap dengan time dimension!

---

## 📊 Comparison Matrix

| Modul                   | siswa_nis | kelas_id | guru_id | Time          | tahun_ajaran_id | Score    |
| ----------------------- | --------- | -------- | ------- | ------------- | --------------- | -------- |
| **student_assessments** | ✅        | ❌       | ❌      | 🟡 semester   | ❌              | 2/5 ⚠️   |
| **growth_records**      | ✅        | ✅       | ✅      | ✅ month+year | ❌              | 4/5 ⭐   |
| **attendance_records**  | ✅        | ✅       | ✅      | ❌ none       | ❌              | 3/5 🟡   |
| **monthly_reports**     | ✅        | ✅       | ✅      | ✅ month+year | ❌              | 4/5 ⭐⭐ |

---

## 🎯 Ideal Linear Hierarchy untuk Modul Akademik

```
Level 1: Sekolah
    ↓
Level 2: Tahun Ajaran (tahun_ajaran_id)
    ↓
Level 3: Kelas (data_kelas_id) ← Guru (data_guru_id)
    ↓
Level 4: Siswa (siswa_nis)
    ↓
Level 5: Transactional Data
    ├─ student_assessments (per semester)
    ├─ growth_records (per month)
    ├─ attendance_records (per period)
    └─ monthly_reports (per month)
```

**Prinsip:**

-   Setiap modul akademik harus bisa di-filter by **tahun ajaran aktif**
-   Setiap record harus traceable dari sekolah → tahun ajaran → kelas → siswa
-   Historical data harus jelas terpisah by tahun ajaran

---

## 🔧 Recommendations

### Priority 1: Student Assessments ⚠️ URGENT

**Problem:** Tidak ada `tahun_ajaran_id`, hanya `semester` string

**Solution:**

```sql
-- Add tahun_ajaran_id to student_assessments
ALTER TABLE student_assessments
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER semester,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;

-- Populate based on semester
UPDATE student_assessments sa
JOIN data_siswa ds ON sa.siswa_nis = ds.nis
JOIN data_kelas dk ON ds.kelas = dk.kelas_id
SET sa.tahun_ajaran_id = dk.tahun_ajaran_id;
```

**Benefits:**

-   ✅ Clear separation: penilaian semester 1 tahun 2024/2025 vs 2025/2026
-   ✅ Easy query: "all assessments in active academic year"
-   ✅ Historical tracking per tahun ajaran

---

### Priority 2: Attendance Records 🟡 IMPORTANT

**Problem:** Tidak ada time dimension (month/year/tahun_ajaran)

**Solution:**

```sql
-- Add time fields to attendance_records
ALTER TABLE attendance_records
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER data_kelas_id,
ADD COLUMN semester VARCHAR(10) NULL AFTER tahun_ajaran_id,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;

-- Or change to monthly tracking:
ALTER TABLE attendance_records
ADD COLUMN month TINYINT NOT NULL AFTER data_kelas_id,
ADD COLUMN year SMALLINT UNSIGNED NOT NULL AFTER month;
```

**Benefits:**

-   ✅ Track attendance per period (semester atau monthly)
-   ✅ Year-over-year comparison possible
-   ✅ Clear data segmentation

---

### Priority 3: Add tahun_ajaran_id to ALL (Optional but Recommended)

**For consistency across all academic modules:**

```sql
-- growth_records
ALTER TABLE growth_records
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER year,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;

-- monthly_reports
ALTER TABLE monthly_reports
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER year,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;
```

**Benefits:**

-   ✅ Unified filtering: `WHERE tahun_ajaran_id = ?` across all modules
-   ✅ Consistent API: all akademik resources use same filter
-   ✅ Future-proof for multi-year data

---

## 📈 Impact Analysis

### Without tahun_ajaran_id (Current)

**Query untuk "Semua penilaian tahun ajaran aktif":**

```php
// COMPLEX! Need to join multiple tables
$assessments = student_assessment::whereHas('siswa', function($q) {
    $q->whereHas('kelasInfo', function($q2) {
        $q2->where('tahun_ajaran_id', $activeYear->tahun_ajaran_id);
    });
})->where('semester', $activeYear->semester)->get();
```

### With tahun_ajaran_id (Proposed)

**Query untuk "Semua penilaian tahun ajaran aktif":**

```php
// SIMPLE! Direct filter
$assessments = student_assessment::where('tahun_ajaran_id', $activeYear->tahun_ajaran_id)
    ->get();
```

**Performance:**

-   ❌ Current: 3-level nested whereHas (slow on large datasets)
-   ✅ Proposed: Direct indexed FK query (fast!)

---

## 🎓 Best Practices: Linear Hierarchy Query Pattern

### Pattern 1: Filter by Active Year

```php
// Get active academic year (Level 2)
$activeYear = academic_year::where('is_active', true)->first();

// Query akademik data (Level 5) filtered by hierarchy
$assessments = student_assessment::where('tahun_ajaran_id', $activeYear->tahun_ajaran_id)->get();
$growth = GrowthRecord::where('tahun_ajaran_id', $activeYear->tahun_ajaran_id)->get();
$reports = monthly_reports::where('tahun_ajaran_id', $activeYear->tahun_ajaran_id)->get();
```

### Pattern 2: Historical Comparison

```php
// Compare data across years
$years = academic_year::orderBy('year', 'desc')->limit(3)->get();

foreach ($years as $year) {
    $stats[$year->year] = [
        'assessments' => student_assessment::where('tahun_ajaran_id', $year->tahun_ajaran_id)->count(),
        'growth' => GrowthRecord::where('tahun_ajaran_id', $year->tahun_ajaran_id)->count(),
    ];
}
```

### Pattern 3: Drill Down (Top to Bottom)

```php
// Sekolah → Tahun Ajaran → Kelas → Siswa → Akademik Data
$path = [
    'sekolah' => sekolah::first(),
    'tahun_ajaran' => academic_year::where('is_active', true)->first(),
    'kelas' => data_kelas::where('tahun_ajaran_id', $tahunAjaran->id)->get(),
    'siswa' => data_siswa::whereIn('kelas', $kelas->pluck('kelas_id'))->get(),
    'penilaian' => student_assessment::whereIn('siswa_nis', $siswa->pluck('nis'))
                    ->where('tahun_ajaran_id', $tahunAjaran->id)->get(),
];
```

---

## 🚦 Implementation Roadmap

### Phase 1: Critical Fix (Week 1) ⚠️

-   [ ] Add `tahun_ajaran_id` to `student_assessments`
-   [ ] Populate existing data
-   [ ] Update StudentAssessmentResource queries
-   [ ] Test assessment generation per year

### Phase 2: Time Dimension (Week 2) 🟡

-   [ ] Add time fields to `attendance_records`
-   [ ] Decide: semester-based or monthly?
-   [ ] Update AttendanceRecordResource
-   [ ] Migrate existing data

### Phase 3: Consistency (Week 3) ⭐

-   [ ] Add `tahun_ajaran_id` to `growth_records`
-   [ ] Add `tahun_ajaran_id` to `monthly_reports`
-   [ ] Update all Resources to use unified filter
-   [ ] Create BaseAcademicResource with shared filtering

### Phase 4: Optimization (Week 4) 🚀

-   [ ] Add composite indexes: (tahun_ajaran_id, siswa_nis)
-   [ ] Add composite indexes: (tahun_ajaran_id, data_kelas_id)
-   [ ] Performance testing with large datasets
-   [ ] Documentation update

---

## 💡 Quick Wins (Can Do NOW)

### 1. Virtual Accessor (No Migration Needed)

**Model: student_assessment.php**

```php
public function getTahunAjaranAttribute()
{
    // Get tahun ajaran from siswa → kelas → tahunAjaran
    return $this->siswa?->kelasInfo?->tahunAjaran;
}

// Usage:
$assessment->tahun_ajaran; // Returns academic_year model
```

### 2. Query Scope (No Migration Needed)

**Model: student_assessment.php**

```php
public function scopeInActiveYear($query)
{
    $activeYear = academic_year::where('is_active', true)->first();

    return $query->whereHas('siswa', function($q) use ($activeYear) {
        $q->whereHas('kelasInfo', function($q2) use ($activeYear) {
            $q2->where('tahun_ajaran_id', $activeYear->tahun_ajaran_id);
        });
    });
}

// Usage:
$assessments = student_assessment::inActiveYear()->get();
```

**Status:** Can implement immediately for temporary solution!

---

## ✅ Summary

**Current Status:**

-   🟢 Growth Records: 4/5 (Good structure)
-   🟢 Monthly Reports: 4/5 (Good structure)
-   🟡 Attendance Records: 3/5 (Missing time dimension)
-   🔴 Student Assessments: 2/5 (Missing hierarchy links)

**Recommendation:**

1. **URGENT:** Add `tahun_ajaran_id` to student_assessments
2. **IMPORTANT:** Add time dimension to attendance_records
3. **NICE TO HAVE:** Add `tahun_ajaran_id` to all for consistency

**Impact:**

-   🚀 **Query Performance:** 3x faster with direct FK
-   📊 **Data Clarity:** Clear year separation
-   🔧 **Maintainability:** Consistent API across all modules
-   📈 **Scalability:** Ready for multi-year historical data

---

_Analysis Date: 2025-11-16_
_System: TK ABA Assalam - Academic Module_
