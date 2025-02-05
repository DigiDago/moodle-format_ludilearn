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
 * Avatar game element class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class avatar extends game_element {

    /**
     * @var int
     */
    protected int $progression;

    /**
     * @var array
     */
    protected array $itemowned;

    /**
     * @var array
     */
    protected array $itemequiped;

    /**
     *
     * @var int Threshold of progression to earn an item.
     */
    protected int $thresholdtoearn;

    /**
     * Default threshold of progression to earn an item.
     *
     * @var int
     */
    const DEFAULT_THRESHOLDTOEARN = 80;

    /**
     * @var int Count of items ownable.
     */
    protected int $itemsownablecount;

    /**
     * @var int Count of items owned.
     */
    protected int $itemownedcount;

    /**
     * @var string World of the avatar.
     */
    protected string $world;

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
        $this->type = 'avatar';

        // Set threshold to earn parameters.
        $this->sectionparameters['thresholdtoearn'] = $this->thresholdtoearn = self::DEFAULT_THRESHOLDTOEARN;
        if (isset($parameters['thresholdtoearn'])) {
            $this->sectionparameters['thresholdtoearn'] = $this->thresholdtoearn = $parameters['thresholdtoearn'];
        }

        // Set the world of the avatar.
        $format = course_get_format($courseid);
        $options = $format->get_format_options();
        $this->world = $options['world'];
        $this->sectionparameters['world'] = $this->world;
        $this->progression = 0;
        $this->itemsownablecount = 0;
        $this->itemownedcount = 0;
        foreach ($cmparameters as $key => $value) {
            if (!isset($value['gamified']) || $value['gamified']) {
                $cmparameters[$key]['gamified'] = true;

                // Do not gamify the activity if it is not gradable and the completion is not enabled.
                if ((!$this->is_gradable($key) && !$this->is_completion_enabled($key))
                    || !$this->is_activity_available_for_user($key, $this->userid)) {
                    $cmparameters[$key]['gamified'] = false;
                    continue;
                }

                // Set activity completion.
                if ($this->is_completed($key)) {
                    $cmparameters[$key]['completion'] = true;
                } else {
                    $cmparameters[$key]['completion'] = false;
                }

                // Set progression.
                if (isset($value['progression'])) {
                    $this->progression += $value['progression'];
                } else {
                    $cmparameters[$key]['progression'] = 0;

                    // If the activity is not gradable and the completion is true, set progression to 100.
                    if (!$this->is_gradable($key) && $cmparameters[$key]['completion']) {
                        $cmparameters[$key]['progression'] = 100;
                    }
                }

                // Set threshold exceeded.
                if (!isset($value['thresholdexceeded'])) {
                    $cmparameters[$key]['thresholdexceeded'] = false;
                }
                $this->itemsownablecount++;
            } else {
                $cmparameters[$key]['gamified'] = false;
            }
        }

        if ($this->get_count_cm_gamified() > 0) {
            $this->progression = intval($this->progression / $this->get_count_cm_gamified());
        }
        $this->cmparameters = $cmparameters;
        $this->sectionparameters['progression'] = $this->progression;
        $this->itemowned = $paramaters['itemowned'];

        // Refresh item owned when we detect an activity is completed.
        // Or detect a progression greater than the threshold to earn an item.
        foreach ($this->cmparameters as $key => $value) {
            if ($value['gamified']) {
                // If the activity is completed but the threshold was not exceeded yet.
                if (!$value['thresholdexceeded'] && $value['completion'] && !$this->is_gradable($key)) {
                    // We refresh mark the trehshold as exceeded and make earn the item.
                    $this->earn_item($key);
                }

                // If the progression is greater than the threshold to earn an item.
                if (!$value['thresholdexceeded'] && $value['progression'] >= $this->thresholdtoearn && $this->is_gradable($key)) {
                    // We refresh mark the trehshold as exceeded and make earn the item.
                    $this->earn_item($key);
                }
            }
        }

        // Set item equiped.
        $itemequiped = [];
        // Initialize all items equiped to false and count item_own.
        foreach ($this->itemowned as $theme => $slots) {
            foreach ($slots as $slot => $owned) {
                $itemequiped[$theme][$slot] = false;

                if ($owned) {
                    $this->itemownedcount++;
                }
            }
        }
        foreach ($itemequiped as $theme => $slots) {
            foreach ($slots as $slot => $owned) {
                if (isset($paramaters['item-equiped-' . $slot])) {
                    if ($paramaters['item-equiped-' . $slot] == $theme) {
                        $itemequiped[$theme][$slot] = true;
                    }
                }
            }
        }

        $this->sectionparameters['itemequiped'] = $this->itemequiped = $itemequiped;
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
     * Get the threshold to earn an item.
     *
     * @return int
     */
    public function get_thresholdtoearn(): int {
        return $this->thresholdtoearn;
    }

    /**
     * Get the count of items ownable.
     *
     * @return int Count of items ownable.
     */
    public function get_items_ownable_count(): int {
        return $this->itemsownablecount;
    }

    /**
     * Get the count of items owned.
     *
     * @return int Count of items owned.
     */
    public function get_count_items_owned(): int {
        return $this->itemownedcount;
    }

    /**
     * Get the count of theme for a slot.
     *
     * @param int $slot Slot of the item.
     * @return int Count of theme for a slot.
     */
    public function get_count_theme_for_slot(int $slot): int {
        $count = 0;
        foreach ($this->itemowned as $theme => $slots) {
            if (isset($slots[$slot])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get the count of slot for a theme.
     *
     * @param int $theme Theme of the item.
     * @return int Count of slot for a theme.
     */
    public function get_count_slot_for_theme(int $theme): int {
        $count = 0;
        foreach ($this->itemowned[$theme] as $slot => $owned) {
            if (isset($owned)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get the max count of slot.
     *
     * @return int Max count of slot.
     */
    public function get_max_count_slot(): int {
        $max = 0;
        foreach ($this->itemowned as $theme => $slots) {
            $count = count($slots);
            if ($count > $max) {
                $max = $count;
            }
        }
        return $max;
    }

    /**
     * Get the count of theme.
     *
     * @return int Count of theme.
     */
    public function get_count_theme(): int {
        $count = 0;
        foreach ($this->itemowned as $theme => $slots) {
            $count++;
        }
        return $count;
    }

    /**
     * Get the world of the avatar.
     *
     * @return string World of the avatar.
     */
    public function get_world(): string {
        return $this->world;
    }

    /**
     * Get parameters by default.
     *
     * @return array Parameters by default.
     */
    public static function get_parameters_default($courseid = 0): array {
        global $DB;
        return parent::get_parameters_default();
    }

    /**
     * Return true if the item is owned.
     *
     * @param int $itemtheme Item theme.
     * @param int $itemslot Item slot.
     * @return bool True if the item is owned.
     */
    public function item_is_owned(int $itemtheme, int $itemslot): bool {
           return $this->itemowned[$itemtheme][$itemslot];
    }

    /**
     * Get the item equiped.
     *
     * @param int $itemtheme Item theme.
     * @param int $itemslot Item slot.
     * @return int Item equiped.
     */
    public function item_is_equiped(int $itemtheme, int $itemslot): int {
        return $this->itemequiped[$itemtheme][$itemslot];
    }

    /**
     * Get the item equiped.
     *
     * @param int $itemslot Item slot.
     * @return int Theme of the item equiped.
     */
    public function get_item_equiped(int $itemslot): int {
        $theme = 0;
        foreach ($this->itemequiped as $itemtheme => $itemslots) {
            if ($itemslots[$itemslot]) {
                $theme = $itemtheme;
                break;
            }
        }
        return $theme;
    }

    /**
     * Get the threshold exceeded.
     *
     * @param int $cmid The cmid.
     * @return int Threshold exceeded.
     */
    public function get_thresholdexceeded(int $cmid): int {
        return $this->cmparameters[$cmid]['thresholdexceeded'];
    }

    /**
     * Set the item owned.
     *
     * @param int $itemtheme Item theme.
     * @param int $itemslot Item slot.
     * @param int $equiped Item equiped.
     */
    public function set_item_equiped(int $itemtheme, int $itemslot, int $equiped): void {
        $this->itemequiped[$itemtheme][$itemslot] = $equiped;
    }

    /**
     * Get the count of item owned.
     *
     * @return int Count ofitem owned.
     */
    public function get_count_owned(): int {
        $count = 0;
        foreach ($this->itemowned as $itemthemes) {
            foreach ($itemthemes as $item) {
                if ($item) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Get empty items.
     *
     * @return array Empty items.
     */
    public static function get_empty_items(string $world): array {
        $items = [];
        if ($world == 'school') {
            for ($theme = 1; $theme <= 9; $theme++) {
                for ($slot = 1; $slot <= 6; $slot++) {
                    $items[$theme][$slot] = 0;
                }
            }
        } else if ($world == 'professional') {
            // Items for the world of the avatar are not regular so we need to set the items manually.
            $items[1][1] = false;
            $items[2][1] = false;
            $items[3][1] = false;
            $items[1][2] = false;
            $items[2][2] = false;
            $items[3][2] = false;
            $items[4][2] = false;
            $items[1][3] = false;
            $items[2][3] = false;
            $items[3][3] = false;
            $items[1][4] = false;
            $items[2][4] = false;
            $items[3][4] = false;
            $items[1][5] = false;
            $items[2][5] = false;
            $items[3][5] = false;
            $items[4][5] = false;
            $items[1][6] = false;
            $items[2][6] = false;
            $items[3][6] = false;
            $items[4][6] = false;
            $items[1][7] = false;
            $items[2][7] = false;
            $items[3][7] = false;
            $items[4][7] = false;
            $items[5][7] = false;
            $items[6][7] = false;
            $items[7][7] = false;
            $items[1][8] = false;
            $items[2][8] = false;
            $items[3][8] = false;
            $items[4][8] = false;
            $items[1][9] = false;
            $items[2][9] = false;
            $items[3][9] = false;
            $items[4][9] = false;
        } else if ($world == "highschool") {
            for ($theme = 1; $theme <= 10; $theme++) {
                for ($slot = 1; $slot <= 5; $slot++) {
                    $items[$theme][$slot] = 0;
                }
            }
        }
        return $items;
    }

    /**
     * Get the item slot name.
     *
     * @param int $itemslot Item slot.
     * @param string $world World of the avatar.
     * @return string
     */
    public static function get_item_slot_name(int $itemslot, string $world): string {
        if ($world == 'school') {
            return match ($itemslot) {
                1 => get_string('leftarm', 'format_ludilearn'),
                2 => get_string('rightarm', 'format_ludilearn'),
                3 => get_string('head', 'format_ludilearn'),
                4 => get_string('face', 'format_ludilearn'),
                5 => get_string('body', 'format_ludilearn'),
                6 => get_string('others', 'format_ludilearn'),
                default => '',
            };
        } else if ($world == 'professional') {
            return match ($itemslot) {
                1 => get_string('decoration', 'format_ludilearn'),
                2 => get_string('picture', 'format_ludilearn'),
                3 => get_string('lamp', 'format_ludilearn'),
                4 => get_string('sportstuff', 'format_ludilearn'),
                5 => get_string('pet', 'format_ludilearn'),
                6 => get_string('desk', 'format_ludilearn'),
                7 => get_string('tshirt', 'format_ludilearn'),
                8 => get_string('ball', 'format_ludilearn'),
                9 => get_string('bed', 'format_ludilearn'),
                default => '',
            };
        } else if ($world == 'highschool') {
            return match ($itemslot) {
                1 => get_string('hull', 'format_ludilearn'),
                2 => get_string('sail', 'format_ludilearn'),
                3 => get_string('flag', 'format_ludilearn'),
                4 => get_string('figurehead', 'format_ludilearn'),
                5 => get_string('propulsion', 'format_ludilearn'),
                default => '',
            };
        }
    }

    /**
     * Get last item not owned.
     *
     * @return stdClass Last item not owned (theme and slot of the item).
     */
    public function get_last_item_not_owned(): stdClass {
        $lastitem = new stdClass();
        foreach ($this->itemowned as $theme => $slots) {
            foreach ($slots as $slot => $owned) {
                if (!$owned) {
                    $lastitem->theme = $theme;
                    $lastitem->slot = $slot;
                    return $lastitem;
                }
            }
        }
        return $lastitem;
    }

    /**
     * Earn an item.
     *
     * @param int $cmid The cmid.
     */
    public function earn_item(int $cmid): void {
        global $DB;
        // Retrieve attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $this->id, 'userid' => $this->userid]);

        // We can earn a new item.
        $lastitemnotowned = $this->get_last_item_not_owned();
        $itemowned = $DB->get_record('format_ludilearn_ele_user',
            [
                'attributionid' => $attribution->id,
                'name' => 'item_owned-' . $lastitemnotowned->theme . '-' . $lastitemnotowned->slot,
            ]
        );
        if ($itemowned) {
            $param = new stdClass();
            $param->id = $itemowned->id;
            $param->value = 1;
            $DB->update_record('format_ludilearn_ele_user', $param);
        } else {
            $DB->insert_record('format_ludilearn_ele_user', [
                'attributionid' => $attribution->id,
                'name' => 'item_owned-' . $lastitemnotowned->theme . '-' . $lastitemnotowned->slot,
                'value' => 1]);
        }

        // Mark the threshold as exceeded.
        $thresholdexceeded = $DB->get_record('format_ludilearn_cm_user',
            ['attributionid' => $attribution->id, 'cmid' => $cmid, 'name' => 'thresholdexceeded']);
        if ($thresholdexceeded) {
            $param = new stdClass();
            $param->id = $thresholdexceeded->id;
            $param->value = 1;
            $DB->update_record('format_ludilearn_cm_user', $param);
        } else {
            $DB->insert_record('format_ludilearn_cm_user', [
                'attributionid' => $attribution->id,
                'cmid' => $cmid,
                'name' => 'thresholdexceeded',
                'value' => 1]);
        }

        $this->cmparameters[$cmid]['thresholdexceeded'] = true;
        $this->itemowned[$lastitemnotowned->theme][$lastitemnotowned->slot] = 1;
        $this->sectionparameters['itemowned'] = $this->itemowned;
        $this->itemownedcount++;
    }

    /**
     * Get status of items owned.
     *
     * @param int $courseid The course id.
     * @param int $userid The user id.
     * @return stdClass Status of items owned.
     */
    public static function get_items_owned_status(int $courseid, int $userid): stdClass {
        global $DB;
        $count = new stdClass();
        // Get the avatar game elements.
        $avatars = $DB->get_records('format_ludilearn_elements',
            ['courseid' => $courseid, 'type' => 'avatar'], '', 'id, sectionid');

        // Get the count of items owned and the count of items ownable.
        $count->owned = 0;
        $count->ownable = 0;
        foreach ($avatars as $avatar) {
            $avatar = self::get($courseid, $avatar->sectionid, $userid);
            if ($avatar) {
                $count->ownable += $avatar->get_items_ownable_count();
                $count->owned = $avatar->get_count_items_owned();
            }
        }

        return $count;
    }

    /**
     * Get status of items owned by section
     *
     * @param int $courseid The course id.
     * @param int $userid The user id.
     * @param int $sectionid The section id.
     * @return stdClass Status of items owned.
     */
    public static function get_items_owned_status_by_section(int $courseid, int $userid, int $sectionid): stdClass {
        global $DB;
        $count = new stdClass();

        // Get the count of items owned and the count of items ownable.
        $count->owned = 0;
        $avatar = self::get($courseid, $sectionid, $userid);
        $count->ownable = $avatar->get_items_ownable_count();

        // Get count of items owned in the game element.
        $sql = "SELECT COUNT(*) as count FROM {format_ludilearn_ele_user} s
                INNER JOIN {format_ludilearn_attributio} a ON s.attributionid = a.id
                INNER JOIN {format_ludilearn_elements} g ON a.gameelementid = g.id
                WHERE g.type = 'avatar'
                AND s.name LIKE '%item_owned-%'
                AND s.value = '1'
                AND a.userid = :userid
                AND g.id = :gameelementid";
        $params = ['userid' => $userid, 'gameelementid' => $avatar->get_id()];
        $res = $DB->get_record_sql($sql, $params);
        if ($res) {
            $count->owned = $res->count;
        }

        return $count;
    }

    /**
     * Update avatar elements.
     *
     * @param int $courseid The course id.
     * @param stdClass $coursemodule The course module.
     * @param string $modulename The module name.
     * @param int $userid The user id.
     */
    public static function update_elements(int $courseid, stdClass $coursemodule, string $modulename, int $userid): void {
        global $DB;

        $manager = new manager();

        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'avatar']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {
            $gameelement = self::get($courseid, $coursemodule->section, $userid);

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

            // If the progression is greater than the threshold to earn an item.
            if ($progression >= $gameelement->get_thresholdtoearn()) {
                // If the threshold is not exceeded before.
                if ($gameelement->get_thresholdexceeded($coursemodule->id) == 0) {
                    // We can earn a new item.
                    $lastitemnotowned = $gameelement->get_last_item_not_owned();
                    $itemowned = $DB->get_record('format_ludilearn_ele_user',
                        [
                            'attributionid' => $attribution->id,
                            'name' => 'item_owned-' . $lastitemnotowned->theme . '-' . $lastitemnotowned->slot,
                        ]
                    );
                    if ($itemowned) {
                        $param = new stdClass();
                        $param->id = $itemowned->id;
                        $param->value = 1;
                        $DB->update_record('format_ludilearn_ele_user', $param);
                    } else {
                        $DB->insert_record('format_ludilearn_ele_user', [
                            'attributionid' => $attribution->id,
                            'name' => 'item_owned-' . $lastitemnotowned->theme . '-' . $lastitemnotowned->slot,
                            'value' => 1]);
                    }

                    // Mark the threshold as exceeded.
                    $thresholdexceeded = $DB->get_record('format_ludilearn_cm_user',
                        ['attributionid' => $attribution->id, 'cmid' => $coursemodule->id, 'name' => 'thresholdexceeded']);
                    if ($thresholdexceeded) {
                        $param = new stdClass();
                        $param->id = $thresholdexceeded->id;
                        $param->value = 1;
                        $DB->update_record('format_ludilearn_cm_user', $param);
                    } else {
                        $DB->insert_record('format_ludilearn_cm_user', [
                            'attributionid' => $attribution->id,
                            'cmid' => $coursemodule->id,
                            'name' => 'thresholdexceeded',
                            'value' => 1]);
                    }
                }
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

        // Get game element.
        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'avatar']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {
            $gameelement = self::get($quiz->course, $coursemodule->section, $userid);

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

            // If the progression is greater than the threshold to earn an item.
            if ($progression >= $gameelement->get_thresholdtoearn()) {
                // If the threshold is not exceeded before.
                if ($gameelement->get_thresholdexceeded($coursemodule->id) == 0) {
                    // We can earn a new item.
                    $lastitemnotowned = $gameelement->get_last_item_not_owned();
                    $itemowned = $DB->get_record('format_ludilearn_ele_user',
                        [
                            'attributionid' => $attribution->id,
                            'name' => 'item_owned-' . $lastitemnotowned->theme . '-' . $lastitemnotowned->slot,
                        ]);
                    if ($itemowned) {
                        $param = new stdClass();
                        $param->id = $itemowned->id;
                        $param->value = 1;
                        $DB->update_record('format_ludilearn_ele_user', $param);
                    } else {
                        $DB->insert_record('format_ludilearn_ele_user', [
                            'attributionid' => $attribution->id,
                            'name' => 'item_owned-' . $lastitemnotowned->theme . '-' . $lastitemnotowned->slot,
                            'value' => 1]);
                    }

                    // Mark the threshold as exceeded.
                    $thresholdexceeded = $DB->get_record('format_ludilearn_cm_user',
                        ['attributionid' => $attribution->id, 'cmid' => $coursemodule->id, 'name' => 'thresholdexceeded']);
                    if ($thresholdexceeded) {
                        $param = new stdClass();
                        $param->id = $thresholdexceeded->id;
                        $param->value = 1;
                        $DB->update_record('format_ludilearn_cm_user', $param);
                    } else {
                        $DB->insert_record('format_ludilearn_cm_user', [
                            'attributionid' => $attribution->id,
                            'cmid' => $coursemodule->id,
                            'name' => 'thresholdexceeded',
                            'value' => 1]);
                    }
                }
            }
        }
    }

    /**
     * Get a game element.
     *
     * @param int $courseid The course ID.
     * @param int $sectionid The section ID.
     * @param int $userid The user ID.
     * @return avatar|null
     */
    public static function get(int $courseid, int $sectionid, int $userid): ?avatar {
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
                'type' => 'avatar']);

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

        // Get all items owned in the course.
        $sqlitemsowned = "SELECT s.id, s.name, s.value
                    FROM {format_ludilearn_ele_user} s
                    INNER JOIN {format_ludilearn_attributio} a ON s.attributionid = a.id
                    INNER JOIN {format_ludilearn_elements} g ON a.gameelementid = g.id
                    WHERE g.type = 'avatar'
                    AND s.name LIKE 'item_owned-%'
                    AND a.userid = :userid
                    AND g.courseid = :courseid";
        $itemsownedreq = $DB->get_records_sql($sqlitemsowned, ['userid' => $userid, 'courseid' => $courseid]);

        // Set the world of the avatar.
        $format = course_get_format($courseid);
        $options = $format->get_format_options();
        $parameters['world'] = $options['world'];

        // Init items owned.
        $itemsowned = [];
        $itemsowned = self::get_empty_items($parameters['world']);

        // Set items owned.
        foreach ($itemsownedreq as $itemownedreq) {
            $item = explode('-', $itemownedreq->name);
            $theme = $item[1];
            $slot = $item[2];
            if (isset($itemsowned[$theme][$slot])) {
                $itemsowned[$theme][$slot] = $itemownedreq->value;
            }
        }
        $parameters['itemowned'] = $itemsowned;

        return new avatar($gameelementreq->gameelementid,
            $gameelementreq->courseid,
            $gameelementreq->sectionid,
            $gameelementreq->userid,
            $parameters,
            $cmparameters);
    }
}
