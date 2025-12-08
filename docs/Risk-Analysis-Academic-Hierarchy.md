# ⚠️ Analisis Risiko Error: Penambahan tahun_ajaran_id ke Modul Akademik

## 📊 Data Current State

**Existing Data:**

-   `student_assessments`: 7 records
-   `growth_records`: 11 records
-   `attendance_records`: 7 records
-   `monthly_reports`: 9 records

**Total:** 34 records yang akan terpengaruh

**Foreign Keys Existing:**

-   ✅ Semua tabel sudah punya FK ke `data_siswa.nis`
-   ✅ Growth/Attendance/Monthly sudah punya FK ke `data_guru.guru_id` dan `data_kelas.kelas_id`
-   ❌ Student assessments TIDAK punya FK ke kelas/guru

---

## 🎯 Risk Assessment per Tabel

### 1. Student Assessments (Priority 1) ⚠️ MEDIUM RISK

**Perubahan yang Diusulkan:**

```sql
ALTER TABLE student_assessments
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER semester,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;
```

#### ✅ AMAN karena:

1. **Kolom NULLABLE** - Tidak akan reject existing rows
2. **ON DELETE SET NULL** - Bukan CASCADE, data tidak ikut terhapus
3. **Data Kecil** - Hanya 7 records, mudah rollback
4. **Backward Compatible** - Kolom baru tidak mengganggu kolom lama

#### ⚠️ RISIKO:

**Risiko 1: Data Existing NULL** (LOW)

```sql
-- Setelah migration, existing data punya tahun_ajaran_id = NULL
SELECT * FROM student_assessments WHERE tahun_ajaran_id IS NULL;
-- Result: 7 rows (semua existing data)
```

**Impact:** Query yang filter by tahun_ajaran_id tidak akan return existing data
**Mitigation:** Perlu populate dengan UPDATE query

**Risiko 2: Populate Query Gagal** (LOW)

```sql
-- Jika ada siswa yang kelasnya NULL atau kelas yang tahun_ajaran_id NULL
UPDATE student_assessments sa
JOIN data_siswa ds ON sa.siswa_nis = ds.nis
JOIN data_kelas dk ON ds.kelas = dk.kelas_id
SET sa.tahun_ajaran_id = dk.tahun_ajaran_id;
-- Mungkin tidak update semua jika ada data incomplete
```

**Impact:** Beberapa assessment mungkin tetap NULL
**Mitigation:** Check data sebelum migrate, handle orphaned records

**Risiko 3: Code Breaking** (VERY LOW)

```php
// Code existing
student_assessment::create([
    'siswa_nis' => $nis,
    'semester' => 'Ganjil',
    'status' => 'belum_dinilai'
]); // Tetap jalan, tahun_ajaran_id optional
```

**Impact:** Tidak ada breaking changes
**Mitigation:** Tidak perlu

#### 📊 Risk Score: **3/10** (LOW-MEDIUM)

---

### 2. Growth Records (Priority 3) ✅ LOW RISK

**Perubahan yang Diusulkan:**

```sql
ALTER TABLE growth_records
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER year,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;
```

#### ✅ AMAN karena:

1. **Sudah punya kelas_id** - Easy to populate via JOIN
2. **NULLABLE** - Tidak forced
3. **ON DELETE SET NULL** - Safe constraint
4. **Sudah punya year** - Bisa validate consistency

#### ⚠️ RISIKO:

**Risiko 1: Year Mismatch** (LOW)

```sql
-- Growth record year=2024 tapi kelas.tahun_ajaran = 2025/2026
-- Harus decide: pakai year dari growth atau dari kelas?
```

**Impact:** Data inconsistency possible
**Mitigation:** Validation before populate

**Risiko 2: Historical Data** (VERY LOW)

```sql
-- Growth records dari tahun lalu yang kelasnya sudah pindah tahun ajaran
```

**Impact:** Mungkin salah mapping
**Mitigation:** Populate based on year field, bukan current kelas

#### 📊 Risk Score: **2/10** (LOW)

---

