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

/**
 * Format Ludimoodle plugin game profile page.
 *
 * @package          format_ludimoodle
 * @copyright        2024 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $OUTPUT, $USER, $DB;
require('../../../config.php');
require_once('lib.php');

$course = get_course(required_param('id', PARAM_INT));
context_helper::preload_course($course->id);
$context = context_course::instance($course->id, MUST_EXIST);
require_login($course);
$params = ['id' => $course->id];
$PAGE->set_pagelayout('course');
$PAGE->set_url(new moodle_url("$CFG->wwwroot/course/format/ludimoodle/questionnaire.php", $params));
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursetitle', 'moodle', ['course' => $course->fullname]));
$PAGE->set_heading(
    $course->fullname . ' : ' .
    get_string('gameprofile', 'format_ludimoodle')
);
$PAGE->add_body_class('limitedwidth');
$format = course_get_format($course);
$course->format = $format->get_format();

$renderer = $PAGE->get_renderer('format_ludimoodle');

echo $OUTPUT->header();

// If user has not already answered the questionnaire, redirect to the questionnaire page.
$profile = $DB->record_exists('ludimoodle_profile', ['userid' => $USER->id]);
if (!$profile) {
    redirect(new moodle_url("$CFG->wwwroot/course/format/ludimoodle/questionnaire.php", ['id' => $course->id]));
}
echo $renderer->render_gameprofile($course->id);

echo $OUTPUT->footer();

