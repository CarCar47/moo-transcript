-- v1.0.32 Rollback Script
-- This script removes the category mapping feature added in v1.0.32
-- Run this script if you need to remove the experimental category mapping feature

-- WARNING: This will remove the mappingtype and categoryid columns
-- Make sure to backup your database before running this script!

-- Step 1: Remove the new fields from gradereport_transcript_courses table
ALTER TABLE mdl_gradereport_transcript_courses
DROP COLUMN IF EXISTS mappingtype;

ALTER TABLE mdl_gradereport_transcript_courses
DROP COLUMN IF EXISTS categoryid;

-- Step 2: Reset plugin version to 1.0.31 (optional - only if rolling back completely)
-- UPDATE mdl_config_plugins
-- SET value = '2025103101'
-- WHERE plugin = 'gradereport_transcript' AND name = 'version';

-- Step 3: Remove the category mapping setting
DELETE FROM mdl_config_plugins
WHERE plugin = 'gradereport_transcript'
AND name = 'enablecategorymapping';

-- Rollback complete!
-- Next steps:
-- 1. Remove v1.0.32 code from the following files:
--    - classes/transcript_generator.php (remove get_student_grade_from_category method)
--    - classes/transcript_generator.php (revert get_student_grades method)
--    - settings.php (remove enablecategorymapping setting)
--    - lang/en/gradereport_transcript.php (remove v1.0.32 strings)
--    - db/upgrade.php (remove upgrade block for 2025110101)
-- 2. Revert version.php to 1.0.31
-- 3. Purge Moodle cache: php admin/cli/purge_caches.php
