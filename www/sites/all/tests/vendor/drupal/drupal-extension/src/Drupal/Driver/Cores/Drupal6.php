<?php

namespace Drupal\Driver\Cores;

use Drupal\Component\Utility\Random;
use Drupal\Exception\BootstrapException;

/**
 * Drupal 6 core.
 */
class Drupal6 implements CoreInterface {
  /**
   * System path to the Drupal installation.
   *
   * @var string
   */
  private $drupalRoot;

  /**
   * URI for the Drupal installation.
   *
   * @var string
   */
  private $uri;

  /**
   * Random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  private $random;

  /**
   * Set drupalRoot.
   */
  public function __construct($drupalRoot, $uri = 'default', Random $random) {
    $this->drupalRoot = realpath($drupalRoot);
    $this->uri = $uri;
    $this->random = $random;
  }

  /**
   * Implements CoreInterface::bootstrap().
   */
  public function bootstrap() {
    // Validate, and prepare environment for Drupal bootstrap.
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->drupalRoot);
      require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
      $this->validateDrupalSite();
    }

    // Bootstrap Drupal.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
    if (empty($GLOBALS['db_url'])) {
      throw new BootstrapException('Missing database setting, verify the database configuration in settings.php.');
    }
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    chdir($current_path);
  }

  /**
   * {@inheritDoc}
   */
  public function getExtensionPathList() {
    $paths = array();

    // Get enabled modules.
    $modules = \module_list();
    foreach ($modules as $module) {
      $paths[] = $this->drupalRoot . DIRECTORY_SEPARATOR . \drupal_get_path('module', $module);
    }

    // Themes.
    // @todo

    // Active profile
    // @todo

    return $paths;
  }

  /**
   * Implements CoreInterface::clearCache().
   */
  public function clearCache() {
    // Need to change into the Drupal root directory or the registry explodes.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_flush_all_caches();
    chdir($current_path);
  }

  /**
   * Implements CoreInterface::nodeCreate().
   */
  public function nodeCreate($node) {
    // Default status to 1 if not set.
    if (!isset($node->status)) {
      $node->status = 1;
    }
    node_save($node);
    return $node;
  }

  /**
   * Implements CoreInterface::nodeDelete().
   */
  public function nodeDelete($node) {
    node_delete($node->nid);
  }

  /**
   * Implements CoreInterface::runCron().
   */
  public function runCron() {
    return drupal_cron_run();
  }

  /**
   * Implements CoreInterface::userCreate().
   */
  public function userCreate(\stdClass $user) {
    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $account = clone $user;

    // Convert role array to a keyed array.
    if (isset($user->roles)) {
      $roles = array();
      foreach ($user->roles as $rid) {
        $roles[$rid] = $rid;
      }
      $user->roles = $roles;
    }

    user_save('', (array) $account);

    // Store UID.
    $user->uid = $account->uid;
  }

  /**
   * Implements CoreInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
    user_delete(array(), $user->uid);
  }

  /**
   * Implements CoreInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $roles = array_flip(user_roles());
    $role = $roles[$role_name];

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    user_multiple_role_edit(array($user->uid), 'add_role', $role);
  }

  /**
   * Impelements CoreInterface::validateDrupalSite().
   */
  public function validateDrupalSite() {
    if ('default' !== $this->uri) {
      // Fake the necessary HTTP headers that Drupal needs:
      $drupal_base_url = parse_url($this->uri);
      // If there's no url scheme set, add http:// and re-parse the url
      // so the host and path values are set accurately.
      if (!array_key_exists('scheme', $drupal_base_url)) {
        $drush_uri = 'http://' . $this->uri;
        $drupal_base_url = parse_url($this->uri);
      }
      // Fill in defaults.
      $drupal_base_url += array(
        'path' => NULL,
        'host' => NULL,
        'port' => NULL,
      );
      $_SERVER['HTTP_HOST'] = $drupal_base_url['host'];

      if ($drupal_base_url['port']) {
        $_SERVER['HTTP_HOST'] .= ':' . $drupal_base_url['port'];
      }
      $_SERVER['SERVER_PORT'] = $drupal_base_url['port'];

      if (array_key_exists('path', $drupal_base_url)) {
        $_SERVER['PHP_SELF'] = $drupal_base_url['path'] . '/index.php';
      }
      else {
        $_SERVER['PHP_SELF'] = '/index.php';
      }
    }
    else {
      $_SERVER['HTTP_HOST'] = 'default';
      $_SERVER['PHP_SELF'] = '/index.php';
    }

    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['REQUEST_METHOD']  = NULL;

    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['HTTP_USER_AGENT'] = NULL;

    $conf_path = conf_path(TRUE, TRUE);
    $conf_file = $this->drupalRoot . "/$conf_path/settings.php";
    if (!file_exists($conf_file)) {
      throw new BootstrapException(sprintf('Could not find a Drupal settings.php file at "%s"', $conf_file));
    }
    $drushrc_file = $this->drupalRoot . "/$conf_path/drushrc.php";
    if (file_exists($drushrc_file)) {
      require_once $drushrc_file;
    }
  }

  /**
   * Implements CoreInterface::termCreate.
   * @todo implement for drupal6
   */
  public function termCreate(\stdClass $term) {
    throw new UnsupportedDriverActionException(
      'No ability to create create terms in %s', $this
    );
  }

  /**
   * Implements CoreInterface::termDelete.
   * @todo implement for drupal6
   */
  public function termDelete(\stdClass $term) {
    throw new UnsupportedDriverActionException(
      'No ability to delete terms in %s', $this
    );
  }

  /**
   * Implements CoreInterface::roleCreate().
   */
  public function roleCreate(array $permissions) {
    // Verify permissions exist.
    $all_permissions = module_invoke_all('perm');
    foreach ($permissions as $name) {
      $search = array_search($name, $all_permissions);
      if (!$search) {
        throw new \RuntimeException(sprintf("No permission '%s' exists.", $name));
      }
    }

    // Create new role.
    $name = $this->random->name(8);
    db_query("INSERT INTO {role} SET name = '%s'", $name);

    // Add permissions to role.
    $rid = db_last_insert_id('role', 'rid');
    db_query("INSERT INTO {permission} (rid, perm) VALUES (%d, '%s')", $rid, implode(', ', $permissions));

    return $rid;
  }

  /**
   * Implements CoreInterface::roleDelete().
   */
  public function roleDelete($rid) {
    db_query('DELETE FROM {role} WHERE rid = %d', $rid);

    if (!db_affected_rows()) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $rid));
    }
  }

  public function processBatch() {
  }
}
