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

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'Academic Transcripts & CEU Certificates';
$string['transcript:view'] = 'View transcript report';
$string['transcript:viewall'] = 'View all student transcripts';
$string['transcript:manage'] = 'Manage transcript settings';
$string['transcript:request'] = 'Request official transcript';
$string['transcript:download'] = 'Download official transcript with verification code';

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
$string['grandtotal'] = 'Grand Total';

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

// Admin menu items.
$string['manageschools'] = 'Manage Schools';
$string['manageprograms'] = 'Manage Programs';
$string['managecourses'] = 'Map Courses to Programs';

// School management.
$string['addschool'] = 'Add New School';
$string['editschool'] = 'Edit School';
$string['deleteschool'] = 'Delete School';
$string['schoolname'] = 'School Name';
$string['schoolname_help'] = 'The official name of the educational institution (e.g., "Professional Career School").';
$string['schooladdress'] = 'School Address';
$string['schooladdress_help'] = 'The physical mailing address of the school. This will appear on transcripts.';
$string['schoolphone'] = 'Phone Number';
$string['schoolphone_help'] = 'Primary contact phone number for the school (e.g., "(123) 456-7890").';
$string['schoolwebsite'] = 'Website URL';
$string['schoolwebsite_help'] = 'School website URL. Must include http:// or https:// (e.g., "https://www.example.edu").';
$string['schoollogo'] = 'School Logo';
$string['schoollogo_help'] = 'Upload the school logo (PNG, JPG, or GIF). Maximum file size: 1 MB. This logo will appear on transcripts.';
$string['noschools'] = 'No schools have been added yet. Click "Add New School" to create your first school.';
$string['schooladded'] = 'School added successfully.';
$string['schoolupdated'] = 'School updated successfully.';
$string['schooldeleted'] = 'School deleted successfully.';
$string['deleteschoolconfirm'] = 'Are you sure you want to delete the school "{$a}"? This action cannot be undone. All programs associated with this school will also be affected.';

// Form validation errors.
$string['error:schoolnameempty'] = 'School name cannot be empty or contain only whitespace.';
$string['error:websiteinvalid'] = 'Website URL must start with http:// or https://';
$string['error:phoneinvalid'] = 'Phone number contains invalid characters. Use only digits, spaces, dashes, parentheses, and plus signs.';
$string['error:programnameempty'] = 'Program name cannot be empty or contain only whitespace.';
$string['error:schoolrequired'] = 'You must select a school.';
$string['error:categoryrequired'] = 'You must select a Moodle category.';
$string['error:invalidprogramtype'] = 'Invalid program type selected.';
$string['error:negativevalue'] = 'Price cannot be negative.';
$string['invalidschoolid'] = 'Invalid school ID provided.';

// Program management.
$string['addprogram'] = 'Add New Program';
$string['editprogram'] = 'Edit Program';
$string['deleteprogram'] = 'Delete Program';
$string['programname'] = 'Program Name';
$string['programname_help'] = 'The official name of the academic program (e.g., "Permanent Makeup Artistry", "Medical Assistant Diploma").';
$string['school'] = 'School';
$string['school_help'] = 'Select the school that offers this program. Schools are configured in "Manage Schools".';
$string['selectschool'] = '-- Select a school --';
$string['noschoolsavailable'] = 'No schools available';
$string['mustcreateschool'] = 'You must create at least one school before adding programs. Go to "Manage Schools" first.';
$string['programcategory'] = 'Moodle Category';
$string['programcategory_help'] = 'Select the Moodle category containing the courses for this program. The plugin will auto-detect courses from this category when mapping courses in Step 3.';
$string['selectcategory'] = '-- Select a category --';
$string['programtype'] = 'Program Type';
$string['programtype_help'] = 'Select the type of program:<br>
<strong>Hour-Based:</strong> Vocational/diploma programs measured in theory and lab hours (e.g., 720 total hours).<br>
<strong>Credit-Based:</strong> Academic degree programs measured in semester credits (e.g., 60 credits for Associate degree).<br>
<strong>CEU:</strong> Single-course continuing education units for professional development.';
$string['pdftemplate'] = 'PDF Template';
$string['pdftemplate_help'] = 'Upload a PDF template with form fields for this program (optional). Maximum file size: 5 MB. If no template is uploaded, the plugin will generate a generic transcript format. See the Help page for instructions on creating PDF templates with Adobe Acrobat Pro.';
$string['noprograms'] = 'No programs have been added yet. Click "Add New Program" to create your first program.';
$string['programadded'] = 'Program added successfully.';
$string['programupdated'] = 'Program updated successfully.';
$string['programdeleted'] = 'Program deleted successfully.';
$string['deleteprogramconfirm'] = 'Are you sure you want to delete the program "{$a}"? This action cannot be undone. All course mappings and transcript templates for this program will also be deleted.';
$string['categorynotfound'] = 'Category not found';
$string['notemplate'] = 'No template';
$string['templateuploaded'] = 'Template uploaded';

