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
 * Score section component.
 *
 * @module     format_ludilearn/local/score/section
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {BaseComponent} from 'core/reactive';
import {getString} from 'core/str';

export default class extends BaseComponent {

    create(descriptor) {
        this.sectionid = descriptor.data.sectionid;
        this.cms = descriptor.data.cms;
        this.selectors = {
            SCORE: `[data-for='ludilearn-section-score']`,
            MAXSCORE: `[data-for='ludilearn-section-maxscore']`,
            SUMMARY: `[data-for='ludilearn-section-summary']`,
            CM: `[data-for='ludilearn-section-cm']`,
            CM_LABELTEXT: `[data-for='ludilearn-section-cm-labeltext']`,
            CM_BONUSCOMPLETION: `[data-for='ludilearn-section-cm-bonuscompletion']`,
            CM_BLOCK_BONUSCOMPLETION: `.bonuscompletion`,
            CM_NAME: `[data-for='ludilearn-section-cm-name']`,
            CM_SCORE: `[data-for='ludilearn-section-cm-score']`,
            CM_MAXSCORE: `[data-for='ludilearn-section-cm-maxscore']`,
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
            {watch: 'currentsection.score:updated', handler: this._refreshScore},
            {watch: 'currentsection.maxscore:updated', handler: this._refreshMaxScore},
            {watch: 'currentsection.summary:updated', handler: this._refreshSummary},
            {watch: `cms.labeltext:updated`, handler: this._refreshCMLabelText},
            {watch: `cms.bonuscompletion:updated`, handler: this._refreshCMBonusCompletion},
            {watch: `cms.score:updated`, handler: this._refreshCMSCore},
            {watch: `cms.maxscore:updated`, handler: this._refreshCMMaxSCore},
            {watch: `cms.name:updated`, handler: this._refreshCMName}
        ];
    }

    _refreshScore({element}) {
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.SCORE);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.score;
    }

    _refreshMaxScore({element}) {
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.MAXSCORE);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.maxscore;
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

    _refreshCMBonusCompletion({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified) {
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.CM_BONUSCOMPLETION, element.id);

        // Bonus completion was at 0 and is now greater than 0, we need to create the bonuscompletion element.
        if (!target && element.bonuscompletion > 0) {
            this._createCMBonusCompletion({element});
            return;
        }

        // If the bonuscompletion is now 0, we need to remove the bonuscompletion element.
        if (element.bonuscompletion === 0) {
            const targetcm = this.getElement(this.selectors.CM_BLOCK_BONUSCOMPLETION);
            if (!targetcm) {
                return;
            }
            targetcm.remove();
            return;
        }

        if (!target) {
            return;
        }

        // Update the bonuscompletion
        target.innerHTML = `+ ${element.bonuscompletion}`;
    }

    _createCMBonusCompletion({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified){
            return;
        }
        const target = this.getElement(this.selectors.CM, element.id);
        if (!target) {
            return;
        }

        /*
        Create the following HTML structure:
        <div class="bonuscompletion">
            + <span
                class="bonuscompletion-value"
                data-for="ludilearn-section-cm-bonuscompletion"
                data-id="{{id}}">{{ parameters.bonuscompletion }}
            </span>
            <span
                class="bonuscompletion-str">{{#str}}
                settings:bonuscompletion, format_ludilearn {{/str}}
            </span>
        </div>
        */
        const bonuscompletiondiv = document.createElement('div');
        bonuscompletiondiv.className = 'bonuscompletion';

        const bonuscompletionvalue = document.createElement('span');
        bonuscompletionvalue.className = 'bonuscompletion-value';
        bonuscompletionvalue.setAttribute('data-for', this.selectors.CM_BONUSCOMPLETION);
        bonuscompletionvalue.setAttribute('data-id', element.id);
        bonuscompletionvalue.innerHTML = `+ ${element.bonuscompletion}`;

        const bonuscompletionstr = document.createElement('span');
        bonuscompletionstr.className = 'bonuscompletion-str';
        getString('settings:bonuscompletion', 'format_ludilearn').then((str) => {
            bonuscompletionstr.innerHTML = str;
        });

        bonuscompletiondiv.appendChild(bonuscompletionvalue);
        bonuscompletiondiv.appendChild(bonuscompletionstr);
        target.querySelector('.ludilearn-img-bag').after(bonuscompletiondiv);
    }

    _refreshCMSCore({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified){
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.CM_SCORE, element.id);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.score;
    }

    _refreshCMMaxSCore({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified){
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.CM_MAXSCORE, element.id);
        if (!target) {
            return;
        }

        // Update the maxscore.
        target.innerHTML = element.maxscore;
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