-- ============================================================
-- FIX: Academic Year Relationship & Password Security Issues
-- Date: December 1, 2025
-- ============================================================

USE sekolah;

-- ============================================================
-- ISSUE 1: Column 'academic_year_id' not found
-- ============================================================
-- Problem: Query mencari 'academic_year_id' tapi kolom sebenarnya 'tahun_ajaran_id'
-- Solution: Sudah difix di code (app/Models/academic_year.php)
-- No database changes needed - kolom 'tahun_ajaran_id' sudah benar

-- Verify kolom yang ada:
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'sekolah'
  AND TABLE_NAME = 'student_assessments'
  AND COLUMN_NAME LIKE '%tahun%';

-- Expected result:
-- tahun_ajaran_id | bigint | YES

-- ============================================================
-- ISSUE 2: Password Security (Code Fix Only)
-- ============================================================
-- Problem: 
-- 1. Browser autocomplete mengisi password dari admin yang login
-- 2. Password tidak di-hash dengan benar saat save
-- 
-- Solution: Fixed in code (DataSiswaResource, DataGuruResource, ProfileResource)
-- - Added autocomplete='new-password' to prevent browser autofill
-- - Added Hash::make() to properly hash passwords
-- - Added dehydrated() check to ensure password only saved when filled
--
-- No database changes needed

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- 1. Check if tahun_ajaran_id column exists and has data
SELECT 
    COUNT(*) as total_assessments,
    COUNT(tahun_ajaran_id) as with_tahun_ajaran,
    COUNT(*) - COUNT(tahun_ajaran_id) as without_tahun_ajaran
FROM student_assessments;

-- 2. Check foreign key constraints
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sekolah'
  AND TABLE_NAME = 'student_assessments'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 3. Test relationship query (should work now)
SELECT 
    sa.penilaian_id,
    sa.siswa_nis,
    sa.tahun_ajaran_id,
    ay.year,
    ay.semester
FROM student_assessments sa
LEFT JOIN academic_year ay ON sa.tahun_ajaran_id = ay.tahun_ajaran_id
LIMIT 5;

-- 4. Check users with hashed passwords (should all be bcrypt hashed)
SELECT 
    user_id,
    username,
    LEFT(password, 10) as password_prefix,
    LENGTH(password) as password_length,
    CASE 
        WHEN password LIKE '$2y$%' THEN 'Bcrypt (OK)'
        WHEN password LIKE '$2a$%' THEN 'Bcrypt (OK)'
        ELSE 'NOT HASHED (WARNING!)'
    END as password_status
FROM users
ORDER BY user_id;

-- Expected: All passwords should have password_length = 60 and status = 'Bcrypt (OK)'

-- ============================================================
-- OPTIONAL: Reset passwords if some are not hashed
-- ============================================================
-- WARNING: Only run this if you find unhashed passwords!
-- This will reset passwords to 'password' (hashed)

-- Uncomment below if needed:
-- UPDATE users 
-- SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
-- WHERE LENGTH(password) < 60 OR password NOT LIKE '$2%';
-- 
-- Note: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
--       is bcrypt hash of 'password'

-- ============================================================
-- DEPLOYMENT NOTES
-- ============================================================
-- After deploying code fixes:
-- 1. composer dump-autoload
-- 2. php artisan optimize:clear
-- 3. php artisan filament:optimize
-- 4. Test login with existing users
-- 5. Test creating new siswa/guru (password should not autofill)
-- 6. Test editing siswa/guru (password should remain hashed)
-- 7. Run verification queries above

-- ============================================================
-- END OF FIX SCRIPT
-- ============================================================
