Feature: test

  @api @probo
  Scenario: quick test
    Given I go to "/node/1"
    Then I should see "hello world"
    When I go to "/node/3"
    Then I should see "ROCKS"

