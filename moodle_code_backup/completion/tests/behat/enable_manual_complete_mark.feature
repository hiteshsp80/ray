@core @core_completion
Feature: Allow students to manually mark an activity as complete
  In order to let students decide when an activity is completed
  As a teacher
  I need to allow students to mark activities as completed

  @javascript
  Scenario: Mark an activity as completed
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activity" exists:
      | activity | forum                  |
      | course   | C1                     |
      | name     | Test forum name        |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I am on the "Test forum name" "forum activity editing" page
    And I set the following fields to these values:
      | completion | 1 |
    And I press "Save and return to course"
    And "Student First" user has not completed "Test forum name" activity
    And I am on the "Course 1" course page logged in as student1
    When I click on "Not completed: Test forum name. Select to mark as complete." "icon"
    Then the "Test forum name" "forum" activity with "manual" completion should be marked as complete
    And I am on the "Course 1" course page logged in as teacher1
    And "Student First" user has completed "Test forum name" activity
