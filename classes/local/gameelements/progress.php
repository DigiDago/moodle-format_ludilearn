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

namespace format_ludilearn\local\gameelements;

use format_ludilearn\manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Progress game element class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress extends game_element {

    /**
     * @var int
     */
    protected int $progression;

    /**
     * Constructor.
     *
     * @param int $id             Id of the game element.
     * @param int $courseid       Id of the course.
     * @param int $sectionid      Id of the section.
     * @param int $userid         Id of the user.
     * @param array $paramaters   Array of parameters.
     * @param array $cmparameters Array of cm parameters.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(int $id, int $courseid, int $sectionid, int $userid, array $paramaters, array $cmparameters) {
        parent::__construct($id, $courseid, $sectionid, $userid, $paramaters, $cmparameters);
        $this->type = 'progress';

        $this->progression = 0;
        foreach ($cmparameters as $key => $value) {
            if (!isset($value['gamified']) || $value['gamified']) {
                $cmparameters[$key]['gamified'] = true;

                // If the module is not gradable and completien is disabled, gamification is disabled.
                if ((!$this->is_gradable($key) && !$this->is_completion_enabled($key))
                    || !$this->is_activity_available_for_user($key, $this->userid)) {
                    $cmparameters[$key]['gamified'] = false;
                    continue;
                }

                // Get the completion status.
                if ($this->is_completed($key)) {
                    $cmparameters[$key]['completion'] = true;
                } else {
                    $cmparameters[$key]['completion'] = false;
                }

                // Get the progression.
                if (isset($value['progression'])) {
                    $this->progression += $value['progression'];
                } else {
                    // If the module is not gradable but the completion is true, the progression is 100.
                    $cmparameters[$key]['progression'] = 0;
                    if (!$this->is_gradable($key) && $cmparameters[$key]['completion']) {
                        $cmparameters[$key]['progression'] = 100;
                        $this->progression += 100;
                    }
                }
            } else {
                $cmparameters[$key]['gamified'] = false;
            }
        }
        $this->cmparameters = $cmparameters;
        if ($this->get_count_cm_gamified() > 0) {
            $this->progression = intval($this->progression / $this->get_count_cm_gamified());
        }

        $this->sectionparameters['progression'] = $this->progression;
    }

    /**
     * Get the score.
     *
     * @return int
     */
    public function get_progression(): int {
        return $this->progression;
    }

    /**
     * Update progress elements.
     *
     * @param int $courseid          The course id.
     * @param stdClass $coursemodule The course module.
     * @param string $modulename     The module name.
     * @param int $userid            The user id.
     *
     * @throws \dml_exception
     */
    public static function update_elements(int $courseid, stdClass $coursemodule, string $modulename, int $userid): void {
        global $DB;

        // Get game element.
        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'progress']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {

            // Get grade.
            $grades = grade_get_grades($courseid, 'mod', $modulename, $coursemodule->instance, $userid);

            // Calculate the score.
            $progression = 0;
            if (count($grades->items) > 0) {
                $grade = $grades->items[0]->grades[$userid]->grade;
                if ($grade == null) {
                    $grade = 0;
                }
                $grademax = $grades->items[0]->grademax;
                // Calculate the score.
                if ($grademax != null && $grademax != 0) {
                    // Calculate the score.
                    $progression = intval($grade * 100 / $grademax);
                }
            }

            // Update the score or create it if it does not exist.
            $cmuser = $DB->get_record('format_ludilearn_cm_user',
                ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'progression']);
            if ($cmuser) {
                // If the score is different from the previous one.
                if ($progression != $cmuser->value) {
                    // Update the score.
                    $param = new stdClass();
                    $param->id = $cmuser->id;
                    $param->value = $progression;
                    $DB->update_record('format_ludilearn_cm_user', $param);
                }
            } else {
                $DB->insert_record('format_ludilearn_cm_user', [
                    'attributionid' => $attribution->id,
                    'name' => 'progression',
                    'cmid' => $coursemodule->id,
                    'value' => $progression]);
            }
        }
    }

    /**
     * Update game elements when quiz has immediate feedback.
     *
     * @param int $quizid The quiz id.
     * @param int $userid The user id.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function update_quiz_immediate_feedback(int $quizid, int $userid): void {
        global $DB;

        $manager = new manager();
        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        $module = $DB->get_record('modules', ['name' => 'quiz']);
        $coursemodule = $DB->get_record('course_modules',
            [
                'course' => $quiz->course,
                'module' => $module->id,
                'instance' => $quiz->id,
            ]
        );

        // Get badge game element.
        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section,
                'type' => 'progress']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {
            // Calculate the progression.
            $progression = 0;
            $grademax = $quiz->grade;

            // Calculate the progression.
            // Get grade.
            $grade = $manager->calculate_quiz_grade($quiz, $userid);
            if ($grademax > 0) {
                $progression = intval($grade * 100 / $grademax);
            }
            // Update the score or create it if it does not exist.
            $cmuser = $DB->get_record('format_ludilearn_cm_user',
                ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'progression']);
            if ($cmuser) {
                // If the progression is different from the previous one.
                if ($progression != $cmuser->value) {
                    // Update the score.
                    $param = new stdClass();
                    $param->id = $cmuser->id;
                    $param->value = $progression;
                    $DB->update_record('format_ludilearn_cm_user', $param);
                }
            } else {
                $DB->insert_record('format_ludilearn_cm_user', [
                    'attributionid' => $attribution->id,
                    'name' => 'progression',
                    'cmid' => $coursemodule->id,
                    'value' => $progression]);
            }
        }
    }

    /**
     * Get a game element.
     *
     * @param int $courseid  The course ID.
     * @param int $sectionid The section ID.
     * @param int $userid    The user ID.
     *
     * @return progress|null The game element.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get(int $courseid, int $sectionid, int $userid): ?progress {
        global $DB;

        $gameelementsql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND g.sectionid = :sectionid
                            AND a.userid = :userid AND g.type = :type';

        $gameelementreq = $DB->get_record_sql($gameelementsql,
            ['courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'type' => 'progress']);

        if (!$gameelementreq) {
            return null;
        }

        // Get all cm of the section.
        $cms = $DB->get_records('course_modules', ['section' => $sectionid]);

        $params = ['gameelementid' => $gameelementreq->gameelementid, 'userid' => $userid];

        // Get game element parameters.
        $parameters = [];
        $sqlparameters = 'SELECT * FROM {format_ludilearn_params} section_params WHERE gameelementid = :gameelementid';
        $parametersreq = $DB->get_records_sql($sqlparameters, $params);
        foreach ($parametersreq as $parameterreq) {
            $parameters[$parameterreq->name] = $parameterreq->value;
        }

        $sqlgameeleuser = 'SELECT s.id, s.name, s.value
                    FROM {format_ludilearn_ele_user} s
                    INNER JOIN {format_ludilearn_attributio} a ON s.attributionid = a.id
                    WHERE a.gameelementid = :gameelementid
                    AND a.userid = :userid';
        $gameleuserreq = $DB->get_records_sql($sqlgameeleuser, $params);
        foreach ($gameleuserreq as $gameleuser) {
            $parameters[$gameleuser->name] = $gameleuser->value;
        }

        // Get cm parameters.
        $cmparameters = [];
        foreach ($cms as $cm) {
            $cmparameters[$cm->id] = [];
            $cmparameters[$cm->id]['id'] = $cm->id;
        }
        $sqlcmparameters = 'SELECT * FROM {format_ludilearn_cm_params} cm_params WHERE gameelementid = :gameelementid';
        $cmparametersreq = $DB->get_records_sql($sqlcmparameters, $params);
        foreach ($cmparametersreq as $cmparameterreq) {
            if (key_exists($cmparameterreq->cmid, $cmparameters)) {
                $cmparameters[$cmparameterreq->cmid][$cmparameterreq->name] = $cmparameterreq->value;
            }
        }

        $sqlcms = 'SELECT cm.id, cm.cmid, cm.name, cm.value
                    FROM {format_ludilearn_cm_user} cm
                    INNER JOIN {format_ludilearn_attributio} a ON cm.attributionid = a.id
                    WHERE a.gameelementid = :gameelementid
                    AND a.userid = :userid';
        $cmsreq = $DB->get_records_sql($sqlcms, $params);
        foreach ($cmsreq as $cmreq) {
            if (key_exists($cmreq->cmid, $cmparameters)) {
                $cmparameters[$cmreq->cmid][$cmreq->name] = $cmreq->value;
            }
        }

        return new progress($gameelementreq->gameelementid,
            $gameelementreq->courseid,
            $gameelementreq->sectionid,
            $gameelementreq->userid,
            $parameters,
            $cmparameters);
    }
}
