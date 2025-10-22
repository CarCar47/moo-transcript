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
 * Upgrade script for transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for transcript plugin
 *
 * @param int $oldversion The old version of the plugin
 * @return bool True on success
 */
function xmldb_gradereport_transcript_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade to version 2025101902 - Add flexible hour types.
    if ($oldversion < 2025101902) {

        // Add clinicalhours column to courses table.
        $table = new xmldb_table('gradereport_transcript_courses');
        $field = new xmldb_field('clinicalhours', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '0', 'labhours');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add hour label columns to programs table.
        $table = new xmldb_table('gradereport_transcript_programs');

        // Hour 1 label (Theory Hours).
        $field = new xmldb_field('hour1label', XMLDB_TYPE_CHAR, '50', null, null, null, 'Theory Hours', 'gradescaleid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Hour 2 label (Lab Hours).
        $field = new xmldb_field('hour2label', XMLDB_TYPE_CHAR, '50', null, null, null, 'Lab Hours', 'hour1label');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Hour 3 label (Clinical Hours).
        $field = new xmldb_field('hour3label', XMLDB_TYPE_CHAR, '50', null, null, null, 'Clinical Hours', 'hour2label');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Transcript savepoint reached.
        upgrade_plugin_savepoint(true, 2025101902, 'gradereport', 'transcript');
    }

    // Upgrade to version 2025101915 - Reset capabilities to apply new context levels.
    if ($oldversion < 2025101915) {

        // Update capabilities from db/access.php.
        // This applies capability definitions (including context levels) from db/access.php
        // and re-applies archetype defaults to standard roles (Student, Teacher, etc.)
        update_capabilities('gradereport_transcript');

        // Transcript savepoint reached.
        upgrade_plugin_savepoint(true, 2025101915, 'gradereport', 'transcript');
    }

    // Upgrade to version 2025101925 - Phase 6.1: Request/Payment System.
    if ($oldversion < 2025101925) {

        // Define table gradereport_transcript_pricing to be created.
        $table = new xmldb_table('gradereport_transcript_pricing');

        // Adding fields to table gradereport_transcript_pricing.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('schoolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('firstfree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('officialprice', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '0.00');
        $table->add_field('unofficialprice', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '0.00');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table gradereport_transcript_pricing.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('schoolid', XMLDB_KEY_FOREIGN, ['schoolid'], 'gradereport_transcript_schools', ['id']);

        // Note: Foreign key automatically creates an index, no need for explicit index.
        // Each school should have only one pricing record (enforced at application level).

        // Conditionally launch create table for gradereport_transcript_pricing.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define fields to be added to gradereport_transcript_requests.
        $table = new xmldb_table('gradereport_transcript_requests');

        // Add recipientname field (institution/company name).
        $field = new xmldb_field('recipientname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'recipientaddress');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add recipientphone field.
        $field = new xmldb_field('recipientphone', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'recipientname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add price field.
        $field = new xmldb_field('price', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '0.00', 'notes');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add paymentstatus field.
        $field = new xmldb_field('paymentstatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending', 'price');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add paymentmethod field.
        $field = new xmldb_field('paymentmethod', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'paymentstatus');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add invoicenumber field.
        $field = new xmldb_field('invoicenumber', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'paymentmethod');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add invoicedate field.
        $field = new xmldb_field('invoicedate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'invoicenumber');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add paiddate field.
        $field = new xmldb_field('paiddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'invoicedate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Transcript savepoint reached.
        upgrade_plugin_savepoint(true, 2025101925, 'gradereport', 'transcript');
    }

    // Upgrade to version 2025101926 - Phase 6.2: Add delivery tracking fields.
    if ($oldversion < 2025101926) {

        // Define table gradereport_transcript_requests to be updated.
        $table = new xmldb_table('gradereport_transcript_requests');

        // Add deliverystatus field.
        $field = new xmldb_field('deliverystatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending', 'paiddate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add deliverydate field.
        $field = new xmldb_field('deliverydate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'deliverystatus');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add deliverynotes field.
        $field = new xmldb_field('deliverynotes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'deliverydate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add trackingnumber field.
        $field = new xmldb_field('trackingnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'deliverynotes');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add receiptnumber field.
        $field = new xmldb_field('receiptnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'trackingnumber');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add paymentnotes field.
        $field = new xmldb_field('paymentnotes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'receiptnumber');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Transcript savepoint reached.
        upgrade_plugin_savepoint(true, 2025101926, 'gradereport', 'transcript');
    }

    // Upgrade to version 2025102207 - Add program completion dates for official transcripts.
    if ($oldversion < 2025102207) {

        // Define table gradereport_transcript_requests to be updated.
        $table = new xmldb_table('gradereport_transcript_requests');

        // Add programstartdate field.
        $field = new xmldb_field('programstartdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'approvaldate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add completionstatus field.
        $field = new xmldb_field('completionstatus', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'programstartdate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add graduationdate field.
        $field = new xmldb_field('graduationdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'completionstatus');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add withdrawndate field.
        $field = new xmldb_field('withdrawndate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'graduationdate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Transcript savepoint reached.
        upgrade_plugin_savepoint(true, 2025102207, 'gradereport', 'transcript');
    }

    return true;
}
