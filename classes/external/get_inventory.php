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

namespace format_ludilearn\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_course;
use external_multiple_structure;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\local\gameelements\avatar;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\manager;
use stdClass;

/**
 * Class for get inventory.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_inventory extends external_api {

    /**
     * Execute the webservice.
     *
     * @param int $courseid  Id of the course.
     * @param int $sectionid Id of the section.
     *
     * @return array The web service return.
     */
    public static function execute(int $courseid, int $sectionid): array {
        global  $USER;

        $context = context_course::instance($courseid);
        self::validate_context($context);
        $gameelement = avatar::get($courseid, $sectionid, $USER->id);

        $counttheme = $gameelement->get_count_theme();
        $maxcountslot = $gameelement->get_max_count_slot();

        // Get the items to own count.
        $ownedstatus = avatar::get_items_owned_status($courseid, $USER->id);
        $itemtoowncount = $ownedstatus->ownable - $ownedstatus->owned;
        $itemtoowncountbyslot = [];
        for ($s = 1; $s <= $maxcountslot; $s++) {
            $itemtoowncountbyslot[$s] = 0;
        }
        $t = 1;
        while ($itemtoowncount > 0 && ($t <= $counttheme)) {
            $countslotfortheme = $gameelement->get_count_slot_for_theme($t);
            for ($s = 1; $s <= $countslotfortheme; $s++) {
                $itemtoowncountbyslot[$s]++;
                $itemtoowncount--;
                if ($itemtoowncount == 0) {
                    break;
                }
            }
            $t++;
        }

        $inventory = [];
        for ($s = 1; $s <= $maxcountslot; $s++) {
            // Get item slot.
            $slot['slot'] = $s;

            // Get item slot name.
            $slot['slotname'] = avatar::get_item_slot_name($s, $gameelement->get_world());;

            $items = [];
            $countthemeforslot = $gameelement->get_count_theme_for_slot($s);
            for ($t = 1; $t <= $countthemeforslot; $t++) {
                if ($gameelement->get_type() == 'avatar' && $gameelement->get_count_cm_gamified() > 0) {
                    // Get theme.
                    $item['theme'] = $t;

                    // Get the items owned.
                    $item['owned'] = $gameelement->item_is_owned($t, $s);

                    // Get the items equiped.
                    $item['equiped'] = $gameelement->item_is_equiped($t, $s);

                    if (!$item['owned']) {
                        $itemtoowncountbyslot[$s]--;
                    }

                    if ($itemtoowncountbyslot[$s] >= 0) {
                        $items[] = $item;
                    }
                }
            }
            $slot['items'] = $items;
            $slot['world'] = $gameelement->get_world();

            $inventory[] = $slot;
        }

        return [
            'inventory' => $inventory,
        ];
    }

    /**
     * Get webservice parameters structure.
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
            'sectionid' => new external_value(
                PARAM_INT,
                'Section ID',
                VALUE_REQUIRED
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get webservice return structure.
     *
     * @return external_single_structure The webservice return structure.
     */
    public static function execute_returns(): external_single_structure {
        $keys = [
            'inventory' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'slot' => new external_value(
                            PARAM_INT,
                            'Theme',
                            VALUE_REQUIRED
                        ),
                        'slotname' => new external_value(
                            PARAM_TEXT,
                            'Slot name of the item',
                            VALUE_REQUIRED
                        ),
                        'items' => new external_multiple_structure(
                            new external_single_structure(
                                [

                                    'theme' => new external_value(
                                        PARAM_INT,
                                        'Theme',
                                        VALUE_REQUIRED
                                    ),
                                    'owned' => new external_value(
                                        PARAM_BOOL,
                                        'If the item is owned',
                                        VALUE_REQUIRED
                                    ),
                                    'equiped' => new external_value(
                                        PARAM_BOOL,
                                        'If the item is equipped',
                                        VALUE_REQUIRED
                                    ),
                                ]
                            ),
                            'Items',
                            VALUE_REQUIRED
                        ),
                        'world' => new external_value(
                            PARAM_TEXT,
                            'World',
                            VALUE_REQUIRED
                        ),
                    ]
                ),
                'Inventory of the user',
                VALUE_REQUIRED,
                ),
        ];

        return new external_single_structure(
            $keys,
            'get_inventory'
        );
    }
}
