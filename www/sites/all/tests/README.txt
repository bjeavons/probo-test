### How to get setup to run tests

Do this stuff in a terminal:

0. try to run "composer" at the shell. If it's installed go to step 2.
1. brew install composer OR sudo apt-get install composer
2. cd sites/all/tests
3. composer install # this will take a while
4. cp example.behat.yml behat.yml
5. Edit the behat.yml file to set the "base_url" and drush "alias" values.
  (You can use `self` as the alias if running against the default site.)
6. bin/behat --init

### How to run tests:

From the Drupal webroot:
1. ./sites/all/tests/bin/behat --config=sites/all/tests/behat.yml
2. Add tags parameter to run specific scenarios: `--tags @acquisition`
3. Omit scenarios with a tilde prepended to tag: `--tags ~@javascript`

To run tests that depend on javascript, you'll need to have the Selenium java
application running. @todo document

### How to write tests

1. go into sites/all/tests/features

See http://docs.behat.org/quick_intro.html


### Things to be aware of:

1. If you are writing an A/B test that affects acquisition, be sure that there's
  an entry in drush_card_disable_ab_tests
