# Primary Key Strategy Analysis

## Complete Table-by-Table Recommendation

**Created:** November 15, 2025  
**Context:** Small school system (50 siswa/year, max 2000 siswa total)  
**Objective:** Determine optimal primary key strategy for each table

---

## 🎯 Quick Summary

| Category           | Tables                                     | Recommendation       | Reason                              |
| ------------------ | ------------------------------------------ | -------------------- | ----------------------------------- |
| **Master Data**    | siswa, users                               | ✅ **Natural Key**   | Stable, meaningful, always assigned |
| **Reference Data** | guru, kelas, academic_year, sekolah        | ⚖️ **Surrogate Key** | Nullable fields, flexible data      |
| **Transactional**  | penilaian, growth_records, monthly_reports | ✅ **Surrogate Key** | High volume, complex relationships  |
| **System Tables**  | sessions, notifications, jobs              | ✅ **Surrogate Key** | Laravel standard                    |

---

## 📋 Detailed Analysis by Table

### **1. data_siswa** ✅ **NATURAL KEY (NIS)**

**Current Structure:**

```php
data_siswa:
├── id (PK) BIGINT AUTO_INCREMENT
├── nis VARCHAR UNIQUE
├── nisn VARCHAR UNIQUE
├── nama_lengkap
└── ... (30+ fields)
```

**Recommendation:** ✅ **Use NIS as Natural Primary Key**

**Rationale:**

```
✅ NIS ALWAYS assigned saat registrasi (mandatory)
✅ NIS is meaningful (nomor urut pendaftaran)
✅ NIS IMMUTABLE (tidak berubah selama sekolah)
✅ NIS is numeric INT (3-4 digit) → fast!
✅ Small scale (50/year, max 2000 total)
✅ Sequential & predictable (001, 002, 003...)
✅ Sesuai requirement akademik pembimbing

❌ No nullable issue (NIS mandatory)
❌ No update cascade issue (NIS never changes)
❌ No format change risk (INT always works)
```

**Proposed Structure:**

```php
Schema::create('data_siswa', function (Blueprint $table) {
    // Natural Key as PK
    $table->integer('nis')->primary();  // 3-4 digit sequential

    // Other identifiers
    $table->string('nisn', 20)->unique();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

    // Student info
    $table->string('nama_lengkap');
    $table->string('kelas');
    $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
    $table->date('tanggal_lahir');
    // ... other fields

    $table->timestamps();

    // Indexes
    $table->index('nisn');
    $table->index('kelas');
    $table->index('is_active');
});
```

**Benefits:**

-   Direct identification: "Siswa NIS 123" (everyone knows)
-   No mapping needed: NIS = PK (simple!)
-   Meaningful in reports: "NIS" column shows actual student number
-   Academic appeal: Natural business identifier as PK

**Trade-offs:**

-   IF NIS needs correction → cascade update to child tables
    -   But: Rare occurrence (< 1x per year)
    -   Acceptable for 2000 records scale (< 1 second)

---

### **2. users** ✅ **NATURAL KEY (Username) - WITH CAVEAT**

**Current Structure:**

```php
users:
├── id (PK) BIGINT AUTO_INCREMENT
├── username VARCHAR UNIQUE
├── email VARCHAR UNIQUE
├── password
└── ...
```

**Recommendation:** ⚖️ **Could use Username as Natural Key, BUT Surrogate is Safer**

**Option A: Natural Key (Username as PK)**

```php
Schema::create('users', function (Blueprint $table) {
    $table->string('username', 50)->primary();  // Natural PK
    $table->string('email')->unique();
    $table->string('name');
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

**Pros:**

-   ✅ Username meaningful & human-readable
-   ✅ Username always required (mandatory)
-   ✅ Direct login identification

**Cons:**

-   ❌ Username COULD change (user rename request)
-   ❌ Laravel ecosystem assumes numeric user_id
-   ❌ Many packages use `user_id` foreign key convention
-   ❌ Cascade update if username changes

**Option B: Surrogate Key (Recommended)**

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();  // Surrogate PK (Laravel standard)
    $table->string('username', 50)->unique();  // Business identifier
    $table->string('email')->unique();
    $table->string('name');
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();

    $table->index('username');
});
```

**✅ FINAL RECOMMENDATION: Surrogate Key (id)**

**Reason:**

