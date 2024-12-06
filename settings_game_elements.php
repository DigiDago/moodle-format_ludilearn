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
 * Format Ludimoodle plugin settings page.
 *
 * @package          format_ludimoodle
 * @copyright        2023 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use format_ludimoodle\output\settings;

require('../../../config.php');
require_once('lib.php');
global $PAGE, $CFG;
$course = get_course(required_param('id', PARAM_INT));
$type = optional_param('type', 'score', PARAM_TEXT);
context_helper::preload_course($course->id);
$context = context_course::instance($course->id, MUST_EXIST);
require_login($course);
has_capability('moodle/course:update', $context);
$params = ['id' => $course->id, 'type' => $type, 'hideheader' => true];
$PAGE->set_pagelayout('course');
$PAGE->set_url(new moodle_url("$CFG->wwwroot/course/format/ludimoodle/settings_game_elements.php", $params));
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursetitle', 'moodle', array('course' => $course->fullname)));
$PAGE->set_heading(
    $course->fullname . ' : ' .
    get_string('editgameeleements', 'format_ludimoodle')
    );
$PAGE->add_body_class('limitedwidth');
$format = course_get_format($course);
$course->format = $format->get_format();
$PAGE->set_pagetype('course-view-' . $course->format);

$renderer = $PAGE->get_renderer('format_ludimoodle', 'settings');

// Renderer settings.
echo $OUTPUT->header();
$settings = new settings($course->id, $type);
echo $renderer->render_settings($settings);
echo $OUTPUT->footer();

