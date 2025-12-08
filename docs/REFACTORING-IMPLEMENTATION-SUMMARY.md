# Database Refactoring Implementation Summary

## 3 Major Changes: Natural Key + Semantic Naming + Single Path

**Created:** November 15, 2025  
**Status:** Ready to Execute  
**Migration Order:** Run in sequence (100001 → 100002 → 100003 → 100004)

---

## 🎯 Overview of Changes

### **1. Natural Key untuk Siswa**

-   `data_siswa.nis` (VARCHAR) → (INT) Primary Key
-   Remove auto-increment `id` column
-   NIS becomes the primary identifier for students

### **2. Semantic Naming Convention**

-   `users.id` → `users.user_id`
-   `data_guru.id` → `data_guru.guru_id`
-   `data_kelas.id` → `data_kelas.kelas_id`
-   `academic_year.id` → `academic_year.tahun_ajaran_id`
-   `sekolah.id` → `sekolah.sekolah_id`
-   `student_assessments.id` → `student_assessments.penilaian_id`
-   `growth_records.id` → `growth_records.pertumbuhan_id`
-   `monthly_reports.id` → `monthly_reports.laporan_id`

### **3. Single Path (Relasi Berjenjang)**

-   Add `data_siswa.kelas_id` FK (relasi berjenjang!)
-   Remove redundant FKs from transactional tables:
    -   ❌ `student_assessments`: Remove guru_id, kelas_id, tahun_ajaran_id
    -   ❌ `growth_records`: Remove guru_id, kelas_id, tahun_ajaran_id
    -   ❌ `monthly_reports`: Remove guru_id, kelas_id
-   Keep only `siswa_nis` FK (SINGLE PATH!)

---

## 📋 Migration Files Created

| #   | File                | Purpose                                        |
| --- | ------------------- | ---------------------------------------------- |
| 1   | `2025_11_15_100001` | Convert siswa to natural key + add kelas_id FK |
| 2   | `2025_11_15_100002` | Rename all primary keys to semantic names      |
| 3   | `2025_11_15_100003` | Update foreign keys to match semantic names    |
| 4   | `2025_11_15_100004` | Implement single path relationships            |

---

## 🚀 Execution Steps

### **Prerequisites:**

```bash
# 1. BACKUP DATABASE (CRITICAL!)
php artisan down
mysqldump -u root -p sekolah > backup_before_refactor_$(date +%Y%m%d_%H%M%S).sql

# 2. Create test database
mysql -u root -p -e "CREATE DATABASE sekolah_test"
mysql -u root -p sekolah_test < backup_before_refactor_*.sql

# 3. Update .env to test database
DB_DATABASE=sekolah_test
```

### **Run Migrations (Test Environment):**

```bash
# Run migrations in order
php artisan migrate --path=database/migrations/2025_11_15_100001_convert_siswa_to_natural_key_and_add_kelas_fk.php

php artisan migrate --path=database/migrations/2025_11_15_100002_rename_primary_keys_to_semantic_names.php

php artisan migrate --path=database/migrations/2025_11_15_100003_update_foreign_keys_to_semantic_names.php

php artisan migrate --path=database/migrations/2025_11_15_100004_implement_single_path_relationships.php

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **Verify Changes:**

```bash
# Check table structures
php artisan tinker

# Verify siswa table
DB::select("DESCRIBE data_siswa");
# Should see: nis INT PRIMARY KEY, kelas_id BIGINT FK

# Verify guru table
DB::select("DESCRIBE data_guru");
# Should see: guru_id BIGINT PRIMARY KEY

# Verify student_assessments
DB::select("DESCRIBE student_assessments");
# Should see: penilaian_id PK, siswa_nis FK (NO guru_id, kelas_id!)

# Test relationships
$siswa = \App\Models\data_siswa::where('nis', 1)->first();
$siswa->kelas; // Should work
$siswa->kelas->walikelas; // Should work
$siswa->kelas->tahunAjaran; // Should work
```

### **If Test Successful, Apply to Production:**

```bash
# 1. Update .env back to production
DB_DATABASE=sekolah

# 2. Run migrations
php artisan migrate

# 3. Clear cache
php artisan cache:clear
php artisan config:clear

# 4. Bring site back up
php artisan up
```

---

## 📊 Database Structure After Changes

### **Master Tables:**

```sql
-- data_siswa (Natural Key)
data_siswa:
├── nis (PK) INT                  ← Primary Key (Natural)
├── nisn VARCHAR UNIQUE
├── user_id BIGINT FK             → users.user_id
├── kelas_id BIGINT FK            → data_kelas.kelas_id (NEW!)
├── nama_lengkap VARCHAR
├── is_active BOOLEAN
└── ...

-- users (Semantic PK)
users:
├── user_id (PK) BIGINT           ← Changed from 'id'
├── username VARCHAR UNIQUE
├── email VARCHAR UNIQUE
└── ...

