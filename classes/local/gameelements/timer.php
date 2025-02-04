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
use format_theunittest\output\courseformat\state\course;
use stdClass;
use tool_admin_presets\form\continue_form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Timer game element class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timer extends game_element {

    /**
     * @var int $averagetime Time average.
     */
    protected int $averagetime;

    /**
     * @var int $penalties Penalties by point lost.
     */
    protected int $penalties;

    /**
     * @var int DEFAULT_PENALTIES Penalties by point lost.
     */
    const DEFAULT_PENALTIES = 20;

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
        $this->type = 'timer';

        $this->averagetime = 0;

        // Set penalties parameter.
        $this->sectionparameters['penalties'] = $this->penalties = self::DEFAULT_PENALTIES;
        if (isset($parameters['penalties'])) {
            $this->sectionparameters['penalties'] = $this->penalties = $parameters['penalties'];
        }

        $count = 0;
        $totaltime = 0;
        foreach ($cmparameters as $key => $value) {
            if (!isset($value['gamified']) || $value['gamified']) {
                $cmparameters[$key]['gamified'] = true;

                // Do not gamify if the course module is not a quiz.
                if (!$this->is_quiz($key)) {
                    $cmparameters[$key]['gamified'] = false;
                    continue;
                }

                // Do not gamify if the course module is not gradable.
                if (!$this->is_gradable($key) || !$this->is_activity_available_for_user($key, $this->userid)) {
                    $cmparameters[$key]['gamified'] = false;
                    continue;
                }

                // Obtain the best results.
                if (isset($value['beststart'])) {
                    $cmparameters[$key]['beststart'] = $value['beststart'];
                } else {
                    $cmparameters[$key]['beststart'] = 0;
                }

                if (isset($value['bestfinish'])) {
                    $cmparameters[$key]['bestfinish'] = $value['bestfinish'];
                } else {
                    $cmparameters[$key]['bestfinish'] = 0;
                }

                if (isset($value['bestpenalties'])) {
                    $cmparameters[$key]['bestpenalties'] = $value['bestpenalties'];
                } else {
                    $cmparameters[$key]['bestpenalties'] = 0;
                }

                if ($cmparameters[$key]['bestfinish'] > 0) {
                    // Calculate the best time.
                    $cmparameters[$key]['besttime'] = $cmparameters[$key]['bestfinish'] - $cmparameters[$key]['beststart'];
                    // Add penalties of time when point lost.
                    $cmparameters[$key]['besttime'] += $cmparameters[$key]['bestpenalties'] * $this->get_penalties();
                    // Add the besttime to the total time of the section.
                    $totaltime += $cmparameters[$key]['besttime'];
                    $count++;
                } else {
                    $cmparameters[$key]['besttime'] = 0;
                }

                // Obtain the current result.
                if (isset($value['currentstart'])) {
                    $cmparameters[$key]['currentstart'] = $value['currentstart'];
                }

                if (isset($value['currentpenalties'])) {
                    $cmparameters[$key]['currentpenalties'] = $value['currentpenalties'];
                } else {
                    $cmparameters[$key]['currentpenalties'] = 0;
                }
            } else {
                $cmparameters[$key]['gamified'] = false;
            }
        }
        // Calculate the average of best times.
        if ($count > 0) {
            $this->averagetime = intval($totaltime / $count);
        }

        $this->sectionparameters['averagetime'] = $this->averagetime;
        $this->cmparameters = $cmparameters;
    }

    /**
     * Get the average time for the quiz.
     *
     * @return int The average time for the quiz
     */
    public function get_averagetime(): int {
        return $this->averagetime;
    }

    /**
     * Get the penalties by point lost.
     *
     * @return int Penalties by point lost
     */
    public function get_penalties(): int {
        return $this->penalties;
    }

    /**
     * Get the besttime of the cm (having to be a quiz).
     *
     * @param int $cmid The cm id.
     * @return int The best time of the cm (having to be a quiz).
     */
    public function get_besttime(int $cmid): int {
        return $this->cmparameters[$cmid]['besttime'];
    }

    /**
     * Update game elements when quiz has immediate feedback.
     *
     * @param int $attemptid The attempt id.
     * @param int $quizid    The quiz id.
     * @param int $userid    The user id.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function update_quiz_immediate_feedback(int $attemptid, int $quizid, int $userid): void {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        $module = $DB->get_record('modules', ['name' => 'quiz']);
        $coursemodule = $DB->get_record('course_modules',
            [
                'course' => $quiz->course,
                'module' => $module->id,
                'instance' => $quiz->id,
            ]
        );

        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'timer']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {
            $gameelement = self::get($quiz->course, $coursemodule->section, $userid);

            // Get quiz questions attempts.
            $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quizid, 'userid' => $userid]);
            if (!$attempts) {
                return;
            }
            $bestattempt = false;
            $bestpenalties = 0;
            $besttime = 0;

            // Search best attempts and the current attempt.
            foreach ($attempts as $attempt) {
                $penalties = 0;
                $pointsnotyet = 0;
                $questionattempts = $DB->get_records('question_attempts', ['questionusageid' => $attempt->uniqueid], 'id');
                foreach ($questionattempts as $questionattempt) {
                    // Add the max grade of the question to the penalties.
                    $penalties += $questionattempt->maxmark;

                    // Retrieve question attempt steps.
                    $questionattemptsteps = $DB->get_records('question_attempt_steps',
                        ['questionattemptid' => $questionattempt->id]);
                    $answered = false;
                    foreach ($questionattemptsteps as $questionattemptstep) {
                        // If the attempt step is done, deduce the grade obtained from penalties.
                        if ($questionattemptstep->state != 'todo') {
                            $answered = true;
                            if ($questionattemptstep->fraction) {
                                $penalties -= $questionattemptstep->fraction * $questionattempt->maxmark;
                            }
                        }
                    }
                    // Point of questions not answered yet.
                    // Usefull for get the penalties by points lost of the current attempts.
                    if (!$answered) {
                        $pointsnotyet += $questionattempt->maxmark;
                    }
                }

                // Calculate the best time with penalties if the attempt is finished.
                // Else if the current attempt is not finished we save it.
                if ($attempt->state == 'finished') {
                    $time = $attempt->timefinish - $attempt->timestart;
                    $time += $penalties * $gameelement->get_penalties();
                    if ($besttime == 0 || $time < $besttime) {
                        $bestattempt = $attempt;
                        $besttime = $time;
                        $bestpenalties = $penalties;
                    }
                } else if ($attempt->id == $attemptid) {
                    $currentattempt = $attempt;
                    $currentpenalties = $penalties - $pointsnotyet;

                    // Save current attempt start.
                    $currentstart = $DB->get_record('format_ludilearn_cm_user',
                        ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'currentstart']);
                    if ($currentstart) {
                        if ($currentstart->value != $attempt->timestart) {
                            $currentstart->value = $attempt->timestart;
                            $DB->update_record('format_ludilearn_cm_user', $currentstart);
                        }
                    } else {
                        $DB->insert_record('format_ludilearn_cm_user', [
                            'attributionid' => $attribution->id,
                            'name' => 'currentstart',
                            'cmid' => $coursemodule->id,
                            'value' => $attempt->timestart]);
                    }

                    // Save current attempt penalties.
                    $currentpenaltiesold = $DB->get_record('format_ludilearn_cm_user',
                        ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'currentpenalties']);
                    if ($currentpenaltiesold) {
                        if ($currentpenaltiesold->value != $currentpenalties) {
                            $currentpenaltiesold->value = $currentpenalties;
                            $DB->update_record('format_ludilearn_cm_user', $currentpenaltiesold);
                        }
                    } else {
                        $DB->insert_record('format_ludilearn_cm_user', [
                            'attributionid' => $attribution->id,
                            'name' => 'currentpenalties',
                            'cmid' => $coursemodule->id,
                            'value' => $currentpenalties]);
                    }
                }
            }

            // If a best attempt is found.
            if ($bestattempt) {
                // Save the time start of the best attempt.
                $beststart = $DB->get_record('format_ludilearn_cm_user',
                    ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'beststart']);
                if ($beststart) {
                    if ($beststart->value != $bestattempt->timestart) {
                        $beststart->value = $bestattempt->timestart;
                        $DB->update_record('format_ludilearn_cm_user', $beststart);
                    }
                } else {
                    $DB->insert_record('format_ludilearn_cm_user', [
                        'attributionid' => $attribution->id,
                        'name' => 'beststart',
                        'cmid' => $coursemodule->id,
                        'value' => $bestattempt->timestart]);
                }

                // Save the time finish of the best attempt.
                $bestfinish = $DB->get_record('format_ludilearn_cm_user',
                    ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'bestfinish']);
                if ($bestfinish) {
                    if ($bestfinish->value != $bestattempt->timefinish) {
                        $bestfinish->value = $bestattempt->timefinish;
                        $DB->update_record('format_ludilearn_cm_user', $bestfinish);
                    }
                } else {
                    $DB->insert_record('format_ludilearn_cm_user', [
                        'attributionid' => $attribution->id,
                        'name' => 'bestfinish',
                        'cmid' => $coursemodule->id,
                        'value' => $bestattempt->timefinish]);
                }

                // Save penalties of the best attempt.
                $bestpenaltiesold = $DB->get_record('format_ludilearn_cm_user',
                    ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'bestpenalties']);
                if ($bestpenaltiesold) {
                    if ($bestpenaltiesold->value != $bestpenalties) {
                        $bestpenaltiesold->value = $bestpenalties;
                        $DB->update_record('format_ludilearn_cm_user', $bestpenaltiesold);
                    }
                } else {
                    $DB->insert_record('format_ludilearn_cm_user', [
                        'attributionid' => $attribution->id,
                        'name' => 'bestpenalties',
                        'cmid' => $coursemodule->id,
                        'value' => $bestpenalties]);
                }
            }
        }
    }

    /**
     * Update game elements when quiz attempt.
     *
     * @param int $attemptid The attempt id.
     * @param int $quizid    The quiz id.
     * @param int $userid    The user id.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function submit_quiz(int $attemptid, int $quizid, int $userid): void {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        $module = $DB->get_record('modules', ['name' => 'quiz']);
        $coursemodule = $DB->get_record('course_modules',
            [
                'course' => $quiz->course,
                'module' => $module->id,
                'instance' => $quiz->id,
            ]
        );

        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'timer']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {
            $gameelement = self::get($quiz->course, $coursemodule->section, $userid);

            // Get quiz questions attempts.
            $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quizid, 'userid' => $userid]);
            if (!$attempts) {
                return;
            }
            $bestattempt = false;
            $bestpenalties = 0;
            $besttime = 0;

            // Search best attempts and the current attempt.
            foreach ($attempts as $attempt) {
                $penalties = 0;
                $questionattempts = $DB->get_records('question_attempts', ['questionusageid' => $attempt->uniqueid], 'id');
                foreach ($questionattempts as $questionattempt) {
                    // Add the max grade of the question to the penalties.
                    $penalties += $questionattempt->maxmark;

                    // Retrieve question attempt steps.
                    $questionattemptsteps = $DB->get_records('question_attempt_steps',
                        ['questionattemptid' => $questionattempt->id]);

                    foreach ($questionattemptsteps as $questionattemptstep) {

                        // If the attempt step is done, deduce the grade obtained from penalties.
                        if ($questionattemptstep->state != 'todo') {
                            if ($questionattemptstep->fraction) {
                                $penalties -= $questionattemptstep->fraction * $questionattempt->maxmark;
                            }
                        }
                    }
                }

                // Calculate the best time with penalties if the attempt is finished.
                // Else if the current attempt is not finished we save it.
                if ($attempt->state == 'finished' || $attempt->id == $attemptid) {
                    $time = $attempt->timefinish - $attempt->timestart;
                    $time += $penalties * $gameelement->get_penalties();
                    if ($besttime == 0 || $time < $besttime) {
                        $bestattempt = $attempt;
                        $besttime = $time;
                        $bestpenalties = $penalties;
                    }
                }
            }

            // Save current attempt penalties.
            $currentpenaltiesres = $DB->get_record('format_ludilearn_cm_user',
                ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'currentpenalties']);
            if ($currentpenaltiesres) {
                $currentpenaltiesres->value = 0;
                $DB->update_record('format_ludilearn_cm_user', $currentpenaltiesres);
            }

            // If a best attempt is found.
            if ($bestattempt) {
                // Save the time start of the best attempt.
                $beststart = $DB->get_record('format_ludilearn_cm_user',
                    ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'beststart']);
                if ($beststart) {
                    if ($beststart->value != $bestattempt->timestart) {
                        $beststart->value = $bestattempt->timestart;
                        $DB->update_record('format_ludilearn_cm_user', $beststart);
                    }
                } else {
                    $DB->insert_record('format_ludilearn_cm_user', [
                        'attributionid' => $attribution->id,
                        'name' => 'beststart',
                        'cmid' => $coursemodule->id,
                        'value' => $bestattempt->timestart]);
                }

                // Save the time finish of the best attempt.
                $bestfinish = $DB->get_record('format_ludilearn_cm_user',
                    ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'bestfinish']);
                if ($bestfinish) {
                    if ($bestfinish->value != $bestattempt->timefinish) {
                        $bestfinish->value = $bestattempt->timefinish;
                        $DB->update_record('format_ludilearn_cm_user', $bestfinish);
                    }
                } else {
                    $DB->insert_record('format_ludilearn_cm_user', [
                        'attributionid' => $attribution->id,
                        'name' => 'bestfinish',
                        'cmid' => $coursemodule->id,
                        'value' => $bestattempt->timefinish]);
                }

                // Save penalties of the best attempt.
                $bestpenaltiesold = $DB->get_record('format_ludilearn_cm_user',
                    ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'bestpenalties']);
                if ($bestpenaltiesold) {
                    if ($bestpenaltiesold->value != $bestpenalties) {
                        $bestpenaltiesold->value = $bestpenalties;
                        $DB->update_record('format_ludilearn_cm_user', $bestpenaltiesold);
                    }
                } else {
                    $DB->insert_record('format_ludilearn_cm_user', [
                        'attributionid' => $attribution->id,
                        'name' => 'bestpenalties',
                        'cmid' => $coursemodule->id,
                        'value' => $bestpenalties]);
                }
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
     * @return timer|null The game element if it exists, null otherwise.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get(int $courseid, int $sectionid, int $userid): ?timer {
        global $DB;

        $gameelementsql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND g.sectionid = :sectionid
                            AND a.userid = :userid AND g.type = :type';

        $gameelementreq = $DB->get_record_sql($gameelementsql,
            ['courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'type' => 'timer']);

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

        return new timer($gameelementreq->gameelementid,
            $gameelementreq->courseid,
            $gameelementreq->sectionid,
            $gameelementreq->userid,
            $parameters,
            $cmparameters);
    }

    /**
     * Update the parameters of a course.
     *
     * @param int $courseid  The course ID.
     * @param int $penalties The value for the penalties parameter.
     *
     * @return bool True if the parameters were updated successfully, false otherwise.
     * @throws \dml_exception
     */
    public static function update_course_parameters(int $courseid, int $penalties): bool {
        global $DB;

        // Retrieve all game elements of the course.
        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid, 'type' => 'timer']);

        if (!$gameelements) {
            return false;
        } else {
            foreach ($gameelements as $gameelement) {
                // Retrieve existing values for penalties parameter.
                $penaltiesecord = $DB->get_record('format_ludilearn_params',
                    ['gameelementid' => $gameelement->id,
                        'name' => 'penalties']);
                // Check value.
                if ($penalties < 0) {
                    $penalties = 0;
                }

                // If existing update values, else add value.
                if ($penaltiesecord) {
                    $penaltiesecord->value = $penalties;
                    $DB->update_record('format_ludilearn_params', $penaltiesecord);
                } else {
                    $penaltiesecord = new stdClass();
                    $penaltiesecord->gameelementid = $gameelement->id;
                    $penaltiesecord->name = 'penalties';
                    $penaltiesecord->value = $penalties;
                    $DB->insert_record('format_ludilearn_params', $penaltiesecord);
                }
            }
        }
        return true;
    }
}