### 3. Attendance Records (Priority 2) 🟡 MEDIUM RISK

**Perubahan yang Diusulkan (Option A):**

```sql
-- Add tahun_ajaran_id + semester
ALTER TABLE attendance_records
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER data_kelas_id,
ADD COLUMN semester VARCHAR(10) NULL AFTER tahun_ajaran_id,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;
```

**Perubahan yang Diusulkan (Option B):**

```sql
-- Add month + year (like growth_records)
ALTER TABLE attendance_records
ADD COLUMN month TINYINT NOT NULL DEFAULT 1 AFTER data_kelas_id,
ADD COLUMN year SMALLINT UNSIGNED NOT NULL DEFAULT 2025 AFTER month;
```

#### ⚠️ RISIKO:

**Risiko 1: Struktur Data Berubah** (MEDIUM)

```
Current: 1 siswa = 1 record (accumulative alfa/ijin/sakit)
Proposed A: 1 siswa = 1 record per semester
Proposed B: 1 siswa = 1 record per month
```

**Impact:** Breaking change! Existing 7 records jadi ambiguous
**Mitigation:** Perlu decide strategy:

-   Keep existing as "total all time"?
-   Migrate to semester 1 active year?
-   Start fresh for new year?

**Risiko 2: Code Breaking** (HIGH if not careful)

```php
// Code lama mungkin expect 1 record per siswa
$attendance = AttendanceRecord::where('siswa_nis', $nis)->first();
// Setelah migration, bisa ada multiple records
$attendance = AttendanceRecord::where('siswa_nis', $nis)->get(); // returns collection
```

**Impact:** Code harus diubah di banyak tempat
**Mitigation:** Search all usage, update queries

**Risiko 3: UI/UX Breaking** (MEDIUM)

```
Current UI: Show total alfa/ijin/sakit (lifetime)
New UI: Show per semester or per month?
```

**Impact:** User confusion, need new UI design
**Mitigation:** Plan UI changes carefully

#### 📊 Risk Score: **6/10** (MEDIUM) ⚠️

---

### 4. Monthly Reports (Priority 3) ✅ LOW RISK

**Perubahan yang Diusulkan:**

```sql
ALTER TABLE monthly_reports
ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL AFTER year,
ADD FOREIGN KEY (tahun_ajaran_id)
    REFERENCES academic_year(tahun_ajaran_id)
    ON DELETE SET NULL;
```

#### ✅ AMAN karena:

1. **Sudah punya month + year** - Easy mapping
2. **Sudah punya kelas_id** - Easy populate
3. **NULLABLE** - Non-breaking
4. **Data structure tidak berubah** - Still per month

#### ⚠️ RISIKO:

**Risiko 1: Month/Year to Tahun Ajaran Mapping** (LOW)

```php
// Month 7-12 → Semester Ganjil (year/year+1)
// Month 1-6 → Semester Genap (year-1/year)
// Logic bisa kompleks
```

**Impact:** Salah mapping tahun ajaran
**Mitigation:** Function helper untuk mapping

#### 📊 Risk Score: **2/10** (LOW)

---

## 🛡️ Overall Risk Matrix

| Tabel                   | Add Column Risk | Populate Risk | Code Breaking Risk | Total Risk | Priority |
| ----------------------- | --------------- | ------------- | ------------------ | ---------- | -------- |
| **student_assessments** | LOW (1/10)      | MEDIUM (4/10) | LOW (1/10)         | **3/10**   | P1 ⚠️    |
| **growth_records**      | LOW (1/10)      | LOW (2/10)    | VERYLOW (0/10)     | **2/10**   | P3 ✅    |
| **attendance_records**  | MEDIUM (3/10)   | HIGH (5/10)   | HIGH (4/10)        | **6/10**   | P2 🛑    |
| **monthly_reports**     | LOW (1/10)      | LOW (2/10)    | VERYLOW (0/10)     | **2/10**   | P3 ✅    |

---

## 🚨 Critical Risk Points

### 1. Attendance Records - Structural Change ⚠️

