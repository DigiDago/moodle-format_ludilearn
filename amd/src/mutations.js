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
 * Format topics mutations.
 *
 * An instance of this class will be used to add custom mutations to the course editor.
 * To make sure the addMutations method find the proper functions, all functions must
 * be declared as class attributes, not a simple methods. The reason is because many
 * plugins can add extra mutations to the course editor.
 *
 * @module     format_ludilearn/mutations
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// AMD module definition for LudilearnMutations
define(['core_courseformat/courseeditor',
    'core_courseformat/local/courseeditor/mutations',
    'core_courseformat/local/content/actions'],
    function(CourseEditor, DefaultMutations, CourseActions) {
    class LudilearnMutations extends DefaultMutations {
        /**
         * Gamify course modules.
         *
         * @param {StateManager} stateManager the current state manager
         * @param {array} cmids the list of cm ids to move
         */
        cmGamify = async function(stateManager, cmids) {
            stateManager.setReadOnly(false);
            cmids.forEach((id) => {
                const element = stateManager.get('cm', id);
                if (element) {
                    element.gamified = true;
                }
            });
            const course = stateManager.get('course');
            const updates = await this._callEditWebservice('cm_gamify', course.id, cmids);
            stateManager.processUpdates(updates, {});
        };

        /**
         * Not gamify course modules.
         *
         * @param {StateManager} stateManager the current state manager
         * @param {array} cmids the list of cm ids to move
         */
        cmNotgamify = async function(stateManager, cmids) {
            stateManager.setReadOnly(false);
            cmids.forEach((id) => {
                const element = stateManager.get('cm', id);
                if (element) {
                    element.gamified = false;
                }
            });
            const course = stateManager.get('course');
            const updates = await this._callEditWebservice('cm_notgamify', course.id, cmids);
            stateManager.processUpdates(updates, {});
        };
    }

    // Export the module with initialization logic
    return {
        init: function() {
            const courseEditor = CourseEditor.getCurrentCourseEditor();
            // Some plugin (activity or block) may have their own mutations already registered.
            // This is why we use addMutations instead of setMutations here.
            courseEditor.addMutations(new LudilearnMutations());
            // Add direct mutation content actions.

            CourseActions.addActions({
                cmGamify: 'cmGamify',
                cmNotgamify: 'cmNotgamify'
            });
        }
    };
});