// Hour type labels (for hour-based programs).
$string['hourlabels'] = 'Hour Type Labels';
$string['hour1label'] = 'Hour Type 1 Label';
$string['hour1label_help'] = 'Label for the first hour column (e.g., "Theory Hours", "Classroom Hours", "Lecture Hours"). Leave blank to hide this column.';
$string['hour2label'] = 'Hour Type 2 Label';
$string['hour2label_help'] = 'Label for the second hour column (e.g., "Lab Hours", "Practical Hours", "Studio Hours"). Leave blank to hide this column.';
$string['hour3label'] = 'Hour Type 3 Label';
$string['hour3label_help'] = 'Label for the third hour column (e.g., "Clinical Hours", "Externship Hours", "Field Work"). Leave blank to hide this column.';

// Course mapping.
$string['selectprogram'] = '-- Select a program --';
$string['loadcourses'] = 'Load Courses';
$string['mappingcoursesfor'] = 'Mapping Courses for: {$a}';
$string['coursemappinginstructions'] = 'Assign hours, credits, or CEU values to each course below. Set the sort order to control how courses appear on transcripts (1 = first, 2 = second, etc.). Click "Save Mappings" when done.';
$string['noprogramsavailable'] = 'No programs available. You must create at least one program first.';
$string['mustcreateprogram'] = 'Go to "Manage Programs" to create a program before mapping courses.';
$string['nocoursesincategory'] = 'No courses found in this program\'s category. Add courses to the category in Moodle first.';
$string['coursemappingssaved'] = 'Course mappings saved successfully.';
$string['savemappings'] = 'Save Mappings';
$string['coursecode'] = 'Course Code';
$string['coursename'] = 'Course Name';
$string['sortorder'] = 'Sort Order';
$string['sortorder_help'] = 'Display order on the transcript (1 = first course, 2 = second course, etc.).';
$string['theoryhours'] = 'Theory Hours';
$string['theoryhours_help'] = 'Number of classroom/lecture hours for this course.';
$string['labhours'] = 'Lab Hours';
$string['labhours_help'] = 'Number of hands-on/practical hours for this course.';
$string['credits'] = 'Credits';
$string['credits_help'] = 'Number of semester credits for this course (e.g., 3.0 for a 3-credit course).';
$string['ceuvalue'] = 'CEU Value';
$string['ceuvalue_help'] = 'Continuing Education Unit value for this course (e.g., 1.2 CEUs).';

// Phase 4: Transcript Generation strings.
$string['generatetranscript'] = 'Generate Transcript';
$string['downloadtranscript'] = 'Download Transcript';
$string['downloadpdf'] = 'Download PDF';
$string['viewtranscript'] = 'View Transcript';
$string['mytranscript'] = 'My Academic Transcript';
$string['studenttranscript'] = 'Student Transcript';
$string['selectprogram'] = 'Select Program';
$string['selectprogram_help'] = 'Choose the program for which you want to generate a transcript.';
$string['officialtranscript'] = 'Official Transcript';
$string['unofficialtranscript'] = 'Unofficial Transcript';
$string['downloadofficial'] = 'Download Official Transcript';
$string['downloadunofficial'] = 'Download Unofficial Transcript';
$string['transcriptfor'] = 'Transcript for {$a}';
$string['viewingtranscriptfor'] = 'Viewing transcript for: {$a}';

// Verification strings.
$string['verificationcode'] = 'Verification Code';
$string['verificationcode_help'] = 'Unique code that can be used to verify the authenticity of this document.';
$string['verifytranscript'] = 'Verify Transcript';
$string['enterverificationcode'] = 'Enter Verification Code';
$string['verify'] = 'Verify';
$string['validtranscript'] = 'Valid Transcript';
$string['invalidcode'] = 'Invalid Verification Code';
$string['codenotfound'] = 'This verification code was not found in our system.';
$string['expired'] = 'Expired';
$string['documentexpired'] = 'This document has expired.';
$string['issuedate'] = 'Issue Date';
$string['expirydate'] = 'Expiry Date';
$string['neverexpires'] = 'Never Expires';

