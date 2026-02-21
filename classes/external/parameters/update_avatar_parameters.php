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
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\local\gameelements\avatar;

/**
 * Class for update course parameters for avatar elements.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_avatar_parameters extends external_api {
    /**
     * Execute the webservice.
     *
     * @param int $courseid  The ID of the course to update.
     * @param int $thresholdtoearn The threshold to earn item.
     *
     * @return array The web service return.
     */
    public static function execute(int $courseid, int $thresholdtoearn): array {

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('format/ludilearn:manage', $context);

        return [
                'success' => avatar::update_course_parameters($courseid, $thresholdtoearn),
        ];
    }

    /**
     * Get the webservice parameters structure.
     *
     * @return external_function_parameters The webservice parameters structure.
     */
    public static function execute_parameters(): external_function_parameters {
        $parameters = [
            'courseid' => new external_value(
                PARAM_INT,
                'Course ID',
                VALUE_REQUIRED
            ),
            'thresholdtoearn' => new external_value(
                PARAM_INT,
                'Threshold to earn item',
                VALUE_REQUIRED
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get the webservice return structure.
     *
     * @return external_single_structure The webservice return structure.
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
            'update_avatar_parameters'
        );
    }
}
