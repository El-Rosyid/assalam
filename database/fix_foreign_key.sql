-- ============================================================
-- FIX: Foreign Key Constraint Error
-- Issue: student_assessment_details references wrong table name
-- ============================================================

USE sekolah;

-- Step 1: Drop existing foreign key yang salah
ALTER TABLE `student_assessment_details` 
DROP FOREIGN KEY `student_assessment_details_variabel_id_foreign`;

-- Step 2: Recreate foreign key dengan nama tabel yang benar
-- Tabel: assessment_variable (singular, bukan assessment_variables)
-- Column: id (bukan variabel_id)
ALTER TABLE `student_assessment_details`
ADD CONSTRAINT `student_assessment_details_variabel_id_foreign` 
FOREIGN KEY (`variabel_id`) 
REFERENCES `assessment_variable` (`id`) 
ON DELETE CASCADE;

-- Step 3: Verify constraints
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sekolah'
  AND TABLE_NAME = 'student_assessment_details'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Expected result:
-- student_assessment_details | penilaian_id | student_assessment_details_penilaian_id_foreign | student_assessments | penilaian_id
-- student_assessment_details | variabel_id  | student_assessment_details_variabel_id_foreign  | assessment_variable | id