-- data_guru (Semantic PK)
data_guru:
├── guru_id (PK) BIGINT           ← Changed from 'id'
├── nik VARCHAR UNIQUE NULLABLE
├── nip VARCHAR UNIQUE NULLABLE
├── user_id BIGINT FK             → users.user_id
└── ...

-- data_kelas (Semantic PK)
data_kelas:
├── kelas_id (PK) BIGINT          ← Changed from 'id'
├── nama_kelas VARCHAR
├── walikelas_id BIGINT FK        → data_guru.guru_id
├── tahun_ajaran_id BIGINT FK     → academic_year.tahun_ajaran_id
└── ...

-- academic_year (Semantic PK)
academic_year:
├── tahun_ajaran_id (PK) BIGINT   ← Changed from 'id'
├── year VARCHAR
├── semester ENUM
└── ...
```

### **Transactional Tables (Single Path):**

```sql
-- student_assessments (Simplified!)
student_assessments:
├── penilaian_id (PK) BIGINT      ← Changed from 'id'
├── siswa_nis (FK) INT            → data_siswa.nis (SINGLE PATH!)
├── semester VARCHAR
├── status ENUM
└── ...
-- ❌ Removed: guru_id, kelas_id, tahun_ajaran_id (access via siswa!)

-- growth_records (Simplified!)
growth_records:
├── pertumbuhan_id (PK) BIGINT    ← Changed from 'id'
├── siswa_nis (FK) INT            → data_siswa.nis (SINGLE PATH!)
├── measurement_date DATE
├── berat_badan DECIMAL
└── ...
-- ❌ Removed: guru_id, kelas_id, tahun_ajaran_id

-- monthly_reports (Simplified!)
monthly_reports:
├── laporan_id (PK) BIGINT        ← Changed from 'id'
├── siswa_nis (FK) INT            → data_siswa.nis (SINGLE PATH!)
├── month TINYINT
├── year INT
└── ...
-- ❌ Removed: guru_id, kelas_id
```

---

## 📝 Model Updates Required

Update all models to use new primary keys and relationships:

### **app/Models/User.php:**

```php
class User extends Authenticatable
{
    protected $primaryKey = 'user_id'; // ← Changed from 'id'
    public $incrementing = true;
}
```

### **app/Models/data_siswa.php:**

```php
class data_siswa extends Model
{
    protected $table = 'data_siswa';
    protected $primaryKey = 'nis'; // ← Changed from 'id', natural key!
    public $incrementing = true;
    protected $keyType = 'int';

    // NEW: Relationship to kelas (relasi berjenjang!)
    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'kelas_id', 'kelas_id');
    }

    // Existing relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Child relationships
    public function assessments()
    {
        return $this->hasMany(student_assessment::class, 'siswa_nis', 'nis');
    }

    public function growthRecords()
    {
        return $this->hasMany(GrowthRecord::class, 'siswa_nis', 'nis');
    }

    public function monthlyReports()
    {
        return $this->hasMany(monthly_reports::class, 'siswa_nis', 'nis');
    }

    // Accessors for hierarchical access
    public function getGuruAttribute()
    {
        return $this->kelas?->walikelas;
    }

    public function getTahunAjaranAttribute()
    {
        return $this->kelas?->tahunAjaran;
    }
}
```

### **app/Models/data_guru.php:**

```php
class data_guru extends Model
{
    protected $table = 'data_guru';
    protected $primaryKey = 'guru_id'; // ← Changed from 'id'
    public $incrementing = true;

    protected $fillable = [
        'nik', 'nip', 'nuptk', 'passport',
        'status', 'data_lengkap',
        'nama_lengkap', 'email', 'user_id',
        // ... other fields
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function kelasWali()
    {
        return $this->hasMany(data_kelas::class, 'walikelas_id', 'guru_id');
    }
}
```

### **app/Models/data_kelas.php:**

```php
class data_kelas extends Model
{
    protected $table = 'data_kelas';
    protected $primaryKey = 'kelas_id'; // ← Changed from 'id'
    public $incrementing = true;

    public function walikelas()
    {
        return $this->belongsTo(data_guru::class, 'walikelas_id', 'guru_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(academic_year::class, 'tahun_ajaran_id', 'tahun_ajaran_id');
    }

    public function siswa()
    {
        return $this->hasMany(data_siswa::class, 'kelas_id', 'kelas_id');
    }
}
```

### **app/Models/academic_year.php:**

```php
class academic_year extends Model
{
    protected $table = 'academic_year';
    protected $primaryKey = 'tahun_ajaran_id'; // ← Changed from 'id'
    public $incrementing = true;
    public $timestamps = false;

    public function kelas()
    {
        return $this->hasMany(data_kelas::class, 'tahun_ajaran_id', 'tahun_ajaran_id');
    }
}
```

### **app/Models/student_assessment.php:**

```php
class student_assessment extends Model
{
    protected $table = 'student_assessments';
    protected $primaryKey = 'penilaian_id'; // ← Changed from 'id'
    public $incrementing = true;

