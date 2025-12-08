# Database Naming Convention

## Semantic Primary & Foreign Key Naming Strategy

**Created:** November 15, 2025  
**Context:** Avoid generic "id" naming, use semantic table-based names  
**Objective:** Clear, self-documenting database schema

---

## 🎯 Core Principle

> **"Primary Key name should reflect the table it identifies"**

```
❌ BAD (Generic):
data_guru.id
data_kelas.id
users.id

✅ GOOD (Semantic):
data_guru.guru_id
data_kelas.kelas_id
users.user_id
```

---

## 📋 Complete Schema with Semantic Naming

### **1. Master Tables**

#### **data_siswa** (Natural Key)

```php
Schema::create('data_siswa', function (Blueprint $table) {
    // Primary Key: Natural (NIS)
    $table->integer('nis')->primary();

    // Other unique identifiers
    $table->string('nisn', 20)->unique();

    // Foreign Keys (semantic naming)
    $table->bigInteger('user_id');
    $table->bigInteger('created_by')->nullable();
    $table->bigInteger('updated_by')->nullable();

    // Student data
    $table->string('nama_lengkap');
    $table->string('kelas');
    $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
    $table->date('tanggal_lahir');
    $table->boolean('is_active')->default(true);

    // ... other fields

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('user_id')->references('user_id')->on('users');
    $table->foreign('created_by')->references('user_id')->on('users');
    $table->foreign('updated_by')->references('user_id')->on('users');

    // Indexes
    $table->index(['kelas', 'is_active']);
    $table->index('nisn');
});
```

---

#### **users** (Surrogate Key with Semantic Name)

```php
Schema::create('users', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('user_id')->primary()->autoIncrement();

    // Unique identifiers
    $table->string('username', 50)->unique();
    $table->string('email')->unique();

    // User data
    $table->string('name');
    $table->string('password');
    $table->string('avatar')->nullable();
    $table->rememberToken();

    $table->timestamps();

    // Indexes
    $table->index('username');
    $table->index('email');
});
```

**Note:** Laravel convention uses `id`, but we override with semantic `user_id`

**Model configuration:**

```php
class User extends Model
{
    protected $primaryKey = 'user_id'; // Override Laravel default
    public $incrementing = true;
}
```

---

#### **data_guru** (Surrogate Key with Semantic Name)

```php
Schema::create('data_guru', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('guru_id')->primary()->autoIncrement();

    // Optional natural identifiers
    $table->string('nik', 20)->unique()->nullable();
    $table->string('nip', 20)->unique()->nullable();
    $table->string('nuptk', 20)->unique()->nullable();
    $table->string('passport', 30)->unique()->nullable();

    // Foreign Keys
    $table->bigInteger('user_id')->nullable();

    // Status tracking
    $table->enum('status', ['PNS', 'Swasta', 'Honorer', 'Kontrak', 'Volunteer'])
        ->default('Swasta');
    $table->boolean('data_lengkap')->default(false);

    // Guru data
    $table->string('nama_lengkap');
    $table->string('email')->unique();
    $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
    $table->string('tempat_lahir')->nullable();
    $table->date('tanggal_lahir')->nullable();
    $table->string('alamat')->nullable();
    $table->string('no_telp', 20)->nullable();

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('user_id')->references('user_id')->on('users');

    // Indexes
    $table->index(['status', 'data_lengkap']);
    $table->index(['nik', 'nip']);
});
```

**Model configuration:**

```php
class data_guru extends Model
{
    protected $table = 'data_guru';
    protected $primaryKey = 'guru_id'; // Semantic PK
    public $incrementing = true;
}
```

---

#### **data_kelas** (Surrogate Key with Semantic Name)

```php
Schema::create('data_kelas', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('kelas_id')->primary()->autoIncrement();

    // Foreign Keys (semantic naming)
    $table->bigInteger('walikelas_id')->nullable();
    $table->bigInteger('tahun_ajaran_id')->nullable();

    // Kelas data
    $table->string('nama_kelas');
    $table->integer('tingkat');

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('walikelas_id')->references('guru_id')->on('data_guru')
        ->onDelete('set null');
    $table->foreign('tahun_ajaran_id')->references('tahun_ajaran_id')->on('academic_year')
        ->onDelete('set null');

    // Indexes
    $table->index(['tahun_ajaran_id', 'tingkat']);
});
```

**Model configuration:**

