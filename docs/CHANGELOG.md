# Changelog

All notable changes to the Academic Transcripts & CEU Certificates plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
