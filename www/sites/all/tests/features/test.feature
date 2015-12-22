Feature: test

  @api
  Scenario: quick test
    Given I go to "/node/1"
    Then I should see "hello world"
