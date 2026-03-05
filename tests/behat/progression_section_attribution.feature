@format @format_ludilearn @javascript @_file_upload @ludilearn_progression
Feature: Progression game element section attribution in Ludilearn course format
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
      | Ludilearn Progression | LP1 | ludilearn | 3 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | LP1 | editingteacher |
      | student1 | LP1 | student |
    And I log in as "teacher1"
    And I am on "Ludilearn Progression" course homepage
    And I visit Ludilearn course settings page for "LP1"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_assignment | bysection |
    And I press "Save and display"
    And I am on "Ludilearn Progression" course homepage with editing mode on
    And I click on "Edit" "link" in the "#section-1 .section_action_menu" "css_element"
    And I click on "a[href*='/course/editsection.php']" "css_element" in the "#section-1 .section_action_menu" "css_element"
    And I set the following fields to these values:
      | name | Progression Section |
    And I press "Save changes"
    And I am on "Ludilearn Progression" course homepage
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Progress Note Only | Test progression with grade | LP1 | prog1 | 1 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Progress Completion Only | Test progression with completion | LP1 | prog2 | 1 | 1 | 0 | 0 | | | | | |
      | quiz | Progress Both | Test progression with grade and completion | LP1 | prog3 | 1 | 1 | 100 | 0 | ##yesterday## | ##tomorrow## | | | |
      | forum | Progress No Gamification | Test progression without gamification | LP1 | prog4 | 1 | 0 | 0 | 0 | | | | | |
    And I log out

  @score_section_display_homepage
  Scenario: Verify progression sections visualization and titles on course homepage before visiting sections
    Given I log in as "teacher1"
    And I am on "Ludilearn Progression" course homepage
    And I am on "Ludilearn Progression" course homepage with editing mode on
    And I click on "Edit" "link" in the "#section-2 .section_action_menu" "css_element"
    And I click on "a[href*='/course/editsection.php']" "css_element" in the "#section-2 .section_action_menu" "css_element"
    And I set the following fields to these values:
      | name | No Game Section |
    And I press "Save changes"
    And I am on "Ludilearn Progression" course homepage
    And I click on "Edit" "link" in the "#section-3 .section_action_menu" "css_element"
    And I click on "a[href*='/course/editsection.php']" "css_element" in the "#section-3 .section_action_menu" "css_element"
    And I set the following fields to these values:
      | name | Empty Section |
    And I press "Save changes"
    And I am on "Ludilearn Progression" course homepage with editing mode off
    And I am on "Ludilearn Progression" course homepage
    When I visit Ludilearn game elements settings page for "LP1"
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I set the field "No Game Section" to "No gamified"
    And I set the field "Empty Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    # State verification
    Given I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
    Then I should see "General" in the ".col-6:nth-child(1) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(1)" "css_element"
    And I should see "Progression Section" in the ".col-6:nth-child(2) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(2)" "css_element"
    And I should see "No Game Section" in the ".col-6:nth-child(3) .sectionname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-6:nth-child(3)" "css_element"
    And I should see "Empty Section" in the ".col-6:nth-child(4) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(4)" "css_element"

  @progression_section_display
  Scenario: Verify progression elements for activies and resources appear only in configured section
    Given I log in as "teacher1"
    And I am on "Ludilearn Progression" course homepage
    And I am on "Ludilearn Progression" course homepage with editing mode on
    And I click on "Edit" "link" in the "#section-2 .section_action_menu" "css_element"
    And I click on "a[href*='/course/editsection.php']" "css_element" in the "#section-2 .section_action_menu" "css_element"
    And I set the following fields to these values:
      | name | No Game Section |
    And I press "Save changes"
    And I am on "Ludilearn Progression" course homepage
    And I click on "Edit" "link" in the "#section-3 .section_action_menu" "css_element"
    And I click on "a[href*='/course/editsection.php']" "css_element" in the "#section-3 .section_action_menu" "css_element"
    And I set the following fields to these values:
      | name | Empty Section |
    And I press "Save changes"
    And I am on "Ludilearn Progression" course homepage with editing mode off
    And I am on "Ludilearn Progression" course homepage
    And the following "activities" exist:
     | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Progress Note Only | Test progression with grade | LP1 | reg1 | 2 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Progress Completion Only | Test progression with completion | LP1 | reg2 | 2 | 1 | 0 | 0 | | | | | |
      | quiz | Progress Both | Test progression with grade and completion | LP1 | reg3 | 2 | 1 | 100 | 0 | ##yesterday## | ##tomorrow## | | | |
      | forum | Progress No Gamification | Test progression without gamification | LP1 | reg4 | 2 | 0 | 0 | 0 | | | | | |
    When I visit Ludilearn game elements settings page for "LP1"
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I set the field "No Game Section" to "No gamified"
    And I set the field "Empty Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
    When I am on "Ludilearn Progression" course homepage
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

   # Check empty section
    When I am on "Ludilearn Progression" course homepage
    And I click on "Empty Section" "link" in the "region-main" "region"
    Then I should see "Empty Section" in the ".section-progress h4" "css_element"

  @progression_completion
  Scenario: Progression updates correctly when activity is completed
    Given I log in as "teacher1"
    And I am on "Ludilearn Progression" course homepage
    When I visit Ludilearn game elements settings page for "LP1"
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
    When I visit Ludilearn game elements settings page for "LP1"
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
    And the following "grade grades" exist:
      | gradeitem           | user     | grade |
      | Progress Note Only  | student1 | 80    |
    And I log out

    # Student verifies updated progression
    And I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
    And the following "grade grades" exist:
      | gradeitem           | user     | grade |
      | Progress Note Only  | student1 | 90    |
    And I log out

    # Student verifies updated progression
    And I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
    And the following "grade grades" exist:
      | gradeitem           | user     | grade |
      | Progress Note Only  | student1 | 100   |
    And I log out

    # Student verifies updated progression
    And I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
      | Course       | LP1       | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Progress Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludilearn Progression" course homepage
    When I visit Ludilearn game elements settings page for "LP1"
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    # Check initial state
    Then I should see "0%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And "img.progression-steps-component.rocket" "css_element" should exist
    And "img.progression-steps-component.planet" "css_element" should exist

    # Complete the activity
    And I am on the "prog3" "quiz activity" page
    And I press "Mark as done"
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "33%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"

  @progression_total
  Scenario: Student completes all activities and total progression updates correctly
    Given I log in as "teacher1"
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | LP1       | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Progress Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludilearn Progression" course homepage
    When I visit Ludilearn game elements settings page for "LP1"
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Progression Section" to "Task progression"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
    And I click on "Progression Section" "link" in the "region-main" "region"
    And I am on the "prog1" "assign activity" page
    And the following "grade grades" exist:
      | gradeitem           | user     | grade |
      | Progress Note Only  | student1 | 100   |
    And I log out
    And I log in as "student1"
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
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
    And I am on "Ludilearn Progression" course homepage
    When I click on "Progression Section" "link" in the "region-main" "region"
    Then I should see "100%" in the ".rightdottedscore .progression-text span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(1) .progression-text.progression-cm span.progression" "css_element"
    And I should see "100%" in the ".col-sm-4:nth-child(2) .progression-text.progression-cm span.progression" "css_element"
    And I should see "0%" in the ".col-sm-4:nth-child(3) .progression-text.progression-cm span.progression" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-progress" "css_element"
