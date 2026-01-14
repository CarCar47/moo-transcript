# Changelog

All notable changes to the Academic Transcripts & CEU Certificates plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.34] - 2025-01-14

### Changed
- **Multi-version Moodle support** - Lowered minimum Moodle requirement from 5.1 to 4.4
  - Changed `$plugin->requires` from `2025092600` to `2024042200`
  - Added `$plugin->supported = [404, 501]` to explicitly declare compatible versions
  - Allows installation on Moodle 4.4, 4.5 LTS, 5.0, and 5.1

### Compatibility
- Moodle 4.4 (2024042200) - Minimum supported
- Moodle 4.5 LTS (2024100700) - Long-term support
- Moodle 5.0 (2024110400) - Current release
- Moodle 5.1 (2025092600) - Latest supported

## [1.0.33] - 2025-11-01 (BETA)

### Fixed
- **Multiple category mappings per course** - Allow adding multiple gradebook categories from the same Moodle course as separate transcript lines
  - Removed unique constraint on (programid, courseid) pair
  - Added composite unique index on (programid, courseid, mappingtype, categoryid)
  - Each category now appears as separate line on transcript with its own grade
  - Category fullname becomes the course code/title on transcript
- **Form submission bug** - Fixed by removing client-side JavaScript validation (version 2025110107)
  - Removed form `id` attribute and JavaScript submit event listener
  - Now uses server-side validation only (matches all other forms in plugin)
  - Server-side validation already handles all error cases with proper redirects
- **Category dropdown not populating** - Fixed JavaScript element ID selectors (version 2025110111)
  - Changed from custom IDs to Moodle's standard `id_{elementname}` pattern
  - JavaScript now correctly finds form elements: `id_courseid`, `id_mappingtype`, `id_categoryid`
  - AJAX category loading now works when course is selected and mapping type is "category"
  - Follows official Moodle Forms API documentation
