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

namespace format_ludilearn\output;

use context_course;
use core_external\external_api;
use coding_exception;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\manager;
use stdClass;

/**
 * Class to display list of game element types.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element_types_editable extends \core\output\inplace_editable {
    /** @var string $typeassigned */
    private $typeassigned;

    /** @var array $types */
    private $types = [];

    /**
     * Constructor.
     *
     * @param stdClass $course The current course
     * @param stdClass $user The current user
     * @param string $typeassigned The type assigned to the user.
     * @param bool $manuallyassigned Whether the type was manually assigned.
     */
    public function __construct(stdClass $course, $user, string $typeassigned, bool $manuallyassigned) {

        $manager = new manager();
        $context = context_course::instance($course->id);
        // Check capabilities to get editable value.
        $editable = has_capability('moodle/course:update', $context);

        // Invent an itemid.
        $itemid = $course->id . ':' . $user->id;
        $this->types = game_element::get_all_types();
        $this->typeassigned = $typeassigned;
        if (empty($this->typeassigned)) {
            $this->typeassigned = 'default';
        }
        $formattedtypeassigned = format_string(get_string($this->typeassigned, 'format_ludilearn'));
        parent::__construct('format_ludilearn', 'element_types', $itemid, $editable, $formattedtypeassigned, $this->typeassigned);

        $options = [];
        // If manually assigned, add default option.
        if ($manuallyassigned) {
            $options['default'] = format_string(get_string('default', 'format_ludilearn'));
        }
        foreach ($this->types as $type) {
            $options[$type] = format_string(get_string($type, 'format_ludilearn'));
        }

        $fullname = htmlspecialchars(fullname($user), ENT_QUOTES, 'utf-8');
        $this->edithint = get_string('editgameeleement', 'format_ludilearn');
        $this->editlabel = get_string('editgameeleement', 'format_ludilearn');

        $attributes = ['multiple' => false];
        $this->set_type_select($options);
    }


    /**
     * Updates the value in database and returns itself, called from inplace_editable callback
     *
     * @param int $itemid
     * @param mixed $newvalue
     * @return \self
     */
    public static function update($itemid, $newvalue) {
        global $DB;

        // Check caps.
        // Do the thing.
        // Return one of me.
        // Validate the inputs.
        [$courseid, $userid] = explode(':', $itemid, 2);

        $courseid = clean_param($courseid, PARAM_INT);
        $userid = clean_param($userid, PARAM_INT);
        $type = clean_param($newvalue, PARAM_TEXT);

        // Check user is enrolled in the course.
        $context = context_course::instance($courseid);
        external_api::validate_context($context);

        // Check permissions.
        $editable = has_capability('moodle/course:update', $context);

        if (!is_enrolled($context, $userid)) {
            throw new coding_exception('User does not belong to the course');
        }

        // Check if the type is valid.
        $alltypes = game_element::get_all_types();
        if (!in_array($type, $alltypes) && $type != 'default') {
            throw new coding_exception('Invalid game element type');
        }

        $manager = new manager();
        $manuallyassigned = false;
        if ($type == 'default') {
            // Remove any manual assignment.
            $manager->remove_game_element_manually_assignment($courseid, $userid);
            $type = $manager->get_element_type($courseid, $userid);
        } else {
            // Assign the type to the user.
            $manager->assign_game_element_manually($courseid, $userid, $type);
            $manuallyassigned = true;
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        if (!$type) {
            $type = 'nogamified';
        }
        return new self($course, $user, $type, $manuallyassigned);
    }
}
