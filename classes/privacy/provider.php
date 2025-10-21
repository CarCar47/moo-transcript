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
 * Privacy Subsystem implementation for gradereport_transcript.
 *
 * @package    gradereport_transcript
 * @copyright  2025 COR4EDU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy Subsystem for gradereport_transcript.
 *
 * @copyright  2025 COR4EDU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'gradereport_transcript_requests',
            [
                'userid' => 'privacy:metadata:transcript_requests:userid',
                'requestdate' => 'privacy:metadata:transcript_requests:requestdate',
                'status' => 'privacy:metadata:transcript_requests:status',
            ],
            'privacy:metadata:transcript_requests'
        );

        $collection->add_database_table(
            'gradereport_transcript_verify',
            [
                'userid' => 'privacy:metadata:transcript_verification:userid',
                'issueddate' => 'privacy:metadata:transcript_verification:issueddate',
            ],
            'privacy:metadata:transcript_verification'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Transcript requests are stored at system context.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {gradereport_transcript_requests} tr ON tr.userid = :userid
                 WHERE ctx.contextlevel = :contextlevel";

        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'contextlevel' => CONTEXT_SYSTEM,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT userid
                  FROM {gradereport_transcript_requests}";

        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            $requests = $DB->get_records('gradereport_transcript_requests', ['userid' => $user->id]);
            foreach ($requests as $request) {
                \core_privacy\local\request\writer::with_context($context)->export_data(
                    [get_string('pluginname', 'gradereport_transcript'), 'requests'],
                    (object)[
                        'requesttype' => $request->requesttype,
                        'status' => $request->status,
                        'requestdate' => \core_privacy\local\request\transform::datetime($request->timecreated),
                    ]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('gradereport_transcript_requests');
        $DB->delete_records('gradereport_transcript_verify');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            $DB->delete_records('gradereport_transcript_requests', ['userid' => $user->id]);
            $DB->delete_records('gradereport_transcript_verify', ['userid' => $user->id]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('gradereport_transcript_requests', "userid $usersql", $userparams);
        $DB->delete_records_select('gradereport_transcript_verify', "userid $usersql", $userparams);
    }
}
