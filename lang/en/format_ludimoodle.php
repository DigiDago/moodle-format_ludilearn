<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     format_ludimoodle
 * @category    string
 * @copyright   2024 Pimenko <support@pimenko.com><pimenko.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Ludimoodle';
$string['assignment'] = 'Allocation of game elements';
$string['assignment_help'] = 'Allocation determines how game elements are assigned to learners. The 3 allocation modes are:<br>
* Automatic: via the Ludimoodle algorithm which automatically assigns a game element according to the learner\'s profile (applied to all courses). If your learners are accessing a game-based course for the first time, they will have to fill in a questionnaire which will take 5 minutes.
* Manual: by selecting a game element of your choice for all learners and for the whole course.
* By section: by selecting a game element of your choice for all learners for each section of the course.';
$string['default'] = 'Default';
$string['manual'] = 'Manually for the whole course';
$string['automatic'] = 'Automatic: based on a questionnaire and the LudiMoodle algorithm';
$string['bysection'] = 'By section';
$string['assignmentbysection'] = 'Allocation of game elements by section';
$string['world'] = 'World';
$string['world_help'] = 'The universe is the design used for the game elements. Choose the most appropriate design for your learners.';
$string['school'] = 'School';
$string['professional'] = 'Professional';
$string['highschool'] = 'High school';
$string['addsections'] = 'Add sections';
$string['currentsection'] = 'This section';
$string['editsection'] = 'Edit section';
$string['editsectionname'] = 'Edit section name';
$string['deletesection'] = 'Delete section';
$string['newsectionname'] = 'New name for section {$a}';
$string['sectionname'] = 'Section';
$string['settingsname'] = 'LudiMoodle customisation of game elements';
$string['section0name'] = 'General';
$string['hidefromothers'] = 'Hide section';
$string['showfromothers'] = 'Show section';
$string['check_format_options_changements'] = 'Check format options changements';
$string['score'] = 'Score';
$string['maxscore'] = 'Maximum score';
$string['default_game_element'] = 'Default game element';
$string['default_game_element_help'] = 'Default game element is the game element that will be assigned to all sections';
$string['lesson'] = 'Lesson';
$string['editgameeleements'] = 'Edit game elements';
$string['gameelements'] = 'Game elements';
$string['gameelement'] = 'Game element';
$string['parameters'] = 'Settings';
$string['saved'] = 'Saved';
$string['error'] = 'Error';
$string['updatescore'] = 'Update game element score';
$string['condition'] = 'Condition';
$string['completion'] = 'Completion';
$string['grade'] = 'Grade';
$string['nogamification'] = 'No gamification';
$string['nogamified'] = 'No gamified';
$string['badge'] = 'Badge';
$string['maxserie'] = 'Maximum serie';
$string['progress'] = 'Task progression';
$string['gameprofile'] = 'Gamer profile';
$string['questionnaire'] = 'Questionnaire';
$string['questionshexad'] = '<h3>You\'re about to access a gamified course.<h3>
<p>Help us adapt it to your HEXAD-12 player profile by indicating how each sentence corresponds to you <br> (1: Not at all agree, 7: Totaly agree)</p>';
$string['questionnaire:question1'] = 'It makes me happy to be able to help others';
$string['questionnaire:question2'] = 'I enjoy group activities';
$string['questionnaire:question3'] = 'The well-being of others is important to me';
$string['questionnaire:question4'] = 'I like being part of a team';
$string['questionnaire:question5'] = 'I like mastering difficult tasks';
$string['questionnaire:question6'] = 'I enjoy emerging victorious out of difcult circumstances';
$string['questionnaire:question7'] = 'Being independent is important to me';
$string['questionnaire:question8'] = 'I dislike following rules';
$string['questionnaire:question9'] = 'If the reward is sufficient, I will put in the effort';
$string['questionnaire:question10'] = 'It is important to me to follow my own path';
$string['questionnaire:question11'] = 'I see myself as a rebel';
$string['questionnaire:question12'] = 'Rewards are a great way to motivate me';
$string['avatar'] = 'Avatar';
$string['items'] = 'Items';
$string['itemunlocked'] = 'Items unlocked';
$string['inventory'] = 'Inventory';
$string['rightarm'] = 'Right arm';
$string['leftarm'] = 'Left arm';
$string['head'] = 'Head';
$string['face'] = 'Face';
$string['body'] = 'Body';
$string['others'] = 'Others';
$string['decoration'] = 'Decoration';
$string['picture'] = 'Picture';
$string['lamp'] = 'Lamp';
$string['sportstuff'] = 'Sport stuff';
$string['pet'] = 'Pet';
$string['desk'] = 'Desk';
$string['tshirt'] = 'T-shirt';
$string['ball'] = 'Ball';
$string['bed'] = 'Bed';
$string['hull'] = 'Hull';
$string['sail'] = 'Sail';
$string['flag'] = 'Flag';
$string['figurehead'] = 'Figurehead';
$string['propulsion'] = 'Propulsion';
$string['theme'] = 'Theme';
$string['timer'] = 'Timer';
$string['besttime'] = 'Best time';
$string['averagetime'] = 'Average time';
$string['reference_time'] = 'Reference time';
$string['ranking'] = 'Ranking';
$string['report'] = 'Report';
$string['progression'] = 'Progression';
$string['missinganswers'] = 'This question must to be answered';
$string['equip'] = 'Equip';
$string['equiped'] = 'Equiped';
$string['first'] = 'st';
$string['second'] = 'nd';
$string['third'] = 'rd';
$string['th'] = 'th';
$string['me'] = 'Me';
$string['gamify'] = 'Gamify';
$string['notgamify'] = 'Not gamify';
$string['seconds'] = 'seconds';
$string['ofpenalties'] = 'of penalties';
$string['globalsettings'] = 'Global settings';
$string['editsettingssuccess'] = 'The changes made have been applied';
$string['editsettingsfailed'] = 'Something wrong happening. The changes have not been applied';
$string['settings:multiplier'] = 'Multiplier';
$string['settings:multiplier_help'] = 'The multiplier is a coefficient applied uniformly to all the points earned in the course, to reinforce the fun aspect of the scoring system.<br>
The default value is 1. Increasing it (to 2 or 10, for example) will adjust all the scores obtained.<br>
This option allows you to create a more playful dimension without altering the balance between the course activities.';
$string['settings:percentagecompletion'] = 'Additional percentage for activities graded with completion';
$string['settings:percentagecompletion_help'] = 'This setting defines an additional bonus awarded when a learner completes an activity that is both graded and has a completion status (e.g. homework, quizzes, lessons).
The bonus is calculated as a percentage of the maximum grade for the activity. For example, if this percentage is set at 10%:<br>
* An assignment marked out of 100 points will give a bonus of 10 points on completion.<br>
* A quiz marked out of 50 will give a bonus of 5 points on completion.<br>
This bonus is added to the grade, rewarding learners for completing the activity, regardless of their performance.';
$string['settings:bonuscompletion'] = 'Completion bonus';
$string['settings:bonuscompletion_help'] = 'The completion bonus is a fixed number of points awarded when a learner completes an activity that does not have a grade and activity completion.<br>
This bonus is applied consistently to all activities in the course that only have the completion option enabled (for example, viewing a resource or participating in an ungraded forum). Using this bonus encourages learners to interact with all elements of the course, even those that are not graded directly.';
$string['settings:badgegold'] = 'Gold badge threshold';
$string['settings:badgegold_help'] = 'This parameter defines the minimum percentage of the mark that a learner must achieve to receive a gold badge in a graded activity. For example, if the threshold is set at 90%:<br>
* For an activity graded out of 100 points, the learner must obtain at least 90 points.<br>
* For a quiz marked out of 50 points, the learner must obtain at least 45 points.';
$string['settings:badgesilver'] = 'Silver badge threshold';
$string['settings:badgesilver_help'] = 'This parameter defines the minimum percentage of the mark that a learner must achieve in order to receive a silver badge in a graded activity. For example, if the threshold is set at 85%:<br>
* For an activity marked out of 100 points, the learner must obtain at least 85 points.<br>
* For a quiz marked out of 50 points, the learner must obtain at least 42.5 points';
$string['settings:badgebronze'] = 'Bronze badge threshold';
$string['settings:badgebronze_help'] = 'This parameter defines the minimum percentage of the mark that a learner must achieve in order to receive a bronze badge in a graded activity. For example, if the threshold is set at 70%:<br> * For an activity graded out of 100 points, the learner must obtain at least 70 points.<br>
* For an activity graded out of 100 points, the learner must obtain at least 70 points.
* For a quiz marked out of 50 points, the learner must obtain at least 35 points.';
$string['settings:nosetting'] = 'This game element works automatically based on the existing structure and settings of your course. It requires no additional configuration and there are no options to adjust.';
$string['settings:penalties'] = 'Penatlies by point lost';
$string['settings:penalties_help'] = 'This parameter defines the number of seconds added to the final quiz time for each wrong answer to a learner question. Each point lost adds the time of the penalty parameter.<br />
For example, if the penalty is set to 20 seconds and a learner gets 0 points on a 2-point quiz, 40 seconds will be added to the learner\'s final time.';
$string['settings:thresholdtoearn'] = 'Threshold for unlocking an avatar element';
$string['settings:thresholdtoearn_help'] = 'This parameter defines the minimum score (as a percentage) that a learner must achieve in a graded activity to unlock a new avatar element. For example, if the threshold is set at 80%:<br />
* A score of 81% in a quiz will unlock an avatar element <br />.
* A score of 79%  will not unlock anything, even if the activity is completed.
Adjust this threshold according to the difficulty of your activities and the frequency with which you want learners to unlock new avatar elements.';
$string['settings:scoredescription'] = '<p>The score game element allows learners to accumulate points by completing course activities. It adapts automatically to the different types of activities offered.<br><br>
1. For graded activities, the score is based directly on the mark obtained. For example, a mark of 16 out of 20 translates into 16 points.<br>
A multiplication coefficient is applied to transform these points into a score, as in games. For example, a coefficient of 80 will show 1280 points in the scoring system.<br>
2. For activities with only completion, a fixed number called the completion bonus is awarded when the activities are completed. For example, at the end of the activity, the learner is awarded 150 points.<br>
3. In the case of activities combining grade and completion, the score takes into account both the grade and an additional percentage added to the grade. For example, completing the activity will add an additional 20% to the total score that can be achieved.</p>';
$string['settings:badgedescription'] = '<p>This game element rewards learners with badges for completing activities. It adapts automatically to the different types of activities in the course<br><br>.
1. For graded activities, three levels of badges (Gold, Silver, Bronze) are awarded automatically when the learner\'s grade reaches or exceeds the threshold defined for each level.<br>
2. For non-graded activities with activity completion, learners are awarded Gold badges directly upon activity completion.<br>
3. In the case of a graded activity with completion of the activity, learners will be able to obtain one of the 3 levels of badges (Gold, Silver, Bronze) and a bonus badge linked to completion.</p>';
$string['settings:progressiondescription'] = '<p>The progress game element displays the learner\'s overall progress through the course in the form of a journey. It automatically adapts to the different types of activities.<br><br>
1. For graded activities, progress corresponds directly to the percentage of the grade obtained. For example, if a learner obtains 80% in a quiz, their progression for this activity will be 80%.<br>
2. For activities with completion only, progress increases by 100%  as soon as the activity is marked as completed.<br>
3. For activities that combine marking and completion, it is the mark achieved that determines progress, completion has no additional impact.</p>';
$string['settings:avatardescription'] = '<p>The avatar game element allows learners to earn items and customise their visual representation in the course. Learners unlock items such as hairstyles, clothing and accessories as they progress through the course. <br><br>
1. For graded activities, unlocking avatar items is based on achieving a score above the threshold to earn an item. For example, if the threshold is set at 80%, the learner will need to score above 80% in a graded activity to unlock a new avatar item.<br>
2. For completion-only activities, completion of the activity unlocks an avatar item. <br>
3. For activities that combine scoring and completion, the score achieved is used. Activity completion has no additional impact.</p>';
$string['settings:timerdescription'] = '<p>The timer game element adds a time dimension to your course quizzes. <strong>It works exclusively with test (or quiz)</strong> type activities. The element displays the time taken by the learner to complete the test, with overtime penalties applied for errors.<br>
- The time used by the learner is counted at the quiz level.<br>
- The learner\'s best time is recorded and displayed in the quiz, and the average quiz time is displayed in the course section.<br>
- In the event of a mistake in the quiz, additional time (penalty) is added.</p>.';
$string['settings:rankingdescription'] = '<p>The ranking game element provides a comparative view of learners\' performance against other learners in the course. It automatically adapts to the different types of activity in the course.<br>
1. For graded activities, the mark obtained is converted directly into points which contribute to the ranking. For example, a mark of 80% out of 100 points translates into 80 points in the ranking. If 6 learners have obtained a better mark, they will be displayed in 7th position in the ranking.<br>
2. Activities with only completion have no impact on the ranking.<br>
3. In the case of activities combining grade and completion, only the grade is taken into account for the ranking.</p>';
$string['settings:nogamifieddescription'] = '<p>The non-game element appears on all non-game activities and course resources.</p>';
$string['gameprofile_desc'] = 'Based on your answers, here\'s your HEXAD-12 player profile :';
$string['achiever'] = 'Achiever';
$string['player'] = 'Player';
$string['socialiser'] = 'Socialiser';
$string['freeSpirit'] = 'Free spirit';
$string['disruptor'] = 'Disruptor';
$string['philanthropist'] = 'Philanthropist';
$string['backtocourse'] = 'Back to course';
$string['achiever_desc'] = 'Motivated by skill and mastery';
$string['player_desc'] = 'Motivated by reward';
$string['socialiser_desc'] = 'Motivated by social relationships and a sense of belonging';
$string['freeSpirit_desc'] = 'Motivated by autonomy';
$string['disruptor_desc'] = 'Motivated by change';
$string['philanthropist_desc'] = 'Motivated by meaning and utility';
$string['partner_text'] = '<p>Developed by <a href="https://pimenko.com">Pimenko</a>.<br>
For technical feedback, please use the <a href="https://github.com/DigiDago/moodle-format_ludimoodle">Github repository</a><br><br>
<b>About Ludimoodle+</b><br><br>
The LudiMoodle+ project, led by the University of Lyon, has been awarded a grant from the French government, managed by the French National Research Agency  under the France 2030 program, under the reference « ANR-22-FRAN-0005 »</p>';
$string['partner_text2'] = '<p><b>Project partners</b></p>';
// String for privacy provider.
$string['privacy:metadata:ludimoodle_profile'] = 'The table ludimoodle_profile stores the HEXAD-12 player profile of each user.';
$string['privacy:metadata:ludimoodle_profile:userid'] = 'The user ID';
$string['privacy:metadata:ludimoodle_profile:combinedaffinities'] = 'The HEXAD-12 player profile of the user.';
$string['privacy:metadata:ludimoodle_profile:type'] = 'The type of game element assigned to the user.';
$string['privacy:metadata:ludimoodle_answers'] = 'The table ludimoodle_answers stores the answers to the HEXAD-12 questionnaire.';
$string['privacy:metadata:ludimoodle_answers:questionid'] = 'The question ID.';
$string['privacy:metadata:ludimoodle_answers:userid'] = 'The user ID.';
$string['privacy:metadata:ludimoodle_answers:score'] = 'The score obtained by the user for the question.';
$string['privacy:metadata:ludimoodle_attribution'] = 'The table ludimoodle_attribution stores the game elements assigned to each user.';
$string['privacy:metadata:ludimoodle_attribution:gameelementid'] = 'The game element ID.';
$string['privacy:metadata:ludimoodle_attribution:userid'] = 'The user ID.';
$string['privacy:metadata:ludimoodle_attribution:timecreated'] = 'The time the game element was assigned to the user.';
$string['privacy:metadata:ludimoodle_gameeele_user'] = 'The table ludimoodle_gameeele_user stores all data related to the game elements assigned to each user.';
$string['privacy:metadata:ludimoodle_gameeele_user:attributionid'] = 'The attribution ID.';
$string['privacy:metadata:ludimoodle_gameeele_user:name'] = 'The name of the data.';
$string['privacy:metadata:ludimoodle_gameeele_user:value'] = 'The value of the data.';
$string['privacy:metadata:ludimoodle_cm_user'] = 'The table ludimoodle_cm_user stores the data related to the game elements in a course module assigned to each user.';
$string['privacy:metadata:ludimoodle_cm_user:attributionid'] = 'The attribution ID.';
$string['privacy:metadata:ludimoodle_cm_user:cmid'] = 'The course module ID.';
$string['privacy:metadata:ludimoodle_cm_user:name'] = 'The name of the data.';
$string['privacy:metadata:ludimoodle_cm_user:value'] = 'The value of the data.';
