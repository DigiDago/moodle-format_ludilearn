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
 * @package     format_ludimoodle
 * @copyright   2024 Pimenko <support@pimenko.com><pimenko.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\task\manager;
use format_ludimoodle\local\gameelements\game_element;
use format_ludimoodle\local\gameelements\score;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Custom code to be run on upgrading the plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Always returns true.
 */
function xmldb_format_ludimoodle_upgrade($oldversion = 0) {
    global $DB;

    if ($oldversion < 2024081902) {
        // Set the new setting world to all courses with ludimoodle.
        $courses = $DB->get_records('course', ['format' => 'ludimoodle']);
        foreach ($courses as $course) {
            $format = course_get_format($course->id);
            $data = $format->get_format_options();
            $data['world'] = 'school';
            $format->update_course_format_options($data);
        }
    }

    if ($oldversion < 2024090600) {
        // Remove all course modules without URL from gamelements.
        // Get all courses with ludimoodle format and all course modules.
        $courses = $DB->get_records('course', ['format' => 'ludimoodle']);
        foreach ($courses as $course) {
            $cms = $DB->get_records('course_modules', ['course' => $course->id]);
            foreach ($cms as $cm) {
                $cminfo = get_fast_modinfo($course->id)->get_cm($cm->id);
                // Check if the course module has a URL.
                if (!$cminfo->get_url()) {
                    // Remove all cms without URL.
                    $DB->delete_records('ludimoodle_cm_params', ['cmid' => $cm->id]);
                    $DB->delete_records('ludimoodle_cm_user', ['cmid' => $cm->id]);
                }
            }
        }
    }
    if ($oldversion < 2024101400) {
        // Add new table ludimoodle_bysection.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('ludimoodle_bysection');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, null);
        $table->add_field('gameelementid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, null);

        // Adding keys to table ludimoodle_bysection.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('sectionid', XMLDB_KEY_FOREIGN, ['sectionid'], 'course_sections', ['id']);
        $table->add_key('gameelementid', XMLDB_KEY_FOREIGN, ['gameelementid'], 'ludimoodle_gameelements',
            ['id']);

        // Create table.
        try {
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        } catch (ddl_exception $e) {
            return false;
        }
        upgrade_plugin_savepoint(true, 2024101400, 'format', 'ludimoodle');
    }
    if ($oldversion < 2024102400) {
        // Replaces questions texts by string identifier in the database.
        $compare = $DB->sql_compare_text('content', 255);
        $sql = 'SELECT * FROM {ludimoodle_questions} WHERE ' . $compare . ' = :content';

        // Question 1.
        $content = 'Cela me rend heureux de pouvoir aider les autres';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question1';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 2.
        $content = 'J\'apprécie les activités de groupe';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question2';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 3.
        $content = 'Le bien-être des autres m\'est important';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question3';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 4.
        $content = 'J\'aime faire partie d\'une équipe';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question4';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 5.
        $content = 'J\'aime gérer des tâches difficiles';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question5';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 6.
        $content = 'J\'aime sortir victorieux de circonstances difficiles';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question6';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 7.
        $content = 'Être indépendant est une chose importante pour moi';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question7';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 8.
        $content = 'Je n\'aime pas suivre les règles';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question8';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 9.
        $content = 'Si la récompense est suffisante, je ferai des efforts';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question9';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 10.
        $content = 'Il est important pour moi de suivre ma propre voie';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question10';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 11.
        $content = 'Je me perçois comme étant rebelle';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question11';
        $DB->update_record('ludimoodle_questions', $question);

        // Question 12.
        $content = 'Les récompenses sont un bon moyen de me motiver';
        $question = $DB->get_record_sql($sql, ['content' => $content]);
        $question->content = 'questionnaire:question12';
        $DB->update_record('ludimoodle_questions', $question);

        upgrade_plugin_savepoint(true, 2024102400, 'format', 'ludimoodle');
    }
    purge_all_caches();
    return true;
}
