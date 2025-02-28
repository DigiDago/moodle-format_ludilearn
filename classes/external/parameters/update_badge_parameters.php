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

namespace format_ludilearn\external\parameters;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_course;
use core_external\restricted_context_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\local\gameelements\badge;

/**
 * Class for update course parameters for badge elements.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_badge_parameters extends external_api {

    /**
     * Executes the webservice.
     *
     * @param int $courseid    The ID of the course to update.
     * @param int $badgegold   The gold badge value.
     * @param int $badgesilver The silver badge value.
     * @param int $badgebronze The bronze badge value.
     *
     * @return array The web service return.
     */
    public static function execute(int $courseid, int $badgegold, int $badgesilver, int $badgebronze): array {

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('format/ludilearn:manage', $context);

        return [
            'success' => badge::update_course_parameters($courseid, $badgegold, $badgesilver, $badgebronze),
        ];
    }

    /**
     * Get the webservice parameters structure.
     *
     * @return external_function_parameters The parameters structure.
     */
    public static function execute_parameters(): external_function_parameters {
        $parameters = [
            'courseid' => new external_value(
                PARAM_INT,
                'Course ID',
                VALUE_REQUIRED
            ),
            'badgegold' => new external_value(
                PARAM_INT,
                'Badge Gold',
                VALUE_REQUIRED
            ),
            'badgesilver' => new external_value(
                PARAM_INT,
                'Badge Silver',
                VALUE_REQUIRED
            ),
            'badgebronze' => new external_value(
                PARAM_INT,
                'Badge Bronze',
                VALUE_REQUIRED
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get the webservice return structure.
     *
     * @return external_single_structure The return structure.
     */
    public static function execute_returns(): external_single_structure {
        $keys = [
            'success' => new external_value(
                PARAM_BOOL,
                'Success of the update',
                VALUE_REQUIRED
            ),
        ];

        return new external_single_structure(
            $keys,
            'update_badge_parameters'
        );
    }
}
