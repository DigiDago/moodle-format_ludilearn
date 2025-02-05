// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope this it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settongs.
 *
 * @module      format_ludilearn/settings
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/str'],
    ($, Ajax) => {

        return {
            init: (timestart) => {
                let currentime = Math.floor(Date.now() / 1000) - parseInt(timestart);
                let timerElements = $('.current-time');
                timerElements.html();
                setInterval(() => {
                    let minutes = parseInt(currentime / 60, 10);
                    let secondes = parseInt(currentime % 60, 10);
                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    secondes = secondes < 10 ? "0" + secondes : secondes;
                    timerElements.html(minutes + ':' + secondes);
                    currentime++;
                }, 1000);
            }
        };
    });
