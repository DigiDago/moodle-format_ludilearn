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
 * Score cm component.
 *
 * @module     format_ludilearn/local/score/cm
 * @copyright  2025 Pimenko <contact@pimenko.com>
 * @author     Jordan Kesraoui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {BaseComponent} from 'core/reactive';
import {getString} from 'core/str';

export default class extends BaseComponent {

    create(descriptor) {
        this.cmid = descriptor.data.cmid;
        this.selectors = {
            SCORE: `[data-for='ludilearn-cm-score']`,
            MAXSCORE: `[data-for='ludilearn-cm-maxscore']`,
            BONUSCOMPLETION: `[data-for='ludilearn-cm-bonuscompletion']`,
            BLOCK_BONUSCOMPLETION: `.bonuscompletion-cm`,
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
            {watch: `currentcm.bonuscompletion:updated`, handler: this._refreshBonusCompletion},
            {watch: `currentcm.score:updated`, handler: this._refreshScore},
            {watch: `currentcm.maxscore:updated`, handler: this._refreshMaxScore}
        ];
    }

    _refreshBonusCompletion({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified) {
            return;
        }

        const target = this.getElement(this.selectors.BONUSCOMPLETION);
        // Bonus completion was at 0 and is now greater than 0, we need to create the bonuscompletion element.
        if (!target && element.bonuscompletion > 0) {
            this._createBonusCompletion({element});
            return;
        }

        // If the bonuscompletion is now 0, we need to remove the bonuscompletion element.
        if (element.bonuscompletion === 0) {
            const targetcm = this.getElement(this.selectors.BLOCK_BONUSCOMPLETION);
            if (!targetcm) {
                return;
            }
            targetcm.remove();
            return;
        }

        // We have a convenience method to locate elements inside the component.
        if (!target) {
            return;
        }

        // Update the bonuscompletion.
        target.innerHTML = `+ ${element.bonuscompletion}`;
    }

    _createBonusCompletion({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified){
            return;
        }
        const target = this.getElement(this.selectors.CM);
        if (!target) {
            return;
        }

        /*
        Create the following HTML structure:
        <div class="bonuscompletion">
            + <span
                class="bonuscompletion-value"
                data-for="ludilearn-cm-bonuscompletion"
                data-id="{{id}}">{{ parameters.bonuscompletion }}
            </span>
            <span class="bonuscompletion-str">{{#str}} settings:bonuscompletion, format_ludilearn {{/str}}</span>
        </div>
        */
        const bonuscompletiondiv = document.createElement('div');
        bonuscompletiondiv.className = 'bonuscompletion bonuscompletion-cm';

        const bonuscompletionvalue = document.createElement('span');
        bonuscompletionvalue.className = 'bonuscompletion-value';
        bonuscompletionvalue.setAttribute('data-for', this.selectors.BONUSCOMPLETION);
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

    _refreshScore({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified){
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.SCORE);
        if (!target) {
            return;
        }

        // Update the score
        target.innerHTML = element.score;
    }

    _refreshMaxScore({element}) {
        // If the cm is not gamified, skip.
        if (!element.gamified){
            return;
        }
        // We have a convenience method to locate elements inside the component.
        const target = this.getElement(this.selectors.MAXSCORE);
        if (!target) {
            return;
        }

        // Update the maxscore.
        target.innerHTML = element.maxscore;
    }
}