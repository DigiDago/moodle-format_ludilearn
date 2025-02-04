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

namespace format_ludilearn\courseformat;

use context_course;
use context_module;
use core_courseformat\stateactions as stateactions_base;
use core_courseformat\stateupdates;
use format_ludilearn\local\gameelements\game_element;
use stdClass;

/**
 * Contains the core course state actions specific to Ludilearn format.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stateactions extends stateactions_base {

    /**
     * Gamify coures module.
     *
     * @param stateupdates $updates the affected course elements track
     * @param stdClass $course      the course object
     * @param int[] $ids            cm ids
     *
     * @throws \moodle_exception
     */
    public function cm_gamify(
        stateupdates $updates,
        stdClass $course,
        array $ids = []
    ): void {
        // Validate the course and cm ids with manageactivities capability and admin capability.
        $this->validate_cms($course, $ids, __FUNCTION__, ['moodle/course:update']);
        $modinfo = get_fast_modinfo($course);

        foreach ($ids as $id) {
            $cm = $modinfo->get_cm($id, MUST_EXIST);
            if ($cm) {
                game_element::gamify($course->id, $cm->id);
                $updates->add_cm_put($cm->id);
            }
        }
    }

    /**
     * Not gamify coures module.
     *
     * @param stateupdates $updates the affected course elements track
     * @param stdClass $course      the course object
     * @param int[] $ids            optional extra cm ids to refresh
     *
     * @throws \moodle_exception
     */
    public function cm_notgamify(
        stateupdates $updates,
        stdClass $course,
        array $ids = []
    ): void {

        $this->validate_cms($course, $ids, __FUNCTION__, ['moodle/course:update']);
        $modinfo = get_fast_modinfo($course);

        foreach ($ids as $id) {
            $cm = $modinfo->get_cm($id, MUST_EXIST);
            if ($cm) {
                game_element::not_gamify($course->id, $cm->id);
                $updates->add_cm_put($cm->id);
            }
        }
    }
}
