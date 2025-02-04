@format @format_ludilearn @javascript @ludilearn_edit_sections
Feature: Sections can be edited and deleted in ludilearn format
  In order to rearrange my course contents
  As a teacher
  I need to manage sections effectively

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname          | shortname | format     | coursedisplay | numsections | initsections |
      | Course Ludilearn | L1        | ludilearn | 0             | 3           | 1            |
    And the following "activities" exist:
      | activity | name                 | intro                       | course | idnumber | section |
      | assign   | Test assignment name | Test assignment description | L1     | assign1  | 0       |
      | book     | Test book name       |                            | L1     | book1    | 1       |
      | lesson   | Test lesson name     | Test lesson description     | L1     | lesson1  | 2       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | L1     | editingteacher |
      | student1 | L1     | student        |
    And I log in as "teacher1"

  @ludilearn_adding_section
  Scenario: Add a section and then add an activity in ludilearn format
    Given I am on "Course Ludilearn" course homepage with editing mode on
    When I click on "Add section" "link" in the "course-addsection" "region"
    And I turn editing mode off
    Then I should see "Section 4" in the ".col-6:nth-child(5) .sectionname" "css_element"

  @ludilearn_deleting_section
  Scenario: Deleting the last section in ludilearn format
    Given I am on "Course Ludilearn" course homepage with editing mode on
    When I delete section "2"
    Then I should see "This will delete Section 2 and all the activities it contains."
    And I click on "Delete" "button" in the "Delete section?" "dialogue"
    And I turn editing mode off
    And I should see "Section 1" in the ".col-6:nth-child(2) .sectionname" "css_element"
    And I should not see "Section 2" in the ".col-6:nth-child(3) .sectionname" "css_element"

  @ludilearn_editing_description_section
  Scenario: Check section name, description and label in Ludilearn format
    Given I log in as "teacher1"
    And I am on "Course Ludilearn" course homepage with editing mode on
    When I edit the section "1" and I fill the form with:
      | Section name | Section 1 - Introduction                                  |
      | Description | This is section 1 description with some LudiLearn content |
    And I am on "Course Ludilearn" course homepage
    Then I should see "Section 1 - Introduction" in the ".col-6:nth-child(2) .sectionname" "css_element"
    And I click on "Section 1 - Introduction" "link" in the "region-main" "region"
    And I should see "Section 1 - Introduction" in the "div.section-score h4" "css_element"
    And I log out
    Given I log in as "student1"
    And I am on "Course Ludilearn" course homepage
    Then I should see "Section 1 - Introduction" in the ".col-6:nth-child(2) .sectionname" "css_element"
    And I click on "Section 1 - Introduction" "link" in the "region-main" "region"
    And I should see "Section 1 - Introduction" in the "div.section-score h4" "css_element"
