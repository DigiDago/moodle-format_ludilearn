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

use completion_info;
use context_module;
use core_availability\info_module;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Class game_element
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class game_element {

    /**
     * @var int
     */
    protected int $id;

    /**
     * @var int
     */
    protected int $courseid;

    /**
     * @var stdClass $course The course.
     */
    protected stdClass $course;

    /**
     * @var int
     */
    protected int $sectionid;

    /**
     * @var int
     */
    protected int $userid;

    /**
     * @var string
     */
    protected string $type = 'undefined';

    /**
     * @var array
     */
    protected array $sectionparameters;

    /**
     * @var array
     */
    protected array $cmparameters;

    /**
     * Constructor.
     * @param int $id Id of the game element.
     * @param int $courseid Id of the course.
     * @param int $sectionid Id of the section.
     * @param int $userid Id of the user.
     * @param array $sectionparameters Array of section parameters.
     * @param array $cmparameters Array of cm parameters.
     */
    public function __construct(int $id,
        int $courseid,
        int $sectionid,
        int $userid,
        array $sectionparameters,
        array $cmparameters) {
        $this->id = $id;
        $this->courseid = $courseid;
        $this->sectionid = $sectionid;
        $this->userid = $userid;
        $this->sectionparameters = $sectionparameters;
        $this->cmparameters = $cmparameters;
        $this->course = get_course($courseid);
    }

    /**
     * Check if a course module is completed for a given user.
     *
     * @param int $cmid The course module ID.
     * @return bool Returns true if the course module is completed, false otherwise.
     */
    public function is_completed(int $cmid): bool {
        $info = new completion_info($this->course);
        $cminfo = get_fast_modinfo($this->course)->get_cm($cmid);
        if ($info->is_enabled($cminfo)) {
            $cm = get_coursemodule_from_id('', $cmid);
            $data = $info->get_data($cm, false, $this->userid);
            if ($data->completionstate != COMPLETION_INCOMPLETE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a course module completion is enabled fo a course module.
     *
     * @param int $cmid The course module ID.
     * @return bool Returns true if the course module completion is enabled, false otherwise.
     */
    public function is_completion_enabled(int $cmid): bool {
        $info = new completion_info($this->course);
        $cm = get_coursemodule_from_id('', $cmid, $this->courseid);
        return $info->is_enabled($cm);
    }

    /**
     * Checks if the given course module is a quiz.
     *
     * @param int $cmid Id of the course module.
     *
     * @return bool True if the course module is a quiz, false otherwise.
     * @throws \coding_exception
     */
    public function is_quiz(int $cmid): bool {
        $cm = get_coursemodule_from_id('', $cmid, $this->courseid);
        if (!$cm) {
            return false;
        }
        return ($cm->modname == 'quiz');
    }

    /**
     * Check if the course module is gradable.
     *
     * @param int $cmid Id of the course module.
     *
     * @return bool Returns true if the course module is gradable, false otherwise.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function is_gradable(int $cmid): bool {
        global $CFG, $DB;
        $cm = get_coursemodule_from_id('', $cmid, $this->courseid);
        if (!$cm) {
            return false;
        }

        $modulename = $cm->modname;
        $libfile = $CFG->dirroot . "/mod/{$modulename}/lib.php";

        // Verify if the lib file exists.
        if (file_exists($libfile)) {
            require_once($libfile);

            // Supports function full name.
            $functionname = $modulename . '_supports';

            // Verify if the supports function exists.
            if (function_exists($functionname)) {
                // If the module does not support grade, directly return false.
                if (!$functionname(FEATURE_GRADE_HAS_GRADE)) {
                    return false;
                }
            }
        }

        // Check if there is a grade item for this course module.
        $gradeitem = $DB->get_record('grade_items',
            ['iteminstance' => $cm->instance,
            'itemmodule' => $cm->modname,
                'courseid' => $this->courseid,
                'itemnumber' => 0]);
        if ($gradeitem && $gradeitem->gradetype != GRADE_TYPE_NONE) {
            return true;
        }

        return false;
    }

    /**
     * Check if an activity is available for a user.
     *
     * @param int $cmid   The course module ID.
     * @param int $userid The user ID.
     *
     * @return bool Returns true if the activity is available for the user, false otherwise.
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function is_activity_available_for_user(int $cmid, int $userid = 0): bool {
        global $USER;

        // If the user ID is not set, use the current user ID.
        if ($userid == 0) {
            $userid = $USER->id;
        }

        // Get the course module.
        $cm = get_coursemodule_from_id('', $cmid);
        if (!$cm) {
            return false;
        }

        // Get $cminfo.
        $coursemodinfo = get_fast_modinfo($cm->course);
        $cm = $coursemodinfo->get_cm($cm->id);
        if (!$cm) {
            return false;
        }

        // Check if the course module is visible.
        if (!$cm->visible) {
            return false;
        }

        // Check if the user meets the access restrictions.
        $context = context_module::instance($cm->id);
        $info = new info_module($cm);
        $availableinfo = $cm->availableinfo;
        return $info->is_available($availableinfo, true, $userid, $coursemodinfo);
    }

    /**
     * Get the grade of a course module.
     *
     * @param int $cmid Id of the course module.
     *
     * @return float Grade of the course module.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_grademax(int $cmid): float {
        global $DB;

        $cm = get_coursemodule_from_id('', $cmid, $this->courseid);
        if (!$this->is_gradable($cmid)) {
            return 0;
        }
        // Check if the course module is a quiz and if it has questions.
        if ($cm->modname == 'quiz') {
            $hasquestion = $DB->record_exists('quiz_slots', ['quizid' => $cm->instance]);
            // If it does not have questions, return 0 as grade max.
            // Because usually Moodle give a default grade max of 10 and we don't want that.
            if (!$hasquestion) {
                return 0;
            }
        }
        $grades = grade_get_grades($this->courseid, 'mod', $cm->modname, $cm->instance, $this->userid);
        if ($grades) {
            $grade = reset($grades->items);
            return $grade->grademax;
        }
        return 0;
    }

    /**
     * Get parameters by default.
     *
     * @return array Parameters by default.
     */
    public static function get_parameters_default(): array {
        return [];
    }

    /**
     * Get the parameters of a game element.
     *
     * @param string $type Type of the game element.
     *
     * @return array Parameters.
     * @throws \moodle_exception
     */
    public static function get_parameters_default_by_type(string $type, $courseid = 0): array {
        switch ($type) {
            case 'score':
                // Get the default parameters of the game element.
                $parameters = score::get_parameters_default();
                break;
            case 'badge':
                // Get the default parameters of the game element.
                $parameters = badge::get_parameters_default();
                break;
            case 'progress':
                // Get the default parameters of the game element.
                $parameters = progress::get_parameters_default();
                break;
            case 'avatar':
                // Get the default parameters of the game element.
                $parameters = avatar::get_parameters_default($courseid);
                break;
            case 'timer':
                // Get the default parameters of the game element.
                $parameters = timer::get_parameters_default();
                break;
            case 'ranking':
                // Get the default parameters of the game element.
                $parameters = ranking::get_parameters_default();
                break;
            case 'nogamified':
                // Get the default parameters of the game element.
                $parameters = nogamified::get_parameters_default();
                break;
            default:
                throw new \moodle_exception('error_game_element_type', 'format_ludilearn');
        }
        return $parameters;
    }

    /**
     * Get the cm parameters of a game element.
     *
     * @param string $type    Type of the game element.
     * @param string $modtype Type of the module.
     *
     * @return array Parameters.
     * @throws \moodle_exception
     */
    public static function get_cm_parameters_default_by_type(string $type, string $modtype, int $cmid): array {
        switch ($type) {
            case 'score':
                $cmparameters = score::get_cm_parameters_default($modtype, $cmid);
                break;
            case 'badge':
                $cmparameters = badge::get_cm_parameters_default($modtype, $cmid);
                break;
            case 'progress':
                $cmparameters = progress::get_cm_parameters_default($modtype, $cmid);
                break;
            case 'avatar':
                $cmparameters = avatar::get_cm_parameters_default($modtype, $cmid);
                break;
            case 'timer':
                $cmparameters = timer::get_cm_parameters_default($modtype, $cmid);
                break;
            case 'ranking':
                $cmparameters = ranking::get_cm_parameters_default($modtype, $cmid);
                break;
            case 'nogamified':
                $cmparameters = nogamified::get_cm_parameters_default($modtype, $cmid);
                break;
            default:
                throw new \moodle_exception('error_game_element_type', 'format_ludilearn');
        }
        return $cmparameters;
    }

    /**
     * Get default parameters for a course module.
     *
     * @param string $moduletype Module type.
     * @param int $cmid Course module ID.
     * @return array Default parameters.
     */
    public static function get_cm_parameters_default(string $moduletype, int $cmid): array {
        return [];
    }

    /**
     * Get the game element type.
     *
     * @return string Game element type.
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Get the game element id.
     *
     * @return int Game element id.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get parameters.
     *
     * @return array Parameters.
     */
    public function get_parameters(): array {
        return $this->sectionparameters;
    }

    /**
     * Get cm parameters.
     *
     * @return array Cm parameters.
     */
    public function get_cm_parameters(): array {
        return $this->cmparameters;
    }

    /**
     * Get cm parameters.
     *
     * @return array Cm parameters.
     */
    public function get_cm_parameters_by_cm(int $cmid): array {
        return $this->cmparameters[$cmid];
    }

    /**
     * Get the course module parameter type.
     *
     * @param string $name Name of the parameter.
     * @return string Parameter type.
     */
    public static function get_cm_parameter_type(string $name): string {
        switch ($name) {
            case 'condition':
                $result = 'select';
                break;
            default:
                $result = 'text';
        }
        return $result;
    }

    /**
     * Get count of cm gamified.
     *
     * @return int Count of cm gamified.
     */
    public function get_count_cm_gamified(): int {
        $count = 0;
        foreach ($this->get_cm_parameters() as $cmparameters) {
            if (isset($cmparameters['gamified'])) {
                if ($cmparameters['gamified']) {
                    $count++;
                }
            } else {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get a game element.
     *
     * @param int $courseid The course ID.
     * @param int $sectionid The section ID.
     * @param int $userid The user ID.
     * @return game_element|null The game element.
     */
    public static function get_element(int $courseid, int $sectionid, int $userid, string $type): ?game_element {
        // Create the game element object.
        return match ($type) {
            'score' => score::get($courseid, $sectionid, $userid),
            'badge' => badge::get($courseid, $sectionid, $userid),
            'progress' => progress::get($courseid, $sectionid, $userid),
            'avatar' => avatar::get($courseid, $sectionid, $userid),
            'timer' => timer::get($courseid, $sectionid, $userid),
            'ranking' => ranking::get($courseid, $sectionid, $userid),
            default => nogamified::get($courseid, $sectionid, $userid)
        };
    }

    /**
     * Get all game elements of a course.
     *
     * @param $courseid int Course ID.
     * @param $userid   int User ID.
     *
     * @return array Game elements.
     * @throws \dml_exception
     */
    public static function get_all(int $courseid, int $userid): array {
        global $DB;

        $gameelements = [];
        $gameelementssql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND a.userid = :userid';

        $gameelementsreq = $DB->get_records_sql($gameelementssql, ['courseid' => $courseid, 'userid' => $userid]);
        foreach ($gameelementsreq as $gameelementreq) {
            $gameelements[] = self::get_element($courseid, $gameelementreq->sectionid, $userid, $gameelementreq->type);
        }

        return $gameelements;
    }

    /**
     * Create a game element.
     *
     * @param $type      string Type of the game element.
     * @param $courseid  int Course ID.
     * @param $sectionid int Section ID.
     *
     * @return int Game element ID.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function create(string $type, int $courseid, int $sectionid): int {
        global $DB;

        // Get the default parameters of the game element.
        $parameters = self::get_parameters_default_by_type($type, $courseid);

        // Create the game element if not exists.
        $gameelementid = $DB->get_field('format_ludilearn_elements', 'id',
            ['courseid' => $courseid, 'sectionid' => $sectionid, 'type' => $type]);
        if (!$gameelementid) {
            $gameelementid = $DB->insert_record('format_ludilearn_elements',
                ['courseid' => $courseid, 'sectionid' => $sectionid, 'type' => $type, 'timecreated' => time()]);
        }

        // Create parameters.
        foreach ($parameters as $name => $value) {
            $sectionparamexist = $DB->record_exists('format_ludilearn_params',
                ['gameelementid' => $gameelementid, 'name' => $name]);
            if (!$sectionparamexist) {
                $DB->insert_record('format_ludilearn_params',
                    ['gameelementid' => $gameelementid, 'name' => $name, 'value' => $value]);
            }
        }

        // Create cm parameters.
        $cms = $DB->get_records('course_modules', ['course' => $courseid, 'section' => $sectionid]);
        foreach ($cms as $cm) {
            $cminfo = get_fast_modinfo($courseid)->get_cm($cm->id);
            if (!$cminfo->get_url()) {
                continue;
            }
            $modetype = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $cmparameters = self::get_cm_parameters_default_by_type($type, $modetype, $cm->id);
            foreach ($cmparameters as $name => $value) {
                $cmparamexist = $DB->record_exists('format_ludilearn_cm_params',
                    ['gameelementid' => $gameelementid, 'cmid' => $cm->id, 'name' => $name]);
                if (!$cmparamexist) {
                    $DB->insert_record('format_ludilearn_cm_params',
                        ['gameelementid' => $gameelementid, 'cmid' => $cm->id, 'name' => $name, 'value' => $value]);
                }
            }
        }

        return $gameelementid;
    }

    /**
     * Create all game elements of a section.
     *
     * @param int $courseid  Course ID.
     * @param int $sectionid Section ID.
     *
     * @return array Game elements ID.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function create_all(int $courseid, int $sectionid): array {
        global $DB;

        $gameelementsid = [];
        foreach (self::get_all_types() as $type) {
            $gameelement = $DB->record_exists('format_ludilearn_elements',
                ['courseid' => $courseid, 'sectionid' => $sectionid, 'type' => $type]);
            if (!$gameelement) {
                $gameelementsid[] = self::create($type, $courseid, $sectionid);
            }
        }
        return $gameelementsid;
    }

    /**
     * Create all game elements of a course.
     *
     * @param int $courseid Course ID.
     *
     * @return array Game elements ID.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function create_all_for_course(int $courseid): array {
        global $DB;

        $gameelementsid = [];
        $sections = $DB->get_records('course_sections', ['course' => $courseid]);
        foreach ($sections as $section) {
            $gameelementsid = array_merge($gameelementsid, self::create_all($courseid, $section->id));
        }
        return $gameelementsid;
    }

    /**
     * Get all types of game elements.
     *
     * @return array All types of game elements.
     */
    public static function get_all_types(): array {
        return [
            'score',
            'badge',
            'progress',
            'avatar',
            'timer',
            'ranking',
            'nogamified',
        ];
    }

    /**
     * Return if a course module is gamified.
     *
     * @param int $cmid Cm id.
     *
     * @return bool true if is gamified.
     * @throws \dml_exception
     */
    public static function is_gamified(int $cmid): bool {
        global $DB;

        $cmparamexist = $DB->get_records('format_ludilearn_cm_params',
            ['cmid' => $cmid, 'name' => 'gamified'], 'id', 'value', 0, 1);
        if ($cmparamexist) {
            $cmparam = reset($cmparamexist);
            return boolval($cmparam->value);
        }
        return true;
    }

    /**
     * Gamify the given course module.
     *
     * @param  int $courseid Course ID.
     * @param int $cmid      Course module ID.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function gamify(int $courseid, int $cmid): void {
        global $DB;

        // Retrieve all game element for a course id.
        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid]);

        // For each game element, check if the param exist for the course module.
        foreach ($gameelements as $gameelement) {
            $cmparamexist = $DB->get_record('format_ludilearn_cm_params',
                ['gameelementid' => $gameelement->id, 'cmid' => $cmid, 'name' => 'gamified']);
            if ($cmparamexist) {
                $cmparamexist->value = 1;
                $DB->update_record('format_ludilearn_cm_params', $cmparamexist);
            } else {
                $DB->insert_record('format_ludilearn_cm_params',
                    ['gameelementid' => $gameelement->id, 'cmid' => $cmid, 'name' => 'gamified', 'value' => 1]);
            }
        }
    }

    /**
     * Not gamify the given course module.
     *
     * @param int $courseid Course ID.
     * @param int $cmid     Course module ID.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function not_gamify(int $courseid, int $cmid): void {
        global $DB;

        // Retrieve all game element for a course id.
        $gameelements = $DB->get_records('format_ludilearn_elements', ['courseid' => $courseid]);

        // For each game element, check if the param exist for the course module.
        foreach ($gameelements as $gameelement) {
            $cmparamexist = $DB->get_record('format_ludilearn_cm_params',
                ['gameelementid' => $gameelement->id, 'cmid' => $cmid, 'name' => 'gamified']);
            if ($cmparamexist) {
                $cmparamexist->value = 0;
                $DB->update_record('format_ludilearn_cm_params', $cmparamexist);
            } else {
                $DB->insert_record('format_ludilearn_cm_params',
                    ['gameelementid' => $gameelement->id, 'cmid' => $cmid, 'name' => 'gamified', 'value' => 0]);
            }
        }
    }

    /**
     * Get the course parameters for a given course ID.
     *
     * @param int $courseid The ID of the course.
     * @param string $type  The type of game elements.
     *
     * @return stdClass The course parameters.
     * @throws \dml_exception
     */
    public static function get_course_parameters(int $courseid, string $type): stdClass {
        global $DB;

        $courseparameters = new stdClass();
        // Get a game element of course to get the parameters.
        // (Because all game elements of a same course have the same parameters).
        $gameelementreq = $DB->get_records('format_ludilearn_elements',
            ['courseid' => $courseid, 'type' => $type], '', 'id', 0, 1);
        if ($gameelementreq) {
            $gameelementid = reset($gameelementreq)->id;
            $parameters = $DB->get_records('format_ludilearn_params', ['gameelementid' => $gameelementid]);
            if ($parameters) {
                foreach ($parameters as $parameter) {
                    $courseparameters->{$parameter->name} = $parameter->value;
                }
            }
        }
        return $courseparameters;
    }

    /**
     * Reset the course progression.
     *
     * @param int $courseid The course ID.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function reset_course(int $courseid): void {
        global $DB;

        // Remove all cm user progression.
        $sqlcms = 'DELETE FROM {format_ludilearn_cm_user}
                    WHERE attributionid IN
                    (SELECT a.id FROM {format_ludilearn_attributio} a WHERE a.gameelementid IN
                        (SELECT g.id FROM {format_ludilearn_elements} g WHERE g.courseid = :courseid))';
        $DB->execute($sqlcms, ['courseid' => $courseid]);

        // Remove all section user progression.
        $sqlsections = 'DELETE FROM {format_ludilearn_ele_user}
                        WHERE attributionid IN
                        (SELECT a.id FROM {format_ludilearn_attributio} a WHERE a.gameelementid IN
                            (SELECT g.id FROM {format_ludilearn_elements} g WHERE g.courseid = :courseid))';
        $DB->execute($sqlsections, ['courseid' => $courseid]);
    }

    /**
     * Get a list of parameters.
     *
     * @return array List of parameters.
     */
    public static function get_parameters_list(): array {
        return [];
    }
}
