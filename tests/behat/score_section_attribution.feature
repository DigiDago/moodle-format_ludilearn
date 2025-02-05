@format @format_ludilearn @javascript @_file_upload @ludilearn_score
Feature: Score game element section attribution in Ludilearn course format
  In order to motivate students with points in specific sections
  As a teacher
  I need to configure score element for a section and verify it works with different activities in that section

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | One | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | numsections | enablecompletion |
      | Ludilearn Score | L1 | ludilearn | 3 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | L1 | editingteacher |
      | student1 | L1 | student |
    And I log in as "teacher1"
    And I am on "Ludilearn Score" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_assignment | bysection |
    And I press "Save and display"
    And I turn editing mode on
    And I edit the section "1" and I fill the form with:
      | name | Score Section |
    And I am on "Ludilearn Score" course homepage
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Score Note Only | Test score with grade | L1 | score1 | 1 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Score Completion Only | Test score with completion | L1 | score2 | 1 | 1 | 0 | 0 | | | | | |
      | quiz | Score Both | Test score with grade and completion | L1 | score3 | 1 | 1 | 100 | 0 | ##yesterday## | ##tomorrow## | | | |
      | forum | Score No Gamification | Test score without gamification | L1 | score4 | 1 | 0 | 0 | 0 | | | | | |
    And I log out

  @score_section_display_homepage
  Scenario: Verify score sections visualization and titles on course homepage before visiting sections
    Given I log in as "teacher1"
    And I am on "Ludilearn Score" course homepage
    And I turn editing mode on
    And I edit the section "2" and I fill the form with:
      | name | No Game Section |
    And I am on "Ludilearn Score" course homepage
    And I edit the section "3" and I fill the form with:
      | name | Empty Section |
    And I turn editing mode off
    And I am on "Ludilearn Score" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Score Section" to "Score"
    And I set the field "No Game Section" to "No gamified"
    And I set the field "Empty Section" to "Score"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    # State verification
    Given I log in as "student1"
    And I am on "Ludilearn Score" course homepage
    Then I should see "General" in the ".col-6:nth-child(1) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(1)" "css_element"
    And I should see "Score Section" in the ".col-6:nth-child(2) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(2)" "css_element"
    And I should see "No Game Section" in the ".col-6:nth-child(3) .sectionname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-6:nth-child(3)" "css_element"
    And I should see "Empty Section" in the ".col-6:nth-child(4) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(4)" "css_element"

  @score_section_display
  Scenario: Verify score elements for activities and resources appear only in configured section
    Given I log in as "teacher1"
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | L1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Score Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludilearn Score" course homepage
    And I turn editing mode on
    And I edit the section "2" and I fill the form with:
      | name | No Game Section |
    And I am on "Ludilearn Score" course homepage
    And I edit the section "3" and I fill the form with:
      | name | Empty Section |
    And I turn editing mode off
    And I am on "Ludilearn Score" course homepage
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | section | completion | grade |
      | assign | Regular Note Only | Test without game element | L1 | reg1 | 2 | 0 | 100 |
      | page | Regular Completion Only | Test without game element | L1 | reg2 | 2 | 2 | 0 |
      | quiz | Regular Both | Test without game element | L1 | reg3 | 2 | 1 | 100 |
      | forum | Regular Forum | Test without game element | L1 | reg4 | 2 | 0 | 0 |
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Score Section" to "Score"
    And I set the field "No Game Section" to "No gamified"
    And I set the field "Empty Section" to "Score"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Score" course homepage

    # Checking the score display at section level
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "Score Section" in the "div.section-score h4" "css_element"
    And "img.ludilearn-img[src*='chest.svg']" "css_element" should exist in the "div.rightdottedscore" "css_element"
    And I should see "0" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "16150 pts" in the "div.rightdottedscore p.scoretext" "css_element"

    # Score Note Only
    And "img.ludilearn-img[src*='bag.svg']" "css_element" should exist in the ".col-8 .row .cm-score:first-child" "css_element"
    And I should see "0" in the ".col-8 .row .cm-score:first-child .playerscore" "css_element"
    And I should see "8000 pts" in the ".col-8 .row .cm-score:first-child .scoretext" "css_element"
    And I should see "Score Note Only" in the ".col-8 .row .cm-score:first-child .cmname" "css_element"

    # Score Completion Only
    And "img.ludilearn-img[src*='bag.svg']" "css_element" should exist in the ".col-8 .row div:nth-child(2) .cm-score" "css_element"
    And I should see "0" in the ".col-8 .row div:nth-child(2) .cm-score .playerscore" "css_element"
    And I should see "150 pts" in the ".col-8 .row div:nth-child(2) .cm-score .scoretext" "css_element"
    And I should see "Score Completion Only" in the ".col-8 .row div:nth-child(2) .cm-score .cmname" "css_element"

    # Score Both
    And "img.ludilearn-img[src*='bag.svg']" "css_element" should exist in the ".col-8 .row div:nth-child(3) .cm-score" "css_element"
    And I should see "0" in the ".col-8 .row div:nth-child(3) .cm-score .playerscore" "css_element"
    And I should see "8000 pts" in the ".col-8 .row div:nth-child(3) .cm-score .scoretext" "css_element"
    And I should see "Score Both" in the ".col-8 .row div:nth-child(3) .cm-score .cmname" "css_element"

    # Score No Gamification
    And "img.ludilearn-img[src*='none.svg']" "css_element" should exist in the ".col-8 .row div:nth-child(4) .cm-score" "css_element"
    And I should see "Score No Gamification" in the ".col-8 .row div:nth-child(4) .cm-score .cmname" "css_element"

    # Check display for non-gamified section
    When I am on "Ludilearn Score" course homepage
    And I click on "No Game Section" "link" in the "region-main" "region"
    And I should see "No Game Section" in the "div.section-nogamified h4" "css_element"
    And I should see "Regular Note Only" in the ".col-8 .row div:nth-child(1) .cm-nogamified .cmname" "css_element"
    And "img.ludilearn-img[src*='none.svg']" "css_element" should exist in the ".col-8 .row div:nth-child(1)" "css_element"
    And I should see "Regular Completion Only" in the ".col-8 .row div:nth-child(2) .cm-nogamified .cmname" "css_element"
    And "img.ludilearn-img[src*='none.svg']" "css_element" should exist in the ".col-8 .row div:nth-child(2)" "css_element"
    And I should see "Regular Both" in the ".col-8 .row div:nth-child(3) .cm-nogamified .cmname" "css_element"
    And "img.ludilearn-img[src*='none.svg']" "css_element" should exist in the ".col-8 .row div:nth-child(3)" "css_element"
    And I should see "Regular Forum" in the ".col-8 .row div:nth-child(4) .cm-nogamified .cmname" "css_element"
    And "img.ludilearn-img[src*='none.svg']" "css_element" should exist in the ".col-8 .row div:nth-child(4)" "css_element"

    # Check empty section
    And I am on "Ludilearn Score" course homepage
    When I click on "Empty Section" "link" in the "region-main" "region"
    Then I should see "Empty Section" in the ".section-score h4" "css_element"

  @score_section_update @score_grade
  Scenario: Score updates correctly when student receives grade
    Given I log in as "teacher1"
    And I am on "Ludilearn Score" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Score Section" to "Score"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out
    # Student submits assignment
    Given I log in as "student1"
    And I am on the "score1" "assign activity" page
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And I log out
    # Teacher grades submission
    And I log in as "teacher1"
    And I am on the "score1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "80"
    And I press "Save changes"
    And I log out
    # Student verifies updated score
    And I log in as "student1"
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "6400" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "pts" in the "div.rightdottedscore p.scoretext" "css_element"
    And I should see "6400" in the ".col-8 .row .cm-score:first-child .playerscore" "css_element"
    And I should see "8000 pts" in the ".col-8 .row .cm-score:first-child .scoretext" "css_element"

  @score_completion
  Scenario: Score updates correctly when activity is completed
    Given I log in as "teacher1"
    And I am on "Ludilearn Score" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Score Section" to "Score"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out
    Given I log in as "student1"
    And I am on the "score2" "page activity" page
    #  Complete the activity
    And I press "Mark as done"
    And I wait until the page is ready
    # Check updated scores
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "150" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "pts" in the "div.rightdottedscore p.scoretext" "css_element"
    And I should see "150" in the ".col-8 .row div:nth-child(2) .cm-score .playerscore" "css_element"
    And I should see "150 pts" in the ".col-8 .row div:nth-child(2) .cm-score .scoretext" "css_element"

  @score_both_completion_and_grade
  Scenario: Score updates correctly with both grade and completion
    Given I log in as "teacher1"
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | L1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Score Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludilearn Score" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Score Section" to "Score"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out
    # First attempt - Half points
    Given I log in as "student1"
    And I am on the "score3" "quiz activity" page
    And I press "Attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "False" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "4000" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "4000" in the ".col-8 .row div:nth-child(3) .cm-score .playerscore" "css_element"
    And I should see "8000 pts" in the ".col-8 .row div:nth-child(3) .cm-score .scoretext" "css_element"
    # Mark activity as done
    And I am on the "score3" "quiz activity" page
    And I press "Mark as done"
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "5600" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "5600" in the ".col-8 .row div:nth-child(3) .cm-score .playerscore" "css_element"
    And I should see "8000 pts" in the ".col-8 .row div:nth-child(3) .cm-score .scoretext" "css_element"
    # Second attempt - Full points
    And I am on the "score3" "quiz activity" page
    And I press "Re-attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "True" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "9600" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "9600" in the ".col-8 .row div:nth-child(3) .cm-score .playerscore" "css_element"
    And I should see "8000 pts" in the ".col-8 .row div:nth-child(3) .cm-score .scoretext" "css_element"

  @score_settings
  Scenario: Teacher can configure score settings
    Given I log in as "teacher1"
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | L1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Score Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludilearn Score" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Score Section" to "Score"
    And I press "Save"
    Then I should see "The changes made have been applied"
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Score"
    And I set the field "Multiplier" to "200"
    And I set the field "Completion bonus" to "300"
    And I set the field "Additional percentage for activities graded with completion" to "10"
    And I press "Save"
    And I log out
    Given I log in as "student1"
    And I am on the "score3" "quiz activity" page
    And I press "Attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "True" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "20000" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "20000" in the ".col-8 .row div:nth-child(3) .cm-score .playerscore" "css_element"
    And I should see "20000 pts" in the ".col-8 .row div:nth-child(3) .cm-score .scoretext" "css_element"
    And I am on the "score3" "quiz activity" page
    And I press "Mark as done"
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "22000" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "22000" in the ".col-8 .row div:nth-child(3) .cm-score .playerscore" "css_element"
    And I should see "20000 pts" in the ".col-8 .row div:nth-child(3) .cm-score .scoretext" "css_element"
    And I am on the "score2" "page activity" page
    And I press "Mark as done"
    And I am on "Ludilearn Score" course homepage
    When I click on "Score Section" "link" in the "region-main" "region"
    Then I should see "22300" in the "div.rightdottedscore span.playerscore" "css_element"
    And I should see "300" in the ".col-8 .row div:nth-child(2) .cm-score .playerscore" "css_element"
    And I should see "300 pts" in the ".col-8 .row div:nth-child(2) .cm-score .scoretext" "css_element"