```php
class data_kelas extends Model
{
    protected $table = 'data_kelas';
    protected $primaryKey = 'kelas_id'; // Semantic PK
    public $incrementing = true;
}
```

---

#### **academic_year** (Surrogate Key with Semantic Name)

```php
Schema::create('academic_year', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('tahun_ajaran_id')->primary()->autoIncrement();

    // Academic year data
    $table->string('year', 10);
    $table->enum('semester', ['ganjil', 'genap']);
    $table->date('pembagian_raport');
    $table->boolean('is_active')->default(false);

    $table->timestamps();

    // Unique constraint
    $table->unique(['year', 'semester']);

    // Indexes
    $table->index('is_active');
});
```

**Model configuration:**

```php
class academic_year extends Model
{
    protected $table = 'academic_year';
    protected $primaryKey = 'tahun_ajaran_id'; // Semantic PK
    public $incrementing = true;
}
```

---

#### **sekolah** (Surrogate Key with Semantic Name)

```php
Schema::create('sekolah', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('sekolah_id')->primary()->autoIncrement();

    // Foreign Keys
    $table->bigInteger('kepala_sekolah_id')->nullable();

    // School data
    $table->string('nama_sekolah');
    $table->string('alamat');
    $table->string('npsn', 20)->unique();
    $table->string('nss', 20)->unique()->nullable();
    $table->string('kode_pos', 10);
    $table->string('logo_sekolah')->nullable();

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('kepala_sekolah_id')->references('guru_id')->on('data_guru')
        ->onDelete('set null');
});
```

---

### **2. Transactional Tables**

#### **student_assessments** (Penilaian Siswa)

```php
Schema::create('student_assessments', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('penilaian_id')->primary()->autoIncrement();

    // Foreign Keys (semantic naming)
    $table->integer('siswa_nis');           // FK to data_siswa.nis (natural key!)
    $table->bigInteger('guru_id');          // FK to data_guru.guru_id
    $table->bigInteger('kelas_id');         // FK to data_kelas.kelas_id
    $table->bigInteger('tahun_ajaran_id');  // FK to academic_year.tahun_ajaran_id

    // Assessment data
    $table->string('semester', 10);
    $table->enum('status', ['belum_dinilai', 'sebagian', 'selesai'])
        ->default('belum_dinilai');
    $table->timestamp('completed_at')->nullable();

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('siswa_nis')->references('nis')->on('data_siswa')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    $table->foreign('guru_id')->references('guru_id')->on('data_guru')
        ->onDelete('cascade');
    $table->foreign('kelas_id')->references('kelas_id')->on('data_kelas')
        ->onDelete('cascade');
    $table->foreign('tahun_ajaran_id')->references('tahun_ajaran_id')->on('academic_year')
        ->onDelete('cascade');

    // Unique constraint
    $table->unique(['siswa_nis', 'tahun_ajaran_id', 'semester'],
        'unique_student_semester_assessment');

    // Indexes
    $table->index(['tahun_ajaran_id', 'semester', 'status']);
});
```

**Model configuration:**

```php
class student_assessment extends Model
{
    protected $table = 'student_assessments';
    protected $primaryKey = 'penilaian_id'; // Semantic PK
    public $incrementing = true;

    // Relationships
    public function siswa()
    {
        return $this->belongsTo(data_siswa::class, 'siswa_nis', 'nis');
    }

    public function guru()
    {
        return $this->belongsTo(data_guru::class, 'guru_id', 'guru_id');
    }

    public function kelas()
    {
        return $this->belongsTo(data_kelas::class, 'kelas_id', 'kelas_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(academic_year::class, 'tahun_ajaran_id', 'tahun_ajaran_id');
    }
}
```

---

#### **student_assessment_details** (Detail Penilaian per Aspek)

```php
Schema::create('student_assessment_details', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('detail_id')->primary()->autoIncrement();

    // Foreign Keys
    $table->bigInteger('penilaian_id');     // FK to student_assessments.penilaian_id
    $table->bigInteger('variabel_id');      // FK to assessment_variables.variabel_id

    // Assessment detail data
    $table->enum('rating', ['BB', 'MB', 'BSH', 'BSB']);
    $table->text('catatan')->nullable();
    $table->json('photos')->nullable();

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('penilaian_id')->references('penilaian_id')->on('student_assessments')
        ->onDelete('cascade');
    $table->foreign('variabel_id')->references('variabel_id')->on('assessment_variables')
        ->onDelete('cascade');

    // Unique constraint
    $table->unique(['penilaian_id', 'variabel_id']);

    // Indexes
    $table->index('rating');
});
```