// Program enrollment strings.
$string['programenrollment'] = 'Program Enrollment';
$string['noprogramsenrolled'] = 'You are not enrolled in any programs.';
$string['multipleprograms'] = 'You are enrolled in multiple programs. Please select one.';
$string['notranscripts'] = 'No transcripts available.';
$string['notranscriptsavailable'] = 'No transcripts are available for this program.';
$string['nogradesyet'] = 'No grades have been recorded yet.';
$string['incompletegrades'] = 'Some courses do not have final grades yet.';
$string['studentnotenrolled'] = 'This student is not enrolled in any mapped courses for transcript programs.';
$string['youarenotenrolled'] = 'You are not enrolled in any courses that are mapped to transcript programs. Please contact your administrator.';
$string['availabletranscripts'] = 'Available Transcripts';
$string['selectprogrambelow'] = 'Select a program below to view or download your transcript.';
$string['school'] = 'School';

// Grade display strings.
$string['grade'] = 'Grade';
$string['gradepercentage'] = 'Percentage';
$string['gradeletter'] = 'Letter Grade';
$string['gpa'] = 'GPA';
$string['cumulativegpa'] = 'Cumulative GPA';
$string['qualitypoints'] = 'Quality Points';
$string['totalhours'] = 'Total Hours';
$string['totalcredits'] = 'Total Credits';
$string['totalceu'] = 'Total CEUs';
$string['coursesandhours'] = 'Courses and Hours';
$string['coursesandgrades'] = 'Courses and Grades';
$string['continuingeducationunits'] = 'Continuing Education Units';

// Document type strings.
$string['documenttype'] = 'Document Type';
$string['transcript'] = 'Transcript';
$string['ceucertificate'] = 'CEU Certificate';
$string['academic_transcript'] = 'Academic Transcript';
$string['official_document'] = 'Official Document';
$string['unofficial_document'] = 'Unofficial Document';
$string['watermark_unofficial'] = 'UNOFFICIAL - NOT FOR OFFICIAL USE';

// Student information strings.
$string['studentinformation'] = 'Student Information';
$string['studentname'] = 'Student Name';
$string['studentid'] = 'Student ID';
$string['studentemail'] = 'Email';
$string['programinformation'] = 'Program Information';
$string['programname'] = 'Program Name';
$string['programtype_hourbased'] = 'Hour-Based Program';
$string['programtype_creditbased'] = 'Credit-Based Program';
$string['programtype_ceu'] = 'CEU Program';

// Error messages.
$string['errorloadingprogram'] = 'Error loading program information.';
$string['errorloadingstudent'] = 'Error loading student information.';
$string['errorgeneratingpdf'] = 'Error generating PDF transcript.';
$string['errornoprogram'] = 'No program specified.';
$string['errornopermission'] = 'You do not have permission to view this transcript.';
$string['invalidprogramtype'] = 'Invalid program type: {$a}';
$string['unabletogenerateverificationcode'] = 'Unable to generate unique verification code.';
$string['errorinsertingverificationrecord'] = 'Error saving verification record to database.';
$string['programnotfound'] = 'Program not found.';
$string['usernotfound'] = 'User not found.';
$string['nocoursesmapped'] = 'No courses are mapped to this program yet.';

// Success messages.
$string['transcriptgenerated'] = 'Transcript generated successfully.';
$string['pdfdownloaded'] = 'PDF downloaded successfully.';
$string['verificationcodesaved'] = 'Verification code saved successfully.';

// Capabilities for transcript viewing.
$string['transcript:generate'] = 'Generate own transcript';
$string['transcript:generateall'] = 'Generate transcripts for all students';
$string['transcript:download'] = 'Download transcript PDF';

// Help text for transcript actions.
$string['transcripthelp'] = 'How to Use Transcripts';
$string['viewtranscripthelp'] = '<strong>View Transcript:</strong> Opens an HTML preview showing your courses, grades, and GPA. This does not create an official document.';
$string['unofficialtranscripthelp'] = '<strong>Download Unofficial PDF:</strong> Downloads a PDF transcript without a verification code. Suitable for personal records or informal purposes. Not valid for official use.';
$string['officialtranscripthelp'] = '<strong>Download Official PDF:</strong> Generates an official transcript with a unique verification code. This document can be verified by third parties and is suitable for official submissions to employers or other institutions.';

