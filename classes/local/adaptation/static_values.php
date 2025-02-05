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

namespace format_ludilearn\local\adaptation;

defined('MOODLE_INTERNAL') || die();

require_once('static_values.php');

/**
 * Hexad scores class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui, Nihal Ouherrou
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_values {
    /**
     * Static rules for the game elements.
     *
     * @return array[] Array of arrays containing the static rules for the game elements.
     */
    public static function static_rules_blank(): array {
        return [
            "avatar" => [
                "achiever" => 0,
                "player" => 0,
                "socialiser" => 0,
                "freespirit" => 0,
                "disruptor" => 0,
                "philantropist" => 0,
            ],
            "badge" => [
                "achiever" => 0,
                "player" => 0,
                "socialiser" => 0,
                "freespirit" => 0,
                "disruptor" => 0,
                "philantropist" => 0,
            ],
            "progress" => [
                "achiever" => 0,
                "player" => 0,
                "socialiser" => 0,
                "freespirit" => 0,
                "disruptor" => 0,
                "philantropist" => 0,
            ],
            "ranking" => [
                "achiever" => 0,
                "player" => 0,
                "socialiser" => 0,
                "freespirit" => 0,
                "disruptor" => 0,
                "philantropist" => 0,
            ],
            "score" => [
                "achiever" => 0,
                "player" => 0,
                "socialiser" => 0,
                "freespirit" => 0,
                "disruptor" => 0,
                "philantropist" => 0,
            ],
            "timer" => [
                "achiever" => 0,
                "player" => 0,
                "socialiser" => 0,
                "freespirit" => 0,
                "disruptor" => 0,
                "philantropist" => 0,
            ],
        ];
    }

    /**
     * Game elements list.
     *
     * @return string[] Array of the different game elements.
     */
    public static function game_elements_list(): array {
        return ["avatar", "badge", "progress", "ranking", "score", "timer"];
    }

    /**
     * Hexad profile list.
     *
     * @return string[] Array of the different types of motivation profiles in the Hexad model.
     */
    public static function hexad_profile(): array {
        return ["achiever", "player", "socialiser", "freespirit", "disruptor", "philanthropist"];
    }

    /**
     * Hexad profile values.
     *
     * @return int[] Array of the different values for the motivation profiles in the Hexad model.
     */
    public static function hexad_profile_values(): array {
        return [
            "achiever" => 0,
            "player" => 0,
            "socialiser" => 0,
            "freespirit" => 0,
            "disruptor" => 0,
            "philanthropist" => 0,
        ];
    }

    /**
     * Affinity matrix file.
     *
     * @return string Path to the affinity matrix file.
     */
    public static function hexad_affinity_matrix(): string {
        global $CFG;
        $dirrules = $CFG->dirroot . "/course/format/ludilearn/classes/local/adaptation/";
        return $dirrules . "rules/hexad_affinity_matrix.json";
    }
}
