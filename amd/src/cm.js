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

/**
 * Format ludilearn section extra logic component.
 *
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core_courseformat/local/courseeditor/dndcmitem',
    'core_courseformat/courseeditor',
    'core/templates',
    'core/str'],
    function(DndCmItem, CourseEditor, Templates, Str) {
    class GamifyCm extends DndCmItem {
        /**
         * Constructor hook.
         */
        create() {
            this.name = 'format_ludilearn_cm';
            this.selectors = {
                CM: '[data-for="cm"]',
                GAMIFY: '[data-action="cmGamify"]',
                NOTGAMIFY: '[data-action="cmNotgamify"]',
                ACTIONTEXT: '.menu-action-text',
                ICON: '.icon'
            };
            this.classes = {
                HIDE: 'd-none',
            };
            this.formatActions = {
                GAMIFY: 'cmGamify',
                NOTGAMIFY: 'cmNotgamify',
            };
            this.icons = {
                ICON_GAMIFIED: 'i/checkedcircle',
                ICON_NOTGAMIFIED: 'i/uncheckedcircle'
            };
            this.id = this.element.dataset.id;
        }

        /**
         * Component watchers.
         *
         * @returns {Array} of watchers
         */
        getWatchers() {
            return [
                {watch: `cm[${this.id}]:updated`, handler: this._refreshGamify},
            ];
        }

        /**
         * Update a content cm using the state information.
         *
         * @param {object} param
         * @param {Object} param.element details the update details.
         */
        async _refreshGamify({element}) {
            let newAction;
            let swapicon;
            if (element.gamified === true) {
                newAction = this.formatActions.NOTGAMIFY;
                swapicon = this.icons.ICON_NOTGAMIFIED;
            } else {
                newAction = this.formatActions.GAMIFY;
                swapicon = this.icons.ICON_GAMIFIED;
            }
            this.element.dataset.action = newAction;
            const actionText = this.element.querySelector(this.selectors.ACTIONTEXT);
            if (this.element.dataset.swapname && actionText) {
                const oldText = actionText.innerText;
                actionText.innerText = this.element.dataset.swapname;
                this.element.dataset.swapname = oldText;
            }
            const icon = this.element.querySelector(this.selectors.ICON);
            if (this.element.dataset.swapicon && icon) {
                const newIcon = this.element.dataset.swapicon;
                if (newIcon) {
                    const pixHtml = await Templates.renderPix(newIcon, 'core');
                    Templates.replaceNode(icon, pixHtml, '');
                }
                this.element.dataset.swapicon = swapicon;
            }
        }
    }

    return {
        init: function() {
            const courseEditor = CourseEditor.getCurrentCourseEditor();
            if (courseEditor.supportComponents && courseEditor.isEditing) {
                let elements = document.getElementsByClassName('editing_gamify');
                elements.forEach((element) => {
                    new GamifyCm({
                        element: element,
                        reactive: courseEditor,
                    });
                });
            }
        }
    };
});