---

#### **growth_records** (Catatan Pertumbuhan)

```php
Schema::create('growth_records', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('pertumbuhan_id')->primary()->autoIncrement();

    // Foreign Keys
    $table->integer('siswa_nis');           // FK to data_siswa.nis
    $table->bigInteger('guru_id');          // FK to data_guru.guru_id
    $table->bigInteger('kelas_id');         // FK to data_kelas.kelas_id
    $table->bigInteger('tahun_ajaran_id');  // FK to academic_year.tahun_ajaran_id

    // Growth record data
    $table->date('measurement_date');
    $table->decimal('lingkar_kepala', 5, 2)->nullable()->comment('dalam cm');
    $table->decimal('lingkar_lengan', 5, 2)->nullable()->comment('dalam cm');
    $table->decimal('berat_badan', 5, 2)->nullable()->comment('dalam kg');
    $table->decimal('tinggi_badan', 5, 2)->nullable()->comment('dalam cm');
    $table->text('catatan')->nullable();

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('siswa_nis')->references('nis')->on('data_siswa')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    $table->foreign('guru_id')->references('guru_id')->on('data_guru')
        ->onDelete('cascade');
    $table->foreign('kelas_id')->references('kelas_id')->on('data_kelas')
        ->onDelete('cascade');
    $table->foreign('tahun_ajaran_id')->references('tahun_ajaran_id')->on('academic_year')
        ->onDelete('cascade');

    // Unique constraint
    $table->unique(['siswa_nis', 'tahun_ajaran_id', 'measurement_date'],
        'unique_student_month_measurement');

    // Indexes
    $table->index(['tahun_ajaran_id', 'measurement_date']);
});
```

---

#### **monthly_reports** (Laporan Bulanan)

```php
Schema::create('monthly_reports', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('laporan_id')->primary()->autoIncrement();

    // Foreign Keys
    $table->integer('siswa_nis');           // FK to data_siswa.nis
    $table->bigInteger('guru_id');          // FK to data_guru.guru_id
    $table->bigInteger('kelas_id');         // FK to data_kelas.kelas_id

    // Report data
    $table->tinyInteger('month')->comment('Month 1-12');
    $table->integer('year')->default(date('Y'));
    $table->text('catatan')->nullable();
    $table->json('photos')->nullable();
    $table->enum('status', ['draft', 'final'])->default('draft');

    $table->timestamps();

    // Foreign key constraints
    $table->foreign('siswa_nis')->references('nis')->on('data_siswa')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    $table->foreign('guru_id')->references('guru_id')->on('data_guru')
        ->onDelete('cascade');
    $table->foreign('kelas_id')->references('kelas_id')->on('data_kelas')
        ->onDelete('cascade');

    // Unique constraint
    $table->unique(['siswa_nis', 'month', 'year'], 'unique_student_month_year');

    // Indexes
    $table->index(['year', 'month', 'status']);
});
```

---

#### **assessment_variables** (Master Variabel Penilaian)

```php
Schema::create('assessment_variables', function (Blueprint $table) {
    // Primary Key: Surrogate (semantic name)
    $table->bigInteger('variabel_id')->primary()->autoIncrement();

    // Variable data
    $table->string('nama_variabel');
    $table->string('kategori')->nullable();
    $table->text('deskripsi')->nullable();
    $table->integer('urutan')->default(0);
    $table->boolean('is_active')->default(true);

    $table->timestamps();

    // Indexes
    $table->index(['kategori', 'urutan']);
    $table->index('is_active');
});
```

---

### **3. System Tables (Keep Laravel Convention)**

```php
// These tables keep Laravel default naming:
sessions → id (string primary key)
notifications → id (uuid primary key)
jobs → id (bigint primary key)
password_reset_tokens → email (string primary key)
```

**Reason:** Don't fight Laravel ecosystem conventions for system tables.

---

## 📊 Naming Convention Summary

### **Pattern:**

| Entity Type        | Primary Key Naming    | Example                               |
| ------------------ | --------------------- | ------------------------------------- |
| **Natural Key**    | Business identifier   | `data_siswa.nis`                      |
| **Surrogate Key**  | `{table_name}_id`     | `data_guru.guru_id`                   |
| **Foreign Key**    | Same as referenced PK | `guru_id` (FK to `data_guru.guru_id`) |
| **Junction Table** | Combination of both   | `kelas_siswa(kelas_id, siswa_nis)`    |