// Settings page strings.
$string['enablestudents'] = 'Enable student access to transcripts';
$string['enablestudents_help'] = 'When enabled, students can view and download their own transcripts. Disable this to restrict access to administrators and teachers only. Students will see a clear error message if they try to access while disabled.';
$string['allowunofficial'] = 'Allow unofficial transcript downloads';
$string['allowunofficial_help'] = 'When enabled, students can download unofficial PDF transcripts for their personal records. Official transcripts (with verification codes) always require the download capability regardless of this setting.';
$string['transcriptlinkinreports'] = 'Show transcript link in grade reports';
$string['transcriptlinkinreports_help'] = 'When enabled, a link to the transcript report will appear when viewing user grade reports. This provides quick access from the gradebook.';
$string['studentaccessdisabled'] = 'Student access to transcripts is currently disabled. Please contact your site administrator for assistance.';
$string['showsignature'] = 'Show signature and seal area on official transcripts';
$string['showsignature_help'] = 'When enabled, official transcripts will include a signature line and official seal/stamp area at the bottom of the document. Disable this if you do not want these areas to appear on the PDF.';

// Admin student transcript viewer strings.
$string['viewstudenttranscripts'] = 'View Student Transcripts';
$string['searchstudent'] = 'Search for a Student';
$string['studentnameoremail'] = 'Student name or email';
$string['nostudentsfound'] = 'No students found matching your search criteria.';
$string['selectedstudent'] = 'Selected Student';
$string['selectdifferentstudent'] = 'Select a Different Student';
$string['searchinstructions'] = 'Use the search box above to find a student by name or email address. Once selected, you can view and download their transcripts for all programs they are enrolled in.';
$string['toomanyresults'] = 'More than 20 results found. Please refine your search to narrow down the results.';

// Verification system strings (Phase 7).
$string['verifytranscript'] = 'Verify Transcript';
$string['verifycode'] = 'Verification Code';
$string['entercode'] = 'Enter verification code';
$string['verifybutton'] = 'Verify';
$string['validtranscript'] = 'Valid Transcript';
$string['invalidcode'] = 'Invalid Verification Code';
$string['codenotfound'] = 'This verification code does not exist in our system.';
$string['verificationresult'] = 'Verification Result';
$string['issuedto'] = 'Issued To';
$string['issuedate'] = 'Issue Date';
$string['documenttype'] = 'Document Type';
$string['transcriptofficial'] = 'Official Transcript';
$string['transcriptunofficial'] = 'Unofficial Transcript';
$string['verificationinstructions'] = 'Enter the verification code found on the transcript to verify its authenticity. The code format is TXN- followed by 12 alphanumeric characters.';
$string['scantoverify'] = 'Scan to verify';
$string['verificationqrcode'] = 'Verification QR Code';

