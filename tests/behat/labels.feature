@format @format_ludilearn @ludilearn_label @javascript
Feature: Sections labels displays in Ludilearn format
  In order to present course content clearly
  As a teacher
  I need to be able to add and view labels

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname           | shortname | format     | numsections |
      | Ludilearn section | L1        | ludilearn | 3          |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | L1     | editingteacher |
      | student1 | L1     | student        |
    And the following "activities" exist:
      | activity | name    | intro               | course | section | idnumber |
      | label    | Label 1 | First section label | L1     | 1       | label1   |
      | label    | Label 2 | Second section label| L1     | 2       | label2   |

  @ludilearn_label_display
  Scenario: Teacher and student can view section labels
    # Check teacher view
    Given I log in as "teacher1"
    And I am on "Ludilearn section" course homepage with editing mode off
    When I click on "Section 1" "link" in the "region-main" "region"
    Then I should see "First section label" in the ".row .col-12 .cm-score:first-child" "css_element"
    And I am on "Ludilearn section" course homepage
    And I click on "Section 2" "link" in the "region-main" "region"
    And I should see "Second section label" in the ".row .col-12 .cm-score:first-child" "css_element"

    # Check student view
    When I log out
    And I log in as "student1"
    And I am on "Ludilearn section" course homepage
    And I click on "Section 1" "link" in the "region-main" "region"
    Then I should see "First section label" in the ".row .col-12 .cm-score:first-child" "css_element"
    And I am on "Ludilearn section" course homepage
    And I click on "Section 2" "link" in the "region-main" "region"
    And I should see "Second section label" in the ".row .col-12 .cm-score:first-child" "css_element"
