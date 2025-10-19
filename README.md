# Moodle Academic Transcript & CEU Certificate System

**Plugin Type:** Grade Report (`gradereport_transcript`)
**Moodle Version:** 5.1+
**PHP Version:** 8.2+
**Version:** 1.0.0
**License:** GNU GPL v3 or later

## Description

A comprehensive Moodle plugin that generates official academic transcripts and CEU (Continuing Education Unit) certificates. Supports multiple document types, custom grading scales, and program-specific PDF templates.

## Features

### Three Document Types
1. **Hour-Based Transcripts** - Vocational/Diploma programs (theory + lab hours)
2. **Credit-Based Transcripts** - Academic degrees (Associate, Bachelor, Master)
3. **CEU Certificates** - Single-course completion certificates

### Key Capabilities
- Program-specific PDF templates (uploaded by admin)
- School-specific grading scales (customizable A-F to GPA mapping)
- Automatic GPA calculation (weighted by hours or credits)
- Multi-school support
- Transfer credit management
- Digital verification codes with QR codes
- Public certificate verification
- FERPA/GDPR compliant (Privacy API implemented)
- Official and unofficial transcript generation
- Transcript request workflow with approval

## System Requirements

- **Moodle:** 5.1 or higher
- **PHP:** 8.2.0 or higher (64-bit)
- **PHP Extensions:** sodium, zlib
- **Optional:** PDFtk (for advanced PDF form filling)

## Installation

See [INSTALLATION.md](INSTALLATION.md) for detailed setup instructions.

## Quick Start

1. Install plugin via ZIP upload or manual installation
2. Add your school information (Site admin → Reports → Transcripts → Schools)
3. Create a grading scale (or use default)
4. Add a program and upload PDF template
5. Map courses to PDF form fields
6. Students can now view and request transcripts!

## Documentation

- [Admin Guide](docs/ADMIN_GUIDE.md) - How to configure programs and templates
- [User Guide](docs/USER_GUIDE.md) - How students access transcripts
- [Developer Guide](docs/DEVELOPER.md) - Plugin architecture and customization

## Support

For issues, feature requests, or questions:
- Email: support@cor4edu.com
- GitHub: https://github.com/cor4edu/moodle-gradereport_transcript

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## Credits

Developed by COR4EDU
Built for Moodle 5.1 following official plugin standards
