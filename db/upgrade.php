<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade.php
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\task\manager;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\local\gameelements\score;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Custom code to be run on upgrading the plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 *
 * @return bool Always returns true.
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws moodle_exception
 * @throws upgrade_exception
 */
function xmldb_format_ludilearn_upgrade($oldversion = 0) {
    global $DB;

    if ($oldversion < 2024081902) {
        // Set the new setting world to all courses with ludilearn.
        $courses = $DB->get_records('course', ['format' => 'ludilearn']);
        foreach ($courses as $course) {
            $format = course_get_format($course->id);
            $data = $format->get_format_options();
            $data['world'] = 'school';
            $format->update_course_format_options($data);
        }

        upgrade_plugin_savepoint(true, 2024081902, 'format', 'ludilearn');
    }

    if ($oldversion < 2024090600) {
        // Remove all course modules without URL from gamelements.
        // Get all courses with ludilearn format and all course modules.
        $courses = $DB->get_records('course', ['format' => 'ludilearn']);
        foreach ($courses as $course) {
            $cms = $DB->get_records('course_modules', ['course' => $course->id]);
            foreach ($cms as $cm) {
                $cminfo = get_fast_modinfo($course->id)->get_cm($cm->id);
                // Check if the course module has a URL.
                if (!$cminfo->get_url()) {
                    // Remove all cms without URL.
                    $DB->delete_records('ludilearn_cm_params', ['cmid' => $cm->id]);
                    $DB->delete_records('ludilearn_cm_user', ['cmid' => $cm->id]);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2024090600, 'format', 'ludilearn');
    }

    if ($oldversion < 2024101400) {
        // Add new table ludilearn_bysection.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('ludilearn_bysection');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, null);
        $table->add_field('gameelementid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, null);

        // Adding keys to table ludilearn_bysection.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('sectionid', XMLDB_KEY_FOREIGN, ['sectionid'], 'course_sections', ['id']);
        $table->add_key('gameelementid', XMLDB_KEY_FOREIGN, ['gameelementid'], 'ludilearn_gameelements',
            ['id']);

        // Create table.
        try {
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        } catch (ddl_exception $e) {
            return false;
        }
        upgrade_plugin_savepoint(true, 2024101400, 'format', 'ludilearn');
    }

    if ($oldversion < 2024102400) {
        // Replaces questions texts by string identifier in the database.
        $compare = $DB->sql_compare_text('content', 255);
        $sql = 'SELECT * FROM {ludilearn_questions} WHERE ' . $compare . ' = :content';

        // Question 1.
        $content = get_string('happy_to_help_others', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question1';
        $DB->update_record('ludilearn_questions', $question);

        // Question 2.
        $content = get_string('enjoy_group_activities', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question2';
        $DB->update_record('ludilearn_questions', $question);

        // Question 3.
        $content = get_string('wellbeing_of_others_is_important', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question3';
        $DB->update_record('ludilearn_questions', $question);

        // Question 4.
        $content = get_string('enjoy_being_part_of_a_team', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question4';
        $DB->update_record('ludilearn_questions', $question);

        // Question 5.
        $content = get_string('enjoy_managing_challenging_tasks', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question5';
        $DB->update_record('ludilearn_questions', $question);

        // Question 6.
        $content = get_string('enjoy_overcoming_difficult_circumstances', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question6';
        $DB->update_record('ludilearn_questions', $question);

        // Question 7.
        $content = get_string('independence_is_important_to_me', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question7';
        $DB->update_record('ludilearn_questions', $question);

        // Question 8.
        $content = get_string('do_not_like_following_rules', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question8';
        $DB->update_record('ludilearn_questions', $question);

        // Question 9.
        $content = get_string('will_effort_if_reward_is_enough', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question9';
        $DB->update_record('ludilearn_questions', $question);

        // Question 10.
        $content = get_string('important_to_follow_my_own_path', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question10';
        $DB->update_record('ludilearn_questions', $question);

        // Question 11.
        $content = get_string('see_myself_as_rebel', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question11';
        $DB->update_record('ludilearn_questions', $question);

        // Question 12.
        $content = get_string('rewards_are_good_for_motivation', 'format_ludilearn');
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question12';
        $DB->update_record('ludilearn_questions', $question);

        upgrade_plugin_savepoint(true, 2024102400, 'format', 'ludilearn');
    }

    if ($oldversion < 2025010700) {
        // Replace all data present the mistake of previous bug.
        $sql = "SELECT * FROM {ludilearn_gameele_user} WHERE name LIKE 'itemowned-%'";
        $gameeleuser = $DB->get_records_sql($sql);
        foreach ($gameeleuser as $geu) {
            $geu->name = str_replace('itemowned', 'item_owned', $geu->name);
            $DB->update_record('ludilearn_gameele_user', $geu);
        }
        upgrade_plugin_savepoint(true, 2025010700, 'format', 'ludilearn');
    }

    if ($oldversion < 2025011200) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('ludilearn_gameelements');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_elements');
        }

        $table = new xmldb_table('ludilearn_params');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_params');
        }

        $table = new xmldb_table('ludilearn_cm_params');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_cm_params');
        }

        $table = new xmldb_table('ludilearn_attribution');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_attributio');
        }

        $table = new xmldb_table('ludilearn_gameele_user');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_ele_user');
        }

        $table = new xmldb_table('ludilearn_cm_user');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_cm_user');
        }

        $table = new xmldb_table('ludilearn_questions');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_questions');
        }

        $table = new xmldb_table('ludilearn_answers');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_answers');
        }

        $table = new xmldb_table('ludilearn_profile');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_profile');
        }

        $table = new xmldb_table('ludilearn_bysection');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'format_ludilearn_bysection');
        }

        upgrade_plugin_savepoint(true, 2025011200, 'format', 'ludilearn');
    }

    purge_all_caches();
    return true;
}
