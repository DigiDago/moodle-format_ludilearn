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
 * Event observers used by the ludilearn course format.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\event\course_deleted;
use core\event\course_module_created;
use core\event\course_module_deleted;
use core\event\course_module_updated;
use core\event\course_reset_ended;
use core\event\course_section_created;
use core\event\course_section_deleted;
use core\event\role_assigned;
use core\event\user_enrolment_created;
use core\event\user_enrolment_updated;
use core\event\user_graded;
use format_ludilearn\local\gameelements\avatar;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\local\gameelements\progress;
use format_ludilearn\local\gameelements\ranking;
use format_ludilearn\local\gameelements\score;
use format_ludilearn\local\gameelements\timer;
use format_ludilearn\manager;
use mod_quiz\event\attempt_deleted;
use mod_quiz\event\attempt_submitted;
use mod_quiz\event\attempt_updated;

/**
 * Event observer for format_ludilearn.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludilearn_observer {
    /**
     * Triggered via \core\event\user_enrolment_created event.
     *
     * @param user_enrolment_created $event The event.
     *
     * @return void
     */
    public static function user_enrolment_created(user_enrolment_created $event): void {
        if (is_enrolled(context_course::instance($event->courseid), $event->relateduserid)) {
            $manager = new manager();
            $course = get_course($event->courseid);
            if ($course->format == 'ludilearn') {
                $userid = $event->relateduserid;
                $format = course_get_format($course);
                // Get the format options.
                $options = $format->get_format_options();
                $manager->sync_user_attribution_by_user(
                    $event->courseid,
                    $options['assignment'],
                    $options['default_game_element'],
                    $userid);
            }
        }
    }

    /**
     * Triggered via \core\event\user_enrolment_updated event.
     *
     * @param user_enrolment_updated $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function user_enrolment_updated(user_enrolment_updated $event): void {
        global $DB;
        if (is_enrolled(context_course::instance($event->courseid), $event->relateduserid)) {
            $manager = new manager();
            $course = get_course($event->courseid);
            if ($course->format == 'ludilearn') {
                $userid = $event->relateduserid;
                $format = course_get_format($course);
                // Get the format options.
                $options = $format->get_format_options();
                $manager->sync_user_attribution_by_user(
                    $event->courseid,
                    $options['assignment'],
                    $options['default_game_element'],
                    $userid);
            }
        }
    }

    /**
     * Triggered via \core\event\role_assigned event.
     *
     * @param role_assigned $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function role_assigned(role_assigned $event): void {
        // Only course level roles are interesting.
        if ($parentcontext = context::instance_by_id($event->contextid, IGNORE_MISSING)) {
            if ($parentcontext->contextlevel == CONTEXT_COURSE) {
                if (is_enrolled(context_course::instance($parentcontext->instanceid), $event->relateduserid)) {
                    $manager = new manager();
                    $course = get_course($parentcontext->instanceid);
                    if ($course->format == 'ludilearn') {
                        $userid = $event->relateduserid;
                        $format = course_get_format($course);

                        // Get the format options.
                        $options = $format->get_format_options();

                        $manager->sync_user_attribution_by_user(
                            $event->courseid,
                            $options['assignment'],
                            $options['default_game_element'],
                            $userid);
                    }
                }
            }
        }
    }

    /**
     * Triggered via \core\event\course_section_created event.
     *
     * @param course_section_created $event The event.
     *
     * @return void
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function section_created(course_section_created $event): void {
        global $DB;
        $manager = new manager();
        $course = $DB->get_record('course', ['id' => $event->courseid]);

        // If it's restoring we do nothing because data are imported from backup.
        // So don't need to create game elements.
        if (self::is_restoring($event->courseid)) {
            return;
        }

        $format = course_get_format($course);
        // Get the format options.
        $options = $format->get_format_options();

        if ($course->format == 'ludilearn') {
            // Create game elements.
            game_element::create_all($course->id, $event->objectid);

            $gameelement = false;
            $users = get_enrolled_users(context_course::instance($course->id));

            // Get the default game element.
            $gameelementbydefault = $DB->get_record('format_ludilearn_elements',
                ['courseid' => $course->id, 'sectionid' => $event->objectid, 'type' => $options['default_game_element']]);

            foreach ($users as $user) {
                $type = $options['default_game_element'];
                if ($options['assignment'] == 'automatic') {
                    // Else if assignment is automatic, we attribute the game element to the user based on his profile.
                    $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $user->id]);
                    if ($profile) {
                        $type = $profile->type;
                    }
                } else if ($options['assignment'] == 'bysection') {
                    $bysection = $DB->get_record('format_ludilearn_bysection',
                        ['courseid' => $course->id, 'sectionid' => $event->objectid]);
                    if ($bysection) {
                        $type = 'bysection';
                    } else {
                        $bysection = $manager->update_attribution_by_section($course->id, $event->objectid,
                            $gameelementbydefault->id);
                    }
                }
                // Attribution game element.
                if (isset($type)) {
                    if ($options['assignment'] != 'bysection') {
                        $gameelement = $DB->get_record('format_ludilearn_elements',
                            ['courseid' => $course->id, 'sectionid' => $event->objectid, 'type' => $type]);
                        $manager->attribution_game_element($gameelement->id, $user->id);
                    } else {
                        // If the game element by section already exist, we attribute it to the user.
                        if (isset($bysection)) {
                            $manager->attribution_game_element($bysection->gameelementid, $user->id);
                        }
                    }
                    $gameelement = $DB->get_record('format_ludilearn_elements',
                        [
                            'sectionid' => $event->objectid,
                            'type' => $type,
                        ]
                    );
                    if ($gameelement) {
                        $manager->attribution_game_element($gameelement->id, $user->id);
                    }
                }
            }
        }
    }

    /**
     * Triggered via \core\event\course_section_deleted event.
     *
     * @param course_section_deleted $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function section_deleted(course_section_deleted $event): void {
        $manager = new manager();
        $manager->remove_game_element_by_section($event->objectid);
    }

    /**
     * Triggered via \core\event\course_deleted event.
     *
     * @param course_deleted $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function course_deleted(course_deleted $event): void {
        $manager = new manager();
        $manager->remove_game_element_by_course($event->courseid);
    }

    /**
     * Triggered via \core\event\course_module_created event.
     *
     * @param course_module_created $event The event.
     *
     * @return void
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function course_module_created(course_module_created $event): void {
        global $DB;

        // If it's restoring we do nothing because data are imported from backup.
        // So don't need to create course module parameters.
        if (self::is_restoring($event->courseid)) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $event->courseid]);
        $cm = $DB->get_record('course_modules', ['id' => $event->objectid]);
        if ($course->format == 'ludilearn') {
            $gameelements = $DB->get_records('format_ludilearn_elements',
                [
                    'courseid' => $course->id,
                    'sectionid' => $cm->section,
                ]
            );
            foreach ($gameelements as $gameelement) {
                $cmparameters = game_element::get_cm_parameters_default_by_type($gameelement->type, $event->other['modulename'],
                    $event->objectid);
                foreach ($cmparameters as $name => $value) {
                    $DB->insert_record('format_ludilearn_cm_params',
                        ['gameelementid' => $gameelement->id, 'cmid' => $cm->id, 'name' => $name, 'value' => $value]);
                }
            }
        }
    }

    /**
     * Triggered via \core\event\course_module_deleted event.
     *
     * @param course_module_deleted $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function course_module_deleted(course_module_deleted $event): void {
        global $DB;
        $DB->delete_records('format_ludilearn_cm_params', ['cmid' => $event->contextinstanceid]);
        $DB->delete_records('format_ludilearn_cm_user', ['cmid' => $event->contextinstanceid]);
    }

    /**
     * Triggered via \core\event\course_module_updated event.
     *
     * @param course_module_updated $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function course_module_updated(course_module_updated $event): void {
        global $DB;
        $course = $DB->get_record('course', ['id' => $event->courseid]);
        $cm = $DB->get_record('course_modules', ['id' => $event->contextinstanceid]);
        if ($course->format == 'ludilearn') {
            // Retrieve the course module object.
            $cmid = $event->contextinstanceid;

            // If the section has changed, we need to update the game element.
            foreach (game_element::get_all_types() as $type) {
                // Game element of the next section.
                $nextgameelement = $DB->get_record('format_ludilearn_elements',
                    ['courseid' => $event->courseid, 'sectionid' => $cm->section, 'type' => $type]);

                $previousgameelementsql = 'SELECT ge.id FROM {format_ludilearn_cm_params} cmp
                                    INNER JOIN {format_ludilearn_elements} ge ON ge.id = cmp.gameelementid
                                    WHERE ge.type = :type
                                    AND cmp.cmid = :cmid';
                $previsousgameelement = $DB->get_record_sql($previousgameelementsql, ['type' => $type, 'cmid' => $cmid]);

                // If no game element, we continue.
                if (!$nextgameelement || !$previsousgameelement) {
                    continue;
                }

                // If no change, we continue.
                if ($nextgameelement->id == $previsousgameelement->id) {
                    continue;
                }
                // Update the game element of the course module.
                $sql = 'UPDATE {format_ludilearn_cm_params} SET gameelementid = :nextgameelement
                        WHERE id = :id';
                $DB->execute($sql, ['nextgameelement' => $nextgameelement->id, 'id' => $previsousgameelement->id]);

                // Attributions of the next section.
                $attributions = $DB->get_records('format_ludilearn_attributio',
                    ['gameelementid' => $nextgameelement->id]);

                // Update the attribution of the course module.
                foreach ($attributions as $attribution) {
                    $sql = 'UPDATE {format_ludilearn_cm_user} SET attributionid = :attributionid WHERE cmid = :cmid';
                    $DB->execute($sql, ['attributionid' => $attribution->id, 'cmid' => $cmid]);
                }
            }
        }
    }

    /**
     * Triggered via \core\event\user_graded event.
     *
     * @param user_graded $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function user_graded(user_graded $event): void {
        global $DB;

        $userid = $event->relateduserid;
        $gradeitemid = $event->other['itemid'];
        $gradeitem = $DB->get_record('grade_items', ['id' => $gradeitemid]);
        if ($gradeitem->itemtype != 'mod') {
            return;
        }
        $module = $DB->get_record('modules', ['name' => $gradeitem->itemmodule]);

        // Ignore if quiz with immediate feedback.
        if ($module->name == 'quiz') {
            $quiz = $DB->get_record('quiz', ['id' => $gradeitem->iteminstance]);
            if ($quiz->preferredbehaviour == 'immediatefeedback') {
                return;
            }
        }

        // Get course module.
        $coursemodule = $DB->get_record('course_modules',
            [
                'course' => $event->courseid,
                'module' => $module->id,
                'instance' => $gradeitem->iteminstance,
            ]
        );

        $course = $DB->get_record('course', ['id' => $event->courseid]);
        if ($course->format == 'ludilearn') {
            // Update score elements.
            score::update_elements($event->courseid, $coursemodule, $module->name, $userid);

            // Update badge elements.
            \format_ludilearn\local\gameelements\badge::update_elements($event->courseid, $coursemodule,
                $module->name, $userid);

            // Update progress elements.
            progress::update_elements($event->courseid, $coursemodule, $module->name, $userid);

            // Update avatar elements.
            avatar::update_elements($event->courseid, $coursemodule, $module->name, $userid);

            // Update ranking elements.
            ranking::update_elements($event->courseid, $coursemodule, $module->name, $userid);
        }
    }

    /**
     * Triggered via \mod_quiz\event\attempt_updated event.
     *
     * @param attempt_updated $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function attempt_updated(attempt_updated $event): void {
        global $DB;
        $quiz = $DB->get_record('quiz', ['id' => $event->other['quizid']]);

        // Check if the quiz is in a ludilearn course.
        $course = $DB->get_record('course', ['id' => $event->courseid]);
        if ($course->format == 'ludilearn') {

            // Ignore if quiz with defered feedback.
            if (!$quiz || $quiz->preferredbehaviour != 'immediatefeedback') {
                return;
            }

            // Update score element.
            score::update_quiz_immediate_feedback($quiz->id, $event->relateduserid);

            // Update badge element.
            \format_ludilearn\local\gameelements\badge::update_quiz_immediate_feedback($quiz->id,
                $event->relateduserid);

            // Update progress element.
            progress::update_quiz_immediate_feedback($quiz->id, $event->relateduserid);

            // Update avatar element.
            avatar::update_quiz_immediate_feedback($quiz->id, $event->relateduserid);

            // Update timer element.
            timer::update_quiz_immediate_feedback($event->objectid, $quiz->id, $event->relateduserid);

            // Update ranking element.
            ranking::update_quiz_immediate_feedback($quiz->id, $event->relateduserid, $event->objectid);
        }
    }

    /**
     * Triggered via \mod_quiz\event\attempt_deleted event.
     *
     * @param attempt_deleted $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function attempt_deleted(attempt_deleted $event) {
        global $DB;
        $quiz = $DB->get_record('quiz', ['id' => $event->other['quizid']]);

        // Check if the quiz is in a ludilearn course.
        $course = $DB->get_record('course', ['id' => $event->courseid]);
        if ($course->format == 'ludilearn') {
            // Ignore if quiz with defered feedback.
            if ($quiz->preferredbehaviour != 'immediatefeedback') {
                return;
            }
            // Update score element.
            score::update_quiz_immediate_feedback($quiz->id, $event->relateduserid);

            // Update badge element.
            \format_ludilearn\local\gameelements\badge::update_quiz_immediate_feedback($quiz->id,
                $event->relateduserid);

            // Update progress element.
            progress::update_quiz_immediate_feedback($quiz->id, $event->relateduserid);

            // Update avatar element.
            avatar::update_quiz_immediate_feedback($quiz->id, $event->relateduserid);

            // Update timer element.
            timer::update_quiz_immediate_feedback($event->objectid, $quiz->id, $event->relateduserid);

            // Update ranking element.
            ranking::update_quiz_immediate_feedback($quiz->id, $event->relateduserid, $event->objectid);

            // Trigger event question attempt updated.

        }
    }

    /**
     * Triggered via \mod_quiz\event\attempt_submitted event.
     *
     * @param attempt_submitted $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function attempt_submitted(attempt_submitted $event): void {
        global $DB;
        $quiz = $DB->get_record('quiz', ['id' => $event->other['quizid']]);

        // Check if the quiz is in a ludilearn course.
        $course = $DB->get_record('course', ['id' => $event->courseid]);
        if ($course->format == 'ludilearn') {
            // Update timer element.
            timer::submit_quiz($event->objectid, $quiz->id, $event->relateduserid);
        }
    }

    /**
     * Triggered via \core\event\course_reset_ended event.
     *
     * @param course_reset_ended $event The event.
     *
     * @return void
     * @throws dml_exception
     */
    public static function course_reset_ended(course_reset_ended $event): void {
        global $DB;

        // If reset gradebook grades is checked when reset course.
        if (!empty($event->other['reset_options']['reset_gradebook_grades'])) {
            $course = $DB->get_record('course', ['id' => $event->courseid]);
            if ($course->format == 'ludilearn') {
                // Reset all game elements progression in this course.
                game_element::reset_course($event->courseid);
            }
        }
    }

    /**
     * Vérify if a restoration is in progress.
     *
     * @param int $courseid The course id.
     *
     * @return bool True if a restoration is in progress, false otherwise.
     * @throws dml_exception
     */
    protected static function is_restoring(int $courseid): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        return $DB->record_exists_sql('SELECT * FROM {backup_controllers}
         WHERE type = :type AND itemid = :itemid AND operation = :operation AND status < :status',
            ['type' => 'course', 'itemid' => $courseid, 'operation' => 'restore', 'status' => backup::STATUS_FINISHED_OK]);
    }
}