-   Laravel convention (Filament, Spatie Permission, etc expect `user_id`)
-   Username could change (user request)
-   Package compatibility (most use numeric user ID)
-   Industry standard for authentication systems

---

### **3. data_guru** ✅ **SURROGATE KEY (id)**

**Current Structure:**

```php
data_guru:
├── id (PK) BIGINT AUTO_INCREMENT
├── nip INT UNIQUE
├── nuptk INT UNIQUE
├── nik (not exists yet)
└── ...
```

**Recommendation:** ✅ **Keep Surrogate Key (id)**

**Rationale:**

```
❌ NIK/NIP NOT always available (guru swasta/honorer)
❌ NIK/NIP could be NULL at hiring time
❌ Guru asing: no NIK, use passport instead
❌ Multiple identifier types (NIK, NIP, NUPTK, Passport)
✅ Need flexibility for incomplete data
✅ Data can be filled gradually
```

**Proposed Structure:**

```php
Schema::create('data_guru', function (Blueprint $table) {
    // Surrogate Key (mandatory, always generated)
    $table->id();

    // Optional natural identifiers
    $table->string('nik', 20)->unique()->nullable();      // KTP (optional)
    $table->string('nip', 20)->unique()->nullable();      // PNS only
    $table->string('nuptk', 20)->unique()->nullable();    // Teacher registry
    $table->string('passport', 30)->unique()->nullable(); // Foreign teachers

    // Status tracking
    $table->enum('status', ['PNS', 'Swasta', 'Honorer', 'Kontrak', 'Volunteer'])
        ->default('Swasta');
    $table->boolean('data_lengkap')->default(false);

    // Required fields
    $table->string('nama_lengkap');
    $table->string('email')->unique();
    $table->foreignId('user_id')->nullable()->constrained('users');

    // Other fields
    $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
    $table->string('tempat_lahir')->nullable();
    $table->date('tanggal_lahir')->nullable();
    $table->string('alamat')->nullable();
    $table->string('no_telp', 20)->nullable();

    $table->timestamps();

    // Indexes
    $table->index(['status', 'data_lengkap']);
    $table->index('nik');
    $table->index('nip');
});
```

**Real-world scenario:**

```
Day 1: Guru baru join
INSERT INTO data_guru (nama_lengkap, email, status, data_lengkap)
VALUES ('Ahmad Suhendra', 'ahmad@school.id', 'Honorer', false);
→ id = 1 (auto-generated) ✅

Week 2: NIK tersedia
UPDATE data_guru SET nik = '3201234567890', data_lengkap = true WHERE id = 1;
→ No cascade! ✅
```

---

### **4. data_kelas** ✅ **SURROGATE KEY (id)**

**Current Structure:**

```php
data_kelas:
├── id (PK) BIGINT
├── nama_kelas VARCHAR
├── walikelas_id FK → data_guru.id
├── tahun_ajaran_id FK → academic_year.id
├── tingkat INT
```

**Recommendation:** ✅ **Keep Surrogate Key (id)**

**Rationale:**

```
❌ nama_kelas NOT unique across years
    - "Kelas A" tahun 2024 ≠ "Kelas A" tahun 2025
❌ Composite PK would be ugly: (nama_kelas, tahun_ajaran_id)
✅ Kelas data minimal, surrogate key is clean
✅ Many relationships (siswa, guru, assessments)
```

**Note:** nama_kelas is NOT a good natural key because it repeats every year.

---

### **5. academic_year** ⚖️ **COULD USE Natural Key (year + semester composite)**

**Current Structure:**

```php
academic_year:
├── id (PK) BIGINT
├── year VARCHAR UNIQUE
├── semester ENUM(ganjil, genap)
├── pembagian_raport DATE
```

**Recommendation:** ⚖️ **Surrogate Key (id) is Fine, but Composite Natural Key is Valid**

**Option A: Surrogate Key (Current - Recommended)**

```php
Schema::create('academic_year', function (Blueprint $table) {
    $table->id();  // Simple, works well
    $table->string('year', 10);
    $table->enum('semester', ['ganjil', 'genap']);
    $table->date('pembagian_raport');
    $table->boolean('is_active')->default(false);

    // Unique constraint
    $table->unique(['year', 'semester']);

    $table->timestamps();
});
```

**Option B: Composite Natural Key (Academic Purist)**

