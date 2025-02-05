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

use core_courseformat\output\section_renderer;
use format_ludilearn\manager;
use html_writer;
use moodle_page;
use plugin_renderer_base;
use renderable;
use section_info;
use stdClass;

/**
 * Ludilearn Plus game element renderer.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludilearn_gameelement_renderer extends plugin_renderer_base {
    /**
     * Render the game element.
     *
     * @param format_ludilearn_gameelement $formatludilearngameelement
     *
     * @return mixed HTML output
     */
    protected function render_format_ludilearn_gameelement(format_ludilearn_gameelement $formatludilearngameelement): mixed {
        return null;
    }
}
