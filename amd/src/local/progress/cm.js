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
 * Progress cm component.
 *
 * @module     format_ludilearn/local/progress/cm
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {BaseComponent} from 'core/reactive';

export default class extends BaseComponent {

    create(descriptor) {
        this.cmid = descriptor.data.cmid;
        this.selectors = {
            PROGRESSION: `[data-for='ludilearn-cm-progression']`,
            CIRCLE_PROGRESSION: `[data-for='ludilearn-cm-circleprogression']`,
        };
    }

    static init(target, reactive, data) {
        return new this({
            element: document.getElementById(target),
            reactive: reactive,
            data: data,
        });
    }

    stateReady() {
        // Dispach auto refresh.
        this.reactive.dispatch('syncCmAuto', this.cmid);
    }

    getWatchers() {
        return [
            {watch: `currentcm.progression:updated`, handler: this._refreshProgression},
        ];
    }

    _refreshProgression({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified) {
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.PROGRESSION);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.progression + '%';

        const circleTarget = this.getElement(this.selectors.CIRCLE_PROGRESSION);
        if (!circleTarget) {
            return;
        }
        circleTarget.className = 'circle-progression c100 biggest ludilearn p' + element.progression;
    }
}