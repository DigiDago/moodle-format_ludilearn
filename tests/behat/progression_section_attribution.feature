@format @format_ludimoodle @javascript @_file_upload @ludimoodle_progression
Feature: Progression game element section attribution in Ludimoodle course format
  In order to motivate students with progression tracking in specific sections
  As a teacher
  I need to configure progression element for a section and verify it works with different activities

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | One | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | numsections | enablecompletion |
      | Ludimoodle Progression | L1 | ludimoodle | 3 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | L1 | editingteacher |
      | student1 | L1 | student |
    And I log in as "teacher1"
    And I am on "Ludimoodle Progression" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_assignment | bysection |
    And I press "Save and display"
    And I turn editing mode on
    And I edit the section "1" and I fill the form with:
      | name | Progression Section |
    And I am on "Ludimoodle Progression" course homepage
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Progress Note Only | Test progression with grade | L1 | prog1 | 1 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Progress Completion Only | Test progression with completion | L1 | prog2 | 1 | 1 | 0 | 0 | | | | | |
      | quiz | Progress Both | Test progression with grade and completion | L1 | prog3 | 1 | 1 | 100 | 0 | ##yesterday## | ##tomorrow## | | | |
      | forum | Progress No Gamification | Test progression without gamification | L1 | prog4 | 1 | 0 | 0 | 0 | | | | | |
    And I log out

  @progression_section_display
  Scenario: Verify progression elements appear only in configured section
    Given I log in as "teacher1"
    And I am on "Ludimoodle Progression" course homepage
    And I turn editing mode on
    And I edit the section "2" and I fill the form with:
      | name | No Game Section |
    And I turn editing mode off
    And I am on "Ludimoodle Progression" course homepage
    And the following "activities" exist:
     | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Progress Note Only | Test progression with grade | L1 | reg1 | 2 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Progress Completion Only | Test progression with completion | L1 | reg2 | 2 | 1 | 0 | 0 | | | | | |
      | quiz | Progress Both | Test progression with grade and completion | L1 | reg3 | 2 | 1 | 100 | 0 | ##yesterday## | ##tomorrow## | | | |
      | forum | Progress No Gamification | Test progression without gamification | L1 | reg4 | 2 | 0 | 0 | 0 | | | | | |
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I set the field "No Game Section" to "No gamified"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    # Check progression display at section level
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "Progression Section" in the ".section-progress h4" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And I should see "0%" in the ".rightdottedscore .progression-text span.progression" "css_element"

    # Check activity progression displays
    # Progress Note Only
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-progress" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And I should see "Progress Note Only" in the ".col-sm-4:nth-child(1) .cmname" "css_element"

    # Progress Completion Only
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(2) .cm-progress" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(2) .progression-text.progression-cm span.progression" "css_element"
    And I should see "Progress Completion Only" in the ".col-sm-4:nth-child(2) .cmname" "css_element"

    # Progress Both
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-progress" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And I should see "Progress Both" in the ".col-sm-4:nth-child(3) .cmname" "css_element"

    # Progress No Gamification
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-progress" "css_element"
    And I should see "Progress No Gamification" in the ".col-sm-4:nth-child(4) .cmname" "css_element"

    # Check display for non-gamified section
    When I am on "Ludimoodle Progression" course homepage
    When I click on "No Game Section" "link" in the "region-main" "region"
    Then I should see "No Game Section" in the "div.section-nogamified h4" "css_element"
    And I should see "Progress Note Only" in the ".col-sm-4:nth-child(1) .cm-nogamified .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-nogamified" "css_element"
    And I should see "Progress Completion Only" in the ".col-sm-4:nth-child(2) .cm-nogamified .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(2) .cm-nogamified" "css_element"
    And I should see "Progress Both" in the ".col-sm-4:nth-child(3) .cm-nogamified .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-nogamified" "css_element"
    And I should see "Progress No Gamification" in the ".col-sm-4:nth-child(4) .cm-nogamified .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-nogamified" "css_element"

  @progression_completion
  Scenario: Progression updates correctly when activity is completed
    Given I log in as "teacher1"
    And I am on "Ludimoodle Progression" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    # Check initial state
    Then I should see "0%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And I should see "0%" in the ".col-sm-4:nth-child(2) .progression-text.progression-cm span.progression" "css_element"

    # Complete the activity
    When I click on "Progress Completion Only" "link"
    And I press "Mark as done"
    And I wait until the page is ready
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"

    # Check updated progression
    Then I should see "33%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(2) .progression-text.progression-cm span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(2) .cm-progress" "css_element"

  @progression_grade
  Scenario: Progression updates correctly when student receives grade
    Given I log in as "teacher1"
    And I am on "Ludimoodle Progression" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    # Check initial state
    Then I should see "0%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And I should see "0%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"

    # Submit assignment
    When I click on "Progress Note Only" "link"
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And I log out

    # Teacher grades submission
    And I log in as "teacher1"
    And I am on the "prog1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "80"
    And I press "Save changes"
    And I log out

    # Student verifies updated progression
    And I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "26%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "80%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-progress" "css_element"
    And I log out

    # Teacher update grade
    And I log in as "teacher1"
    And I am on the "prog1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "90"
    And I press "Save changes"
    And I log out

    # Student verifies updated progression
    And I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "30%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "90%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-progress" "css_element"
    And I log out

    # Teacher update grade
    And I log in as "teacher1"
    And I am on the "prog1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "100"
    And I press "Save changes"
    And I log out

    # Student verifies updated progression
    And I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "33%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-progress" "css_element"
    And I log out

  @progression_both_completion_and_grade
  Scenario: Progression updates correctly with both grade and completion
    Given I log in as "teacher1"
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | L1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Progress Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludimoodle Progression" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    # Check initial state
    Then I should see "0%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist

    # Complete the activity
    And I am on the "prog3" "quiz activity" page
    And I press "Mark as done"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "0%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist
    And "img.img-responsive" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-progress" "css_element"

    # Complete quiz with grade 50%
    And I am on the "prog3" "quiz activity" page
    And I press "Attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "False" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "16%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "50%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"

    # Complete quiz with grade 100%
    And I am on the "prog3" "quiz activity" page
    And I press "Re-attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "True" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "33%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"

  @progression_total
  Scenario: Student completes all activities and total progression updates correctly
    Given I log in as "teacher1"
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | L1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Progress Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludimoodle Progression" course homepage
    When I navigate to "LudiMoodle customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    # Check initial state
    Then I should see "0%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist

    # Complete Progress Note Only (Assignment)
    When I click on "Progress Note Only" "link"
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Ludimoodle Progression" course homepage
    And I click on "Progression Section" "link" in the "region-main" "region"
    And I click on "Progress Note Only" "link" in the "region-main" "region"
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "100"
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "33%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    # Verify  state of all activities
    And I should see "100%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(2) .progression-text.progression-cm span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-progress" "css_element"

    # Complete Progress Completion Only (Page)
    When I click on "Progress Completion Only" "link"
    And I press "Mark as done"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "66%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    # Verify  state of all activities
    And I should see "100%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(2) .progression-text.progression-cm span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-progress" "css_element"

    # Complete Progress Both (Quiz with grade and completion)
    And I am on the "prog3" "quiz activity" page
    And I press "Mark as done"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "66%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And I am on the "prog3" "quiz activity" page
    And I press "Attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "False" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "83%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "50%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And I am on the "prog3" "quiz activity" page
    And I press "Re-attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "True" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludimoodle Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "100%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(2) .progression-text.progression-cm span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-progress" "css_element"
