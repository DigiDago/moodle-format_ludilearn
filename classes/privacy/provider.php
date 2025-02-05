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
 * Privacy Subsystem implementation for format_ludilearn.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludilearn\privacy;

use context;
use context_course;
use context_module;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;

/**
 * Privacy Subsystem for format_ludilearn implementing provider.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    core_userlist_provider {

    /**
     * Get the metadata for this plugin.
     *
     * @param collection $collection The initialised collection to add the plugin's metadata to.
     *
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {

        // Adding description of table format_ludilearn_profile to the collection.
        $collection->add_database_table(
            'format_ludilearn_profile',
            [
                'userid' => 'privacy:metadata:format_ludilearn_profile:userid',
                'combinedaffinities' => 'privacy:metadata:format_ludilearn_profile:combinedaffinities',
                'type' => 'privacy:metadata:format_ludilearn_profile:type',
            ],
            'privacy:metadata:format_ludilearn_profile'
        );

        // Adding description of table format_ludilearn_answers to the collection.
        $collection->add_database_table(
            'format_ludilearn_answers',
            [
                'questionid' => 'privacy:metadata:format_ludilearn_answers:questionid',
                'userid' => 'privacy:metadata:format_ludilearn_answers:userid',
                'score' => 'privacy:metadata:format_ludilearn_answers:score',
            ],
            'privacy:metadata:format_ludilearn_answers'
        );

        // Adding description of table format_ludilearn_attributio to the collection.
        $collection->add_database_table(
            'format_ludilearn_attributio',
            [
                'gameelementid' => 'privacy:metadata:format_ludilearn_attributio:gameelementid',
                'userid' => 'privacy:metadata:format_ludilearn_attributio:userid',
                'timecreated' => 'privacy:metadata:format_ludilearn_attributio:timecreated',
            ],
            'privacy:metadata:format_ludilearn_attributio'
        );

        // Adding description of table ludilearn_gameeele_user.
        $collection->add_database_table(
            'format_ludilearn_gameeele_user',
            [
                'attributionid' => 'privacy:metadata:ludilearn_gameeele_user:attributionid',
                'name' => 'privacy:metadata:ludilearn_gameeele_user:name',
                'value' => 'privacy:metadata:ludilearn_gameeele_user:value',
            ],
            'privacy:metadata:ludilearn_gameeele_user'
        );

        // Adding description of table format_ludilearn_cm_user.
        $collection->add_database_table(
            'format_ludilearn_cm_user',
            [
                'attributionid' => 'privacy:metadata:format_ludilearn_cm_user:attributionid',
                'cmid' => 'privacy:metadata:format_ludilearn_cm_user:cmid',
                'name' => 'privacy:metadata:format_ludilearn_cm_user:name',
                'value' => 'privacy:metadata:format_ludilearn_cm_user:value',
            ],
            'privacy:metadata:format_ludilearn_cm_user'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     *
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get all the contexts where the user has data.
        // Context modules.
        $paramscm = [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $sql = "SELECT DISTINCT(c.id)
                FROM {context} c
                INNER JOIN {course_modules} cm ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                INNER JOIN {format_ludilearn_cm_user} cmu ON cmu.cmid = cm.id
                INNER JOIN {format_ludilearn_attributio} a ON a.id = cmu.attributionid
                WHERE a.userid = :userid";
        $contextlist->add_from_sql($sql, $paramscm);

        // Context course.
        $paramscourse = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];
        $sql = "SELECT DISTINCT(c.id)
                FROM {context} c
                INNER JOIN {gameelements} g ON g.courseid = c.instanceid
                INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                WHERE c.contextlevel = :contextlevel AND a.userid = :userid";
        $contextlist->add_from_sql($sql, $paramscourse);

        // Context user.
        $paramsuser = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];
        $sql = "SELECT DISTINCT(c.id)
                FROM {context} c
                INNER JOIN {format_ludilearn_profile} p ON c.instanceid = p.userid
                WHERE c.contextlevel = :contextlevel AND p.userid = :userid";
        $contextlist->add_from_sql($sql, $paramsuser);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     *
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }
        $contexts = $contextlist->get_contexts();
        $user = $contextlist->get_user();

        // Export data for each context.
        foreach ($contexts as $context) {
            // If context is user context.
            if ($context instanceof context_user) {
                // Export profile data.
                $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $user->id]);
                self::export_profile_data($profile, $context);

                // Export answers data.
                $answers = $DB->get_records('format_ludilearn_answers', ['userid' => $user->id]);
                self::export_answers_data($answers, $context);
            }

            // If context is course context.
            if ($context instanceof context_course) {
                // Export attribution data.
                $params = [
                    'userid' => $user->id,
                    'courseid' => $context->instanceid,
                ];
                $sql = "SELECT g.id, g.courseid, g.sectionid, g.type, a.id as attributionid, a.timecreated
                        FROM {format_ludilearn_attributio} a
                        INNER JOIN {format_ludilearn_elements} g ON g.id = a.gameelementid
                        WHERE a.userid = :userid AND g.courseid = :courseid";
                $attributions = $DB->get_records_sql($sql, $params);
                self::export_gameelements_data($attributions, $context);
            }

            // If context is course context.
            if ($context instanceof context_module) {
                // Export attribution data.
                $params = [
                    'userid' => $user->id,
                    'cmid' => $context->instanceid,
                ];
                $sql = "SELECT cmu.*
                        FROM {format_ludilearn_attributio} a
                        INNER JOIN {format_ludilearn_cm_user} cmu ON cmu.attributionid = a.id
                        WHERE cmu.cmid = :cmid AND a.userid = :userid";
                $cmuser = $DB->get_records_sql($sql, $params);
                self::export_course_module_data($cmuser, $context);
            }
        }
    }

    /**
     * Export profile data for the specified user, in the specified context.
     *
     * @param stdClass $profile     The profile data to export.
     * @param context_user $context The context to export data in.
     *
     * @throws \coding_exception
     */
    public static function export_profile_data(stdClass $profile, context_user $context): void {

        // Prepare the data for export.
        $data = (object)[
            'combinedaffinities' => $profile->combinedaffinities,
            'type' => get_string($profile->type, 'format_ludilearn'),
        ];

        // Export the data.
        writer::with_context($context)
            ->export_data(['profile'], $data);
    }

    /**
     * Export answers data for the specified user, in the specified context.
     *
     * @param array $answers        The answers data to export.
     * @param context_user $context The context to export data in.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_answers_data(array $answers, context_user $context): void {
        global $DB;

        // Prepare the data for export.
        $data = [];
        $question = $DB->get_records('format_ludilearn_questions');
        foreach ($answers as $answer) {
            $content = $question[$answer->questionid]->content;
            $data[] = (object)[
                'question' => get_string($content, 'format_ludilearn'),
                'score' => $answer->score,
            ];
        }

        // Export the data.
        writer::with_context($context)
            ->export_data(['answers'], (object)['answers' => $data]);
    }

    /**
     * Export gameelements data for the specified user, in the specified context.
     *
     * @param array $gameelements     The gameelements data to export.
     * @param context_course $context The context to export data in.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_gameelements_data(array $gameelements, context_course $context): void {
        global $DB;

        // Prepare the data for export.
        $data = [];
        foreach ($gameelements as $gameelement) {
            $userdatas = $DB->get_records('format_ludilearn_ele_user', ['attributionid' => $gameelement->attributionid]);
            $subdatas = [];
            foreach ($userdatas as $userdata) {
                $subdatas[] = (object)[
                    'name' => $userdata->name,
                    'value' => $userdata->value,
                ];
            }
            $data[] = (object)[
                'gameelementid' => $gameelement->id,
                'sectionid' => $gameelement->sectionid,
                'type' => get_string($gameelement->type, 'format_ludilearn'),
                'timecreated' => $gameelement->timecreated,
                'data' => $subdatas,
            ];
        }

        // Export the data.
        writer::with_context($context)
            ->export_data(['attributions'], (object)['game_elements' => $data]);
    }

    /**
     * Export course module data for the specified user, in the specified context.
     *
     * @param array $cmusers          The course module data to export.
     * @param context_module $context The context to export data in.
     */
    public static function export_course_module_data(array $cmusers, context_module $context): void {
        // Prepare the data for export.
        $data = [];
        foreach ($cmusers as $cmuser) {
            $data[] = (object)[
                'name' => $cmuser->name,
                'value' => $cmuser->value,
            ];
        }

        // Export the data.
        writer::with_context($context)
            ->export_data(['cmuser'], (object)['cmuser' => $data]);
    }

    /**
     * Delete all user data for the specified user, in the specified context.
     *
     * @param context $context Context to delete data from.
     *
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        // Check if the context is a module context.
        if ($context instanceof context_module) {
            $params = ['instanceid' => $context->instanceid];
            $sql = "DELETE cmu
                    FROM {format_ludilearn_cm_user} cmu
                    WHERE cmu.cmid = :instanceid";
            $DB->execute($sql, $params);
        }

        // Check if the context is a course context.
        if ($context instanceof context_course) {
            $params = ['instanceid' => $context->instanceid];
            $sql = "DELETE gu
                    FROM {format_ludilearn_ele_user} gu
                    INNER JOIN {format_ludilearn_attributio} a ON a.id = gu.attributionid
                    INNER JOIN {gameelements} g ON g.id = a.gameelementid
                    WHERE g.courseid = :instanceid";
            $DB->execute($sql, $params);

            $sql = "DELETE a
                    FROM {format_ludilearn_attributio} a
                    INNER JOIN {gameelements} g ON g.id = a.gameelementid
                    WHERE g.courseid = :instanceid";
            $DB->execute($sql, $params);
        }

        // Check if the context is a user context.
        if ($context instanceof context_user) {
            $params = ['instanceid' => $context->instanceid];
            $sql = "DELETE p
                    FROM {format_ludilearn_profile} p
                    WHERE p.userid = :instanceid";
            $DB->execute($sql, $params);

            $sql = "DELETE a
                    FROM {format_ludilearn_answers} a
                    WHERE a.userid = :instanceid";
            $DB->execute($sql, $params);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     *
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }
        $contexts = $contextlist->get_contexts();
        $user = $contextlist->get_user();

        // Delete data for each context.
        foreach ($contexts as $context) {
            // Check if the context is a module context.
            if ($context instanceof context_module) {
                $params = ['instanceid' => $context->instanceid];
                $sql = "DELETE cmu
                    FROM {format_ludilearn_cm_user} cmu
                    INNER JOIN {format_ludilearn_attributio} a ON a.id = cmu.attributionid
                    WHERE cmu.cmid = :instanceid AND a.userid = :userid";
                $DB->execute($sql, $params);
            }

            // Check if the context is a course context.
            if ($context instanceof context_course) {
                $params = ['instanceid' => $context->instanceid];
                $sql = "DELETE gu
                    FROM {format_ludilearn_ele_user} gu
                    INNER JOIN {format_ludilearn_attributio} a ON a.id = gu.attributionid
                    INNER JOIN {gameelements} g ON g.id = a.gameelementid
                    WHERE g.courseid = :instanceid AND a.userid = :userid";
                $DB->execute($sql, $params);

                $sql = "DELETE a
                    FROM {format_ludilearn_attributio} a
                    INNER JOIN {gameelements} g ON g.id = a.gameelementid
                    WHERE g.courseid = :instanceid AND a.userid = :userid";
                $DB->execute($sql, $params);
            }

            // Check if the context is a user context.
            if ($context instanceof context_user) {
                $params = ['userid' => $user->id];
                $sql = "DELETE p
                    FROM {format_ludilearn_profile} p
                    WHERE p.userid = :userid";
                $DB->execute($sql, $params);

                $sql = "DELETE a
                    FROM {format_ludilearn_answers} a
                    WHERE a.userid = :userid";
                $DB->execute($sql, $params);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin
     *                           combination.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        // Check if the context is a module context.
        if ($context instanceof context_module) {
            $params = ['instanceid' => $context->instanceid];
            $sql = "SELECT DISTINCT(a.userid)
                    FROM {format_ludilearn_attributio} a
                    INNER JOIN {format_ludilearn_cm_user} cmu ON cmu.attributionid = a.id
                    WHERE cmu.cmid = :instanceid";
            $userlist->add_from_sql('userid', $sql, $params);
        }

        // Check if the context is a course context.
        if ($context instanceof context_course) {
            $params = ['instanceid' => $context->instanceid];
            $sql = "SELECT DISTINCT(a.userid)
                    FROM {format_ludilearn_attributio} a
                    INNER JOIN {gameelements} g ON g.id = a.gameelementid
                    WHERE g.courseid = :instanceid";
            $userlist->add_from_sql('userid', $sql, $params);
        }

        // Check if the context is a user context.
        if ($context instanceof context_user) {
            $params = ['instanceid' => $context->instanceid];
            $sql = "SELECT DISTINCT(p.userid)
                    FROM {format_ludilearn_profile} p
                    WHERE p.userid = :instanceid";
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     *
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        $users = $userlist->get_users();

        foreach ($users as $user) {
            // Check if the context is a module context.
            if ($context instanceof context_module) {
                $params = [
                    'instanceid' => $context->instanceid,
                    'userid' => $user->id,
                ];
                $sql = "DELETE cmu
                    FROM {format_ludilearn_cm_user} cmu
                    INNER JOIN {format_ludilearn_attributio} a ON a.id = cmu.attributionid
                    WHERE cmu.cmid = :instanceid AND a.userid = :userid";
                $DB->execute($sql, $params);
            }

            // Check if the context is a course context.
            if ($context instanceof context_course) {
                $params = [
                    'instanceid' => $context->instanceid,
                    'userid' => $user->id,
                ];
                $sql = "DELETE gu
                    FROM {format_ludilearn_ele_user} gu
                    INNER JOIN {format_ludilearn_attributio} a ON a.id = gu.attributionid
                    INNER JOIN {gameelements} g ON g.id = a.gameelementid
                    WHERE g.courseid = :instanceid AND a.userid = :userid";
                $DB->execute($sql, $params);

                $sql = "DELETE a
                    FROM {format_ludilearn_attributio} a
                    INNER JOIN {gameelements} g ON g.id = a.gameelementid
                    WHERE g.courseid = :instanceid AND a.userid = :userid";
                $DB->execute($sql, $params);
            }

            // Check if the context is a user context.
            if ($context instanceof context_user) {
                $params = ['userid' => $user->id];
                $sql = "DELETE p
                    FROM {format_ludilearn_profile} p
                    WHERE p.userid = :userid";
                $DB->execute($sql, $params);

                $sql = "DELETE a
                    FROM {format_ludilearn_answers} a
                    WHERE a.userid = :userid";
                $DB->execute($sql, $params);
            }
        }
    }
}