    protected $fillable = [
        'siswa_nis', // ← Only FK needed! (SINGLE PATH)
        'semester',
        'status',
        'completed_at',
    ];

    // SINGLE PATH: Only relationship to siswa
    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }

    public function details()
    {
        return $this->hasMany(student_assessment_detail::class, 'penilaian_id', 'penilaian_id');
    }

    // Accessors: Access related data via siswa (hierarchical)
    public function getKelasAttribute()
    {
        return $this->siswa?->kelas;
    }

    public function getGuruAttribute()
    {
        return $this->siswa?->kelas?->walikelas;
    }

    public function getTahunAjaranAttribute()
    {
        return $this->siswa?->kelas?->tahunAjaran;
    }
}
```

### **app/Models/GrowthRecord.php:**

```php
class GrowthRecord extends Model
{
    protected $table = 'growth_records';
    protected $primaryKey = 'pertumbuhan_id'; // ← Changed from 'id'
    public $incrementing = true;

    protected $fillable = [
        'siswa_nis', // ← Only FK needed!
        'measurement_date',
        'lingkar_kepala', 'lingkar_lengan',
        'berat_badan', 'tinggi_badan',
        'catatan',
    ];

    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }
}
```

### **app/Models/monthly_reports.php:**

```php
class monthly_reports extends Model
{
    protected $table = 'monthly_reports';
    protected $primaryKey = 'laporan_id'; // ← Changed from 'id'
    public $incrementing = true;

    protected $fillable = [
        'siswa_nis', // ← Only FK needed!
        'month', 'year',
        'catatan', 'photos', 'status',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }
}
```

---

## 🎯 Benefits After Refactoring

### **1. Natural Key untuk Siswa**

```
✅ NIS is meaningful identifier
✅ Direct student identification (no lookup)
✅ Follows academic requirement from pembimbing
✅ No redundant id + nis columns
```

### **2. Semantic Naming**

```
✅ Self-documenting schema (guru_id clearly from data_guru)
✅ No ambiguity in queries (no multiple 'id' columns)
✅ Professional naming convention
✅ Easier for new developers
```

### **3. Single Path (Relasi Berjenjang)**

```
✅ Clear hierarchy: assessment → siswa → kelas → guru/tahun_ajaran
✅ Data consistency guaranteed (one source of truth)
✅ Simplified schema (removed 11 redundant FK columns!)
✅ Follows pembimbing's "relasi satu jalur" principle
✅ Easier maintenance (update kelas = 1 place only)
```

---

## ⚠️ Important Notes

### **Breaking Changes:**

-   All Model `$primaryKey` must be updated
-   All Filament Resources need FK column name updates
-   All queries using `->find()` on siswa must use NIS instead of id
-   Relationships need to specify foreign/local keys explicitly

### **Data Migration:**

-   Migration 1 converts existing NIS VARCHAR to INT
-   Migration 1 populates kelas_id from kelas string
-   Migrations remove redundant FK columns (data not lost, accessed via siswa)

### **Rollback:**

-   Backup database before migration (CRITICAL!)
-   Rollback is complex, restore from backup recommended
-   Test in dev environment first!

---

## ✅ Verification Checklist

After migration:

-   [ ] All tables have semantic primary keys
-   [ ] data_siswa uses nis (INT) as PK
-   [ ] data_siswa has kelas_id FK
-   [ ] student_assessments only has siswa_nis FK
-   [ ] growth_records only has siswa_nis FK
-   [ ] monthly_reports only has siswa_nis FK
-   [ ] All foreign keys reference correct semantic columns
-   [ ] All models updated with new $primaryKey
-   [ ] All relationships work correctly
-   [ ] Filament Resources load without errors
-   [ ] Can create/edit/delete records
-   [ ] Reports generate correctly

---

## 🎓 Justification for Pembimbing

> "Pak/Bu, kami telah mengimplementasikan 3 prinsip yang dijelaskan:
>
> ### 1. Natural Key (NIS sebagai Primary Key)
>
> -   Siswa menggunakan NIS sebagai primary key (bukan auto-increment id)
> -   NIS adalah identitas bisnis yang meaningful
> -   Mengurangi redundansi (tidak ada kolom id yang "tidak berguna")
>
> ### 2. Semantic Naming (Bukan Generic 'id')
>
> -   guru_id (bukan generic 'id') → jelas dari tabel guru
> -   kelas_id → jelas dari tabel kelas
> -   Self-documenting, tidak ambiguous
>
> ### 3. Relasi Satu Jalur (Berjenjang)
>
> -   Assessment → Siswa → Kelas → Guru/Tahun Ajaran
> -   TIDAK ada shortcut/multiple paths
> -   Satu jalur yang jelas dan terstruktur
> -   Data consistency terjamin
>
> Struktur ini sangat **professional** dan mengikuti **database best practices**!"

---

**Status:** Ready to Execute  
**Risk Level:** Medium-High (structural changes)  
**Recommendation:** Test thoroughly in dev environment first!
