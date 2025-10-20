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
 * Verification code generator class
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Verification generator class
 *
 * Generates unique verification codes for transcripts and CEU certificates.
 * Stores codes in database with document metadata for verification system.
 *
 * Code Format: TXN-XXXXXXXXXXXX (TXN- prefix + 12 alphanumeric characters)
 * Example: TXN-A7B9C2D4E6F8
 *
 * Uses characters that avoid confusion: A-Z (no O), 2-9 (no 0, 1)
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradereport_transcript_verification_generator {

    /** @var string Characters to use in code generation (no ambiguous 0, O, I, 1) */
    const CODE_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** @var int Length of random portion of code */
    const CODE_LENGTH = 12;

    /** @var string Prefix for verification codes */
    const CODE_PREFIX = 'TXN-';

    /**
     * Generate a unique verification code
     *
     * Generates codes in format: TXN-XXXXXXXXXXXX
     * Ensures uniqueness by checking against database.
     *
     * @return string Unique verification code
     * @throws moodle_exception If unable to generate unique code after max attempts
     */
    public function generate_code() {
        global $DB;

        $maxattempts = 100; // Prevent infinite loop.
        $attempts = 0;

        do {
            $attempts++;

            // Generate random code.
            $randompart = '';
            $charcount = strlen(self::CODE_CHARS);

            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $randompart .= self::CODE_CHARS[random_int(0, $charcount - 1)];
            }

            $code = self::CODE_PREFIX . $randompart;

            // Check if code already exists.
            $exists = $DB->record_exists('gradereport_transcript_verify', ['verificationcode' => $code]);

            if (!$exists) {
                return $code;
            }

            if ($attempts >= $maxattempts) {
                throw new moodle_exception('unabletogenerateverificationcode', 'gradereport_transcript');
            }

        } while ($exists);

        // Should never reach here, but just in case.
        throw new moodle_exception('unabletogenerateverificationcode', 'gradereport_transcript');
    }

    /**
     * Check if a verification code exists in database
     *
     * @param string $code Verification code to check
     * @return bool True if code exists
     */
    public function code_exists($code) {
        global $DB;
        return $DB->record_exists('gradereport_transcript_verify', ['verificationcode' => $code]);
    }

    /**
     * Calculate SHA256 hash of PDF content
     *
     * Used for document integrity verification. The hash can be used to detect
     * if the PDF has been tampered with after issuance.
     *
     * @param string $pdfcontent Binary PDF content
     * @return string SHA256 hash (64 character hex string)
     */
    public function calculate_pdf_hash($pdfcontent) {
        return hash('sha256', $pdfcontent);
    }

    /**
     * Save verification record to database
     *
     * Stores verification code with document metadata.
     *
     * @param int $userid User ID
     * @param int $programid Program ID
     * @param string $verificationcode Verification code
     * @param string $documenttype Document type ('transcript' or 'ceu')
     * @param string $pdfhash Optional SHA256 hash of PDF
     * @param int $expirydate Optional expiry timestamp (0 = never expires)
     * @return int Record ID
     * @throws moodle_exception If insert fails
     */
    public function save_verification($userid, $programid, $verificationcode, $documenttype, $pdfhash = '', $expirydate = 0) {
        global $DB;

        $record = new stdClass();
        $record->userid = $userid;
        $record->programid = $programid;
        $record->verificationcode = $verificationcode;
        $record->documenttype = $documenttype;
        $record->issuedate = time();
        $record->pdfhash = $pdfhash;
        $record->expirydate = $expirydate;
        $record->timecreated = time();

        $id = $DB->insert_record('gradereport_transcript_verify', $record);

        if (!$id) {
            throw new moodle_exception('errorinsertingverificationrecord', 'gradereport_transcript');
        }

        return $id;
    }

    /**
     * Generate verification code and save to database
     *
     * Convenience method that combines code generation and database save.
     *
     * @param int $userid User ID
     * @param int $programid Program ID
     * @param string $documenttype Document type ('transcript' or 'ceu')
     * @param string $pdfcontent Optional PDF content for hash calculation
     * @param int $expirydate Optional expiry timestamp (0 = never expires)
     * @return string Generated verification code
     */
    public function generate_and_save($userid, $programid, $documenttype, $pdfcontent = '', $expirydate = 0) {
        // Generate unique code.
        $code = $this->generate_code();

        // Calculate PDF hash if content provided.
        $pdfhash = '';
        if (!empty($pdfcontent)) {
            $pdfhash = $this->calculate_pdf_hash($pdfcontent);
        }

        // Save to database.
        $this->save_verification($userid, $programid, $code, $documenttype, $pdfhash, $expirydate);

        return $code;
    }

    /**
     * Verify a verification code
     *
     * Looks up code in database and returns verification record if found.
     *
     * @param string $code Verification code to verify
     * @return stdClass|false Verification record or false if not found
     */
    public function verify_code($code) {
        global $DB;

        $record = $DB->get_record('gradereport_transcript_verify', ['verificationcode' => $code]);

        if ($record) {
            // Check if expired.
            if ($record->expirydate > 0 && $record->expirydate < time()) {
                $record->expired = true;
            } else {
                $record->expired = false;
            }

            return $record;
        }

        return false;
    }

    /**
     * Get verification record with full details
     *
     * Returns verification record with joined user and program information.
     *
     * @param string $code Verification code
     * @return stdClass|false Verification record with user and program data, or false
     */
    public function get_verification_details($code) {
        global $DB;

        $sql = "SELECT v.*, u.firstname, u.lastname, u.email,
                       p.name AS programname, p.type AS programtype,
                       s.name AS schoolname
                  FROM {gradereport_transcript_verify} v
                  JOIN {user} u ON v.userid = u.id
                  JOIN {gradereport_transcript_programs} p ON v.programid = p.id
                  JOIN {gradereport_transcript_schools} s ON p.schoolid = s.id
                 WHERE v.verificationcode = ?";

        $record = $DB->get_record_sql($sql, [$code]);

        if ($record) {
            // Check if expired.
            if ($record->expirydate > 0 && $record->expirydate < time()) {
                $record->expired = true;
            } else {
                $record->expired = false;
            }

            // Format issue date.
            $record->issuedateformatted = userdate($record->issuedate, get_string('strftimedatefullshort'));

            return $record;
        }

        return false;
    }

    /**
     * Get all verification codes for a user
     *
     * Returns all transcripts/certificates issued to a specific user.
     *
     * @param int $userid User ID
     * @return array Array of verification records
     */
    public function get_user_verifications($userid) {
        global $DB;

        $sql = "SELECT v.*, p.name AS programname, p.type AS programtype
                  FROM {gradereport_transcript_verify} v
                  JOIN {gradereport_transcript_programs} p ON v.programid = p.id
                 WHERE v.userid = ?
              ORDER BY v.issuedate DESC";

        $records = $DB->get_records_sql($sql, [$userid]);

        foreach ($records as $record) {
            // Check if expired.
            if ($record->expirydate > 0 && $record->expirydate < time()) {
                $record->expired = true;
            } else {
                $record->expired = false;
            }

            // Format issue date.
            $record->issuedateformatted = userdate($record->issuedate, get_string('strftimedatefullshort'));
        }

        return $records;
    }

    /**
     * Delete verification record (admin only)
     *
     * Removes a verification code from database. Should be used with caution
     * as this invalidates previously issued documents.
     *
     * @param int $id Verification record ID
     * @return bool True on success
     */
    public function delete_verification($id) {
        global $DB;
        return $DB->delete_records('gradereport_transcript_verify', ['id' => $id]);
    }

    /**
     * Invalidate verification code by setting expiry date to past
     *
     * Safer alternative to deletion - keeps record but marks as expired.
     *
     * @param int $id Verification record ID
     * @return bool True on success
     */
    public function invalidate_verification($id) {
        global $DB;

        $record = new stdClass();
        $record->id = $id;
        $record->expirydate = time() - 1; // Set to 1 second ago.

        return $DB->update_record('gradereport_transcript_verify', $record);
    }

    /**
     * Get verification statistics
     *
     * Returns counts of verification codes by status.
     *
     * @return stdClass Statistics object
     */
    public function get_statistics() {
        global $DB;

        $stats = new stdClass();
        $stats->total = $DB->count_records('gradereport_transcript_verify');
        $stats->active = $DB->count_records_select('gradereport_transcript_verify',
            'expirydate = 0 OR expirydate > ?', [time()]);
        $stats->expired = $stats->total - $stats->active;
        $stats->transcripts = $DB->count_records('gradereport_transcript_verify', ['documenttype' => 'transcript']);
        $stats->ceu = $DB->count_records('gradereport_transcript_verify', ['documenttype' => 'ceu']);

        return $stats;
    }
}