```php
Schema::create('academic_year', function (Blueprint $table) {
    $table->string('year', 10);
    $table->enum('semester', ['ganjil', 'genap']);

    // Composite primary key
    $table->primary(['year', 'semester']);

    $table->date('pembagian_raport');
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});

// Child tables would have composite FK:
$table->string('academic_year', 10);
$table->enum('academic_semester', ['ganjil', 'genap']);
$table->foreign(['academic_year', 'academic_semester'])
    ->references(['year', 'semester'])
    ->on('academic_year');
```

**✅ RECOMMENDATION: Keep Surrogate Key (id)**

**Reason:**

-   Simpler foreign keys (single column)
-   Less complex queries
-   Academic year rarely referenced directly (usually through kelas)
-   Composite FK makes code verbose

---

### **6. sekolah** ✅ **SURROGATE KEY (id)** - OR Single Record Pattern

**Current Structure:**

```php
sekolah:
├── id (PK) BIGINT
├── nama_sekolah VARCHAR
├── npsn INT
├── nss INT
├── kepala_sekolah VARCHAR
└── ...
```

**Recommendation:** ⚖️ **Surrogate Key (id) OR Singleton Pattern**

**Option A: Current (Multiple Schools - Future-proof)**

```php
// If system might support multiple schools in future
Schema::create('sekolah', function (Blueprint $table) {
    $table->id();
    $table->string('npsn', 20)->unique();  // National school ID
    $table->string('nss', 20)->unique()->nullable();
    $table->string('nama_sekolah');
    $table->string('alamat');
    $table->foreignId('kepala_sekolah_id')->nullable()
        ->constrained('data_guru')
        ->onDelete('set null');
    $table->string('logo_sekolah')->nullable();
    $table->timestamps();
});
```

**Option B: Singleton (Only One School)**

```php
// If ALWAYS only one school (no multi-tenant)
Schema::create('sekolah', function (Blueprint $table) {
    $table->id()->default(1);  // Always ID = 1
    $table->string('npsn', 20)->unique();
    $table->string('nama_sekolah');
    // ... same fields

    // Ensure only 1 record
    DB::statement('CREATE UNIQUE INDEX idx_singleton ON sekolah ((1))');
});
```

**✅ RECOMMENDATION: Keep Current (Surrogate with id)**

**Reason:**

-   Future-proof (might expand to multi-school)
-   Standard pattern
-   Not worth optimization for 1 record

---

### **7. student_assessments** ✅ **SURROGATE KEY (id)**

**Current Structure:**

```php
student_assessments:
├── id (PK) BIGINT
├── data_siswa_id FK
├── data_guru_id FK
├── data_kelas_id FK
├── academic_year_id FK
├── semester VARCHAR
├── status ENUM
└── UNIQUE(data_siswa_id, academic_year_id, semester)
```

**Recommendation:** ✅ **Keep Surrogate Key (id)**

**Rationale:**

```
✅ Transactional table (high volume)
✅ Complex composite unique constraint already exists
✅ Many child records (student_assessment_details)
✅ Status changes frequently (belum_dinilai → sebagian → selesai)
❌ Composite natural key would be 3 columns (ugly FK)
```

**Perfect as-is!** Surrogate PK + unique constraint on business logic.

---

### **8. growth_records** ✅ **SURROGATE KEY (id)**

**Current Structure:**

```php
growth_records:
├── id (PK) BIGINT
├── data_siswa_id FK
├── data_guru_id FK
├── data_kelas_id FK
├── academic_year_id FK
├── measurement_date DATE
├── berat_badan, tinggi_badan, etc
└── UNIQUE(data_siswa_id, academic_year_id, measurement_date)
```

**Recommendation:** ✅ **Keep Surrogate Key (id)**

**Rationale:** Same as student_assessments - transactional with composite business logic.

---

### **9. monthly_reports** ✅ **SURROGATE KEY (id)**

**Current Structure:**

```php
monthly_reports:
├── id (PK) BIGINT
├── data_siswa_id FK
├── data_guru_id FK
├── data_kelas_id FK
├── month TINYINT
├── year INT
├── catatan TEXT
├── photos JSON
└── UNIQUE(data_siswa_id, month, year)
```

**Recommendation:** ✅ **Keep Surrogate Key (id)**

**Rationale:** Transactional, composite natural key (siswa + month + year) too complex for PK.

---

### **10. System Tables** ✅ **Follow Laravel Convention**

