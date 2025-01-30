@format @format_ludimoodle @ludimoodle_subsections @javascript
Feature: Subsections display in Ludimoodle format
  In order to organize course content hierarchically
  As a teacher
  I need to be able to add and view subsections

  Background:
    Given I enable "subsection" "mod" plugin
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | numsections | enablecompletion |
      | Ludimoodle subsections | L1 | ludimoodle | 3 | 1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | L1     | student        |
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | section | completion | grade | completionusegrade | allowsubmissionsfromdate | duedate | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes |
      | assign | Badge Note Only | Test badge with grade | L1 | badge1 | 1 | 0 | 100 | 0 | ##yesterday## | ##tomorrow## | 1 | 1 | 4096 |
      | page | Badge Completion Only | Test badge with completion | L1 | badge2 | 1 | 1 | 0 | 0 | | | | | |
      | forum | Badge No Gamification | Test badge without gamification | L1 | badge4 | 1 | 0 | 0 | 0 | | | | | |
      | subsection | Subsection | Test subsection | L1 | sub1 | 1 | 0 | 0 | 0 | | | | | |
      | page | Page1 in Subsection | Page in subsection | L1 | page11 | 4 | 1 | 0 | 0 | | | | | |

  Scenario: Student can view subsections correctly
    When I log in as "student1"
    And I am on "Ludimoodle subsections" course homepage
    Then I should see "Section 1" in the "region-main" "region"
    And I click on "Section 1" "link" in the "region-main" "region"
    And I should see "Subsection" in the "region-main" "region"
    And I click on "Subsection" "link" in the "region-main" "region"
    And I should see "Subsection" in the "region-main" "region"
    And I should see "Page1 in Subsection" in the ".col-8 .row .cm-score:first-child" "css_element"
