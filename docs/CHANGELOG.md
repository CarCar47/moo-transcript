# Changelog

All notable changes to the Academic Transcripts & CEU Certificates plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.33] - 2025-11-01 (BETA)

### Fixed
- **Multiple category mappings per course** - Allow adding multiple gradebook categories from the same Moodle course as separate transcript lines
  - Removed unique constraint on (programid, courseid) pair
  - Added composite unique index on (programid, courseid, mappingtype, categoryid)
  - Each category now appears as separate line on transcript with its own grade
  - Category fullname becomes the course code/title on transcript
- **Form submission bug** - Fixed JavaScript validation preventing form submission (version 2025110106)
  - Added explicit `return true;` at end of validation function
  - Form now submits correctly when all validations pass

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
- JavaScript updated to handle category dropdown in Add Mapping form
- JavaScript validation fixed to explicitly allow form submission when validation passes
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