```php
// sessions: string id as PK (Laravel default)
// notifications: uuid as PK (Laravel default)
// jobs: bigint id as PK (Laravel default)
// password_reset_tokens: email as PK (Laravel default)
```

**Recommendation:** ✅ **Keep as-is** - Don't fight Laravel conventions

---

## 🎯 Final Recommendation Matrix

### **Use NATURAL KEY (Recommended):**

| Table          | Natural Key | Type            | Rationale                                     |
| -------------- | ----------- | --------------- | --------------------------------------------- |
| **data_siswa** | **nis**     | INT (3-4 digit) | Mandatory, immutable, meaningful, small scale |

### **Use SURROGATE KEY (Recommended):**

| Table                   | Surrogate Key       | Natural Keys (Unique)                    | Rationale                              |
| ----------------------- | ------------------- | ---------------------------------------- | -------------------------------------- |
| **users**               | **id**              | username, email                          | Laravel convention, rename flexibility |
| **data_guru**           | **id**              | nik, nip, nuptk, passport (all nullable) | Incomplete data, multiple ID types     |
| **data_kelas**          | **id**              | nama_kelas + tahun_ajaran_id             | Not unique across years                |
| **academic_year**       | **id**              | year + semester                          | Avoid composite FK                     |
| **sekolah**             | **id**              | npsn                                     | Future-proof for multi-school          |
| **student_assessments** | **id**              | siswa + year + semester                  | Transactional, complex composite       |
| **growth_records**      | **id**              | siswa + year + date                      | Transactional, complex composite       |
| **monthly_reports**     | **id**              | siswa + month + year                     | Transactional, complex composite       |
| **All system tables**   | **Laravel default** | -                                        | Don't fight framework                  |

---

## 📊 Migration Strategy

### **Priority 1: Refactor data_siswa to Natural Key**

```php
// New migration: 2025_11_15_000002_refactor_siswa_to_natural_key.php
public function up(): void
{
    // Step 1: Add new nis_temp as integer
    Schema::table('data_siswa', function (Blueprint $table) {
        $table->integer('nis_temp')->nullable()->after('id');
    });

    // Step 2: Convert existing nis VARCHAR to INT
    DB::statement("UPDATE data_siswa SET nis_temp = CAST(nis AS UNSIGNED)");

    // Step 3: Drop old string nis
    Schema::table('data_siswa', function (Blueprint $table) {
        $table->dropColumn('nis');
    });

    // Step 4: Rename nis_temp to nis and make it primary
    Schema::table('data_siswa', function (Blueprint $table) {
        $table->renameColumn('nis_temp', 'nis');
    });

    // Step 5: Update all child tables FK
    // student_assessments: Add siswa_nis column
    Schema::table('student_assessments', function (Blueprint $table) {
        $table->integer('siswa_nis')->after('id');
    });

    // Copy data: siswa_nis = siswa.nis where siswa_id = siswa.id
    DB::statement('
        UPDATE student_assessments sa
        JOIN data_siswa ds ON sa.data_siswa_id = ds.id
        SET sa.siswa_nis = ds.nis
    ');

    // Step 6: Drop old FK, make nis the new PK and FK
    Schema::table('data_siswa', function (Blueprint $table) {
        $table->dropPrimary('id');
        $table->dropColumn('id');
        $table->primary('nis');
    });

    Schema::table('student_assessments', function (Blueprint $table) {
        $table->dropForeign(['data_siswa_id']);
        $table->dropColumn('data_siswa_id');

        $table->foreign('siswa_nis')
            ->references('nis')
            ->on('data_siswa')
            ->onUpdate('cascade')
            ->onDelete('cascade');
    });

    // Repeat for: growth_records, monthly_reports, etc
}
```

### **Priority 2: Ensure data_guru has nullable NIK/NIP**

Already done in previous migration: `2025_11_15_000001_refactor_guru_table_to_surrogate_key.php`

---

## 💡 Benefits Summary

### **With Natural Key for data_siswa:**

```
✅ Direct student identification
   - Query: SELECT * FROM penilaian WHERE siswa_nis = 123
   - Everyone knows "Siswa NIS 123" instantly

✅ Meaningful reports
   - Raport shows NIS (actual student number)
   - No need to join siswa table for identification

✅ Academic credibility
   - "We use NIS as primary key" sounds professional
   - Natural business identifier as PK (academic best practice)

✅ Simple data entry
   - Guru inputs NIS (they know by heart)
   - No need to lookup id first

✅ Reduced redundancy (pembimbing concern addressed!)
   - No separate id column
   - NIS serves both business and technical purposes
```

