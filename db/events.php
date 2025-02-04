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
 * Ludilearn format plugin event handler definition.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => 'format_ludilearn_observer::user_enrolment_created',
    ],
    [
        'eventname' => '\core\event\user_enrolment_updated',
        'callback' => 'format_ludilearn_observer::user_enrolment_updated',
    ],
    [
        'eventname' => '\core\event\role_assigned',
        'callback' => 'format_ludilearn_observer::role_assigned',
    ],
    [
        'eventname' => '\core\event\course_section_created',
        'callback' => 'format_ludilearn_observer::section_created',
    ],
    [
        'eventname' => '\core\event\course_section_deleted',
        'callback' => 'format_ludilearn_observer::section_deleted',
    ],
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => 'format_ludilearn_observer::course_module_created',
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => 'format_ludilearn_observer::course_deleted',
    ],
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => 'format_ludilearn_observer::course_module_deleted',
    ],
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => 'format_ludilearn_observer::course_module_updated',
    ],
    [
        'eventname' => '\core\event\user_graded',
        'callback' => 'format_ludilearn_observer::user_graded',
    ],
    [
        'eventname' => '\mod_quiz\event\attempt_updated',
        'callback' => 'format_ludilearn_observer::attempt_updated',
    ],
    [
        'eventname' => '\mod_quiz\event\attempt_deleted',
        'callback' => 'format_ludilearn_observer::attempt_deleted',
    ],
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => 'format_ludilearn_observer::attempt_submitted',
    ],
    [
        'eventname' => '\core\event\course_reset_ended',
        'callback' => 'format_ludilearn_observer::course_reset_ended',
    ],
];
