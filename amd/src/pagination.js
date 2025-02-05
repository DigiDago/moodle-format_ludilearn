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
 * Rendering pagination.
 *
 * @module     format_ludilearn/pagination
 * @package
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates'],
    ($, Ajax, Templates) => {

        /**
         * Pagination Oject using for rendering.
         */
        function Pagination(elementid, pagination) {
            this.elementid = elementid;
            this.sort = 'id';
            this.limit = 10;
            this.pagination = pagination;
            this.currentpage = 1;
        }

        /**
         * Load the pagination element.
         */
        Pagination.prototype.load = function(loadData, params) {
            let that = this;

            // Construct the template.
            let template = {};
            template.pages = [];

            // Disable the button Next when there is only one page.
            template.onepage = (this.pagination === 1);

            // Create the page.
            for (let i = 1; i <= this.pagination; i++) {
                let page = {};
                page.number = i;
                template.pages.push(page);

                // Set the first page at active.
                page.active = (i === 1);
            }

            // Render the table of programs.
            Templates.render('format_ludilearn/report/pagination', template)
                .then((html) => {
                    // Add the element to the DOM.
                    let element = document.getElementById(that.elementid);
                    element.innerHTML = html;

                    // Set the current page to the first page.
                    that.currentpage = 1;

                    // Load events of pagination.
                    that.chargeEventsPagination(loadData, params);
                }).fail((ex) => {
                    console.error(ex);
                }
            );
        };

        Pagination.prototype.changeCurrentPage = function(page, loadData, params) {
            if (page !== this.currentpage && page <= this.pagination && page > 0) {
                this.currentpage = page;
            }

            // Put limit, offset, sort in the params of the callback function loading data.
            params.limit = this.limit;
            params.offset = this.limit * (this.currentpage - 1);
            params.sort = this.sort;
            loadData(params);
        };

        Pagination.prototype.chargeEventsPagination = function(loadData, params) {
            let that = this;

            $('.page-link').on('click', (event) => {
                event.preventDefault();
                let link = event.currentTarget;

                // If the link is not disabled.
                if (link.getAttribute('tabindex') !== -1) {
                    let id = link.id;
                    let number = $(event.currentTarget).attr("data-id");
                    let nextCurrentPage = that.currentpage;

                    if (id === 'page-link-previous') { // Next page link case.
                        nextCurrentPage--;
                    } else if (id === 'page-link-next') { // Previous page link case.
                        nextCurrentPage++;
                    } else { // Number page link case.
                        nextCurrentPage = parseInt(number);
                    }

                    if (nextCurrentPage !== that.currentpage) {
                        // Change the active link.
                        $('#page-link-' + that.currentpage).parent().removeClass('active');
                        $('#page-link-' + nextCurrentPage).parent().addClass('active');

                        // If the new current page is mor than 1.
                        let previouslink = $('#page-link-' + 'previous');
                        if (nextCurrentPage > 1 && that.currentpage === 1) {
                            // Enable the previous page link.
                            previouslink.parent().removeClass('disabled');
                            previouslink.attr('tabindex', 0);
                        } else if (nextCurrentPage === 1) {
                            // Disable the previous page link.
                            previouslink.parent().addClass('disabled');
                            previouslink.attr('tabindex', -1);
                        }

                        // If the new current page is equal to the last page.
                        let nextlink = $('#page-link-' + 'next');
                        if (nextCurrentPage < that.pagination && that.currentpage === that.pagination) {
                            // Disable the previous page link.
                            nextlink.parent().removeClass('disabled');
                            nextlink.attr('tabindex', 0);
                        } else if (nextCurrentPage === that.pagination) {
                            // Disable the previous page link.
                            nextlink.parent().addClass('disabled');
                            nextlink.attr('tabindex', -1);
                        }

                        that.changeCurrentPage(nextCurrentPage, loadData, params);
                    }

                }
            });
        };

        return {
            load: (elementid, pagination, loadData, params) => {
                let pag = new Pagination(elementid, pagination);
                pag.load(loadData, params);
            }
        };
    });