### **With Surrogate Key for data_guru:**

```
✅ Flexible data entry
   - Insert guru without NIK/NIP (data lengkap nanti)
   - Support guru asing (passport instead of NIK)

✅ No cascade nightmares
   - Update NIK: 1 row (5ms)
   - vs Natural Key: 1000+ rows cascade (500ms-5s)

✅ Multiple identifier support
   - NIK, NIP, NUPTK, Passport (all optional)
   - No "dummy" values (000000, N/A)

✅ Future-proof
   - Format changes easy
   - Data evolution supported
```

---

## 🎓 Justification untuk Pembimbing

**Argument:**

> "Pak/Bu, setelah analisis mendalam terhadap semua tabel dalam sistem:
>
> ### Natural Key (data_siswa):
>
> -   **NIS cocok sebagai Primary Key** karena:
>     -   Format INT (bukan VARCHAR) → performance sama dengan surrogate key
>     -   SELALU diisi saat registrasi → tidak pernah NULL
>     -   TIDAK PERNAH berubah → immutable
>     -   Meaningful untuk admin & guru
>     -   Scale kecil (2000 siswa) → update cascade acceptable
>
> ### Surrogate Key (data_guru & others):
>
> -   **Guru TIDAK cocok Natural Key** karena:
>     -   NIK/NIP bisa NULL (guru honorer/swasta belum punya)
>     -   Guru asing pakai passport (format berbeda)
>     -   Data bisa incomplete saat hiring
>     -   PRIMARY KEY tidak bisa NULL!
>
> ### Hasil:
>
> -   ✅ **Hybrid approach**: Natural untuk siswa, Surrogate untuk guru
> -   ✅ **Mengurangi redundansi** di siswa (no id column)
> -   ✅ **Fleksibilitas** di guru (nullable NIK/NIP)
> -   ✅ **Best of both worlds!**"

---

## 📋 Implementation Checklist

### Phase 1: Guru Table (Already Done ✅)

-   [x] Make NIK/NIP nullable
-   [x] Add status field
-   [x] Add data_lengkap flag
-   [x] Add passport field
-   [x] Migration created

### Phase 2: Siswa Table (TODO)

-   [ ] Convert nis VARCHAR → INT
-   [ ] Make nis PRIMARY KEY
-   [ ] Update all child table FKs (student_assessments, growth_records, monthly_reports)
-   [ ] Test cascade updates
-   [ ] Update Filament Resources

### Phase 3: Models & Resources (TODO)

-   [ ] Update data_siswa model (primaryKey = 'nis')
-   [ ] Update data_guru model (add new fields to fillable)
-   [ ] Update StudentAssessmentResource (FK to siswa_nis)
-   [ ] Update GrowthRecordResource (FK to siswa_nis)
-   [ ] Update MonthlyReportResource (FK to siswa_nis)

### Phase 4: Testing (TODO)

-   [ ] Test siswa CRUD with NIS as PK
-   [ ] Test guru CRUD with NULL NIK/NIP
-   [ ] Test relationships (assessments, growth, reports)
-   [ ] Test cascade updates (update NIS)
-   [ ] Performance benchmark (JOIN queries)

---

## ✅ Conclusion

**Optimal Strategy untuk Sekolah Anda:**

```
MASTER DATA (Predictable):
✅ data_siswa → Natural Key (nis INT)

REFERENCE DATA (Flexible):
✅ users, data_guru, data_kelas, academic_year → Surrogate Key (id)

TRANSACTIONAL DATA (Complex):
✅ student_assessments, growth_records, monthly_reports → Surrogate Key (id)

SYSTEM TABLES:
✅ Follow Laravel conventions
```

**Benefits:**

-   Meaningful student identification (NIS)
-   Flexible guru data (nullable NIK/NIP)
-   Production-ready (handles real-world scenarios)
-   Academic credibility (natural key for core entity)
-   Future-proof (surrogate for complex/changing data)

**This is the BEST HYBRID APPROACH for your specific context!**

---

**References:**

-   Database Design Principles (Connolly & Begg)
-   Laravel Best Practices
-   Industry Standards (Oracle, Microsoft SQL)
-   Real-world School System Requirements
