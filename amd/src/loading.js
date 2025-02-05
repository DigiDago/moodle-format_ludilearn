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
 * Rendering loading.
 *
 * @module     format_ludilearn/loading
 * @package
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates'],
    ($, Ajax, Templates) => {

        /**
         * Loading Oject using for rendering.
         */
        function Loading(parentelementid) {
            this.parentelementid = parentelementid;
        }

        /**
         * Load loading.
         */
        Loading.prototype.load = function() {
            let that = this;

            // Render the table of programs.
            Templates.render('core/loading', {})
                .then((html) => {
                    // Add the element to the DOM.
                    let element = document.getElementById(that.parentelementid);
                    element.innerHTML = html;
                }).fail((ex) => {
                    console.error(ex);
                }
            );
        };

        /**
         * Load loading.
         */
        Loading.prototype.noResult = function() {
            let that = this;

            // Render the table of programs.
            Templates.render('format_ludilearn/report/noresult', {})
                .then((html) => {
                    // Add the element to the DOM.
                    let element = document.getElementById(that.parentelementid);
                    element.innerHTML = html;
                }).fail((ex) => {
                    console.error(ex);
                }
            );
        };

        return {
            load: (parentelementid) => {
                let load = new Loading(parentelementid);
                load.load();
            },

            noResult: (parentelementid) => {
                let load = new Loading(parentelementid);
                load.noResult();
            }
        };
    });