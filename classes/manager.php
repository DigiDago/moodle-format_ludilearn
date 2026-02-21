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

namespace format_ludilearn;

use backup;
use context_course;
use context_module;
use format_ludilearn\local\gameelements\avatar;
use format_ludilearn\local\gameelements\badge;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\local\gameelements\nogamified;
use format_ludilearn\local\gameelements\progress;
use format_ludilearn\local\gameelements\ranking;
use format_ludilearn\local\gameelements\score;
use format_ludilearn\local\gameelements\timer;
use moodle_url;
use question_engine;
use stdClass;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/course/format/lib.php');
/**
 * Format Ludilearn's manager class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Get game elements ID by section.
     *
     * @param int $courseid  The course ID.
     * @param int $sectionid The section ID.
     *
     * @return array The game elements ID.
     * @throws \dml_exception
     */
    public function get_gameelements_id_by_section(int $courseid, int $sectionid): array {
        global $DB;

        $gameelementsid = [];
        $gameelementsreq = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid, 'sectionid' => $sectionid]);
        foreach ($gameelementsreq as $gameelementreq) {
            $gameelementsid[] = $gameelementreq->id;
        }

        return $gameelementsid;
    }

    /**
     * Check if a game element is attributed to a user.
     *
     * @param int $courseid Course ID.
     * @param int $userid   User ID.
     *
     * @return bool True if the game element is attributed to the user, false otherwise.
     * @throws \dml_exception
     */
    public function has_attribution(int $courseid, int $userid): bool {
        global $DB;

        $sql = "SELECT COUNT(*) FROM {format_ludilearn_attributio} a
                JOIN {format_ludilearn_elements} ge ON ge.id = a.gameelementid
                WHERE ge.courseid = :courseid AND a.userid = :userid";
        $attributions = $DB->count_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        return $attributions > 0;
    }

    /**
     * Check the attribution of a game element to a user and attribute it if necessary.
     *
     * @param int $courseid Course ID.
     * @param int $userid   User ID.
     * @param string $type  Type of the game element.
     *
     * @throws \dml_exception
     */
    public function check_attribution_course(int $courseid, int $userid, string $type) {
        global $DB;

        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid, 'type' => $type]);
        foreach ($gameelements as $gameelement) {
            // Check attribution.
            $attribution = $DB->get_record(
                'format_ludilearn_attributio',
                [
                    'gameelementid' => $gameelement->id,
                    'userid' => $userid,
                ]
            );
            if (!$attribution) {
                $this->attribution_game_element($gameelement->id, $userid);
            }
        }
    }

    /**
     * Attribution of a game element to a user.
     *
     * @param int $gameelementid Game element ID.
     * @param int $userid        User ID.
     * @param bool $force        Force the attribution.
     *
     * @return int Attribution ID.
     * @throws \dml_exception
     */
    public function attribution_game_element(int $gameelementid, int $userid, bool $force = false): int {
        global $DB;

        $gameelement = $DB->get_record('format_ludilearn_elements', ['id' => $gameelementid]);
        $attribution = $DB->get_record('format_ludilearn_attributio', ['gameelementid' => $gameelementid, 'userid' => $userid]);
        $req = 'SELECT * FROM {format_ludilearn_elements} WHERE courseid = :courseid AND sectionid = :sectionid AND type != :type';
        $gameelementtodeletes = $DB->get_records_sql(
            $req,
            [
                'courseid' => $gameelement->courseid,
                'sectionid' => $gameelement->sectionid,
                'type' => $gameelement->type,
            ]
        );
        foreach ($gameelementtodeletes as $gameelementtodelete) {
            $DB->delete_records('format_ludilearn_attributio', ['gameelementid' => $gameelementtodelete->id, 'userid' => $userid]);
        }
        if ($attribution) {
            if ($force) {
                $DB->delete_records('format_ludilearn_attributio', ['gameelementid' => $gameelementid, 'userid' => $userid]);
                return $this->attribution_game_element($gameelementid, $userid, false);
            }

            return $attribution->id;
        } else {
            return $DB->insert_record(
                'format_ludilearn_attributio',
                [
                    'gameelementid' => $gameelementid,
                    'userid' => $userid,
                    'timecreated' => time(),
                ]
            );
        }
    }

    /**
     * Attribution of a game element to users by section.
     *
     * @param int $courseid      Course ID.
     * @param int $sectionid     Section ID.
     * @param int $gameelementid Game element ID.
     *
     * @throws \dml_exception
     */
    public function attribution_by_section(int $courseid, int $sectionid, int $gameelementid): void {
        $context = context_course::instance($courseid);

        // Update the attribution of the game element.
        $this->update_attribution_by_section($courseid, $sectionid, $gameelementid);

        // For each user in the course, update the attribution of the game element.
        $users = get_enrolled_users($context);
        foreach ($users as $user) {
            // Verify if the user has been attributed the game element manually.
            if ($this->get_game_element_manually_assigned($courseid, $user->id)) {
                continue;
            }
            // Attribute the game element to the user.
            $this->attribution_game_element($gameelementid, $user->id);
        }
    }

    /**
     * Update the attribution of the game element.
     *
     * @param int $courseid      Course ID.
     * @param int $sectionid     Section ID.
     * @param int $gameelementid Game element ID.
     *
     * @return stdClass The attribution by section.
     * @throws \dml_exception
     */
    public function update_attribution_by_section(int $courseid, int $sectionid, int $gameelementid): stdClass {
        global $DB;

        // Update the attribution of the game element.
        $bysection = $DB->get_record('format_ludilearn_bysection', ['courseid' => $courseid, 'sectionid' => $sectionid]);
        if ($bysection) {
            if ($bysection->gameelementid != $gameelementid) {
                $bysection->gameelementid = $gameelementid;
                $DB->update_record('format_ludilearn_bysection', $bysection);
            }
        } else {
            $bysection = new stdClass();
            $bysection->courseid = $courseid;
            $bysection->sectionid = $sectionid;
            $bysection->gameelementid = $gameelementid;
            $id = $DB->insert_record(
                'format_ludilearn_bysection',
                ['courseid' => $courseid, 'sectionid' => $sectionid, 'gameelementid' => $gameelementid]
            );
            $bysection->id = $id;
        }
        return $bysection;
    }

    /**
     * Get game elements with auto assignement.
     *
     * @param string $type       Type of the game elements.
     * @param int|null $courseid Course ID.
     *
     * @return array Array of game elements with auto assignements.
     * @throws \dml_exception
     */
    public function get_gameelements_auto(string $type, ?int $courseid = null): array {
        global $DB;

        $gameelements = [];
        if ($courseid == null) {
            $sqlcourses = 'SELECT * FROM {course_format_options}
                            WHERE format = :format AND name = :name
                            AND ' . $DB->sql_compare_text("value") . ' = ' . $DB->sql_compare_text(":value");
            $courses = $DB->get_records_sql(
                $sqlcourses,
                ['format' => 'ludilearn', 'name' => 'assignment', 'value' => 'automatic']
            );
            foreach ($courses as $course) {
                $gameelems = $DB->get_records(
                    'format_ludilearn_elements',
                    ['courseid' => $course->courseid, 'type' => $type]
                );
                $gameelements = array_merge($gameelements, $gameelems);
            }
        } else {
            $gameelems = $DB->get_records(
                'format_ludilearn_elements',
                ['courseid' => $courseid, 'type' => $type]
            );
            $gameelements = $gameelems;
        }
        return $gameelements;
    }

    /**
     * Update the type of game element.
     *
     * @param int $gameelementid Game element ID.
     * @param string $type       Type of the game element.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function update_type_game_element(int $gameelementid, string $type): void {
        global $DB;
        $params = new stdClass();
        $params->id = $gameelementid;
        $params->type = $type;
        $DB->update_record('format_ludilearn_elements', $params);

        $gameelement = $DB->get_record('format_ludilearn_elements', ['id' => $gameelementid]);

        // Get the default parameters of the game element.
        $parameters = $this->get_parameters_default($type, $gameelement->courseid);

        // Create parameters if not exist.
        foreach ($parameters as $name => $value) {
            $exist = $DB->record_exists('format_ludilearn_params', ['gameelementid' => $gameelementid, 'name' => $name]);
            if (!$exist) {
                $DB->insert_record(
                    'format_ludilearn_params',
                    ['gameelementid' => $gameelementid, 'name' => $name, 'value' => $value]
                );
            }
        }

        // Create cm parameters if not exist.
        $cms = $DB->get_records('course_modules', ['course' => $gameelement->courseid, 'section' => $gameelement->sectionid]);
        foreach ($cms as $cm) {
            $modetype = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $cmparameters = game_element::get_cm_parameters_default_by_type($type, $modetype);
            foreach ($cmparameters as $name => $value) {
                $exist = $DB->record_exists(
                    'format_ludilearn_cm_params',
                    ['gameelementid' => $gameelementid, 'cmid' => $cm->id, 'name' => $name]
                );
                if (!$exist) {
                    $DB->insert_record(
                        'format_ludilearn_cm_params',
                        ['gameelementid' => $gameelementid, 'cmid' => $cm->id, 'name' => $name, 'value' => $value]
                    );
                }
            }
        }
    }

    /**
     * Update a course module parameter of a game element.
     *
     * @param int $gameelementid Game element ID.
     * @param int $cmid          Course module ID.
     * @param string $name       Name of the parameter.
     * @param string $value      Value of the parameter.
     *
     * @return bool
     * @throws \dml_exception
     */
    public function update_cm_parameter(int $gameelementid, int $cmid, string $name, string $value): bool {
        global $DB;
        return $DB->set_field(
            'format_ludilearn_cm_params',
            'value',
            $value,
            ['gameelementid' => $gameelementid, 'cmid' => $cmid, 'name' => $name]
        );
    }

    /**
     * Get the global type of the game element on a course.
     * Return the default game element type if the course not have a section.
     *
     * @param int $courseid Course ID.
     * @param int $userid   User ID.
     *
     * @return string  Type of the game element.
     * @throws \dml_exception
     */
    public function get_global_type_game_element(int $courseid, int $userid): string {
        global $DB;
        $sql = "SELECT ge.id, ge.type
                FROM {format_ludilearn_elements} ge
                JOIN {format_ludilearn_attributio} a ON a.gameelementid = ge.id
                WHERE ge.courseid = :courseid AND a.userid = :userid
                LIMIT 1";
        $gameelementres = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        if ($gameelementres) {
            return $gameelementres->type;
        } else {
            $format = course_get_format($courseid);
            $options = $format->get_format_options();
            return $options['default_game_element'];
        }
    }

    /**
     * Remove a game element.
     *
     * @param int $gameelementid Game element ID.
     *
     * @return void
     * @throws \dml_exception
     */
    public function remove_game_element(int $gameelementid): void {
        global $DB;
        $DB->delete_records('format_ludilearn_elements', ['id' => $gameelementid]);
        $DB->delete_records('format_ludilearn_params', ['gameelementid' => $gameelementid]);
        $DB->delete_records('format_ludilearn_cm_params', ['gameelementid' => $gameelementid]);
        $DB->delete_records('format_ludilearn_attributio', ['gameelementid' => $gameelementid]);
        $DB->delete_records('format_ludilearn_bysection', ['gameelementid' => $gameelementid]);
    }

    /**
     * Remove a game element by section.
     *
     * @param int $sectionid Section ID.
     *
     * @throws \dml_exception
     */
    public function remove_game_element_by_section(int $sectionid): void {
        global $DB;
        $gameelementid = $DB->get_field('format_ludilearn_elements', 'id', ['sectionid' => $sectionid]);
        if ($gameelementid) {
            $this->remove_game_element($gameelementid);
        }
    }

    /**
     * Remove a game element by course.
     *
     * @param int $courseid Course ID.
     *
     * @return void
     * @throws \dml_exception
     */
    public function remove_game_element_by_course(int $courseid): void {
        global $DB;
        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid], '', 'id');
        if ($gameelements) {
            foreach ($gameelements as $gameelement) {
                $this->remove_game_element($gameelement->id);
            }
        }
    }

    /**
     * Get the type of course module parameter (text or number).
     *
     * @param string $type Type of the game element.
     * @param string $name Name of the parameter.
     *
     * @return string Type of the parameter.
     */
    public function get_cm_parameter_type(string $type, string $name): string {
        switch ($type) {
            case 'score':
                // Get the default parameters of the game element.
                $result = score::get_cm_parameter_type($name);
                break;
            case 'badge':
                // Get the default parameters of the game element.
                $result = badge::get_cm_parameter_type($name);
                break;
            case 'progress':
                // Get the default parameters of the game element.
                $result = progress::get_cm_parameter_type($name);
                break;
            case 'avatar':
                // Get the default parameters of the game element.
                $result = avatar::get_cm_parameter_type($name);
                break;
            case 'timer':
                // Get the default parameters of the game element.
                $result = timer::get_cm_parameter_type($name);
                break;
            case 'ranking':
                // Get the default parameters of the game element.
                $result = ranking::get_cm_parameter_type($name);
                break;
            case 'nogamified':
                $result = nogamified::get_cm_parameter_type($name);
                break;
            default:
                $result = 'text';
        }
        return $result;
    }

    /**
     * Update the value of a course module user parameter.
     *
     * @param int $gameelementid Game element ID.
     * @param int $cmid          Course module ID.
     * @param int $userid        User ID.
     * @param string $name       Name of the parameter.
     * @param string $value      Value of the parameter.
     *
     * @return bool True if the update is successful, false otherwise.
     * @throws \dml_exception
     */
    public function update_cm_user(int $gameelementid, int $cmid, int $userid, string $name, string $value): bool {
        global $DB;
        $attributionid = $DB->get_field(
            'format_ludilearn_attributio',
            'id',
            ['gameelementid' => $gameelementid,
            'userid' => $userid]
        );
        $exists = $DB->record_exists(
            'format_ludilearn_cm_user',
            ['attributionid' => $attributionid, 'name' => $name]
        );

        // Update the value of the parameter if it exists, otherwise insert a new record.
        if ($exists) {
            return $DB->set_field(
                'format_ludilearn_cm_user',
                'value',
                $value,
                ['attributionid' => $attributionid, 'name' => $name]
            );
        } else {
            return $DB->insert_record(
                'format_ludilearn_cm_user',
                [
                    'attributionid' => $attributionid,
                    'name' => $name,
                    'value' => $value,
                ]
            );
        }
    }

    /**
     * Update the value of a game element user parameter.
     *
     * @param int $gameelementid Game element ID.
     * @param int $userid        User ID.
     * @param string $name       Name of the parameter.
     * @param string $value      Value of the parameter.
     *
     * @return bool True if the update is successful, false otherwise.
     * @throws \dml_exception
     */
    public function update_gameelement_user(int $gameelementid, int $userid, string $name, string $value): bool {
        global $DB;
        $attributionid = $DB->get_field(
            'format_ludilearn_attributio',
            'id',
            ['gameelementid' => $gameelementid, 'userid' => $userid]
        );
        $exists = $DB->record_exists(
            'format_ludilearn_ele_user',
            ['attributionid' => $attributionid, 'name' => $name]
        );

        // Update the value of the parameter if it exists, otherwise insert a new record.
        if ($exists) {
            return $DB->set_field(
                'format_ludilearn_ele_user',
                'value',
                $value,
                ['attributionid' => $attributionid, 'name' => $name]
            );
        } else {
            return $DB->insert_record(
                'format_ludilearn_ele_user',
                [
                    'attributionid' => $attributionid,
                    'name' => $name,
                    'value' => $value,
                ]
            );
        }
    }

    /**
     * Calculate the best grade of a quiz.
     *
     * @param object $quiz Quiz.
     * @param array $attempts Attempts.
     *
     * @return float Best grade of the quiz.
     */
    public function quiz_calculate_best_grade(object $quiz, array $attempts): float {
        switch ($quiz->grademethod) {
            case QUIZ_ATTEMPTFIRST:
            case QUIZ_ATTEMPTLAST:
            case QUIZ_GRADEAVERAGE:
                $sum = 0;
                $count = 0;
                foreach ($attempts as $attempt) {
                    if (!is_null($attempt->sumgrades)) {
                        $sum += $attempt->sumgrades;
                        $count++;
                    }
                }
                // No break - intentional fall-through to calculate/compare highest score.
            case QUIZ_GRADEHIGHEST:
            default:
                $max = 0;
                foreach ($attempts as $attempt) {
                    if ($attempt->sumgrades > $max) {
                        $max = $attempt->sumgrades;
                    }
                }
                $result = $max;
        }

        if ($result == null) {
            return 0;
        } else {
            return $result;
        }
    }

    /**
     * Calculate the grade of a quiz.
     *
     * @param object $quiz Quiz.
     * @param int $userid User ID.
     *
     * @return float Grade of the quiz.
     * @throws \dml_exception
     */
    public function calculate_quiz_grade(object $quiz, int $userid): float {
        global $DB;

        $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id, 'userid' => $userid]);
        if ($attempts) {
            foreach ($attempts as &$attempt) {
                // If attempt is in progress, calculate the grade.
                if ($attempt->state == 'inprogress') {
                    $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);
                    $attempt->sumgrades = $quba->get_total_mark();
                }
            }
            return intval($this->quiz_calculate_best_grade($quiz, $attempts));
        } else {
            return 0;
        }
    }

    /**
     * Check if a course module has been viewed by a user.
     *
     * @param int $cmid   Course module ID.
     * @param int $userid User ID.
     *
     * @return bool True if the course module has been viewed by the user, false otherwise.
     * @throws \dml_exception
     */
    public function cm_viewed_by_user(int $cmid, int $userid): bool {
        global $DB;

        return $DB->record_exists('logstore_standard_log', [
            'userid' => $userid,
            'contextlevel' => CONTEXT_MODULE,
            'action' => 'viewed',
            'origin' => 'web',
            'contextinstanceid' => $cmid,
        ]);
    }

    /**
     * Get the possible conditions of a course module.
     *
     * @param int $cmid Course module ID.
     *
     * @return array Possible conditions.
     * @throws \dml_exception
     */
    public function get_cm_possible_conditions(int $cmid): array {
        global $DB;

        $cm = $DB->get_record('course_modules', ['id' => $cmid]);
        $moduletype = $DB->get_field('modules', 'name', ['id' => $cm->module]);
        $conditions = ['completion'];
        // Check if the module has a grade capability.
        if (get_capability_info('mod/' . $moduletype . ':grade')) {
            $conditions[] = 'grade';
        }
        $conditions[] = 'nogamification';
        return $conditions;
    }

    /**
     * Get attempts of a quiz.
     *
     * @param int $attemptid Attempt ID.
     *
     * @return array Attempts.
     * @throws \dml_exception
     */
    public function get_attempt(int $attemptid): array {
        global $DB;

        $query = "SELECT qa.questionid as questionid, max(qas.fraction) AS fraction, max(qa.maxmark) as maxgrade,
                           max(qasd.value) as ludigrade, qas.state
                    FROM {quiz_attempts} za
                    JOIN {question_attempts} qa ON qa.questionusageid=za.uniqueid
                    JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id AND qas.state
                        IN ('complete', 'gaveup', 'gradedwrong', 'gradedright', 'gradedpartial')
                    LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id AND qasd.name = '-ludigrade'
                    WHERE za.id=:attemptid
                    GROUP BY qa.questionid, qas.state";
        $sqlresult = $DB->get_records_sql($query, ['attemptid' => $attemptid]);

        $result = [];
        foreach ($sqlresult as $questionid => $record) {
            if ($record->fraction !== null) {
                $grade = $record->fraction * $record->maxgrade;
            } else if ($record->ludigrade !== null) {
                $grade = $record->ludigrade;
            } else if ($record->state == 'gaveup') {
                $grade = 0;
            } else {
                continue;
            }
            $result[] = (object)[
                "questionid" => $record->questionid,
                "grade" => $grade,
                "maxgrade" => $record->maxgrade,
            ];
        }
        return $result;
    }

    /**
     * Return quiz attempts info
     *
     * @param int $attemptid Attempt ID.
     *
     * @return object Attempt info.
     * @throws \dml_exception
     */
    public function fetch_attempt_info(int $attemptid): object {
        global $DB;

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);

        $questionsnumber = $DB->get_record_sql('
            SELECT count(*) as qnumber
            FROM {question_attempts}
            WHERE questionusageid = ?
        ', [$attempt->uniqueid]);
        $attempt->questionsnumber = $questionsnumber->qnumber ?? 0;

        return $attempt;
    }

    /**
     * Get the type of global user game element.
     *
     * Get the game element of the user obtained by the algo questionnary.
     * If the user has not obtained a game element, return the first attribution.
     * else return null.
     *
     * @param int $userid        User ID.
     * @param int|null $courseid Course ID.
     *
     * @return string The type of game element.
     * @throws \dml_exception
     */
    public function get_global_user_game_element(int $userid, $courseid = null): string {
        global $DB;

        $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $userid]);
        if ($profile) {
            return $profile->type;
        } else {
            if ($courseid) {
                return $this->get_global_type_game_element($courseid, $userid);
            } else {
                $req = 'SELECT g.type FROM {format_ludilearn_elements} g
                        JOIN {format_ludilearn_attributio} a ON a.gameelementid = g.id
                        WHERE a.userid = :userid
                        LIMIT 1';
                $gameelement = $DB->get_record_sql($req, ['userid' => $userid]);
                if ($gameelement) {
                    return $gameelement->type;
                } else {
                    return 'nogamified';
                }
            }
        }
    }

    /**
     * Get the last page attempted by a user in a quiz.
     *
     * @param int $quizid ID of the quiz.
     * @param int $userid ID of the user.
     *
     * @return int The last page attempted by the user.
     * @throws \dml_exception
     */
    public function get_last_page_attempted(int $quizid, int $userid): int {
        global $DB;

        $attempt = $DB->get_records('quiz_attempts', ['quiz' => $quizid, 'userid' => $userid], 'id DESC', '*', 0, 1);
        if (count($attempt) > 0) {
            $attempt = reset($attempt);
            $questions = $DB->get_records('question_attempts', ['questionusageid' => $attempt->uniqueid], 'timemodified DESC');
            if ($questions) {
                $page = $DB->get_field(
                    'quiz_slots',
                    'page',
                    ['slot' => reset($questions)->slot, 'quizid' => $quizid]
                );
                if ($page) {
                    return intval($page);
                }
            }
        }
        return 0;
    }

    /**
     * Stringify a rank.
     *
     * @param int $rank Rank.
     *
     * @return string Stringified rank.
     */
    public function stringify_rank(int $rank): string {
        return $rank . $this->get_postfix($rank);
    }

    /**
     * Get the postfix of a rank.
     *
     * @param int $rank Rank.
     *
     * @return string Postfix of the rank.
     * @throws \coding_exception
     */
    public function get_postfix(int $rank): string {
        if ($rank == 1) {
            return get_string('first', 'format_ludilearn');
        } else if ($rank == 2) {
            return get_string('second', 'format_ludilearn');
        } else if ($rank == 3) {
            return get_string('third', 'format_ludilearn');
        } else {
            return get_string('th', 'format_ludilearn');
        }
    }

    /**
     * Sync the user attribution.
     *
     * @param int $courseid              Course ID.
     * @param string $assignment         Assignment type.
     * @param string $defaultgameelement Default game element.
     * @param bool $assignmentchanged    Assignment changed.
     *
     * @return void
     * @throws \dml_exception
     */
    public function sync_user_attribution(
        int $courseid,
        string $assignment,
        string $defaultgameelement,
        bool $assignmentchanged
    ): void {
        global $DB;

        $context = context_course::instance($courseid);
        $users = get_enrolled_users($context);

        $sections = $DB->get_records('course_sections', ['course' => $courseid]);
        foreach ($sections as $section) {
            // Get the default game element.
            $gameelementbydefault = $DB->get_record(
                'format_ludilearn_elements',
                ['courseid' => $courseid, 'sectionid' => $section->id, 'type' => $defaultgameelement]
            );

            // To verify if attribution by section already exist in case the attribtion is by section.
            $bysection = $DB->get_record(
                'format_ludilearn_bysection',
                ['courseid' => $courseid, 'sectionid' => $section->id]
            );
            if (!$bysection && $assignment == 'bysection') {
                $bysection = $this->update_attribution_by_section($courseid, $section->id, $gameelementbydefault->id);
            }

            // Attribution of the game elements to the users.
            foreach ($users as $user) {
                // Verify if the user has been attributed the game element manually.
                if ($this->get_game_element_manually_assigned($courseid, $user->id)) {
                    continue;
                }

                if ($assignment == 'manual' || $assignment == 'bysection') {
                    // Type of game element.
                    $type = $defaultgameelement;
                } else if ($assignment == 'automatic') {
                    // Else if assignment is automatic, we attribute the game element to the user based on his profile.
                    $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $user->id]);
                    if ($profile) {
                        $type = $profile->type;
                    }
                }
                // Attribution game element.
                if (isset($type)) {
                    if ($assignment != 'bysection') {
                        $gameelement = $DB->get_record(
                            'format_ludilearn_elements',
                            ['courseid' => $courseid, 'sectionid' => $section->id, 'type' => $type]
                        );
                        $this->attribution_game_element($gameelement->id, $user->id);
                    } else {
                        // If the game element by section already exist, we attribute it to the user.
                        if ($bysection) {
                            $this->attribution_game_element($bysection->gameelementid, $user->id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Sync the user attribution by user.
     *
     * @param int $courseid              Course ID.
     * @param string $assignment         Assignment type.
     * @param string $defaultgameelement Default game element.
     * @param int $userid                User ID.
     *
     * @return void
     * @throws \dml_exception
     */
    public function sync_user_attribution_by_user(
        int $courseid,
        string $assignment,
        string $defaultgameelement,
        int $userid
    ): void {
        global $DB;

        // Verify if the user has been attributed the game element manually.
        if ($this->get_game_element_manually_assigned($courseid, $userid)) {
            return;
        }

        $sections = $DB->get_records('course_sections', ['course' => $courseid]);
        foreach ($sections as $section) {
            // Get the default game element.
            $gameelementbydefault = $DB->get_record(
                'format_ludilearn_elements',
                ['courseid' => $courseid, 'sectionid' => $section->id, 'type' => $defaultgameelement]
            );

            // To verify if attribution by section already exist in case the attribtion is by section.
            $bysection = $DB->get_record(
                'format_ludilearn_bysection',
                ['courseid' => $courseid, 'sectionid' => $section->id]
            );
            if (!$bysection && $assignment == 'bysection') {
                $bysection = $this->update_attribution_by_section($courseid, $section->id, $gameelementbydefault->id);
            }

            if ($assignment == 'manual' || $assignment == 'bysection') {
                // Type of game element.
                $type = $defaultgameelement;
            } else if ($assignment == 'automatic') {
                // Else if assignment is automatic, we attribute the game element to the user based on his profile.
                $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $userid]);
                if ($profile) {
                    $type = $profile->type;
                }
            }
            // Attribution game element.
            if (isset($type)) {
                if ($assignment != 'bysection') {
                    $gameelement = $DB->get_record(
                        'format_ludilearn_elements',
                        ['courseid' => $courseid, 'sectionid' => $section->id, 'type' => $type]
                    );
                    $this->attribution_game_element($gameelement->id, $userid);
                } else {
                    // If the game element by section already exist, we attribute it to the user.
                    if ($bysection) {
                        $this->attribution_game_element($bysection->gameelementid, $userid);
                    }
                }
            }
        }
    }

    /**
     * Vérify if a restoration is in progress.
     *
     * @param int $courseid The course id.
     *
     * @return bool True if a restoration is in progress, false otherwise.
     * @throws \dml_exception
     */
    public function is_restoring(int $courseid): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        return $DB->record_exists_sql(
            'SELECT * FROM {backup_controllers}
             WHERE type = :type AND itemid = :itemid AND operation = :operation AND status < :status',
            [
                'type' => 'course',
                'itemid' => $courseid,
                'operation' => 'restore',
                'status' => backup::STATUS_FINISHED_OK,
            ]
        );
    }

    /**
     * Refresh the progression of all users in a course.
     * (That is usefull when a game element of course or a section is changed).
     *
     * @param int $courseid
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function refresh_progression(int $courseid): void {
        global $DB;

        // Get all the game elements of the course.
        $gameelementrecords = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid]);
        foreach ($gameelementrecords as $gameelementrecord) {
            // Get all the attributions of the game element.
            $attributions = $DB->get_records('format_ludilearn_attributio', ['gameelementid' => $gameelementrecord->id]);
            foreach ($attributions as $attribution) {
                // Get the game element of the user.
                $gameelement = game_element::get_element(
                    $courseid,
                    $gameelementrecord->sectionid,
                    $attribution->userid,
                    $gameelementrecord->type
                );

                // For each course module.
                $cms = $gameelement->get_cm_parameters();
                foreach ($cms as $cm) {
                    if ($gameelement->is_gradable($cm['id']) && $cm['gamified']) {
                        $coursemodule = $DB->get_record('course_modules', ['id' => $cm['id']]);
                        $module = $DB->get_record('modules', ['id' => $coursemodule->module]);
                        // Update the progression of the user.
                        switch ($gameelement->get_type()) {
                            case 'score':
                                score::update_elements($courseid, $coursemodule, $module->name, $attribution->userid);
                                break;
                            case 'badge':
                                badge::update_elements($courseid, $coursemodule, $module->name, $attribution->userid);
                                break;
                            case 'progress':
                                progress::update_elements($courseid, $coursemodule, $module->name, $attribution->userid);
                                break;
                            case 'avatar':
                                avatar::update_elements($courseid, $coursemodule, $module->name, $attribution->userid);
                                break;
                            case 'ranking':
                                ranking::update_elements($courseid, $coursemodule, $module->name, $attribution->userid);
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Populate the course module parameters.
     *
     * @param stdClass $course     Course.
     * @param stdClass $parameters Parameters to populate.
     * @param stdClass $section    Section of the course module.
     * @param string $type         Type of the section.
     *
     * @return stdClass Populated parameters.
     * @throws \moodle_exception
     */
    public function populate_section(stdClass $course, stdClass $parameters, stdClass $section, string $type): stdClass {
        global $USER;

        $sectioninfo = get_fast_modinfo($course)->get_section_info($section->section);

        // Populate the section parameters in function of type.
        switch ($type) {
            case 'score':
                if ($parameters->maxscore == 0) {
                    $parameters->noscore = true;
                }
                break;
            case 'badge':
                $parameters->badge = $section->gameelement->get_current_badge();

                // Count of badges for each type.
                $parameters->bronzecount = $section->gameelement->get_bronze_count() == 0
                    ? false : $section->gameelement->get_bronze_count();
                $parameters->silvercount = $section->gameelement->get_silver_count() == 0
                    ? false : $section->gameelement->get_silver_count();
                $parameters->goldcount = $section->gameelement->get_gold_count() == 0
                    ? false : $section->gameelement->get_gold_count();
                $parameters->completioncount = $section->gameelement->get_completion_count() == 0
                    ? false : $section->gameelement->get_completion_count();

                break;
            case 'progress':
                // Different display if the section is completed.
                if ($section->gameelement->get_progression() == 100) {
                    $parameters->completed = true;
                }

                // Step of the progress bar.
                $parameters->step = 0;
                if ($section->gameelement->get_progression() != 0) {
                    $parameters->step = intval($section->gameelement->get_progression() / 10);
                }

                // Attribution of planete number.
                // Modulo 9 because there are 9 diffenrent planets.
                $parameters->planetenumber = ($section->section) % 8 + 1;
                break;
            case 'avatar':
                $ownedstatus = avatar::get_items_owned_status_by_section($course->id, $USER->id, $section->id);
                $parameters->itemsownedcount = $ownedstatus->owned;
                $parameters->itemsownablecount = $ownedstatus->ownable;
                $ownedstatus = avatar::get_items_owned_status($course->id, $USER->id);

                // Get total of items ownable in the section and total of items owned in the section.
                $parameters->totalitems = $ownedstatus->ownable;
                $parameters->totalitemsowned = $ownedstatus->owned;
                if ($parameters->totalitemsowned >= 10) {
                    $parameters->itemsownedcounttwodigits = true;
                }
                break;
            case 'ranking':
                $parameters->rank = $parameters->ranking->user_rank;
                if ($parameters->rank != null) {
                    $parameters->postfix = $this->get_postfix($parameters->rank);
                }
                if ($parameters->maxscore == 0) {
                    $parameters->noscore = true;
                }

                $parameters->ranks = [];
                if ($parameters->rank == 1) {
                    // Case when user is first.
                    $parameters->crowned = true;
                    $parameters->ranked = false;

                    // First user.
                    $rank = new stdClass();
                    $rank->rank = $this->stringify_rank(1);
                    $rank->score = intval($parameters->score);
                    $rank->me = true;
                    $parameters->ranks[] = $rank;

                    // User.
                    if ($parameters->ranking->succeeding_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $this->stringify_rank($parameters->ranking->succeeding_user_rank);
                        if ($parameters->ranking->succeeding_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->succeeding_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }

                    // After user.
                    if ($parameters->ranking->succeeding2_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $this->stringify_rank(3);
                        if ($parameters->ranking->succeeding2_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->succeeding2_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }
                } else if ($parameters->rank == 2) {
                    // Case when user is not first.
                    $parameters->ranked = true;

                    // First user.
                    $rank = new stdClass();
                    $rank->rank = $this->stringify_rank(1);
                    if ($parameters->ranking->first_user_total_score != null) {
                        $rank->score = intval($parameters->ranking->first_user_total_score);
                    } else {
                        $rank->score = 0;
                    }
                    $parameters->ranks[] = $rank;

                    // User.
                    $rank = new stdClass();
                    $rank->rank = $this->stringify_rank($parameters->rank);
                    $rank->score = intval($parameters->score);
                    $rank->me = true;
                    $parameters->ranks[] = $rank;

                    // After user.
                    if ($parameters->ranking->succeeding_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $this->stringify_rank($parameters->ranking->succeeding_user_rank);
                        if ($parameters->ranking->succeeding_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->succeeding_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }
                } else {
                    // Case when user is last.
                    $parameters->ranked = true;

                    // First user.
                    $rank = new stdClass();
                    $rank->rank = $this->stringify_rank(1);
                    if ($parameters->ranking->first_user_total_score != null) {
                        $rank->score = intval($parameters->ranking->first_user_total_score);
                    } else {
                        $rank->score = 0;
                    }
                    $parameters->ranks[] = $rank;

                    // Before user.
                    if ($parameters->ranking->preceding_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $this->stringify_rank($parameters->ranking->preceding_user_rank);
                        if ($parameters->ranking->preceding_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->preceding_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }

                    // User.
                    $rank = new stdClass();
                    if ($parameters->rank != null) {
                        $rank->rank = $this->stringify_rank($parameters->rank);
                    }
                    $rank->score = intval($parameters->score);
                    $rank->me = true;
                    $parameters->ranks[] = $rank;
                }
                $parameters->uniqueid = uniqid('section-summary-');
                $parameters->uniqueid2 = uniqid('section-groupview-');
                break;
            case 'timer':
                $parameters->averagetime = $section->gameelement->get_averagetime();
                if ($parameters->averagetime > 0) {
                    $minutes = str_pad(intval($parameters->averagetime / 60), 2, "0", STR_PAD_LEFT);
                    $secondes = str_pad(intval($parameters->averagetime % 60), 2, "0", STR_PAD_LEFT);
                    $parameters->averagetime = $minutes . ":" . $secondes;
                } else {
                    $parameters->averagetime = "--:--";
                }
                break;
            default:
                break;
        }

        if (!$sectioninfo->get_available()) {
            $parameters->restricted = true;
            $parameters->restrictedinfo =
                \core_availability\info::format_info($sectioninfo->availableinfo, $course->id);
        }
        return $parameters;
    }

    /**
     * Populate the course module parameters.
     *
     * @param stdClass $course        Course.
     * @param stdClass $parameters    Parameters to populate.
     * @param stdClass $cm            Course module.
     * @param stdClass $parentsection Section of the course module.
     * @param string $type            Type of the section.
     *
     * @return stdClass Populated parameters.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function populate_cm(
        stdClass $course,
        stdClass $parameters,
        stdClass $cm,
        stdClass $parentsection,
        string $type
    ): stdClass {
        global $PAGE, $DB;
        $cminfo = get_fast_modinfo($course)->get_cm($cm->id);

        // Populate the section parameters in function of type.
        switch ($type) {
            case 'score':
                if ($parameters->gamified) {
                    if ($parameters->maxscore == 0) {
                        $parameters->noscore = true;
                    }
                }
                break;
            case 'badge':
                if ($parameters->gamified) {
                    if (isset($parameters->progression)) {
                        $parameters->badge = $parentsection->gameelement->get_cm_badge($parameters->progression);
                    } else {
                        $parameters->badge = $parentsection->gameelement->get_cm_badge(0);
                    }
                }
                break;
            case 'progress':
                if ($parameters->gamified) {
                    $parameters->step = 0;
                    if ($parameters->progression > 0) {
                        $parameters->step = intval($parameters->progression / 10);
                    }

                    $parameters->sectionprogression = $parentsection->gameelement->get_progression();
                    $parameters->sectionstep = 0;
                    if ($parameters->sectionprogression > 0) {
                        $parameters->sectionstep = intval($parameters->sectionprogression / 10);
                    }
                    // Attribution of planete number.
                    // Modulo 9 because there are 9 diffenrent planets.
                    $parameters->planetenumber = ($parentsection->section) % 8 + 1;
                }
                break;
            case 'avatar':
                if ($parameters->gamified) {
                    if ($parameters->progression > 0) {
                        $parameters->completed = true;
                    }
                }
                $parameters->sectionparameters = $parentsection->parameters;
                break;
            case 'timer':
                if ($parameters->gamified) {
                    // Format best time.
                    if ($parameters->besttime > 0) {
                        $besttime = intval($parameters->besttime);
                        $minutes = str_pad(intval($besttime / 60), 2, "0", STR_PAD_LEFT);
                        $secondes = str_pad(intval($besttime % 60), 2, "0", STR_PAD_LEFT);
                        $parameters->besttime = $minutes . ":" . $secondes;

                        if ($parameters->bestpenalties > 0) {
                            $parameters->bestpenaltiescalc = $parameters->bestpenalties *
                                $parentsection->gameelement->get_penalties();
                        }
                    } else {
                        $parameters->besttime = "--:--";
                    }
                }
                break;
            case 'ranking':
                if ($parameters->gamified) {
                    if ($parameters->maxscore == 0) {
                        $parameters->noscore = true;
                    }
                    if ($parameters->maxscore == 0) {
                        $parameters->noscore = true;
                    }
                    if ($parameters->ranking->user_rank == 1) {
                        $parameters->crowned = true;
                        $parameters->ranked = false;
                    } else {
                        $parameters->ranked = true;
                    }

                    // Only if gradable.
                    $gradable = $parentsection->gameelement->is_gradable($cminfo->id);
                    if ($gradable && $parameters->gamified) {
                        $parameters->score = intval($parameters->score);
                        if (isset($parameters->ranking->preceding2_user_rank)) {
                            $before2 = $parameters->ranking->preceding2_user_rank;
                            if ($before2 != 0 && $before2 != null) {
                                $parameters->before_2 = $before2;
                                $parameters->before_2_th = $this->get_postfix($before2);
                            }
                        }
                        if (isset($parameters->ranking->preceding_user_rank)) {
                            $before1 = $parameters->ranking->preceding_user_rank;
                            if ($before1 != 0 && $before1 != null) {
                                $parameters->before_1 = $before1;
                                $parameters->before_1_th = $this->get_postfix($before1);
                            }
                        }
                        if (isset($parameters->ranking->user_rank)) {
                            $parameters->rank = $parameters->ranking->user_rank;
                            if ($parameters->ranking->user_rank != 0 && $parameters->ranking->user_rank != null) {
                                $parameters->postfix = $this->get_postfix($parameters->ranking->user_rank);
                            }
                        }
                        if (isset($parameters->ranking->succeeding_user_rank)) {
                            $after1 = $parameters->ranking->succeeding_user_rank;
                            if ($after1 != 0 && $after1 != null) {
                                $parameters->after_1 = $after1;
                                $parameters->after_1_th = $this->get_postfix($after1);
                            }
                        }
                        if (isset($parameters->ranking->succeeding2_user_rank)) {
                            $after2 = $parameters->ranking->succeeding2_user_rank;
                            if ($after2 != 0 && $after2 != null) {
                                $parameters->after_2 = $after2;
                                $parameters->after_2_th = $this->get_postfix($after2);
                            }
                        }
                    }
                    $parameters->uniqueid = uniqid('cm-summary-');
                    $parameters->uniqueid2 = uniqid('cm-groupview-');
                }
                break;
            default:
                break;
        }

        // Check if the course module is restricted.
        $parameters->restricted = false;
        if (!$cminfo->available) {
            $parameters->restricted = true;
            $parameters->restrictedinfo =
                \core_availability\info::format_info($cminfo->availableinfo, $course->id);
        }
        return $parameters;
    }

    /**
     * Get the element by section.
     *
     * @param int $courseid The course id.
     * @param int $sectionid The section id.
     * @param string $type The type of the game element.
     *
     * @return game_element The game element.
     * @throws \coding_exception
     */
    public function get_element_by_section(int $courseid, int $sectionid, string $type): game_element {
        global $DB, $USER;
        $gameelement = null;
        $format = course_get_format($courseid);
        $options = $format->get_format_options();
        $assignment = $options['assignment'];

        $isenrolled = false;
        if (is_enrolled(context_course::instance($courseid), $USER->id)) {
            $isenrolled = true;
        }
        $typeassigedmanually = $this->get_game_element_manually_assigned($courseid, $USER->id);
        // Verify if the user has been attributed the game element manually.
        if ($typeassigedmanually) {
            return game_element::get_element(
                $courseid,
                $sectionid,
                $USER->id,
                $typeassigedmanually
            );
        }
        if ($assignment != 'bysection' || !$isenrolled) {
            $gameelement = game_element::get_element(
                $courseid,
                $sectionid,
                $USER->id,
                $type
            );
            // If attribution is missing, create one.
            if ($gameelement == null && $isenrolled) {
                $gameelement = $DB->get_record(
                    'format_ludilearn_elements',
                    [
                        'courseid' => $courseid,
                        'sectionid' => $sectionid,
                        'type' => $type,
                    ]
                );
                $this->attribution_game_element($gameelement->id, $USER->id);
                $gameelement = game_element::get_element(
                    $courseid,
                    $sectionid,
                    $USER->id,
                    $type
                );
            }
        } else {
            // Get the attributions by section.
            $sql = "SELECT ge.id, ge.type FROM {format_ludilearn_bysection} bs
                        INNER JOIN {format_ludilearn_elements} ge ON bs.gameelementid = ge.id
                        WHERE bs.courseid = :courseid AND bs.sectionid = :sectionid";
            $bysection = $DB->get_record_sql(
                $sql,
                ['courseid' => $courseid, 'sectionid' => $sectionid]
            );
            if ($bysection) {
                $gameelement = game_element::get_element(
                    $courseid,
                    $sectionid,
                    $USER->id,
                    $bysection->type
                );
                // If attribution is missing, create one.
                if ($gameelement == null) {
                    $this->attribution_game_element($bysection->id, $USER->id);
                    $gameelement = game_element::get_element(
                        $courseid,
                        $sectionid,
                        $USER->id,
                        $bysection->type
                    );
                }
            } else {
                $gameelement = game_element::get_element(
                    $courseid,
                    $sectionid,
                    $USER->id,
                    $type
                );
                // If attribution is missing, create one.
                if ($gameelement == null) {
                    $gameelement = $DB->get_record(
                        'format_ludilearn_elements',
                        ['courseid' => $courseid, 'sectionid' => $sectionid, 'type' => $type]
                    );
                    $this->attribution_game_element($gameelement->id, $USER->id);
                    $gameelement = game_element::get_element(
                        $courseid,
                        $sectionid,
                        $USER->id,
                        $type
                    );
                }
            }
        }
        return $gameelement;
    }

    /**
     * Get the element type for a course.
     *
     * @param int $courseid Course ID.
     * @param int $userid   User ID.
     *
     * @return string|bool The element type or false.
     * @throws \dml_exception
     */
    public function get_element_type(int $courseid, int $userid): string|bool {
        global $CFG, $DB, $USER;
        $context = context_course::instance($courseid);
        $format = course_get_format($courseid);
        require_once($CFG->libdir . '/accesslib.php');
        // Verify if the game elements has been assigned manually to the user.
        $gameelementtype = $this->get_game_element_manually_assigned($courseid, $userid);
        if ($gameelementtype) {
            return $gameelementtype;
        }
        // Get the format options.
        $options = $format->get_format_options();

        // Get the default game element.
        $gameelementtype = $options['default_game_element'];
        $assignment = $options['assignment'];
        $notanswered = false;
        $isenrolled = false;
        if (is_enrolled($context, $userid)) {
            $isenrolled = true;
        }

        // Verify if the cours is assigned automatically and if the user has answered yet to the questionnaire.
        if ($assignment == 'automatic') {
            $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $userid]);
            if ($profile) {
                $gameelementtype = $profile->type;

                // Verify if the user has attributions.
                $this->check_attribution_course($courseid, $userid, $gameelementtype);
            } else {
                $notanswered = true;
            }
        }

        // If the user has capabilities to update the course or is role switched and has an attribution for the course,
        // And he is not enrolled or the course is assigned automatically.
        $canupdate = has_capability('moodle/course:update', $context);
        $roleswitchedandattr = is_role_switched($courseid) && $this->has_attribution($courseid, $userid);
        if (
            $USER->id == $userid
            && ($canupdate || $roleswitchedandattr)
            && ($assignment == 'automatic' || !$isenrolled)
        ) {
            // Verify is there is an attribution for this user.
            $attributionexist = $this->has_attribution($courseid, $userid);
            // If not attribution exist, create one with nogamified type.
            $gameelementtype = 'nogamified';
            if (!$attributionexist) {
                $this->check_attribution_course($courseid, $userid, $gameelementtype);
            }
            $notanswered = false;
        }

        // If the user is not enrolled and doesn't have game element assiged but his role is switched.
        if ($USER->id == $userid && !$isenrolled && is_role_switched($courseid) && !$this->has_attribution($courseid, $userid)) {
            $this->sync_user_attribution_by_user(
                $courseid,
                $options['assignment'],
                $options['default_game_element'],
                $userid
            );
            return $this->get_element_type($courseid, $userid);
        }

        if ($notanswered) {
            return false;
        }
        return $gameelementtype;
    }

    /**
     * Get if the game element is manually assigned for a user.
     *
     * @param int $courseid Course ID.
     * @param int $userid   User ID.
     *
     * @return string|bool The element type or false.
     * @throws \dml_exception
     */
    public function get_game_element_manually_assigned(int $courseid, int $userid): string|bool {
        global $DB;

        $gameelementtyperecord = $DB->get_record('format_ludilearn_manual', ['courseid' => $courseid, 'userid' => $userid]);
        if ($gameelementtyperecord) {
            return $gameelementtyperecord->type;
        }
        return false;
    }

    /**
     * Assign a game element type manually to a user.
     *
     * @param int $courseid Course ID.
     * @param int $userid   User ID.
     * @param string $type  Game element type.
     *
     * @return void
     * @throws \dml_exception
     */
    public function assign_game_element_manually(int $courseid, int $userid, string $type): void {
        global $DB;

        // Verify if an attribution already exist.
        $gameelementtyperecord = $DB->get_record('format_ludilearn_manual', ['courseid' => $courseid, 'userid' => $userid]);
        if ($gameelementtyperecord) {
            if ($gameelementtyperecord->type != $type) {
                // Update the type.
                $gameelementtyperecord->type = $type;
                $DB->update_record('format_ludilearn_manual', $gameelementtyperecord);
            } else {
                // No change.
                return;
            }
        } else {
            // Create a new record.
            $newrecord = new stdClass();
            $newrecord->courseid = $courseid;
            $newrecord->userid = $userid;
            $newrecord->type = $type;
            $DB->insert_record('format_ludilearn_manual', $newrecord);
        }

        // Get all game elements of the course.
        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid, 'type' => $type]);
        foreach ($gameelements as $gameelement) {
            $this->attribution_game_element($gameelement->id, $userid);
        }
    }

    /**
     * Remove the manual assignment of a game element type for a user.
     *
     * @param int $courseid Course ID.
     * @param int $userid   User ID.
     *
     * @return void
     * @throws \dml_exception
     */
    public function remove_game_element_manually_assignment(int $courseid, int $userid): void {
        global $DB;

        // Remove the manual assignment record.
        $DB->delete_records('format_ludilearn_manual', ['courseid' => $courseid, 'userid' => $userid]);

        $context = context_course::instance($courseid);
        $manager = new manager();
        $format = course_get_format($courseid);

        // Get the format options.
        $options = $format->get_format_options();

        // Get the default game element.
        $gameelementtype = $options['default_game_element'];
        $assignment = $options['assignment'];

        // Change the attributions to the default game element.
        $this->sync_user_attribution_by_user($courseid, $assignment, $gameelementtype, $userid);
    }

    /**
     * Get the state of the course for a user.
     *
     * @param int $courseid Course ID.
     *
     * @return string State of the course for the user.
     * @throws \dml_exception
     */
    public function get_course_state(int $courseid): string {
        global $DB, $USER;

        $states = [];
        $course = get_course($courseid);
        $format = course_get_format($courseid);

        // Get the game element type for the course.
        $gameelementtype = $this->get_element_type($courseid, $USER->id);

        if (!$gameelementtype) {
            return json_encode($states);
        }
        // Get all the sections with their game element.
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section');
        foreach ($sections as $key => $section) {
            // Don't get hidden sections.
            $sectioninfo = get_fast_modinfo($course)->get_section_info($section->section);
            $uservisible = $format->is_section_visible($sectioninfo);
            if (!$uservisible) {
                continue;
            }

            $gameelement = $this->get_element_by_section(
                $courseid,
                $section->id,
                $gameelementtype
            );

            // Prepare the state.
            $state = new stdClass();
            $state->action = 'put';
            $state->name = 'sections';
            $state->fields = new stdClass();
            $state->fields->courseid = $course->id;
            $state->fields->sectionid = $section->id;
            $options = $format->get_format_options();
            $state->fields->world = $options['world'];

            // Get the section parameters.
            foreach ($gameelement->get_parameters() as $keyparam => $valueparam) {
                $state->fields->$keyparam = $valueparam;
            }
            $section->gameelement = $gameelement;
            // Populate the section parameters with more specific data.
            $state->fields = $this->populate_section($course, $state->fields, $section, $gameelementtype);
            // Check if the gamelement is gamified.
            $state->fields->gamified = false;
            if ($section->gameelement->get_count_cm_gamified() > 0) {
                $state->fields->gamified = true;
            }
            $state->fields->id = $section->id;
            $states[] = $state;
        }

        return json_encode($states);
    }

    /**
     * Get the state of a course section for a user.
     *
     * @param stdClass $course Course.
     * @param stdClass $section Section.
     * @param game_element $gameelement Game element.
     *
     * @return string State of the course section for the user.
     * @throws \dml_exception
     */
    public function get_course_section_state(stdClass $course, stdClass $section, game_element $gameelement) {
        global $DB;
        $format = course_get_format($course->id);

        $states = [];
        // Prepare the state.
        $state = new stdClass();
        $state->action = 'put';
        $state->name = 'sections';
        $state->fields = new stdClass();
        $state->fields->courseid = $course->id;
        $state->fields->sectionid = $section->id;
        $options = $format->get_format_options();
        $state->fields->world = $options['world'];

        // Get the section parameters.
        foreach ($gameelement->get_parameters() as $keyparam => $valueparam) {
            $state->fields->$keyparam = $valueparam;
        }
        $section->gameelement = $gameelement;
        // Populate the section parameters with more specific data.
        $state->fields = $this->populate_section($course, $state->fields, $section, $gameelement->get_type());
        // Check if the gamelement is gamified.
        $state->fields->gamified = false;
        if ($section->gameelement->get_count_cm_gamified() > 0) {
            $state->fields->gamified = true;
        }
        $state->fields->id = $section->id;
        $states[] = $state;
        return json_encode($states);
    }

    /**
     * Get the state of a section for a user.
     *
     * @param stdClass $section Section.
     * @param game_element $gameelement Game element.
     *
     * @return string State of the section for the user.
     * @throws \dml_exception
     */
    public function get_section_state(stdClass $section, game_element $gameelement): string {
        global $DB, $USER;

        $states = [];
        $course = get_course($section->course);
        $contextcourse = context_course::instance($course->id);
        $format = course_get_format($course->id);

        // Prepare the state.
        $state = new stdClass();
        $state->action = 'put';
        $state->name = 'currentsection';
        $state->fields = new stdClass();
        $state->fields->courseid = $course->id;
        $state->fields->sectionid = $section->id;
        $options = $format->get_format_options();
        $state->fields->world = $options['world'];

        // Get the section parameters.
        foreach ($gameelement->get_parameters() as $keyparam => $valueparam) {
            $state->fields->$keyparam = $valueparam;
        }

        $section->gameelement = $gameelement;
        // Populate the section parameters with more specific data.
        $state->fields = $this->populate_section($course, $state->fields, $section, $gameelement->get_type());
        // Check if the gamelement is gamified.
        $state->fields->gamified = false;
        if ($section->gameelement->get_count_cm_gamified() > 0) {
            $state->fields->gamified = true;
        }
        $state->fields->id = $gameelement->get_id();
        $states[] = $state;

        // Get cms in the section.
        $sequence = explode(",", $section->sequence);
        foreach ($sequence as $cmidsequence) {
            if (!empty($cmidsequence)) {
                $cm = $DB->get_record('course_modules', ['id' => $cmidsequence]);
                if ($cm) {
                    $cminfo = get_fast_modinfo($course->id)->get_cm($cm->id);

                    // Don't show hidden course module.
                    if (!$cminfo->visible || !$cminfo->is_visible_on_course_page()) {
                        continue;
                    }

                    $state = new stdClass();
                    $state->action = 'put';
                    $state->name = 'cms';
                    $state->fields = new stdClass();
                    $state->fields->courseid = $course->id;
                    $state->fields->sectionid = $section->id;
                    $state->fields->id = $cminfo->id;
                    $state->fields->name = format_string($cminfo->name);
                    $state->fields->viewed = $this->cm_viewed_by_user($cminfo->id, $USER->id);

                    // Verify if the cm is a label.
                    if ($cminfo->modname == 'label') {
                        $state->fields->label = true;
                        $label = $DB->get_record('label', ['id' => $cminfo->instance]);
                        if ($label) {
                            $state->fields->labeltext = $cminfo->get_formatted_content();
                        }
                        $states[] = $state;
                        continue;
                    }

                    // Verify if the cm is a subsection.
                    if ($cminfo->modname == 'subsection') {
                        // Get section and the game element associated.
                        $subsection = $DB->get_record(
                            'course_sections',
                            ['itemid' => $cminfo->instance, 'component' => 'mod_subsection']
                        );
                        $subsection->gamelement = $this->get_element_by_section(
                            $course->id,
                            $subsection->id,
                            $gameelement->get_type()
                        );

                        // Don't show if the section is not visible.
                        $sectioninfo = get_fast_modinfo($course)->get_section_info($subsection->section);
                        $format = course_get_format($course->id);
                        $uservisible = $format->is_section_visible($sectioninfo);
                        if (!$uservisible) {
                            continue;
                        }

                        $state->fields->subsection = true;
                        $state->fields->courseid = $course->id;
                        $state->fields->sectionid = $section->id;
                        $state->fields->section = $subsection->section;
                        $state->fields->name = format_string(get_section_name($course, $subsection));
                        if (isset($subsection->gameelement)) {
                            $type = $subsection->gameelement->get_type();
                            $state->fields->$type = true;

                            // Get the section parameters.
                            foreach ($subsection->gameelement->get_parameters() as $key => $value) {
                                $state->fields->$key = $value;
                            }

                            // Populate the section parameters.
                            $state->fields = $this->populate_section($course, $state->fields, $subsection, $type);
                            $state->fields->gamified = false;
                            if ($subsection->gameelement->get_count_cm_gamified() > 0) {
                                $state->fields->gamified = true;
                            }

                            if ($subsection->visible) {
                                $state->fields->visible = true;
                            }
                        }
                        if (has_capability('moodle/course:update', $contextcourse) || $sectioninfo->get_available()) {
                            $urlsection = new moodle_url('/course/section.php?id=' . $subsection->id);
                            $state->fields->url = $urlsection->out(false);
                        }
                        $states[] = $state;
                        continue;
                    }

                    $cmparameters = $gameelement->get_cm_parameters();
                    foreach ($cmparameters[$cminfo->id] as $key => $value) {
                        $state->fields->$key = $value;
                    }
                    $options = $format->get_format_options();
                    $state->fields->world = $options['world'];

                    $section->gameelement = $gameelement;
                    // Populate the course module parameters.
                    $state->fields = $this->populate_cm($course, $state->fields, $cm, $section, $gameelement->get_type());

                    $contextactivity = context_module::instance($cminfo->id);
                    if (
                        has_capability('moodle/course:viewhiddenactivities', $contextactivity)
                        || has_capability('moodle/course:update', $contextcourse)
                        || $cminfo->available
                    ) {
                        if ($cminfo->get_url()) {
                            $state->fields->url = $cminfo->get_url()->out(false);
                        }
                    }
                    $states[] = $state;
                }
            }
        }
        return json_encode($states);
    }


    /**
     * Get the state of a course module for a user.
     *
     * @param stdClass $cm Course module.
     * @param stdClass $section Section of the course module.
     * @param game_element $gameelement Game element.
     *
     * @return string State of the course module for the user.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_cm_state(stdClass $cm, stdClass $section, game_element $gameelement): string {
        global $DB;

        $course = get_course($cm->course);
        $format = course_get_format($course->id);
        $states = [];

        // Prepare the state.
        $state = new stdClass();
        $state->action = 'put';
        $state->name = 'currentcm';
        $state->fields = new stdClass();
        $state->fields->courseid = $course->id;
        $state->fields->sectionid = $section->id;
        $options = $format->get_format_options();
        $state->fields->world = $options['world'];
        $parameters = $gameelement->get_cm_parameters();
        // Get the cm parameters.
        foreach ($gameelement->get_cm_parameters()[$cm->id] as $keyparam => $valueparam) {
            if ($keyparam == 'id' && $valueparam != $cm->id) {
                continue;
            }
            $state->fields->$keyparam = $valueparam;
        }
        $section->gameelement = $gameelement;
        // Populate the cm parameters with more specific data.
        $state->fields = $this->populate_cm($course, $state->fields, $cm, $section, $gameelement->get_type());
        $state->fields->id = $cm->id;
        $states[] = $state;

        return json_encode($states);
    }

    /**
     * Get the state of a resource for a user.
     *
     * @param string $resource Resource type (course, section, cm).
     * @param int $id Resource ID.
     *
     * @return string State of the resource for the user.
     * @throws \dml_exception
     */
    public function get_state(string $resource, int $id): string {
        global $DB, $USER;

        $states = [];
        switch ($resource) {
            case 'course':
                return $this->get_course_state($id);
            case 'section':
                $section = $DB->get_record('course_sections', ['id' => $id]);
                $course = get_course($section->course);
                $format = course_get_format($course->id);

                // Don't get hidden sections.
                $sectioninfo = get_fast_modinfo($course)->get_section_info($section->section);
                $uservisible = $format->is_section_visible($sectioninfo);

                // If the section is not visible for the user, return empty states.
                if (!$uservisible) {
                    return json_encode($states);
                }
                // Get the global game element type for the course.
                $gameelementtype = $this->get_element_type($course->id, $USER->id);

                // If the there is no game element type, it means the user has not game element assigned.
                if (!$gameelementtype) {
                    return json_encode($states);
                }

                $gameelement = $this->get_element_by_section(
                    $course->id,
                    $section->id,
                    $gameelementtype
                );
                return $this->get_section_state($section, $gameelement);
            case 'cm':
                $cm = $DB->get_record('course_modules', ['id' => $id]);
                $course = get_course($cm->course);
                $format = course_get_format($course->id);

                $section = $DB->get_record('course_sections', ['id' => $cm->section]);

                // Don't show hidden course module.
                $cminfo = get_fast_modinfo($course->id)->get_cm($cm->id);
                if (!$cminfo->visible || !$cminfo->is_visible_on_course_page()) {
                    return json_encode($states);
                }

                // Get the global game element type for the course.
                $gameelementtype = $this->get_element_type($course->id, $USER->id);

                // If the there is no game element type, it means the user has not game element assigned.
                if (!$gameelementtype) {
                    return json_encode($states);
                }

                $gameelement = $this->get_element_by_section(
                    $course->id,
                    $section->id,
                    $gameelementtype
                );
                return $this->get_cm_state($cm, $section, $gameelement);
        }
        return json_encode($states);
    }
}