// Request/Payment system strings (Phase 6.1).
$string['requesttranscript'] = 'Request Transcript';
$string['requestdescription'] = 'Use this form to request an official or unofficial transcript. Official transcripts are sent directly to institutions and may have a fee. Unofficial transcripts are for your personal records.';
$string['selectprogram'] = 'Select Program';
$string['selectprogram_help'] = 'Choose the program for which you want to request a transcript.';
$string['requesttype'] = 'Request Type';
$string['requesttype_help'] = 'Official transcripts are sent directly to institutions and include verification features. Unofficial transcripts are for personal use only.';
$string['deliverymethod'] = 'Delivery Method';
$string['deliverymethod_help'] = 'Choose how you would like to receive your transcript.';
$string['deliveryemail'] = 'Email Delivery';
$string['deliverypostal'] = 'Postal Mail';
$string['deliverypickup'] = 'In-Person Pickup';
$string['recipientinformation'] = 'Recipient Information';
$string['recipientdescription'] = 'For official transcripts, please provide the complete mailing information for the institution or organization that will receive the transcript.';
$string['recipientname'] = 'Institution/Company Name';
$string['recipientname_help'] = 'REQUIRED for official transcripts. Enter the full name of the institution or company that will receive the transcript.';
$string['recipientaddress'] = 'Mailing Address';
$string['recipientaddress_help'] = 'REQUIRED for postal delivery. Enter the complete mailing address including street, city, state/province, and postal code.';
$string['recipientphone'] = 'Phone Number';
$string['recipientphone_help'] = 'Optional. Contact phone number for the recipient institution.';
$string['recipientemail'] = 'Email Address';
$string['recipientemail_help'] = 'REQUIRED for email delivery. Email address where the transcript should be sent.';
$string['requestnotes'] = 'Additional Notes';
$string['requestnotes_help'] = 'Optional. Any special instructions or notes for processing your transcript request.';
$string['pricinginformation'] = 'Pricing Information';
$string['nopricingconfigured'] = 'Pricing has not been configured yet. Please contact an administrator.';
$string['firsttranscriptfree'] = 'Your first official transcript is FREE!';
$string['unofficialfree'] = 'Unofficial transcripts are FREE.';
$string['officialpricelabel'] = 'Official Transcript Price';
$string['officialprice'] = 'Official Transcript Price';
$string['officialprice_help'] = 'Set the price for official transcripts in USD. Enter 0.00 to make official transcripts free.';
$string['unofficialpricelabel'] = 'Unofficial Transcript Price';
$string['unofficialprice'] = 'Unofficial Transcript Price';
$string['unofficialprice_help'] = 'Set the price for unofficial transcripts in USD. Enter 0.00 to make unofficial transcripts free.';
$string['submitrequest'] = 'Submit Request';
$string['requestsubmitted'] = 'Your transcript request has been submitted successfully. You will be notified when it is processed.';
$string['yourrequests'] = 'Your Previous Requests';
$string['requestdate'] = 'Request Date';
$string['program'] = 'Program';
$string['type'] = 'Type';
$string['status'] = 'Status';
$string['price'] = 'Price';
$string['paymentstatus'] = 'Payment Status';
$string['recipient'] = 'Recipient';
$string['actions'] = 'Actions';
$string['student'] = 'Student';
$string['requests'] = 'Transcript Requests';
$string['managerequests'] = 'Manage Transcript Requests';
$string['managerequestsdesc'] = 'View and manage student transcript requests. You can approve or reject requests, mark payments as received, and view recipient information.';
$string['filters'] = 'Filters';
$string['filter'] = 'Apply Filters';
$string['norequests'] = 'No transcript requests found.';
$string['noprogramsavailable'] = 'No programs are available for transcript requests. Please ensure you are enrolled in at least one course.';
$string['statuspending'] = 'Pending';
$string['statusapproved'] = 'Approved';
$string['statusrejected'] = 'Rejected';
$string['paymentpending'] = 'Payment Pending';
$string['paymentpaid'] = 'Paid';
$string['paymentfree'] = 'Free';
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['markpaid'] = 'Mark as Paid';
$string['confirmapprove'] = 'Are you sure you want to approve this transcript request for {$a->studentname} ({$a->programname}, {$a->requesttype})?';
$string['confirmreject'] = 'Are you sure you want to reject this transcript request for {$a->studentname} ({$a->programname}, {$a->requesttype})?';
$string['confirmmarkpaid'] = 'Are you sure you want to mark this request as paid for {$a->studentname} ({$a->programname}, {$a->requesttype})?';
$string['requestapproved'] = 'Transcript request has been approved.';
$string['requestrejected'] = 'Transcript request has been rejected.';
$string['markedpaid'] = 'Request has been marked as paid.';
$string['newrequestsubject'] = 'New Transcript Request';
$string['newrequestbody'] = 'A new transcript request has been submitted:

Student: {$a->studentname}
Program: {$a->programname}
Type: {$a->requesttype}
Price: ${$a->price}

Please review and process this request at: {$a->url}';
$string['requeststatusapprovedsubject'] = 'Transcript Request Approved';
$string['requeststatusapprovedbody'] = 'Dear {$a->studentname},

Your transcript request has been approved.

Program: {$a->programname}
Type: {$a->requesttype}

You will receive your transcript shortly.';
$string['requeststatusrejectedsubject'] = 'Transcript Request Rejected';
$string['requeststatusrejectedbody'] = 'Dear {$a->studentname},

Unfortunately, your transcript request has been rejected.

Program: {$a->programname}
Type: {$a->requesttype}

Please contact the registrar for more information.';
$string['requeststatuspaidsubject'] = 'Transcript Payment Received';
$string['requeststatuspaidbody'] = 'Dear {$a->studentname},

