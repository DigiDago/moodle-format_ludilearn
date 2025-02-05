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
 * Questionnaire.
 *
 * @module      format_ludilearn/questionnaire
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/str'],
    ($, Ajax, Str) => {
        let COURSE_ID = 0;
        let QUESTIONS_COUNT = 0;
        let URL_GAME_PROFILE = '';

        let submit = () => {
            $('#form-questionnaire').on('submit', (event) => {
                // Cancel event to customise it.
                event.preventDefault();
                let formData = new FormData(event.target);
                let data = {};
                data.answers = [];
                let missinganswers = false;
                let answersmissing = 0;
                for (let i = 1; i <= QUESTIONS_COUNT; i++) {
                    let answer = {};
                    answer.id = formData.get('questionid-' + i);
                    answer.score = formData.get('question-' + i);
                    if (answer.score === null) {
                        missinganswers = true;
                        answersmissing = i;
                        break;
                    }
                    data.answers.push(answer);
                }
                if (!missinganswers) {
                    Ajax.call([{
                        methodname: 'format_ludilearn_submit_questionnaire',
                        args: data
                    }], true, true)[0].done((response) => {
                        window.location.href = URL_GAME_PROFILE;
                    }).fail((ex) => {
                        console.error(ex);
                    });
                } else {
                    $('.invalid-feedback').html('');
                    let message = '';
                    Str.get_string('missinganswers', 'format_ludilearn').then((string) => {
                        message = string;
                        let elementerror = $('#id_error_' + answersmissing);
                        elementerror.html(message);
                        elementerror.show();
                        document.querySelector('#id_error_' + answersmissing).scrollIntoView({
                            behavior: 'smooth'
                        });
                    });
                }
            });
        };

        return {
            init: (courseid, questionscounts, urlgameprofile) => {
                COURSE_ID = courseid;
                QUESTIONS_COUNT = questionscounts;
                URL_GAME_PROFILE = urlgameprofile;
                submit();
            }
        };
    });
