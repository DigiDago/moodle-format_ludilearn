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
 * Score mutations.
 *
 * @module     format_ludilearn/local/mutations
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import Ajax from 'core/ajax';
import Notification from 'core/notification';

class Mutations {
    init() {
        this._courseSyncLaunched = false;
    }

    syncCourseAuto(stateManager, courseid) {
        if (this._courseSyncLaunched) {
            return;
        }
        this._courseSyncLaunched = true;
        this._syncRunning = false;
        this._courseTimer = setInterval(() => {
            // Prevent overlap, skip if a turn is already in progress
            if (this._syncRunning) {
                return;
            }
            this._syncRunning = true;

            Ajax.call([{
                methodname: 'format_ludilearn_get_state',
                args: {ressource: 'course', ressourceid: courseid},
            }])[0]
                .then((updates) => {
                    let updatesobj = JSON.parse(updates);
                    // Update the state with the fetched data.
                    stateManager.processUpdates(updatesobj);
                })
                .catch(Notification.exception)
                .always(() => {
                    this._syncRunning = false;
                });
        }, 5000);
    }

    stopCourseAuto() {
        if (this._courseTimer) {
            clearInterval(this._courseTimer);
            this._courseTimer = null;
        }
        this._courseSyncLaunched = false;
    }

    syncSectionAuto(stateManager, sectionid) {
        this._sectionSyncRunning = false;
        this._sectionTimer = setInterval(() => {
            // Prevent overlap, skip if a turn is already in progress
            if (this._sectionSyncRunning) {
                return;
            }
            this._sectionSyncRunning = true;

            Ajax.call([{
                methodname: 'format_ludilearn_get_state',
                args: {ressource: 'section', ressourceid: sectionid},
            }])[0]
                .then((updates) => {
                    let updatesobj = JSON.parse(updates);
                    // Update the state with the fetched data.
                    stateManager.processUpdates(updatesobj);
                })
                .catch(Notification.exception)
                .always(() => {
                    this._sectionSyncRunning = false;
                });
        }, 5000);
    }

    syncCmAuto(stateManager, cmid) {
        this._cmSyncRunning = false;
        this._cmTimer = setInterval(() => {
            // Prevent overlap, skip if a turn is already in progress
            if (this._cmSyncRunning) {
                return;
            }
            this._cmSyncRunning = true;

            Ajax.call([{
                methodname: 'format_ludilearn_get_state',
                args: {ressource: 'cm', ressourceid: cmid},
            }])[0]
                .then((updates) => {
                    let updatesobj = JSON.parse(updates);
                    // Update the state with the fetched data.
                    stateManager.processUpdates(updatesobj);
                })
                .catch(Notification.exception)
                .always(() => {
                    this._cmSyncRunning = false;
                });
        }, 5000);
    }

}

export const mutations = new Mutations();