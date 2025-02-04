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
require_once($CFG->libdir.'/completionlib.php');

/**
 * Score game element class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class score extends game_element {

    /**
     * @var int
     */
    protected int $score;

    /**
     * @var int
     */
    protected int $maxscore;

    /**
     * @var int
     */
    protected int $multiplier;

    /**
     * @var stdClass $course The course.
     */
    protected stdClass $course;

    /**
     * @var int
     */
    protected int $bonuscompletion;

    /**
     * @var int $percentagecompletion The percentage completion value.
     */
    protected int $percentagecompletion;

    /**
     * @var int $totalbonuscompletion The total bonus completion value.
     */
    protected int $totalbonuscompletion;

    /**
     * Default multiplier value.
     *
     * @var int
     */
    const DEFAULT_MULTIPLIER = 80;

    /**
     * Default bonus completion value.
     *
     * @var int
     */
    const DEFAULT_BONUSCOMPLETION = 150;

    /**
     * Default completion percentage value.
     *
     * @var int
     */
    const DEFAULT_PERCENTAGECOMPLETION = 20;

    /**
     * Constructor.
     *
     * @param int $id             Id of the game element.
     * @param int $courseid       Id of the course.
     * @param int $sectionid      Id of the section.
     * @param int $userid         Id of the user.
     * @param array $parameters   Array of parameters.
     * @param array $cmparameters Array of cm parameters.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(int $id, int $courseid, int $sectionid, int $userid, array $parameters, array $cmparameters) {
        parent::__construct($id, $courseid, $sectionid, $userid, $parameters, $cmparameters);
        $this->type = 'score';

        // Set multiplier parameter.
        $this->sectionparameters['multiplier'] = $this->multiplier = self::DEFAULT_MULTIPLIER;
        if (isset($parameters['multiplier'])) {
            $this->sectionparameters['multiplier'] = $this->multiplier = $parameters['multiplier'];
        }

        // Set bonus completion parameter.
        $this->sectionparameters['bonuscompletion'] = $this->bonuscompletion = self::DEFAULT_BONUSCOMPLETION;
        if (isset($parameters['bonuscompletion'])) {
            $this->sectionparameters['bonuscompletion'] = $this->bonuscompletion = $parameters['bonuscompletion'];
        }

        // Set percentage completion parameter.
        $this->sectionparameters['percentagecompletion'] = $this->percentagecompletion = self::DEFAULT_PERCENTAGECOMPLETION;
        if (isset($parameters['percentagecompletion'])) {
            $this->sectionparameters['percentagecompletion'] = $this->percentagecompletion = $parameters['percentagecompletion'];
        }

        $this->score = 0;
        $this->maxscore = 0;
        $this->totalbonuscompletion = 0;
        foreach ($cmparameters as $key => $value) {
            if (!isset($value['gamified']) || $value['gamified']) {
                $cmparameters[$key]['gamified'] = true;
                $completion = $this->is_completion_enabled($key);
                $completed = $this->is_completed($key);
                $gradable = $this->is_gradable($key);

                // If the module is not gradable and completien is disabled, gamification is disabled.
                if ((!$gradable && !$completion)
                    || !$this->is_activity_available_for_user($key, $this->userid)) {
                    $cmparameters[$key]['gamified'] = false;
                    continue;
                }

                // If the module is not gradable and completion is enabled.
                // The score is the bonus completion if the module is completed.
                if ($completion && !$gradable) {
                    $cmparameters[$key]['maxscore'] = $this->bonuscompletion;
                    $this->maxscore += $cmparameters[$key]['maxscore'];
                    if ($completed) {
                        $cmparameters[$key]['score'] = $this->bonuscompletion;
                        $this->score += $cmparameters[$key]['score'];
                    } else {
                        $cmparameters[$key]['score'] = 0;
                    }
                }

                // If the module is gradable.
                if ($gradable) {
                    $cmparameters[$key]['maxscore'] = intval($this->get_grademax($key) * $this->multiplier);
                    $this->maxscore += $cmparameters[$key]['maxscore'];

                    // If the module is completed, the bonus completion is added to the score.
                    if ($completed && $completion) {
                        // Calculate bonus completion.
                        $numerator = $cmparameters[$key]['maxscore'] * $this->percentagecompletion;
                        if ($numerator > 0) {
                            $cmparameters[$key]['bonuscompletion'] = intval($numerator / 100);
                        } else {
                            $cmparameters[$key]['bonuscompletion'] = 0;
                        }
                    } else {
                        $cmparameters[$key]['bonuscompletion'] = 0;
                    }
                    $this->totalbonuscompletion += $cmparameters[$key]['bonuscompletion'];

                    if (isset($value['score'])) {
                        // Transform score to score * multplier + bonus completion.
                        $cmparameters[$key]['score'] =
                            intval($value['score'] * $this->multiplier) + $cmparameters[$key]['bonuscompletion'];
                    } else {
                        // Only bonus completion.
                        $cmparameters[$key]['score'] = $cmparameters[$key]['bonuscompletion'];
                    }
                    $this->score += $cmparameters[$key]['score'];
                }
            } else {
                $cmparameters[$key]['gamified'] = false;
            }
        }
        $this->cmparameters = $cmparameters;
        $this->sectionparameters['score'] = $this->score;
        $this->sectionparameters['maxscore'] = $this->maxscore;
        $this->sectionparameters['totalbonuscompletion'] = $this->totalbonuscompletion;

    }

    /**
     * Get the score.
     *
     * @return int
     */
    public function get_score(): int {
        return $this->score;
    }

    /**
     * Get the max score.
     *
     * @return int
     */
    public function get_max_score(): int {
        return $this->maxscore;
    }

    /**
     * Get multiplier.
     *
     * @return int Multiplier
     */
    public function get_multiplier(): int {
        return $this->multiplier;
    }

    /**
     * Get bonus completion.
     *
     * @return int Bonus completion
     */
    public function get_bonus_completion(): int {
        return $this->bonuscompletion;
    }

    /**
     * Get the total bonus completion.
     *
     * @return int
     */
    public function get_total_bonus_completion(): int {
        return $this->totalbonuscompletion;
    }

    /**
     * Get the score percentage.
     *
     * @return float
     */
    public function get_score_percentage(): float {
        return $this->maxscore > 0 ? $this->score / $this->maxscore : 0;
    }

    /**
     * Get the default parameters for a CM.
     *
     * @param string $moduletype The module type.
     * @param int $cmid          The CM ID.
     *
     * @return array The default parameters for the CM.
     * @throws \dml_exception
     */
    public static function get_cm_parameters_default(string $moduletype, int $cmid): array {
        global $DB;

        // Retrieve grade max for set max score parameter.
        $parameters['maxscore'] = 0;
        $coursemodule = $DB->get_record('course_modules', ['id' => $cmid]);
        if ($coursemodule) {
            $gradeitem = $DB->get_record('grade_items',
                [
                    'courseid' => $coursemodule->course,
                    'itemmodule' => $moduletype,
                    'iteminstance' => $coursemodule->instance,
                    'itemnumber' => 0,
                ]
            );
            if ($gradeitem) {
                $parameters['maxscore'] = $gradeitem->grademax;
            }
        }
        return array_merge($parameters, parent::get_cm_parameters_default($moduletype, $cmid));
    }

    /**
     * Get the type of a parameter.
     *
     * @param string $name Name of the parameter.
     * @return string Type of the parameter.
     */
    public static function get_cm_parameter_type(string $name): string {
        switch ($name) {
            case 'maxscore':
                // Get the default parameters of the game element.
                $result = 'number';
                break;
            default:
                $result = parent::get_cm_parameter_type($name);
        }
        return $result;
    }

    /**
     * Get a list of parameters.
     *
     * @return array List of parameters.
     */
    public static function get_parameters_list(): array {
        return ['multiplier', 'bonuscompletion', 'percentagecompletion'];
    }

    /**
     * Update score elements.
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
            ['sectionid' => $coursemodule->section, 'type' => 'score']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {

            // Get grade.
            $grades = grade_get_grades($courseid, 'mod', $modulename, $coursemodule->instance, $userid);

            // Calculate the score.
            $score = 0;
            $maxscore = 0;
            if (count($grades->items) > 0) {
                $score = $grades->items[0]->grades[$userid]->grade;
                if (($score) == null) {
                    $score = 0;
                }
                $maxscore = $grades->items[0]->grademax;
                if (($maxscore) == null) {
                    $maxscore = 0;
                }
            }

            // Update the score or create it if it does not exist.
            $userscore = $DB->get_record('format_ludilearn_cm_user',
                ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'score']);

            if ($userscore) {
                // If the score is different from the previous one.
                if ($score != $userscore->value) {
                    // Update the score.
                    $param = new stdClass();
                    $param->id = $userscore->id;
                    $param->value = $score;
                    $DB->update_record('format_ludilearn_cm_user', $param);
                }
            } else {
                $DB->insert_record('format_ludilearn_cm_user', [
                    'attributionid' => $attribution->id,
                    'name' => 'score',
                    'cmid' => $coursemodule->id,
                    'value' => $score]);
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

        // Get score game element.
        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section,
                'type' => 'score']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {

            // Calculate the score.
            $maxscore = $quiz->grade;
            // Calculate the score.
            // Get grade.
            $score = $manager->calculate_quiz_grade($quiz, $userid);
            // Update the score or create it if it does not exist.
            $userscore = $DB->get_record('format_ludilearn_cm_user',
                ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'score']);
            if ($userscore) {
                // If the score is different from the previous one.
                if ($score != $userscore->value) {
                    // Update the score.
                    $param = new stdClass();
                    $param->id = $userscore->id;
                    $param->value = $score;
                    $DB->update_record('format_ludilearn_cm_user', $param);
                }
            } else {
                $DB->insert_record('format_ludilearn_cm_user', [
                    'attributionid' => $attribution->id,
                    'name' => 'score',
                    'cmid' => $coursemodule->id,
                    'value' => $score]);
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
     * @return score|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get(int $courseid, int $sectionid, int $userid): ?score {
        global $DB;

        $gameelementsql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND g.sectionid = :sectionid
                            AND a.userid = :userid AND g.type = :type';

        $gameelementreq = $DB->get_record_sql($gameelementsql,
            ['courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'type' => 'score']);

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

        return new score($gameelementreq->gameelementid,
            $gameelementreq->courseid,
            $gameelementreq->sectionid,
            $gameelementreq->userid,
            $parameters,
            $cmparameters);
    }

    /**
     * Update course parameters.
     *
     * @param int $courseid        The course ID.
     * @param int $multiplier      The multiplier value.
     * @param int $bonuscompletion The bonus completion value.
     *
     * @return bool Returns true if the course parameters were successfully updated, false otherwise.
     * @throws \dml_exception
     */
    public static function update_course_parameters(int $courseid, int $multiplier, int $bonuscompletion,
        int $percentagecompletion): bool {
        global $DB;

        // Retrieve all game elements of the course.
        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid, 'type' => 'score']);

        if (!$gameelements) {
            return false;
        } else {
            foreach ($gameelements as $gameelement) {
                // Retrieve existing values for multiplier parameter.
                $multiplierrecord = $DB->get_record('format_ludilearn_params',
                    ['gameelementid' => $gameelement->id,
                    'name' => 'multiplier']);
                // If existing update values, else add value.
                if ($multiplierrecord) {
                    $multiplierrecord->value = $multiplier;
                    $DB->update_record('format_ludilearn_params', $multiplierrecord);
                } else {
                    $multiplierrecord = new stdClass();
                    $multiplierrecord->gameelementid = $gameelement->id;
                    $multiplierrecord->name = 'multiplier';
                    $multiplierrecord->value = $multiplier;
                    $DB->insert_record('format_ludilearn_params', $multiplierrecord);
                }

                // Retrieve existing values for bonus completion parameter.
                $bonuscompletionrecord = $DB->get_record('format_ludilearn_params',
                    ['gameelementid' => $gameelement->id,
                        'name' => 'bonuscompletion']);
                // If existing update values, else add value.
                if ($bonuscompletionrecord) {
                    $bonuscompletionrecord->value = $bonuscompletion;
                    $DB->update_record('format_ludilearn_params', $bonuscompletionrecord);
                } else {
                    $bonuscompletionrecord = new stdClass();
                    $bonuscompletionrecord->gameelementid = $gameelement->id;
                    $bonuscompletionrecord->name = 'bonuscompletion';
                    $bonuscompletionrecord->value = $bonuscompletion;
                    $DB->insert_record('format_ludilearn_params', $bonuscompletionrecord);
                }

                // Retrieve existing values for percentage completion parameter.
                $percentagecompletionrecord = $DB->get_record('format_ludilearn_params',
                    ['gameelementid' => $gameelement->id,
                        'name' => 'percentagecompletion']);
                // If existing update values, else add value.
                if ($percentagecompletionrecord) {
                    $percentagecompletionrecord->value = $percentagecompletion;
                    $DB->update_record('format_ludilearn_params', $percentagecompletionrecord);
                } else {
                    $percentagecompletionrecord = new stdClass();
                    $percentagecompletionrecord->gameelementid = $gameelement->id;
                    $percentagecompletionrecord->name = 'percentagecompletion';
                    $percentagecompletionrecord->value = $percentagecompletion;
                    $DB->insert_record('format_ludilearn_params', $percentagecompletionrecord);
                }
            }
        }
        return true;
    }
}