- **Category form validation failing** - Fixed by pre-loading all options server-side (version 2025110112)
  - Per Moodle best practices: "For select elements, only data that could have been selected will be allowed"
  - All gradebook categories from all program courses now loaded in form definition
  - Changed from `hideIf()` to `disabledIf()` for proper form submission
  - Removed AJAX dynamic population (not compatible with Moodle's validation)
  - Category options formatted as "CourseShortname - Category Name" for clarity
- **Form loading error** - Fixed missing global $CFG declaration (version 2025110113)
  - Added `global $CFG;` at start of definition() method
  - Matches pattern used in program_form.php and ajax_get_categories.php
  - Resolves "Failed opening required '/gradelib.php'" error
  - Follows official Moodle moodleform best practices
- **Namespace error** - Fixed grade_category class reference (version 2025110114)
  - Changed `grade_category::fetch_all()` to `\grade_category::fetch_all()`
  - Leading backslash `\` references global namespace in PHP
  - Required because form is in `gradereport_transcript\forms` namespace
  - `grade_category` is legacy Moodle class (not namespaced)
  - Resolves "Class 'gradereport_transcript\forms\grade_category' not found" error
- **Transcript display errors for category mappings** - Fixed (version 2025110115-2025110118)
  - **ROOT CAUSE** (version 2025110118) - `get_course_mappings()` method was NOT retrieving `mappingtype` and `categoryid` fields from database
    - SQL query at line 177 only selected old fields, missing the new category mapping fields added in v1.0.32
    - Added conditional field detection for `mappingtype` and `categoryid` (lines 183-186)
    - Added defensive defaults for backward compatibility (lines 200-204)
    - This fix makes all the other fixes (lines 2025110116-2025110117) actually work
  - **Category names not displaying in PDF** (version 2025110116) - Added missing `global $DB;` declaration in `add_hourbased_courses_table()` method
    - Without global declaration, `$DB->get_record()` call for category names failed silently
    - Now category fullname displays correctly on PDF transcripts instead of course name
  - **Category names not displaying in HTML view** (version 2025110117) - Added category name logic to `generate_transcript.php` line 258-274
    - HTML view was hardcoded to show `$course->shortname . ' - ' . $course->fullname` without checking mappingtype
    - Added same category detection logic as PDF generator
    - Now category fullname displays correctly in HTML view, unofficial PDF, and official PDF
  - **Empty categories showing "A" grade** (version 2025110116) - Fixed by checking for actual activities (itemtype='mod')
    - Previous `get_children()` approach returned category total items, making empty categories appear non-empty
    - Now uses `$DB->count_records_select()` to check specifically for gradeable activities (quiz, assignment, etc.)
    - Empty categories (no mod items) now correctly show "N/A" instead of "A"
  - Matches working course mapping pattern (line 395 check for gradeable activities)
  - Prevents empty categories from incorrectly aggregating to 100%

### Changed
- **Redesigned manage_courses.php UI**
  - New "Add Mapping" form for adding courses or categories one at a time
  - Table now displays ALL existing mappings (not just one per course)
  - Added DELETE button for each mapping row
  - Mapping type displayed as read-only text (cannot change after creation)
  - Helper function `extract_course_code_from_category()` extracts code from category name
  - Save handler now works with mapping IDs instead of course IDs

### Technical Details
- Database upgrade: Version 2025110102 migrates index structure
- Grade fetching verified to work correctly with multiple category mappings from same course
- JavaScript updated to handle category dropdown in Add Mapping form (AJAX only)
- Client-side form validation removed to match Moodle plugin patterns
- All code marked with `v1.0.33` comments for easy identification

## [1.0.32] - 2025-11-01 (BETA)

### Added (Experimental - Versioned for Easy Removal)
- **Gradebook category mapping** - Program courses can now be mapped to gradebook categories within a single Moodle course
  - Useful for short programs where all content is taught in one course but separated by categories
  - New database fields: `mappingtype` ('course' or 'category'), `categoryid`
  - New setting: `enablecategorymapping` (disabled by default)
  - Feature can be completely disabled via admin settings
  - All code marked with `v1.0.32` comments for easy identification and removal

### Technical Details
- New method: `get_student_grade_from_category()` in `transcript_generator.php`
- Modified: `get_student_grades()` to support dual mapping (course OR category)
- Database upgrade: Version 2025110101 adds new fields with backward compatibility
- All existing course-based mappings continue working unchanged
- Rollback script available: `db/rollback_v1.0.32.sql`

### Changed
- Plugin maturity set to BETA for testing phase
- Default mapping type: 'course' (maintains backward compatibility)

## [1.0.31] - 2025-11-01

### Fixed
- Removed obsolete `showsignature` setting that was replaced by QR code verification in v1.0.20
- Fixed transfer credit quality points incorrectly displaying in student HTML view (now shows "N/A" as per AACRAO standards)
- Fixed transfer credits incorrectly inflating institutional GPA in student view
- Implemented `allowunofficial` setting enforcement to properly control student access to unofficial transcripts
- Added unofficial disclaimer message to HTML views (top and bottom) and PDF transcripts
- Fixed pickup person field permanently blocked due to hardFreeze - replaced with dynamic form controls

### Changed
- Updated .gitignore to comprehensively prevent build artifacts and temporary files from being committed
- Reorganized documentation into `docs/` directory
- Moved DEPLOYMENT-TO-PRODUCTION.md to docs/DEPLOYMENT.md
- Added CHANGELOG.md for better version tracking

### Removed
- Moved all build artifacts (.tar.gz files) to external archive location
- Removed debug.patch from repository

## [1.0.30] - 2025-10-30

### Fixed
- Smart pagination to eliminate blank pages while maintaining AACRAO compliance
- Improved quality points calculation for excluded grades

## [1.0.29] - 2025-10-25

### Added
- Customizable academic policies per school for transcripts

### Fixed
- School selector and missing parameter error in manage_policies.php
- Added "Manage Academic Policies" menu link

## [1.0.28] - 2025-10-25

### Fixed
- AACRAO compliance - exclude transfer credit quality points from institutional GPA calculation

## Previous Versions

See git commit history for detailed changes in versions prior to 1.0.28.
