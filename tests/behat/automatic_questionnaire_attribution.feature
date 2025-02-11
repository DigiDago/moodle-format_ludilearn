@format @format_ludilearn @javascript @ludimoodle_questionnaire
Feature: Automatic questionnaire assignment in ludimoodle
  In order to automatically assign levels to students
  As a teacher
  I need students to see and complete the questionnaire when first accessing the course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname                  | shortname | format     | numsections | enablecompletion |
      | LudiLearn Questionnaire | L1        | ludilearn | 3           | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | L1     | editingteacher |
      | student1 | L1     | student        |
    And I log in as "teacher1"
    And I am on "LudiLearn Questionnaire" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_assignment | automatic |
    And I press "Save and display"
    And I turn editing mode on
    And I edit the section "1" and I fill the form with:
      | name | Activities Section |
    And I am on "LudiLearn Questionnaire" course homepage
    And the following "activities" exist:
     | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Progress Note Only | Test progression with grade | L1 | reg1 | 2 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Progress Completion Only | Test progression with completion | L1 | reg2 | 2 | 1 | 0 | 0 | | | | | |
      | quiz | Progress Both | Test progression with grade and completion | L1 | reg3 | 2 | 1 | 100 | 0 | ##yesterday## | ##tomorrow## | | | |
      | forum | Progress No Gamification | Test progression without gamification | L1 | reg4 | 2 | 0 | 0 | 0 | | | | | |
    And I log out

  @automatic_attribution_questionnaire
  Scenario: Student sees the questionnaire when automatic assignment is enabled
    Given I log in as "student1"
    And I am on site homepage
    When I click on "LudiLearn Questionnaire" "link" in the "region-main" "region"
    Then I should see "You're about to access a gamified course." in the "region-main" "region"
    And I should see "You're about to access a gamified course." in the "region-main" "region"
    And I click on "question-1-5" "radio" in the "region-main" "region"
    And I click on "question-2-4" "radio" in the "region-main" "region"
    And I click on "question-3-6" "radio" in the "region-main" "region"
    And I click on "question-4-3" "radio" in the "region-main" "region"
    And I click on "question-5-7" "radio" in the "region-main" "region"
    And I click on "question-6-5" "radio" in the "region-main" "region"
    And I click on "question-7-4" "radio" in the "region-main" "region"
    And I click on "question-8-2" "radio" in the "region-main" "region"
    And I click on "question-9-6" "radio" in the "region-main" "region"
    And I click on "question-10-5" "radio" in the "region-main" "region"
    And I click on "question-11-3" "radio" in the "region-main" "region"
    And I click on "question-12-7" "radio" in the "region-main" "region"
    And I click on "Save" "button"
    Then I should see "Based on your answers, here's your HEXAD-12 player profile" in the "region-main" "region"
    And I should see "Your full results" in the "#hexad-results-title" "css_element"
    And I click on "Continue" "link"
    And I am on "LudiLearn Questionnaire" course homepage
    And I visit "/course/format/ludilearn/gameprofile.php"
    Then I should see "Based on your answers, here's your HEXAD-12 player profile" in the "region-main" "region"
    And I should see "Your full results" in the "#hexad-results-title" "css_element"