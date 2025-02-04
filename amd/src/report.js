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
 * Show the report.
 *
 * @module     format_ludilearn/report
 * @package
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'format_ludilearn/pagination', 'format_ludilearn/loading'],
    ($, Ajax, Templates, Pagination, Loading) => {
        let COURSE_ID;

        let loadReport = (newtable, contain, limit, offset, sort) => {

            // Prepare resquest.
            let data = {};
            data.courseid = COURSE_ID;
            data.contain = contain;
            data.limit = limit;
            data.offset = offset;
            data.sort = sort;

            let table = document.getElementById('tableReport');
            let tbody = table.getElementsByTagName('tbody');
            if (tbody.length > 0) {
                // Display icon loading during loading of data.
                Loading.load('tbodyReport');
            }

            // Request.
            Ajax.call([{
                methodname: 'format_ludilearn_get_report',
                args: data
            }], true, true)[0].done((response) => {

                // Render the table of programs.
                Templates.render('format_ludilearn/report/table_report', response)
                    .then((html) => {

                        if (tbody.length > 0) {
                            // Display icon loading during loading of data.
                            tbody[0].remove();
                        }
                        table.insertAdjacentHTML('beforeend', html);

                        if (newtable === true) {
                            let pagination = Math.ceil(response.countWithoutLimit / limit);
                            if (pagination === 0) {
                                pagination = 1;
                            }
                            let params = {};
                            params.contain = contain;
                            Pagination.load('pagination', pagination, callbackForPagination, params);
                        }
                    }).fail((ex) => {
                        console.error(ex);
                    }
                );
            }).fail((ex) => {
                console.error(ex);
            });
        };

        // Format callback function loading data for pagination.
        let callbackForPagination = (params) => {
            loadReport(false, params.contain, params.limit, params.offset, params.sort);
        };

        let searchReport = () => {
            $('#formSearchReport').on('submit', (event) => {
                event.preventDefault();
                let inputsearch = $('#inputSearchReport');
                let search = inputsearch.val();
                loadReport(true, search, 10, 0, 'firstname');
            });
        };

        return {
            init: (courseid) => {
                COURSE_ID = courseid;
                searchReport();
                loadReport(true, '', 10, 0, 'id');
            }
        };
    });