**Problem:** Sekarang 1 siswa = 1 record total. Jika add time dimension, structure berubah drastis.

**Decision Required:**

```
Option A: Keep accumulative (current) + add tahun_ajaran_id
  Pro: Minimal breaking
  Con: Tidak detail per bulan

Option B: Change to per-semester tracking
  Pro: More detailed
  Con: Breaking change, perlu migrate existing data

Option C: Change to per-month tracking (like growth_records)
  Pro: Most detailed
  Con: Biggest breaking change
```

**Recommendation:** Start with Option A (minimal risk), plan Option C for future

---

### 2. Data Populate Strategy

**Challenge:** Existing data tidak punya tahun_ajaran_id

**Safe Populate Strategy:**

```sql
-- Step 1: Check data integrity first
SELECT sa.penilaian_id, sa.siswa_nis, ds.kelas, dk.tahun_ajaran_id
FROM student_assessments sa
LEFT JOIN data_siswa ds ON sa.siswa_nis = ds.nis
LEFT JOIN data_kelas dk ON ds.kelas = dk.kelas_id
WHERE dk.tahun_ajaran_id IS NULL; -- Find orphans

-- Step 2: Only populate clean data
UPDATE student_assessments sa
JOIN data_siswa ds ON sa.siswa_nis = ds.nis
JOIN data_kelas dk ON ds.kelas = dk.kelas_id
SET sa.tahun_ajaran_id = dk.tahun_ajaran_id
WHERE dk.tahun_ajaran_id IS NOT NULL;

-- Step 3: Handle orphans manually
-- Either assign to active year or leave NULL
```

---

### 3. Code Update Required

**Files yang Perlu Dicek:**

```
✅ Models: Add 'tahun_ajaran_id' to $fillable
✅ Resources: Update form fields
✅ Resources: Update table filters
✅ Services: Update create/update logic
✅ Observers: Update auto-populate logic
⚠️ AttendanceRecord: Check all queries if changing structure
```

**Search Pattern:**

```bash
# Find all places creating assessments
grep -r "student_assessment::create" app/
grep -r "GrowthRecord::create" app/
grep -r "AttendanceRecord::create" app/
grep -r "monthly_reports::create" app/
```

---

## ✅ Mitigation Strategies

### Strategy 1: Phased Rollout

**Phase 1: Add Columns (Week 1)**

-   ✅ Add nullable `tahun_ajaran_id` columns
-   ✅ Keep existing code working
-   ✅ Populate existing data
-   ✅ Test dual-path (with & without tahun_ajaran_id)

**Phase 2: Update Code (Week 2)**

-   ✅ Update models $fillable
-   ✅ Update Resources to include field
-   ✅ Update Services to auto-populate
-   ✅ Add validation

**Phase 3: Enforce (Week 3+)**

-   ✅ Change to NOT NULL (optional)
-   ✅ Add validation rules
-   ✅ Full testing

---

### Strategy 2: Rollback Plan

**Each migration should have clean rollback:**

```php
public function down(): void
{
    Schema::table('student_assessments', function (Blueprint $table) {
        $table->dropForeign(['tahun_ajaran_id']);
        $table->dropColumn('tahun_ajaran_id');
    });
}
```

**Before Migration:**

```bash
# Backup database
mysqldump -u root sekolah > backup_before_hierarchy_$(date +%Y%m%d).sql

# Test migration on copy
mysql -u root -e "CREATE DATABASE sekolah_test;"
mysql -u root sekolah_test < backup_before_hierarchy_*.sql
# Run migration on test DB first
```

---

### Strategy 3: Data Validation

**Pre-Migration Checks:**

```sql
-- Check siswa without kelas
SELECT COUNT(*) FROM data_siswa WHERE kelas IS NULL;

-- Check kelas without tahun_ajaran_id
SELECT COUNT(*) FROM data_kelas WHERE tahun_ajaran_id IS NULL;

-- Check assessments for siswa without kelas
SELECT COUNT(*)
FROM student_assessments sa
LEFT JOIN data_siswa ds ON sa.siswa_nis = ds.nis
WHERE ds.kelas IS NULL;
```

