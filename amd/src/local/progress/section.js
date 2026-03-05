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
 * Progress section component.
 *
 * @module     format_ludilearn/local/progress/section
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {BaseComponent} from 'core/reactive';
import * as config from 'core/config';

export default class extends BaseComponent {

    create(descriptor) {
        this.sectionid = descriptor.data.sectionid;
        this.cms = descriptor.data.cms;
        this.selectors = {
            IMG_PLANET_STEP: `[data-for='ludilearn-section-img-planetstep']`,
            PROGRESSION: `[data-for='ludilearn-section-progression']`,
            SUMMARY: `[data-for='ludilearn-section-summary']`,
            CM_LABELTEXT: `[data-for='ludilearn-section-cm-labeltext']`,
            CM_NAME: `[data-for='ludilearn-section-cm-name']`,
            CM_PROGRESSION: `[data-for='ludilearn-section-cm-progression']`,
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
        this.reactive.dispatch('syncSectionAuto', this.sectionid);
    }

    getWatchers() {
        return [
            {watch: 'currentsection.step:updated', handler: this._refreshStep},
            {watch: 'currentsection.progression:updated', handler: this._refreshProgression},
            {watch: 'currentsection.summary:updated', handler: this._refreshSummary},
            {watch: `cms.labeltext:updated`, handler: this._refreshCMLabelText},
            {watch: `cms.progression:updated`, handler: this._refreshCMProgression},
            {watch: `cms.name:updated`, handler: this._refreshCMName}
        ];
    }

    _refreshStep({element}) {
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.IMG_PLANET_STEP);
        if (!target) {
            return;
        }

        const namefile = 'progression-step-' + element.step + '.svg';
        // Change img src.
        target.src = config.wwwroot + '/course/format/ludilearn/pix/' + element.world + '/progress/' + namefile;
    }

    _refreshProgression({element}) {
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.PROGRESSION);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.progression + '%';
    }

    _refreshSummary({element}) {
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.SUMMARY);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.summary;
    }

    _refreshCMLabelText({element}) {
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.CM_LABELTEXT, element.id);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.labeltext;
    }

    _refreshCMProgression({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified) {
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.CM_PROGRESSION, element.id);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.progression + '%';
    }

    _refreshCMName({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified){
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.CM_NAME, element.id);
        if (!target) {
            return;
        }

        // Update the name.
        target.innerHTML = element.name;
    }

}