We have received your payment for the transcript request.

Program: {$a->programname}
Type: {$a->requesttype}

Your transcript will be processed shortly.';

// Phase 6.2: Order buttons and pricing configuration
$string['orderofficial'] = 'Order Official Transcript';
$string['orderunofficial'] = 'Order Unofficial Transcript';
$string['pricingnotconfigured'] = 'Pricing not configured. Please contact administrator.';

// Pricing configuration
$string['configurepricing'] = 'Configure Pricing';
$string['pricingconfiguration'] = 'Transcript Pricing Configuration';
$string['pricingdescription'] = 'Configure transcript pricing for each school. Set prices for official and unofficial transcripts, and optionally make the first official transcript free for students.';
$string['firstfree'] = 'First Official Transcript Free';
$string['firstfree_help'] = 'Enable this to make the first official transcript free for each student. Subsequent official transcripts will be charged at the official price.';
$string['officialpriceis'] = 'Official transcripts: {$a}';
$string['unofficialpriceis'] = 'Unofficial transcripts: {$a}';
$string['pricingupdated'] = 'Pricing configuration updated successfully.';
$string['noschoolsconfigured'] = 'No schools configured. Please create a school first before configuring pricing.';
$string['notconfigured'] = 'Not Configured';
$string['setup'] = 'Setup';
$string['school'] = 'School';

// Delivery tracking
$string['deliverystatus'] = 'Delivery Status';
$string['deliverytracking'] = 'Delivery Tracking';
$string['trackingnumber'] = 'Tracking Number';
$string['deliverynotes'] = 'Delivery Notes';
$string['deliverydate'] = 'Delivery Date';

// Delivery statuses
$string['deliverypending'] = 'Pending Delivery';
$string['deliverysent'] = 'Sent';
$string['deliverydelivered'] = 'Delivered';
$string['deliverypickedup'] = 'Picked Up';

// Payment tracking
$string['recordpayment'] = 'Record Payment';
$string['receiptnumber'] = 'Receipt Number';
$string['paymentnotes'] = 'Payment Notes';
$string['markaspaid'] = 'Mark as Paid';
$string['paymentdetails'] = 'Payment Details';
$string['paymentinformation'] = 'Payment Information';

// Delivery actions
$string['markassent'] = 'Mark as Sent';
$string['markasmailed'] = 'Mark as Mailed';
$string['markaspickedup'] = 'Mark as Picked Up';
$string['pickupdate'] = 'Pickup Date';
$string['pickedupby'] = 'Person Who Picked Up';
$string['maileddate'] = 'Mailed Date';
$string['emailsentdate'] = 'Email Sent Date';
$string['carrier'] = 'Carrier';

// Transaction history
$string['transactionhistory'] = 'Transaction History';
$string['officialtranscriptnumber'] = 'Official #';
$string['alltranscriptrequests'] = 'All Requests for {$a}';
$string['viewtransactionhistory'] = 'View Transaction History';
$string['notransactionhistory'] = 'No transcript requests found for this student.';

// Delivery method labels
$string['deliveryemail'] = 'Email Delivery';
$string['deliverypostal'] = 'Postal Mail';
$string['deliverypickup'] = 'Pickup at Registrar';

// Order information
$string['orderinformation'] = 'Order Information';
$string['orderdate'] = 'Order Date';
$string['ordernumber'] = 'Order Number';

// Pricing display
$string['pricinginformationtitle'] = 'Pricing Information';
$string['calculatedprice'] = 'Calculated Price';
$string['freefirstofficial'] = 'Your first official transcript is FREE!';
$string['subsequentprice'] = 'Subsequent official transcripts: {$a}';
$string['thisistranscript'] = 'This is your {$a} official transcript.';

// Request form updates
$string['selectdeliverymethod'] = 'Select Delivery Method';
$string['deliverymethodemail'] = 'Email to Me';
$string['deliverymethodpickup'] = 'Pickup at Registrar Office';
$string['deliverymethodemailinstitution'] = 'Email to Institution';
$string['deliverymethodpostalinstitution'] = 'Mail to Institution';

// Admin delivery tracking
$string['deliveryinformation'] = 'Delivery Information';
$string['recorddelivery'] = 'Record Delivery';
$string['emaildelivery'] = 'Email Delivery';
$string['postaldelivery'] = 'Postal Delivery';
$string['pickupdelivery'] = 'Pickup Delivery';
$string['deliverymethod'] = 'Delivery Method';
$string['recipientinformation'] = 'Recipient Information';

