<?php
namespace Civi\Cv;

require_once 'Encoder.php';

/**
 * Bootstrap the CMS runtime.
 *
 * @code
 * // Use default bootstrap
 * require_once '/path/to/CmsBootstrap.php';
 * Civi\Cv\CmsBootstrap::singleton()->bootCms()->bootCivi();
 *
 * // Use custom bootstrap
 * require_once '/path/to/CmsBootstrap.php';
 * Civi\Cv\CmsBootstrap::singleton()
 *   ->addOptions(array(...))
 *   ->bootCms()
 *   ->bootCivi();
 * @endcode
 *
 * This class is intended to be run *before* the classloader is available. Therefore, it
 * must be self-sufficient.
 *
 * By default, bootstrap will scan PWD and every ancestor directory to see if it
 * contains a supported CMS. If you have performance considerations, or if the
 * directory structure is not amenable to scanning, then set the environment
 * variable CIVICRM_BOOT (in bashrc and/or httpd vhost).
 *
 * The bootstrapper accepts a few options (either via the constructor or addOptions()). They are:
 *   - env: string|NULL. The environment variable which may contain boot options
 *     Ex: 'Drupal://var/www' or 'WordPress://admin@var/www'. Set NULL to disable.
 *     (Default: CIVICRM_BOOT)
 *   - search: bool|string. Attempt to determine root+settings by searching
 *     the file system and checking against common Civi directory structures.
 *     Boolean TRUE means it should use a default (PWD).
 *     (Default: TRUE aka PWD)
 *   - user: string|NULL. The name of a CMS user to authenticate as.
 *
 * @package Civi\Cv
 */
class CmsBootstrap {

  protected static $singleton = NULL;

  protected $options = array();

  /**
   * Symphony OutputInterface provide during booting to enable the command-line
   * 'v', 'vv' and 'vvv' options
   * @see http://symfony.com/doc/current/console/verbosity.html
   */
  protected $output = FALSE;

  /**
   * @var array|NULL
   */
  protected $bootedCms = NULL;