**Post-Migration Validation:**

```sql
-- Check successful population rate
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN tahun_ajaran_id IS NOT NULL THEN 1 ELSE 0 END) as populated,
    ROUND(SUM(CASE WHEN tahun_ajaran_id IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as percentage
FROM student_assessments;

-- Should be 100% or close to it
```

---

## 🎯 Final Recommendations

### DO NOW (Low Risk, High Value):

1. ✅ **Add tahun_ajaran_id to student_assessments**

    - Risk: 3/10 (LOW-MEDIUM)
    - Value: HIGH (fixes ambiguous semester)
    - Time: 30 minutes
    - Rollback: Easy

2. ✅ **Add tahun_ajaran_id to monthly_reports**

    - Risk: 2/10 (LOW)
    - Value: MEDIUM (consistency)
    - Time: 20 minutes
    - Rollback: Easy

3. ✅ **Add tahun_ajaran_id to growth_records**
    - Risk: 2/10 (LOW)
    - Value: MEDIUM (consistency)
    - Time: 20 minutes
    - Rollback: Easy

### PLAN CAREFULLY (Medium Risk):

4. 🟡 **Restructure attendance_records**
    - Risk: 6/10 (MEDIUM)
    - Value: HIGH (enables time-based tracking)
    - Time: 2-4 hours (including code updates)
    - Rollback: Possible but tedious
    - **Recommendation:** Do in separate sprint with proper testing

### DON'T DO:

❌ **Make tahun_ajaran_id NOT NULL** - Keep nullable for flexibility
❌ **Use ON DELETE CASCADE** - Too risky, use SET NULL
❌ **Migrate without backup** - Always backup first
❌ **Skip testing on copy DB** - Test first!

---

## 📋 Pre-Flight Checklist

Before running migration:

-   [ ] Database backup created
-   [ ] Tested on copy database
-   [ ] Checked data integrity (no orphans)
-   [ ] Reviewed all foreign keys
-   [ ] Confirmed ON DELETE SET NULL (not CASCADE)
-   [ ] Migration has rollback method
-   [ ] Code changes identified and planned
-   [ ] Team notified of changes
-   [ ] Downtime window scheduled (if needed)
-   [ ] Rollback plan documented

---

## 🚀 Execution Readiness

**Student Assessments:** ✅ **READY**

-   Low risk (3/10)
-   High value
-   Easy rollback
-   **Go ahead!**

**Growth & Monthly Reports:** ✅ **READY**

-   Very low risk (2/10)
-   Good value
-   Easy rollback
-   **Go ahead!**

**Attendance Records:** ⚠️ **NEEDS PLANNING**

-   Medium risk (6/10)
-   High value
-   Complex changes
-   **Plan in next sprint**

---

## 💡 Quick Start (Minimal Risk)

**Safest approach right now:**

```sql
-- 1. Add columns only (no data changes)
ALTER TABLE student_assessments ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL;
ALTER TABLE growth_records ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL;
ALTER TABLE monthly_reports ADD COLUMN tahun_ajaran_id BIGINT UNSIGNED NULL;

-- 2. Add indexes (for performance)
CREATE INDEX idx_assessments_tahun ON student_assessments(tahun_ajaran_id);
CREATE INDEX idx_growth_tahun ON growth_records(tahun_ajaran_id);
CREATE INDEX idx_monthly_tahun ON monthly_reports(tahun_ajaran_id);

-- 3. Populate for NEW records going forward
-- Old records stay NULL (won't break anything)

-- 4. Add foreign keys later when data is clean
```

**This approach:**

-   ✅ Zero breaking changes
-   ✅ Can be done anytime
-   ✅ Existing functionality unchanged
-   ✅ New records can use tahun_ajaran_id
-   ✅ Easy rollback (just drop columns)

---

_Risk Analysis Date: 2025-11-16_
_Data Size: 34 records total_
_Overall Risk Level: LOW to MEDIUM (manageable with proper planning)_
