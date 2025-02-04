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
 *  Display the whole course.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

global $DB, $PAGE, $USER;

// Retrieve course format option fields and add them to the $course object.
$format = course_get_format($course);
$course = $format->get_course();
$context = context_course::instance($course->id);
$options = $format->get_format_options();

if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

// Make sure section 0 is created.
course_create_sections_if_missing($course, 0);

if ($options['assignment'] == 'automatic') {
    $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $USER->id]);
    if (!$profile) {
        // Verify if the user is a teacher or a manager.
        if (!has_capability('moodle/course:update', $context)) {
            // If the user is not a teacher or a manager, redirect him to the questionnaire page.
            redirect(new moodle_url("$CFG->wwwroot/course/format/ludilearn/questionnaire.php", ['id' => $course->id]));
        }
    }
}

$renderer = $PAGE->get_renderer('format_ludilearn');

if (!empty($displaysection)) {
    $format->set_section_number($displaysection);
}

// Check if edition mode is enabled.
if ($PAGE->user_is_editing()) {
    $outputclass = $format->get_output_classname('content');
    $widget = new $outputclass($format);
    echo $renderer->render($widget);
}

// Include any format js module here using $PAGE->requires->js.