  /**
   * @return CmsBootstrap
   */
  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new CmsBootstrap(array(
        'env' => 'CIVICRM_BOOT',
        'search' => TRUE,
        'httpHost' => array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : 'localhost',
        'user' => NULL,
      ));
    }
    return self::$singleton;
  }

  /**
   * @param array $options
   *   See options in class doc.
   */
  public function __construct($options = array()) {
    $this->addOptions($options);
  }

  /**
   * Bootstrap the CiviCRM runtime.
   *
   * @return CmsBootstrap
   * @throws \Exception
   */
  public function bootCms() {
    echo "Options: " . Encoder::encode($this->options, 'json-pretty') . " \n\n";

    if ($this->options['env'] && getenv($this->options['env'])) {
      $cmsExpr = getenv($this->options['env']);
      echo "Parse CMS options ($cmsExpr) \n\n";
      $cms = array(
        'type' => parse_url($cmsExpr, PHP_URL_SCHEME),
        'path' => '/' . parse_url($cmsExpr, PHP_URL_HOST) . parse_url($cmsExpr, PHP_URL_PATH),
      );
      $cms['path'] = preg_replace(';^//+;', '/', $cms['path']);
      if (!isset($this->options['user']) && parse_url($cmsExpr, PHP_URL_USER)) {
        $this->options['user'] = parse_url($cmsExpr, PHP_URL_USER);
      }
    }
    else {
      echo "Find CMS... \n\n";
      $cms = $this->findCmsRoot($this->getSearchDir());
    }

    echo "CMS: " . Encoder::encode($cms, 'json-pretty') . " \n\n";
    if (empty($cms['path']) || empty($cms['type']) || !file_exists($cms['path'])) {
      throw new \Exception("Failed to parse or find a CMS");
    }

    if (PHP_SAPI === "cli") {
      echo "Simulate web environment in CLI \n\n";
      $this->simulateWebEnv($this->options['httpHost'], $cms['path'] . '/index.php');
    }

    $func = 'boot' . $cms['type'];
    if (!is_callable([$this, $func])) {
      throw new \Exception("Failed to locate boot function ($func)");
    }

    call_user_func([$this, $func],
      $cms['path'], $this->options['user'], $this->options['httpHost']);

    if (PHP_SAPI === "cli") {
      error_reporting(1);
    }

    echo "Finished";
    $this->bootedCms = $cms;
    return $this;
  }

  /**
   * Determine what, if any, CMS has been bootstrapped.
   *
   * @return string|NULL
   *   Ex: 'Backdrop', 'Drupal', 'Drupal8', 'Joomla', 'WordPress'.
   */
  public function getBootedCmsType() {
    return isset($this->bootedCms['type']) ? $this->bootedCms['type'] : NULL;
  }

  /**
   * Determine what, if any, CMS has been bootstrapped.
   *
   * @return string|NULL
   *   Ex: '/var/www'.
   */
  public function getBootedCmsPath() {
    return isset($this->bootedCms['path']) ? $this->bootedCms['path'] : NULL;
  }

  /**
   * @return $this
   * @throws \Exception
   */
  public function bootCivi() {
    // PRE-CONDITIONS: CMS has already been booted, and Civi is already installed.
    if (function_exists('civicrm_initialize')) {
      civicrm_initialize();
    }
    // else if Joomla weirdness, do that
    else {
      throw new \Exception("This system does not appear to have CiviCRM");
    }

    if (!empty($this->options['user'])) {
      $this->ensureUserContact();
    }

    return $this;
  }

  public function bootBackdrop($cmsPath, $cmsUser) {
    if (!file_exists("$cmsPath/core/includes/bootstrap.inc")) {
      throw new \Exception('Sorry, could not locate Backdrop\'s bootstrap.inc');
    }
    chdir($cmsPath);
    define('BACKDROP_ROOT', $cmsPath);
    require_once "$cmsPath/core/includes/bootstrap.inc";
    require_once "$cmsPath/core/includes/config.inc";
    \backdrop_bootstrap(BACKDROP_BOOTSTRAP_FULL);

    if (!function_exists('module_exists')) {
      throw new \Exception('Sorry, could not bootstrap Backdrop.');
    }

    if ($cmsUser) {
      global $user;
      $user = \user_load(array('name' => $cmsUser));
    }

    return $this;
  }

  public function bootDrupal($cmsPath, $cmsUser) {
    if (!file_exists("$cmsPath/includes/bootstrap.inc")) {
      // Sanity check.
      throw new \Exception('Sorry, could not locate Drupal\'s bootstrap.inc');
    }
    chdir($cmsPath);
    define('DRUPAL_ROOT', $cmsPath);
    require_once 'includes/bootstrap.inc';
    \drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

    if (!function_exists('module_exists')) {
      throw new \Exception('Sorry, could not bootstrap Drupal.');
    }

    if ($cmsUser) {
      global $user;
      $user = \user_load_by_name($cmsUser);
    }

    return $this;
  }

  public function bootDrupal8($cmsRootPath, $cmsUser) {
    if (!file_exists("$cmsRootPath/core/core.services.yml")) {
      // Sanity check.
      throw new \Exception('Sorry, could not locate Drupal8\'s core.services.yml');
    }

    chdir($cmsRootPath);
    define('DRUPAL_DIR', $cmsRootPath);
    $autoloader = require_once DRUPAL_DIR . '/autoload.php';
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();
    $kernel->prepareLegacyRequest($request);

    if (!function_exists('t')) {
      throw new \Exception('Sorry, could not bootstrap Drupal8.');
    }

    if ($cmsUser) {
      $entity_manager = \Drupal::entityManager();
      $users = $entity_manager->getStorage($entity_manager->getEntityTypeFromClass('Drupal\user\Entity\User'))
        ->loadByProperties(array(
          'name' => $cmsUser,
        ));
      if (count($users) == 1) {
        foreach ($users as $uid => $user) {
          user_login_finalize($user);
        }
      }
      elseif (empty($users)) {
        throw new \Exception(sprintf("Failed to find Drupal8 user (%s)", $cmsUser));
      }
      else {
        throw new \Exception(sprintf("Found too many Drupal8 users (%d)", count($users)));
      }
    }

    return $this;
  }

  // TODO public function bootJoomla($cmsRootPath, $cmsUser) { }

  /**
   * @param string $cmsRootPath
   * @param string $cmsUser
   * @return $this
   */
  public function bootWordPress($cmsRootPath, $cmsUser) {
    if (!file_exists($cmsRootPath . DIRECTORY_SEPARATOR . 'wp-load.php')) {
      throw new \Exception('Sorry, could not locate WordPress\'s wp-load.php.');
    }
    chdir($cmsRootPath);
    require_once $cmsRootPath . DIRECTORY_SEPARATOR . 'wp-load.php';

    if (!function_exists('wp_set_current_user')) {
      throw new \Exception('Sorry, could not bootstrap WordPress.');
    }

    if ($cmsUser) {
      wp_set_current_user(NULL, $cmsUser);
    }

    return $this;
  }

  /**
   * @return array
   *   See options in class doc.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * @param array $options
   *   See options in class doc.
   * @return CmsBootstrap
   */
  public function addOptions($options) {
    $this->options = array_merge($this->options, $options);

    if (!empty($options['output'])) {
      $this->output = $options['output'];
    }

    return $this;
  }

  /**
   * @param string $searchDir
   *   The directory from which to begin the upward search.
   * @return array|NULL
   *   Ex: ['path' => '/var/www', 'type' => 'WordPress]
   */
  protected function findCmsRoot($searchDir) {
    // A list of file patterns; if one of the patterns matches a give
    // directory, then we can assume that this directory is the
    // CMS root.
    $cmsPatterns = array(
      'WordPress' => array(
        // 'wp-includes/version.php',
        'wp-load.php',
      ),
      'Joomla' => array(
        'administrator/components/com_users/users.php',
      ),
      'Drupal' => array(
        'modules/system/system.module',
      ),
      'Drupal8' => array(
        'core/core.services.yml',
      ),
      'Backdrop' => array(
        'core/modules/layout/layout.module',
      ),
    );

    $parts = explode('/', str_replace('\\', '/', $searchDir));
    while (!empty($parts)) {
      $basePath = implode('/', $parts);

      foreach ($cmsPatterns as $cmsType => $relPaths) {
        if (!empty($this->options['cmsType']) && $this->options['cmsType'] != $cmsType) {
          continue;
        }
        foreach ($relPaths as $relPath) {
          $matches = glob("$basePath/$relPath");
          if (!empty($matches)) {
            return array('path' => $basePath, 'type' => $cmsType);
          }
        }
      }

      array_pop($parts);
    }

    return NULL;
  }

  /**
   * @return string
   */
  protected function getSearchDir() {
    if ($this->options['search'] === TRUE) {
      // exec(pwd) works better with symlinked source trees, but it's
      // probably not portable to Windows.
      if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return getcwd();
      }
      else {
        exec('pwd', $output);
        return trim(implode("\n", $output));
      }
    }
    else {
      return $this->options['search'];
    }
  }

  /**
   * @param $scriptFile
   * @param $host
   */
  protected function simulateWebEnv($host, $scriptFile) {
    $_SERVER['SCRIPT_FILENAME'] = $scriptFile;
    $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SERVER_NAME'] = $host;
    $_SERVER['HTTP_HOST'] = $host;
    if (ord($_SERVER['SCRIPT_NAME']) != 47) {
      $_SERVER['SCRIPT_NAME'] = '/' . $_SERVER['SCRIPT_NAME'];
    }
  }

  protected function ensureUserContact() {
    if ($cid = \CRM_Core_Session::getLoggedInContactID()) {
      return $cid;
    }

    // Ugh, this codepath.
    switch (CIVICRM_UF) {
      case 'Drupal':
      case 'Drupal6':
      case 'Backdrop':
        \CRM_Core_BAO_UFMatch::synchronize($GLOBALS['user'], TRUE, CIVICRM_UF, 'Individual');
        break;

      case 'Drupal8':
        \CRM_Core_BAO_UFMatch::synchronize(\Drupal::currentUser(), TRUE, CIVICRM_UF, 'Individual');
        break;

      case 'Joomla':
        \CRM_Core_BAO_UFMatch::synchronize(\JFactory::getUser(), TRUE, CIVICRM_UF, 'Individual');
        break;

      case 'WordPress':
        \CRM_Core_BAO_UFMatch::synchronize($GLOBALS['current_user'], TRUE, CIVICRM_UF, 'Individual');
        break;

      default:
        throw new \Exception("Unrecognized UF: " . CIVICRM_UF);
    }

    return \CRM_Core_Session::getLoggedInContactID();
  }

}