// Phase 6.3: Request Details and Management
$string['requestdetails'] = 'Request Details';
$string['requestsummary'] = 'Request Summary';
$string['requestupdated'] = 'Request details updated successfully';
$string['details'] = 'Details';

// Program completion dates for official transcripts (v1.0.7)
$string['programcompletioninformation'] = 'Program Completion Information';
$string['programcompletioninformation_help'] = 'Enter the program start date and completion status for official transcripts. These dates will appear on the official transcript PDF.';
$string['programstartdate'] = 'Program Start Date';
$string['programstartdate_help'] = 'The date when the student began this program.';
$string['completionstatus'] = 'Completion Status';
$string['completionstatus_help'] = 'Select whether the student graduated from the program or withdrew.';
$string['completionstatus_graduated'] = 'Graduated';
$string['completionstatus_withdrawn'] = 'Withdrawn';
$string['graduationdate'] = 'Graduation Date';
$string['graduationdate_help'] = 'The date when the student graduated from this program.';
$string['withdrawndate'] = 'Withdrawn Date';
$string['withdrawndate_help'] = 'The date when the student withdrew from this program.';

// Payment methods
$string['paymentmethod'] = 'Payment Method';
$string['paymentmethod_help'] = 'Select the payment method used for this transaction.';
$string['paymentcash'] = 'Cash';
$string['paymentcheck'] = 'Check';
$string['paymentcredit'] = 'Credit Card';
$string['paymentdebit'] = 'Debit Card';
$string['paymentonline'] = 'Online Payment';
$string['paymentother'] = 'Other';
$string['paiddate'] = 'Date Paid';
$string['paiddate_help'] = 'The date when the payment was received.';
$string['paymentrefunded'] = 'Refunded';

// Receipt and invoice
$string['receiptnumber_help'] = 'Enter the receipt or transaction number for record keeping.';
$string['paymentnotes_help'] = 'Any additional notes about the payment (optional).';
$string['invoicenumber'] = 'Invoice Number';
$string['invoicedate'] = 'Invoice Date';

// Delivery tracking
$string['deliverystatus_help'] = 'Current status of transcript delivery.';
$string['deliverydate_help'] = 'The date when the transcript was sent or delivered.';
$string['trackingnumber_help'] = 'Postal tracking number (for mail delivery only).';
$string['deliverynotes_help'] = 'Any additional notes about the delivery (optional).';
$string['pickupperson'] = 'Person Who Picked Up';
$string['pickupperson_help'] = 'Enter the name of the person who picked up the transcript.';

// Delivery notifications
$string['requeststatusdelivery_pendingsubject'] = 'Transcript Request Status Update';
$string['requeststatusdelivery_pendingbody'] = 'Dear {$a->studentname},

Your transcript request is awaiting delivery.

Program: {$a->programname}
Type: {$a->requesttype}';

$string['requeststatusdelivery_sentsubject'] = 'Transcript Has Been Sent';
$string['requeststatusdelivery_sentbody'] = 'Dear {$a->studentname},

Your transcript has been sent.

Program: {$a->programname}
Type: {$a->requesttype}

You should receive it soon.';

$string['requeststatusdelivery_deliveredsubject'] = 'Transcript Delivered';
$string['requeststatusdelivery_deliveredbody'] = 'Dear {$a->studentname},

Your transcript has been delivered.

Program: {$a->programname}
Type: {$a->requesttype}';

$string['requeststatusdelivery_pickedupsubject'] = 'Transcript Picked Up';
$string['requeststatusdelivery_pickedupbody'] = 'Dear {$a->studentname},

Your transcript has been picked up.

Program: {$a->programname}
Type: {$a->requesttype}

Thank you for using our service.';

// Validation messages
$string['paymentmethodrequired'] = 'Payment method is required when marking as paid.';
$string['paiddaterequired'] = 'Payment date is required when marking as paid.';
$string['deliverydaterequired'] = 'Delivery date is required when marking as sent/delivered.';
$string['pickuppersonrequired'] = 'Pickup person name is required when marking as picked up.';

// Additional payment statuses
$string['paymentstatus_help'] = 'Current payment status. Change to "Paid" once payment is received.';
