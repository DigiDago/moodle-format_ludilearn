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
 * Badge game element class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge extends game_element {

    /**
     * @var int $progression Progression of the user.
     */
    protected int $progression;

    /**
     * @var int $badgegold Progression for gold badge owning.
     */
    protected int $badgegold;

    /**
     * @var int $badgesilver Progression for silver badge owning.
     */
    protected int $badgesilver;

    /**
     * @var int $badgebronze Progression for bronze badge owning.
     */
    protected int $badgebronze;

    /**
     * @var int $goldcount Bronze bagde count of the user.
     */
    protected int $bronzecount = 0;

    /**
     * @var int $silvercount Silver bagde count of the user.
     */
    protected int $silvercount = 0;

    /**
     * @var int $goldcount Gold bagde count of the user.
     */
    protected int $goldcount = 0;

    /**
     * @var int $completioncount Completion bagde count of the user.
     */
    protected int $completioncount = 0;

    /**
     * Default value for Gold badge owning.
     */
    const DEFAULT_BADGE_GOLD = 100;

    /**
     * Default value for silver badge owning.
     *
     * @var int DEFAULT_BADGE_SILVER
     */
    const DEFAULT_BADGE_SILVER = 85;

    /**
     * Default value for bronze badge owning.
     *
     * @var int DEFAULT_BADGE_BRONZE
     */
    const DEFAULT_BADGE_BRONZE = 70;

    /**
     * Constructor.
     *
     * @param int $id Id of the game element.
     * @param int $courseid Id of the course.
     * @param int $sectionid Id of the section.
     * @param int $userid Id of the user.
     * @param array $paramaters Array of parameters.
     * @param array $cmparameters Array of cm parameters.
     */
    public function __construct(int $id, int $courseid, int $sectionid, int $userid, array $paramaters, array $cmparameters) {
        parent::__construct($id, $courseid, $sectionid, $userid, $paramaters, $cmparameters);
        $this->type = 'badge';

        // Set badge gold parameter.
        $this->sectionparameters['badgegold'] = $this->badgegold = self::DEFAULT_BADGE_GOLD;
        if (isset($paramaters['badgegold'])) {
            $this->sectionparameters['badgegold'] = $this->badgegold = $paramaters['badgegold'];
        }

        // Set badge silver parameter.
        $this->sectionparameters['badgesilver'] = $this->badgesilver = self::DEFAULT_BADGE_SILVER;
        if (isset($paramaters['badgesilver'])) {
            $this->sectionparameters['badgesilver'] = $this->badgesilver = $paramaters['badgesilver'];
        }

        // Set badge bronze parameter.
        $this->sectionparameters['badgebronze'] = $this->badgebronze = self::DEFAULT_BADGE_BRONZE;
        if (isset($paramaters['badgebronze'])) {
            $this->sectionparameters['badgebronze'] = $this->badgebronze = $paramaters['badgebronze'];
        }

        // Calculate progression and completion.
        $this->progression = 0;
        $this->completioncount = 0;
        $sumprogression = 0;
        $maxprogression = 0;
        foreach ($cmparameters as $key => $value) {
            if (!isset($value['gamified']) || $value['gamified']) {
                $cmparameters[$key]['gamified'] = true;
                $gradable = $this->is_gradable($key);
                $completion = $this->is_completion_enabled($key);

                // If the module is not gradable and completien is disabled, gamification is disabled.
                if ((!$gradable && !$completion)
                    || !$this->is_activity_available_for_user($key, $this->userid)) {
                    $cmparameters[$key]['gamified'] = false;
                    continue;
                }

                $cmparameters[$key]['completion'] = false;
                // If the module is gradable.
                if ($gradable) {
                    $maxprogression += 100;
                    if (isset($value['progression'])) {
                        $sumprogression += $value['progression'];

                        // Define badge.
                        if ($value['progression'] >= $this->badgegold) {
                            $cmparameters[$key]['badge'] = 'gold';
                            $this->goldcount++;
                        } else if ($value['progression'] >= $this->badgesilver) {
                            $cmparameters[$key]['badge'] = 'silver';
                            $this->silvercount++;
                        } else if ($value['progression'] >= $this->badgebronze) {
                            $cmparameters[$key]['badge'] = 'bronze';
                            $this->bronzecount++;
                        } else {
                            $cmparameters[$key]['badge'] = 'none';
                        }
                    }
                }

                $completed = $this->is_completed($key);
                // If the module is not gradable but completion is enabled.
                if (!$gradable && $completion) {
                    // If the module is completed, the progression is 100% and the badge is gold.
                    if ($completed) {
                        $cmparameters[$key]['progression'] = 100;
                        $cmparameters[$key]['badge'] = 'gold';
                        $this->goldcount++;
                    } else {
                        $cmparameters[$key]['progression'] = 0;
                        $cmparameters[$key]['badge'] = 'none';
                    }
                }
                // If completien is enabled.
                if ($completion) {
                    // If the module is completed, we mark it as completed and add a completed count (for the completion badge).
                    if ($completed) {
                        $cmparameters[$key]['completion'] = true;
                        $this->completioncount++;
                    }
                }
            } else {
                $cmparameters[$key]['gamified'] = false;
            }
        }
        $this->cmparameters = $cmparameters;
        if ($maxprogression > 0) {
            $this->progression = round($sumprogression / $maxprogression * 100);
        }
        $this->sectionparameters['progression'] = $this->progression;
        $this->sectionparameters['completioncount'] = $this->completioncount;
    }

    /**
     * Get a list of parameters.
     *
     * @return array List of parameters.
     */
    public static function get_parameters_list(): array {
        return ['badgegold', 'badgesilver', 'badgebronze'];
    }

    /**
     * Get the progression of the user.
     *
     * @return int Progression of the user.
     */
    public function get_progression(): int {
        return $this->progression;
    }

    /**
     * Get the current badge of the user.
     *
     * @return string Current badge of the user.
     */
    public function get_current_badge(): string {
        if ($this->progression >= $this->badgegold) {
            return 'gold';
        } else if ($this->progression >= $this->badgesilver) {
            return 'silver';
        } else if ($this->progression >= $this->badgebronze) {
            return 'bronze';
        } else {
            return 'none';
        }
    }

    /**
     * Get the badge of the user in function of the cm progression.
     *
     * @param int $progression
     * @return string
     */
    public function get_cm_badge(int $progression) {
        if ($progression >= $this->badgegold) {
            return 'gold';
        } else if ($progression >= $this->badgesilver) {
            return 'silver';
        } else if ($progression >= $this->badgebronze) {
            return 'bronze';
        } else {
            return 'none';
        }
    }

    /**
     * Get the bronze count of the user.
     *
     * @return int Bronze count of the user.
     */
    public function get_bronze_count(): int {
        return $this->bronzecount;
    }

    /**
     * Get the silver count of the user.
     *
     * @return int Silver count of the user.
     */
    public function get_silver_count(): int {
        return $this->silvercount;
    }

    /**
     * Get the gold count of the user.
     *
     * @return int Gold count of the user.
     */
    public function get_gold_count(): int {
        return $this->goldcount;
    }

    /**
     * Get the total badges count of the user.
     *
     * @return int Total count of the user.
     */
    public function get_total_count(): int {
        return $this->goldcount + $this->silvercount + $this->bronzecount;
    }

    /**
     * Get the completion count of the user.
     *
     * @return int Completion count of the user.
     */
    public function get_completion_count(): int {
        return $this->completioncount;
    }

    /**
     * Update badge elements.
     *
     * @param int $courseid The course id.
     * @param stdClass $coursemodule The course module.
     * @param string $modulename The module name.
     * @param int $userid The user id.
     */
    public static function update_elements(int $courseid, stdClass $coursemodule, string $modulename, int $userid): void {
        global $DB;

        $manager = new manager();

        // Get game element.
        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'badge']);

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
                if ($grademax != null && $grademax != 0) {
                    // Calculate the score.
                    $progression = intval($grade * 100 / $grademax);
                }
            }

            $gameelement = self::get($courseid, $coursemodule->section, $userid);
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
     * @return void
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
                'type' => 'badge']);

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
            $gameelement = self::get($quiz->course, $coursemodule->section, $userid);
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
     * @param int $courseid The course ID.
     * @param int $sectionid The section ID.
     * @param int $userid The user ID.
     * @return badge|null
     */
    public static function get(int $courseid, int $sectionid, int $userid): ?badge {
        global $DB;

        $gameelement = [];
        $gameelementsql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND g.sectionid = :sectionid
                            AND a.userid = :userid AND g.type = :type';

        $gameelementreq = $DB->get_record_sql($gameelementsql,
            ['courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'type' => 'badge']);

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

        return new badge($gameelementreq->gameelementid,
            $gameelementreq->courseid,
            $gameelementreq->sectionid,
            $gameelementreq->userid,
            $parameters,
            $cmparameters);
    }

    /**
     * Update the parameters of a course.
     *
     * @param int $courseid The course ID.
     * @param int $badgegold The value for the badgegold parameter.
     * @param int $badgesilver The value for the badgesilver parameter.
     * @param int $badgebronze The value for the badgebronze parameter.
     * @return bool True if the parameters were updated successfully, false otherwise.
     */
    public static function update_course_parameters(int $courseid, int $badgegold, int $badgesilver, $badgebronze): bool {
        global $DB;

        // Retrieve all game elements of the course.
        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid, 'type' => 'badge']);

        if (!$gameelements) {
            return false;
        } else {
            foreach ($gameelements as $gameelement) {
                // Retrieve existing values for badgegold parameter.
                $badgegoldrecord = $DB->get_record('format_ludilearn_params',
                    ['gameelementid' => $gameelement->id,
                        'name' => 'badgegold']);
                // Check value.
                if ($badgegold < 0) {
                    $badgegold = 0;
                }
                if ($badgegold > 100) {
                    $badgegold = 100;
                }
                // If existing update values, else add value.
                if ($badgegoldrecord) {
                    $badgegoldrecord->value = $badgegold;
                    $DB->update_record('format_ludilearn_params', $badgegoldrecord);
                } else {
                    $badgegoldrecord = new stdClass();
                    $badgegoldrecord->gameelementid = $gameelement->id;
                    $badgegoldrecord->name = 'badgegold';
                    $badgegoldrecord->value = $badgegold;
                    $DB->insert_record('format_ludilearn_params', $badgegoldrecord);
                }

                // Retrieve existing values for badgesilver parameter.
                $badgesilverrecord = $DB->get_record('format_ludilearn_params',
                    ['gameelementid' => $gameelement->id,
                        'name' => 'badgesilver']);
                // Check value.
                if ($badgesilver < 0) {
                    $badgesilver = 0;
                }
                if ($badgesilver > 100) {
                    $badgesilver = 100;
                }

                // If existing update values, else add value.
                if ($badgesilverrecord) {
                    $badgesilverrecord->value = $badgesilver;
                    $DB->update_record('format_ludilearn_params', $badgesilverrecord);
                } else {
                    $badgesilverrecord = new stdClass();
                    $badgesilverrecord->gameelementid = $gameelement->id;
                    $badgesilverrecord->name = 'badgesilver';
                    $badgesilverrecord->value = $badgesilver;
                    $DB->insert_record('format_ludilearn_params', $badgesilverrecord);
                }

                // Retrieve existing values for badgebronze parameter.
                $badgebronzerecord = $DB->get_record('format_ludilearn_params',
                    ['gameelementid' => $gameelement->id,
                        'name' => 'badgebronze']);
                // Check value.
                if ($badgebronze < 0) {
                    $badgebronze = 0;
                }
                if ($badgebronze > 100) {
                    $badgebronze = 100;
                }

                // If existing update values, else add value.
                if ($badgebronzerecord) {
                    $badgebronzerecord->value = $badgebronze;
                    $DB->update_record('format_ludilearn_params', $badgebronzerecord);
                } else {
                    $badgebronzerecord = new stdClass();
                    $badgebronzerecord->gameelementid = $gameelement->id;
                    $badgebronzerecord->name = 'badgebronze';
                    $badgebronzerecord->value = $badgebronze;
                    $DB->insert_record('format_ludilearn_params', $badgebronzerecord);
                }
            }
        }
        return true;
    }
}
