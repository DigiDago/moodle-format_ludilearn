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
 * External functions for format_ludilearn.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'format_ludilearn_update_cm_parameters' => [
        'classname' => 'format_ludilearn\external\update_cm_parameters',
        'description' => 'Update course module parameters.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_submit_questionnaire' => [
        'classname' => 'format_ludilearn\external\submit_questionnaire',
        'description' => 'Submit questionnaire.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_get_inventory' => [
        'classname' => 'format_ludilearn\external\get_inventory',
        'description' => 'Get inventory.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_set_item_equiped' => [
        'classname' => 'format_ludilearn\external\set_item_equiped',
        'description' => 'Set item equiped.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_get_report' => [
        'classname' => 'format_ludilearn\external\get_report',
        'description' => 'Get report.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_update_score_parameters' => [
        'classname' => 'format_ludilearn\external\parameters\update_score_parameters',
        'description' => 'Update course parameters for score element',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_update_badge_parameters' => [
        'classname' => 'format_ludilearn\external\parameters\update_badge_parameters',
        'description' => 'Update course parameters for badge element',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_update_timer_parameters' => [
        'classname' => 'format_ludilearn\external\parameters\update_timer_parameters',
        'description' => 'Update course parameters for timer element',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_update_assignmentbysection_parameters' => [
        'classname' => 'format_ludilearn\external\parameters\update_assignmentbysection_parameters',
        'description' => 'Update course parameters for assignment by section element',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'format_ludilearn_update_updateprogression_parameters' => [
        'classname' => 'format_ludilearn\external\parameters\update_updateprogression_parameters',
        'description' => 'Update course parameters for update progression element',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
