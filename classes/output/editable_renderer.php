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

namespace format_ludilearn\output;

use core_reportbuilder\external\filters\set;
use format_ludilearn\local\gameelements\game_element;
use moodle_url;
use plugin_renderer_base;

/**
 * Renderer for the Ludilearn editable.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editable_renderer extends plugin_renderer_base {
    /**
     * Render the element types editable.
     *
     * @param element_types_editable $element the element to render
     *
     * @return string the rendered element
     */
    public function render_element_types_editable(element_types_editable $element): string {
        return $this->render_from_template('core/inplace_editable', $element->export_for_template($this));
    }
}
