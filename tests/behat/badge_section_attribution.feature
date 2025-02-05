@format @format_ludilearn @javascript @_file_upload @ludilearn_badge
Feature: Badge game element configuration and validation by section in Ludilearn
  In order to create engaging learning course with gamification elements
  As a teacher
  I need to configure badge elements per section and verify their behavior across different activity types and completion scenarios

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | One | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | numsections | enablecompletion |
      | Ludilearn Badge | L1 | ludilearn | 3 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | L1 | editingteacher |
      | student1 | L1 | student |
    And I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_assignment | bysection |
    And I press "Save and display"
    And I turn editing mode on
    And I edit the section "1" and I fill the form with:
      | name | Badge Section |
    And I am on "Ludilearn Badge" course homepage
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Badge Note Only | Test badge with grade | L1 | badge1 | 1 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Badge Completion Only | Test badge with completion | L1 | badge2 | 1 | 1 | 0 | 0 | | | | | |
      | quiz | Badge Both | Test badge with grade and completion | L1 | badge3 | 1 | 1 | 100 | 0 | ##yesterday## | ##tomorrow## | | | |
      | forum | Badge No Gamification | Test badge without gamification | L1 | badge4 | 1 | 0 | 0 | 0 | | | | | |
    And I log out

  @score_section_display_homepage
  Scenario: Verify badge sections visualization and titles on course homepage before visiting sections
    Given I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    And I turn editing mode on
    And I edit the section "2" and I fill the form with:
      | name | No Game Section |
    And I am on "Ludilearn Badge" course homepage
    And I edit the section "3" and I fill the form with:
      | name | Empty Section |
    And I turn editing mode off
    And I am on "Ludilearn Badge" course homepage
    When I navigate to "LudiLearn customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Badge Section" to "Badge"
    And I set the field "No Game Section" to "No gamified"
    And I set the field "Empty Section" to "Badge"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    # State verification
    Given I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    Then I should see "General" in the ".col-6:nth-child(1) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(1)" "css_element"
    And I should see "Badge Section" in the ".col-6:nth-child(2) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(2)" "css_element"
    And I should see "No Game Section" in the ".col-6:nth-child(3) .sectionname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-6:nth-child(3)" "css_element"
    And I should see "Empty Section" in the ".col-6:nth-child(4) .sectionname" "css_element"
    And "img[src*='unkown.svg']" "css_element" should exist in the ".col-6:nth-child(4)" "css_element"

  @badge_section_display
  Scenario: Verify badge elements appear only in configured section
    Given I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    And I turn editing mode on
    And I edit the section "2" and I fill the form with:
      | name | No Game Section |
    And I am on "Ludilearn Badge" course homepage
    And I edit the section "3" and I fill the form with:
      | name | Empty Section |
    And I turn editing mode off
    And I am on "Ludilearn Badge" course homepage
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Regular Note Only | Test without game element | L1 | reg1 | 2 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Regular Completion Only | Test without game element | L1 | reg2 | 2 | 2 | 0 | 0 | | | | | |
      | quiz | Regular Both | Test without game element | L1 | reg3 | 2 | 1 | 100 | 1 | ##yesterday## | ##tomorrow## | | | |
      | forum | Regular Forum | Test without game element | L1 | reg4 | 2 | 0 | 0 | 0 | | | | | |
    When I navigate to "LudiLearn customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Badge Section" to "Badge"
    And I set the field "No Game Section" to "No gamified"
    And I set the field "Empty Section" to "Badge"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    # Check badge display at section level
    When I click on "Badge Section" "link" in the "region-main" "region"
    Then I should see "Badge Section" in the ".section-badge h4" "css_element"
    # Check summary badges (bronze, silver, gold, completion)
    And "img[src*='badge_bronze_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_silver_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_gold_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_completion_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    # Check badge counters
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    # Check activity badges
    And "img[src*='badge_none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-badge" "css_element"
    And I should see "Badge Note Only" in the ".col-sm-4:nth-child(1) .cmname" "css_element"
    And "img[src*='badge_none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(2) .cm-badge" "css_element"
    And I should see "Badge Completion Only" in the ".col-sm-4:nth-child(2) .cmname" "css_element"
    And "img[src*='badge_none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-badge" "css_element"
    And I should see "Badge Both" in the ".col-sm-4:nth-child(3) .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-badge" "css_element"
    And I should see "Badge No Gamification" in the ".col-sm-4:nth-child(4) .cmname" "css_element"
    # Check display for non-gamified section
    When I am on "Ludilearn Badge" course homepage
    And I click on "No Game Section" "link" in the "region-main" "region"
    Then I should see "No Game Section" in the ".section-nogamified h4" "css_element"
    And I should see "Regular Note Only" in the ".col-sm-4:nth-child(1) .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-nogamified" "css_element"
    And I should see "Regular Completion Only" in the ".col-sm-4:nth-child(2) .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(2) .cm-nogamified" "css_element"
    And I should see "Regular Both" in the ".col-sm-4:nth-child(3) .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-nogamified" "css_element"
    And I should see "Regular Forum" in the ".col-sm-4:nth-child(4) .cmname" "css_element"
    And "img[src*='none.svg']" "css_element" should exist in the ".col-sm-4:nth-child(4) .cm-nogamified" "css_element"
    # Check empty section
    When I am on "Ludilearn Badge" course homepage
    And I click on "Empty Section" "link" in the "region-main" "region"
    Then I should see "Empty Section" in the ".section-badge h4" "css_element"

  @badge_completion
  Scenario: Badge updates correctly when activity is completed
    Given I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    When I navigate to "LudiLearn customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Badge Section" to "Badge"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    And I click on "Badge Completion Only" "link"
    # Complete the activity
    And I press "Mark as done"
    # Check updated badges
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    # Check section badge counters
    Then "img[src*='badge_completion.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_gold.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".rightlined div:nth-of-type(2) .badge-number" "css_element"
    And I should see "1" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "1" in the ".col-3:nth-child(4) .badge-number" "css_element"
    # Check updated activity badges
    And "img[src*='badge_gold.svg']" "css_element" should exist in the ".col-sm-4:nth-child(2) .cm-badge" "css_element"
    And "img[src*='badge_completion.svg']" "css_element" should exist in the ".col-sm-4:nth-child(2) .cm-badge" "css_element"
    And I should see "Badge Completion Only" in the ".col-sm-4:nth-child(2) .cmname" "css_element"

  @badge_grade
  Scenario: Badge updates correctly from silver to gold when student receives grade
    Given I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    When I navigate to "LudiLearn customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Badge Section" to "Badge"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    # Student submits assignment
    Given I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    # Check initial state
    Then "img[src*='badge_bronze_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_silver_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_gold_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_completion_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"

    # Submit assignment
    When I click on "Badge Note Only" "link"
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And I log out

    # Teacher grades submission
    And I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    And I click on "Badge Section" "link" in the "region-main" "region"
    And I click on "Badge Note Only" "link" in the "region-main" "region"
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "80"
    And I press "Save changes"
    And I log out

    # Student verifies updated badges
    And I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    # Check badges after grading
    Then "img[src*='badge_bronze.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "1" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".rightlined div:nth-of-type(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_bronze.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-badge" "css_element"
    And I log out
    # Teacher updates grade to 90%
    And I log in as "teacher1"
    And I am on the "badge1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "90"
    And I press "Save changes"
    And I log out
    # Student verifies updated badges after grade
    And I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    Then "img[src*='badge_silver.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "1" in the ".rightlined div:nth-of-type(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_silver.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-badge" "css_element"
    # Teacher updates grade to 100%
    And I log in as "teacher1"
    And I am on the "badge1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "100"
    And I press "Save changes"
    And I log out
    # Student verifies updated badges after grade
    And I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    # Check badges after perfect grade
    Then "img[src*='badge_gold.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".rightlined div:nth-of-type(2) .badge-number" "css_element"
    And I should see "1" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_gold.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-badge" "css_element"

  @badge_both_completion_and_grade
  Scenario: Badge updates correctly with both grade and completion
    Given I log in as "teacher1"
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | L1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              | answer 1 | grade |
      | Test questions   | truefalse | First question | This is the first question| True     | 50    |
      | Test questions   | truefalse | Second question| This is the second question| False   | 50    |
    And quiz "Badge Both" contains the following questions:
      | question       | page | maxmark |
      | First question | 1    | 50      |
      | Second question| 1    | 50      |
    And I am on "Ludilearn Badge" course homepage
    When I navigate to "LudiLearn customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Badge Section" to "Badge"
    And I press "Save"
    Then I should see "The changes made have been applied"
    And I log out

    Given I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    # Check initial state
    Then "img[src*='badge_bronze_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_silver_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_gold_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_completion_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"

    # Get grade from quiz
    And I am on the "badge3" "quiz activity" page
    And I press "Attempt quiz"
    And I click on "True" "radio" in the "This is the first question" "question"
    And I click on "True" "radio" in the "This is the second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I am on "Ludilearn Badge" course homepage
    And I click on "Badge Section" "link" in the "region-main" "region"
    # Check badges after quiz completion
    Then "img[src*='badge_gold.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_completion_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "1" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_gold.svg']" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-badge" "css_element"

    # Complete the activity
    And I am on the "badge3" "quiz activity" page
    And I press "Mark as done"
    And I am on "Ludilearn Badge" course homepage
    And I click on "Badge Section" "link" in the "region-main" "region"
    # Check final state with both badges
    Then "img[src*='badge_gold.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_completion.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "1" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "1" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_gold.svg']" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-badge" "css_element"
    And "img[src*='badge_completion.svg']" "css_element" should exist in the ".col-sm-4:nth-child(3) .cm-badge" "css_element"
    And I should see "Badge Both" in the ".col-sm-4:nth-child(3) .cmname" "css_element"

  @badge_settings
  Scenario: Teacher can configure badge thresholds and student gets the expected badges
    # Teacher configures game elements allocation
    Given I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    When I navigate to "LudiLearn customisation of game elements" in current page administration
    And I set the field "Settings" to "Allocation of game elements by section"
    And I set the field "Badge Section" to "Badge"
    And I press "Save"
    Then I should see "The changes made have been applied"

    # Teacher sets badge thresholds
    When I navigate to "LudiLearn customisation of game elements" in current page administration
    And I set the field "Settings" to "Badge"
    And I set the field "Gold badge threshold" to "85"
    And I set the field "Silver badge threshold" to "70"
    And I set the field "Bronze badge threshold" to "50"
    And I press "Save"
    And I log out

    # Check initial state - no badges earned
    Given I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    Then "img[src*='badge_bronze_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_silver_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_gold_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And "img[src*='badge_completion_none.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"

    # Student submits assignment
    When I click on "Badge Note Only" "link"
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And I log out

    # Teacher grades submission - Bronze badge (55%)
    And I log in as "teacher1"
    And I am on "Ludilearn Badge" course homepage
    And I click on "Badge Section" "link" in the "region-main" "region"
    And I click on "Badge Note Only" "link" in the "region-main" "region"
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "60"
    And I press "Save changes"
    And I log out

    # Student verifies Bronze badge earned
    And I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    Then "img[src*='badge_bronze.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "1" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".rightlined div:nth-of-type(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_bronze.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-badge" "css_element"
    And I log out

    # Teacher updates grade - Silver badge (75%)
    And I log in as "teacher1"
    And I am on the "badge1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "75"
    And I press "Save changes"
    And I log out

    # Student verifies Silver badge earned
    And I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    Then "img[src*='badge_silver.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "1" in the ".rightlined div:nth-of-type(2) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_silver.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-badge" "css_element"
    And I log out

    # Teacher updates grade - Gold badge (90%)
    And I log in as "teacher1"
    And I am on the "badge1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "student1@example.com" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "90"
    And I press "Save changes"
    And I log out

    # Student verifies Gold badge earned
    And I log in as "student1"
    And I am on "Ludilearn Badge" course homepage
    When I click on "Badge Section" "link" in the "region-main" "region"
    Then "img[src*='badge_gold.svg']" "css_element" should exist in the ".rightlined" "css_element"
    And I should see "0" in the ".col-3:nth-child(1) .badge-number" "css_element"
    And I should see "0" in the ".rightlined div:nth-of-type(2) .badge-number" "css_element"
    And I should see "1" in the ".col-3:nth-child(3) .badge-number" "css_element"
    And I should see "0" in the ".col-3:nth-child(4) .badge-number" "css_element"
    And "img[src*='badge_gold.svg']" "css_element" should exist in the ".col-sm-4:nth-child(1) .cm-badge" "css_element"