### **Examples:**

```php
// ✅ GOOD: Semantic naming
data_guru.guru_id (PK)
data_kelas.kelas_id (PK)
student_assessments.guru_id (FK → data_guru.guru_id)
student_assessments.siswa_nis (FK → data_siswa.nis)

// ❌ BAD: Generic naming
data_guru.id (unclear)
data_kelas.id (unclear)
student_assessments.data_guru_id (verbose)
student_assessments.data_siswa_id (verbose, plus siswa uses natural key!)
```

---

## 🔄 Migration Strategy

### **Phase 1: Rename Primary Keys**

```php
// Migration: 2025_11_15_000003_rename_primary_keys_to_semantic.php

public function up(): void
{
    // 1. Users table
    Schema::table('users', function (Blueprint $table) {
        $table->renameColumn('id', 'user_id');
    });

    // 2. Data guru table
    Schema::table('data_guru', function (Blueprint $table) {
        $table->renameColumn('id', 'guru_id');
    });

    // 3. Data kelas table
    Schema::table('data_kelas', function (Blueprint $table) {
        $table->renameColumn('id', 'kelas_id');
    });

    // 4. Academic year table
    Schema::table('academic_year', function (Blueprint $table) {
        $table->renameColumn('id', 'tahun_ajaran_id');
    });

    // 5. Sekolah table
    Schema::table('sekolah', function (Blueprint $table) {
        $table->renameColumn('id', 'sekolah_id');
    });

    // ... continue for all tables
}
```

### **Phase 2: Update Foreign Keys**

```php
// Migration: 2025_11_15_000004_update_foreign_keys_to_semantic.php

public function up(): void
{
    // Example: student_assessments table
    Schema::table('student_assessments', function (Blueprint $table) {
        // Drop old constraints
        $table->dropForeign(['data_guru_id']);
        $table->dropForeign(['data_kelas_id']);

        // Rename FK columns
        $table->renameColumn('data_guru_id', 'guru_id');
        $table->renameColumn('data_kelas_id', 'kelas_id');

        // Add new constraints with semantic names
        $table->foreign('guru_id')->references('guru_id')->on('data_guru');
        $table->foreign('kelas_id')->references('kelas_id')->on('data_kelas');
    });
}
```

### **Phase 3: Update Models**

```php
// Update all models to use semantic primary keys

// app/Models/data_guru.php
class data_guru extends Model
{
    protected $table = 'data_guru';
    protected $primaryKey = 'guru_id'; // ← Changed from 'id'
    public $incrementing = true;
}

// app/Models/data_kelas.php
class data_kelas extends Model
{
    protected $table = 'data_kelas';
    protected $primaryKey = 'kelas_id'; // ← Changed from 'id'
    public $incrementing = true;
}

// app/Models/User.php
class User extends Model
{
    protected $primaryKey = 'user_id'; // ← Changed from 'id'
    public $incrementing = true;
}
```

### **Phase 4: Update Filament Resources**

```php
// Update relationship definitions in Filament Resources

// StudentAssessmentResource.php
Select::make('guru_id')  // ← Changed from 'data_guru_id'
    ->relationship('guru', 'nama_lengkap')
    ->label('Wali Kelas'),

Select::make('kelas_id')  // ← Changed from 'data_kelas_id'
    ->relationship('kelas', 'nama_kelas')
    ->label('Kelas'),
```

---

## ✅ Benefits of Semantic Naming

### **1. Self-Documenting Code**

```php
// ❌ BEFORE (unclear):
$assessment->data_guru_id  // What table? What purpose?
$assessment->id            // Which table's id?

// ✅ AFTER (clear):
$assessment->guru_id       // Obviously from data_guru table
$assessment->penilaian_id  // Obviously assessment table's PK
```

### **2. Clear Foreign Key References**

```php
// ❌ BEFORE:
$table->foreign('data_guru_id')->references('id')->on('data_guru');
// Verbose, unclear that 'id' is the PK

// ✅ AFTER:
$table->foreign('guru_id')->references('guru_id')->on('data_guru');
// Clear: guru_id FK references guru_id PK
```

### **3. Simplified Queries**

