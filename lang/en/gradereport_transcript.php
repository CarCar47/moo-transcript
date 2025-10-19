<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'gradereport_transcript', language 'en'
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin name.
$string['pluginname'] = 'Academic Transcripts & CEU Certificates';
$string['transcript:view'] = 'View transcript report';
$string['transcript:viewall'] = 'View all student transcripts';
$string['transcript:manage'] = 'Manage transcript settings';
$string['transcript:request'] = 'Request official transcript';

// Page titles.
$string['viewtranscript'] = 'View Transcript';
$string['mytranscript'] = 'My Transcript';
$string['requesttranscript'] = 'Request Official Transcript';

// Document types.
$string['hourbased'] = 'Hour-Based Transcript';
$string['creditbased'] = 'Credit-Based Transcript';
$string['ceu'] = 'CEU Certificate';

// Grade scales.
$string['gradevalue'] = 'Grade Value';
$string['gradenumber'] = 'Grade Number';
$string['gpa'] = 'GPA';
$string['totalhours'] = 'Total Hours';
$string['totalcredits'] = 'Total Credits';

// Privacy API.
$string['privacy:metadata'] = 'The transcript report plugin stores user transcript requests and verification codes.';
$string['privacy:metadata:transcript_requests'] = 'Transcript request records';
$string['privacy:metadata:transcript_requests:userid'] = 'The ID of the user who requested the transcript';
$string['privacy:metadata:transcript_requests:requestdate'] = 'The date the transcript was requested';
$string['privacy:metadata:transcript_requests:status'] = 'The status of the transcript request';
$string['privacy:metadata:transcript_verification'] = 'Verification codes for issued transcripts';
$string['privacy:metadata:transcript_verification:userid'] = 'The ID of the user for whom the transcript was issued';
$string['privacy:metadata:transcript_verification:issueddate'] = 'The date the transcript was issued';

// Navigation.
$string['eventgradereportviewed'] = 'Transcript report viewed';

// Help page.
$string['help'] = 'Help & Documentation';
$string['pluginhelp'] = 'Transcript Plugin Help & Documentation';
$string['quickstartguide'] = 'Quick Start Guide';
$string['quickstartintro'] = 'Follow these 5 steps to set up the transcript plugin for your institution:';
$string['step1schools'] = 'Step 1: Add Schools';
$string['step1schoolsdesc'] = 'Create school records with name, address, and contact information. Each school uses Moodle\'s grade scale (Grades → Letters).';
$string['step2programs'] = 'Step 2: Create Programs';
$string['step2programsdesc'] = 'Set up academic programs linked to Moodle categories. Choose program type: Hour-Based, Credit-Based, or CEU.';
$string['step3courses'] = 'Step 3: Map Courses';
$string['step3coursesdesc'] = 'Map Moodle courses to programs and assign hours/credits. Courses auto-populate from the selected category.';
$string['step4template'] = 'Step 4: Upload PDF Templates (Optional)';
$string['step4templatedesc'] = 'Upload pre-designed transcript PDF templates with form fields. If no template, plugin generates generic format.';
$string['step5test'] = 'Step 5: Test Transcripts';
$string['step5testdesc'] = 'View sample transcripts to verify grade calculations, GPA, and PDF formatting.';

// PDF Template Creation.
$string['pdftemplatecreation'] = 'PDF Template Creation Guide';
$string['pdftemplateintro'] = 'Create professional transcript templates using Adobe Acrobat Pro or similar PDF editing software. The plugin fills form fields with student data automatically.';
$string['adobeacrobatsetup'] = 'Adobe Acrobat Pro Setup Instructions';
$string['adobestep1'] = 'Open your transcript PDF template in Adobe Acrobat Pro.';
$string['adobestep2'] = 'Go to Tools → Prepare Form. Acrobat will auto-detect fields or you can add them manually.';
$string['adobestep3'] = 'Click "Add a Text Field" tool from the toolbar.';
$string['adobestep4'] = 'Draw a text field where you want student data to appear (e.g., student name, grades).';
$string['adobestep5'] = 'Right-click the field → Properties → General tab.';
$string['adobestep6'] = 'Enter the exact field name from the reference table below (CASE-SENSITIVE!). Example: student_name, grade_letter_1';
$string['adobestep7'] = 'Set field properties: Font (10-12pt), Alignment (Left for text, Right for numbers), Border (None for clean look).';
$string['adobestep8'] = 'Save the PDF. Upload it to the program in Step 4.';

// PDF Field Reference.
$string['pdffieldreference'] = 'PDF Form Field Reference';
$string['pdffieldintro'] = 'Use these exact field names in your PDF template. Field names are case-sensitive. The plugin automatically fills these fields with student data.';
$string['studentinfofields'] = 'Student Information Fields';
$string['coursefields'] = 'Course Fields (Repeating Pattern)';
$string['coursefieldsnote'] = 'Replace {N} with course number (1, 2, 3, etc.). Example: course_number_1, course_number_2, course_number_3...';
$string['summaryfields'] = 'Summary & Calculation Fields';
$string['fieldname'] = 'Field Name';
$string['fieldtype'] = 'Type';
$string['fieldpattern'] = 'Field Pattern';
$string['description'] = 'Description';
$string['example'] = 'Example';

// Troubleshooting.
$string['troubleshooting'] = 'Troubleshooting';
$string['fieldsnotfilling'] = 'PDF Fields Not Filling';
$string['fieldsnotfillingdesc'] = 'If PDF form fields are not being populated with student data:';
$string['troubleshoot1'] = 'Verify field names exactly match the reference table (case-sensitive).';
$string['troubleshoot2'] = 'Check that courses are properly mapped to the program in Step 3.';
$string['troubleshoot3'] = 'Ensure PDF was created with editable form fields (not just text).';
$string['missinggrades'] = 'Missing or Incomplete Grades';
$string['missingradesdesc'] = 'Courses without grades will show "N/A" on transcripts. Verify students have final grades entered in the Moodle gradebook for all completed courses.';
$string['incorrectgpa'] = 'Incorrect GPA Calculation';
$string['incorrectgpadesc'] = 'GPA is calculated using the school\'s grade scale (Grades → Letters). Verify the grade scale is correctly configured at Site Administration → Grades → Letters.';

// Best Practices.
$string['bestpractices'] = 'Best Practices';
$string['bestpractice1'] = 'Test PDF templates with sample data before going live. Use a test student account to generate transcripts.';
$string['bestpractice2'] = 'Use consistent field naming: Add a prefix for each program (e.g., PMU1, PMU2 for Permanent Makeup program).';
$string['bestpractice3'] = 'Keep PDF templates simple: Fewer form fields = easier maintenance. Only add fields for data that changes per student.';
$string['bestpractice4'] = 'Back up PDF templates: Save the original editable .pdf file with form fields before uploading.';
$string['bestpractice5'] = 'Document your field mappings: Keep a spreadsheet of which Moodle courses map to which PDF fields.';
