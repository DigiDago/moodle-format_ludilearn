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
        let COURSE_ID = 0;
        let TYPE = '';
        let PARAMETERSLIST = [];

        let submit = () => {
            $('#form-parameters').on('submit', (event) => {
                // Cancel event to customise it.
                event.preventDefault();
                let fieldEmpty = false;
                let formData = new FormData(event.target);
                PARAMETERSLIST.forEach((parameter) => {
                    if (!formData.has(parameter) || formData.get(parameter) === '') {
                        fieldEmpty = true;
                    }
                });
                if (fieldEmpty) {
                    // TODO Display error message.
                } else {
                    let data = {};
                    data.courseid = COURSE_ID;

                    if (TYPE !== 'assignmentbysection') {
                        PARAMETERSLIST.forEach((parameter) => {
                            data[parameter] = formData.get(parameter);
                        });
                    } else {
                        // If the type is assignmentbysection, we need to format the data differently.
                        // We need to create an array of sections.
                        data.sections = [];
                        PARAMETERSLIST.forEach((parameter) => {
                            let section = {};
                            // Explode the parameter to get the section id.
                            section.id = parameter.split('_')[1];
                            // Get the game element id.
                            section.gameelementid = formData.get(parameter);
                            data.sections.push(section);
                        });
                    }

                    // Call the web service to update the parameters.
                    Ajax.call([{
                        methodname: 'format_ludilearn_update_' + TYPE + '_parameters',
                        args: data
                    }], true, true)[0].done((response) => {
                        $('.editsettingssuccess').show();
                    }).fail((ex) => {
                        $('.editsettingsfailed').show();
                        console.error(ex);
                    });
                }

            });
        };

        return {
            init: (courseid, type, parameterslist) => {
                COURSE_ID = courseid;
                TYPE = type;
                PARAMETERSLIST = parameterslist;
                submit();
            }
        };
    });
