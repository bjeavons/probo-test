<?php
/**
 * @file
 * CardContext.php
 *
 * Put things here that are useful on both CARDcom and CARDlike.
 */

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;
use Drupal\DrupalExtension\Event\EntityEvent;
use Behat\Gherkin\Node\TableNode;

class CardContext extends Drupal\DrupalExtension\Context\DrupalContext {

  /**
   * @Then /^I should see comment matching "([^"]*)"$/
   */
  public function iShouldSeeCommentMatching($text) {
    $comment = $this->fixStepArgument($text);
    $html = $this->getSession()->getPage()->getHtml();

    if (stripos($html, $comment) === FALSE) {
      throw new \Exception(sprintf('The comment "%s" was not found anywhere in the text of the current page.', $comment));
    }
  }


  /**
   * Helper function.
   */
  public function createNewUser($user = NULL) {
    if (!is_object($user)) {
      $user = new stdClass();
    }
    // Ensure required properties are present, or set them.
    if (!isset($user->name)) {
      $user->name = $this->getDrupal()->random->name(8);
    }
    if (!isset($user->pass)) {
      $user->pass = $this->getDrupal()->random->name(16);
    }
    if (!isset($user->mail)) {
      $user->mail = "john.smith{$user->name}@example.com";
    }

    // Create a new user.
    $this->getDriver()->userCreate($user);

    $this->users[$user->name] = $this->user = $user;

    return $user;
  }


  /**
   * @Given /^I fill in my email address for "([^"]*)"$/
   */
  public function iFillInMyEmailAddressFor($field) {
    // Use the Mink Extenstion step definition.
    return new Given("I fill in \"$field\" with \"{$this->user->mail}\"");
  }

  /**
   * @Given /^(?:a|an) "(?P<type>[^"]*)" node:$/
   */
  public function aNode($type, TableNode $fields) {
    return $this->assertViewingNode($type, $fields);
  }

  /**
   * @Given /^a file at "([^"]*)"$/
   */
  public function aFileAt($filepath) {
    $pathArr = explode(DIRECTORY_SEPARATOR, $filepath);
    //Strip the filename so we can make the directory.
    array_pop($pathArr);
    $dir = implode(DIRECTORY_SEPARATOR, $pathArr);
    exec('mkdir -p '. $dir);
    exec('touch '. $filepath);
  }


  /**
   * @Given /^I am a user with the "([^"]*)" role$/
   */
  public function iAmAUserWithTheRole($role) {
    return new Given("I am logged in as a user with the \"$role\" role");
  }

  /**
   * @When /^I login$/
   */
  public function iLogin() {
    $this->login();
  }

  /**
   * @When /^I fill in my password for "([^"]*)"$/
   */
  public function iFillInMyPasswordFor($field) {
    return new Given("I fill in \"{$this->user->pass}\" for \"$field\"");
  }



  /**
   * @Given /^Drupal has "([^"]*)" set to "([^"]*)"$/
   */
  public function drupalHasSetTo($name, $value) {
    variable_set($name, $value);
  }

  /**
   * @When /^Drupal has "([^"]*)" set to JSON decoded \'([^\']*)\'$/
   */
  public function drupalHasSetToJsonDecoded($name, $value) {
    variable_set($name, json_decode($value, TRUE));
  }



  /**
   * @Given /^I enable "([^"]*)"$/
   */
  public function iEnable($modules) {
    $modules = explode(',', $modules);
    module_enable($modules);
  }

  /**
   * @Given /^I disable "([^"]*)"$/
   */
  public function iDisable($modules) {
    $modules = explode(',', $modules);
    module_disable($modules);
  }

  /**
   * @Given /^I make sure menus are rebuilt$/
   */
  public function iMakeSureMenusAreRebuilt() {
    menu_rebuild();
  }

  /**
   * @Given /^I update search index for "([^"]*)"$/
   */
  public function iUpdateSearchIndexFor($node_title) {
    // We want to find both campaigns and card art.
    $nodes = db_select('node')
      ->fields('node', ['nid'])
      ->condition('title', $node_title)
      ->condition('type', ['card_art', 'campaign'])
      ->execute();
    foreach ($nodes as $node) {
      search_touch_node($node->nid);
      _node_index_node($node);
      search_update_totals();
    }
  }



}