```php
// ❌ BEFORE:
DB::table('student_assessments as sa')
    ->join('data_guru as dg', 'sa.data_guru_id', '=', 'dg.id')
    ->join('data_kelas as dk', 'sa.data_kelas_id', '=', 'dk.id')
    ->select('sa.id', 'dg.nama_lengkap', 'dk.nama_kelas');
// Confusing: Which 'id'?

// ✅ AFTER:
DB::table('student_assessments as sa')
    ->join('data_guru as g', 'sa.guru_id', '=', 'g.guru_id')
    ->join('data_kelas as k', 'sa.kelas_id', '=', 'k.kelas_id')
    ->select('sa.penilaian_id', 'g.nama_lengkap', 'k.nama_kelas');
// Clear: Each entity has semantic identifier
```

### **4. No Ambiguity in Complex Queries**

```php
// ❌ BEFORE (which id?):
SELECT sa.id, dg.id, dk.id, ds.id
FROM student_assessments sa
JOIN data_guru dg ON sa.data_guru_id = dg.id
JOIN data_kelas dk ON sa.data_kelas_id = dk.id
JOIN data_siswa ds ON sa.data_siswa_id = ds.id;
// Must alias every 'id' column!

// ✅ AFTER (no ambiguity):
SELECT sa.penilaian_id, g.guru_id, k.kelas_id, s.nis
FROM student_assessments sa
JOIN data_guru g ON sa.guru_id = g.guru_id
JOIN data_kelas k ON sa.kelas_id = k.kelas_id
JOIN data_siswa s ON sa.siswa_nis = s.nis;
// Each column name is unique and meaningful!
```

---

## 🎓 Justification untuk Pembimbing

**Argument:**

> "Pak/Bu, kami menggunakan **semantic naming convention** untuk Primary Key dan Foreign Key:
>
> ### Alasan:
>
> 1. **Self-documenting**: `guru_id` langsung jelas dari tabel `data_guru`
> 2. **No ambiguity**: Tidak ada multiple kolom bernama `id` yang membingungkan
> 3. **Clear relationships**: `student_assessments.guru_id` → `data_guru.guru_id` (explicit!)
> 4. **Industry practice**: Banyak perusahaan pakai pattern ini (semantic over generic)
> 5. **Maintenance easier**: Developer baru langsung paham struktur database
>
> ### Contoh:
>
> -   `data_guru.guru_id` (bukan generic `id`)
> -   `data_kelas.kelas_id` (bukan generic `id`)
> -   `student_assessments.guru_id` (FK, langsung jelas referensi ke guru)
>
> Ini membuat database **lebih professional dan readable**!"

---

## 📋 Implementation Checklist

### Phase 1: Planning ✅

-   [x] Document semantic naming convention
-   [x] Define naming pattern for all tables
-   [x] Plan migration strategy

### Phase 2: Migration (TODO)

-   [ ] Create migration to rename all PKs
-   [ ] Create migration to update all FKs
-   [ ] Test migrations on dev database
-   [ ] Backup production before migration

### Phase 3: Code Update (TODO)

-   [ ] Update all Model `$primaryKey` properties
-   [ ] Update all relationship definitions
-   [ ] Update all Filament Resources
-   [ ] Update all raw queries/QueryBuilder calls

### Phase 4: Testing (TODO)

-   [ ] Test all CRUD operations
-   [ ] Test all relationships
-   [ ] Test all Filament Resources
-   [ ] Verify foreign key constraints work

### Phase 5: Documentation (TODO)

-   [ ] Update ERD with semantic names
-   [ ] Update API documentation (if any)
-   [ ] Update developer onboarding docs

---

## ✅ Conclusion

**Semantic Naming Convention:**

```
PRIMARY KEYS:
✅ Natural Key: Use business identifier (siswa.nis)
✅ Surrogate Key: Use {table}_id pattern (guru.guru_id)

FOREIGN KEYS:
✅ Match the referenced PK name
✅ Example: guru_id (FK) → guru_id (PK)

BENEFITS:
✅ Self-documenting schema
✅ No ambiguity in queries
✅ Professional & maintainable
✅ Easier for new developers
✅ Pembimbing will appreciate clarity!
```

**This naming convention makes your database schema much more professional and readable!**

---

**References:**

-   Database Design Best Practices
-   Rails Active Record Conventions
-   Django ORM Naming Patterns
-   Professional Database Standards
