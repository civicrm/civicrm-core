<?php

namespace Civi;

require_once 'civicrm-version.php';

use _CiviVersion_\Util;

/**
 * Bootstrap the CiviCRM runtime.
 *
 * @code
 * // Use default bootstrap
 * require_once '/path/to/civicrm.config.php';
 * Civi\Bootstrap::singleton()->bootCivi();
 *
 * // Use custom bootstrap
 * require_once '/path/to/civicrm.config.php';
 * Civi\Cv\CmsBootstrap::singleton()
 *   ->setOptions(array(...))
 *   ->bootCms()
 *   ->bootCivi();
 * @endcode
 *
 * This class is intended to be run *before* the classloader is available. Therefore, it
 * must be self-sufficient.
 *
 * Recommendations:
 *   - Administrators with an unusual directory structure should either:
 *     - Set env var CIVICRM_SETTINGS in their httpd vhost and bashrc, or
 *     - Create settings_location.php
 *   - Primary CMS-integration modules should preemptively configure the
 *     defaults so that other code may bootstrap without specifying options.
 *       require_once $cividir/Civi/Bootstrap.php
 *       Civi\Bootstrap::singleton()->setOptions(
 *         'settingsFile' => ...,
 *         'search' => FALSE,
 *       ));
 *   - External scripts should call:
 *       require_once $cividir/Civi/Bootstrap.php;
 *       Civi\Bootstrap::singleton()->bootCivi();
 *   - Administrators who are concerned about bootstrap time for external
 *     scripts should use CIVICRM_SETTINGS or settings_location.php.
 *   - Pre-installation programs (code-generators, installers, etc) should not
 *     use Civi\Bootstrap. Instead, they should use CRM_Core_ClassLoader.
 *
 * The bootstrapper accepts a few options (either via constructor or setOptions()). They are:
 *   - dynamicSettingsFile: string|NULL. The location of a PHP file which dynamically
 *     determines the location of civicrm.settings.php. This is provided for backward
 *     compatibility.
 *     (Default: $civiRoot/settings_location.php)
 *   - cmsType: string|NULL. Give a hint to the search algorithm about which
 *     type of CMS is being used.
 *     (Default: NULL)
 *   - env: string|NULL. The environment variable which may contain the path to
 *     civicrm.settings.php. Set NULL to disable.
 *     (Default: CIVICRM_SETTINGS)
 *   - settingsFile: string|NULL. The full path to the civicrm.settings.php
 *     (Default: NULL)
 *   - prefetch: bool. Whether to load various caches.
 *     (Default: FALSE)
 *   - search: bool|string. Attempt to determine root+settings by searching
 *     the file system and checking against common Civi directory structures.
 *     Boolean TRUE means it should use a default (PWD).
 *     (Default: TRUE aka PWD)
 *
 * TODO: Consider adding flags for CMS bootstrap.
 *
 * @package Civi
 */
class Bootstrap {

  protected static $singleton = NULL;

  protected $options = array();

  /**
   * @return Bootstrap
   */
  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new Bootstrap(array(
        'dynamicSettingsFile' => dirname(__DIR__) . '/settings_location.php',
        'env' => 'CIVICRM_SETTINGS',
        'settingsFile' => NULL,
        'search' => TRUE,
        'cmsType' => NULL,
        'cmsRootPath' => NULL,
        'prefetch' => FALSE,
        'httpHost' => array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : '',
      ));
    }
    return self::$singleton;
  }

  /**
   * @param array $options
   *   See options in class doc.
   */
  public function __construct($options = array()) {
    $this->options = $options;
  }

  /**
   * @param array $options
   *   See options in class doc.
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * Bootstrap the CiviCRM runtime.
   *
   * @param array $options
   *   See options in class doc.
   * @throws \Exception
   */
  public function bootCms($options = array()) {
    if (empty($this->options['cmsType']) || empty($this->options['cmsRootPath'])) {
      list($this->options['cmsType'], $this->options['cmsRootPath']) = \_CiviVersion_\Util::findCMSRootPath();
    }
    if (empty($this->options['cmsRootPath']) || empty($this->options['cmsType']) || !file_exists($this->options['cmsRootPath'])) {
      throw new \Exception("Failed to parse or find a CMS");
    }

    $func = 'boot' . $this->options['cmsType'];
    if (!is_callable([$this, $func])) {
      throw new \Exception("Failed to locate boot function ($func)");
    }

    call_user_func([$this, $func], $this->options['cmsRootPath']);

    return $this;
  }

  /**
   * @param array $options
   * @return string
   * @throws \Exception
   */
  public function getCivicrmSettingsPhp($options) {
    if (!empty($options['dynamicSettingsFile']) && file_exists($options['dynamicSettingsFile'])) {
      include $options['dynamicSettingsFile'];
    }

    //   Path to the settings file.
    $settingFile = NULL;

    if (defined('CIVICRM_CONFDIR') && file_exists(CIVICRM_CONFDIR . '/civicrm.settings.php')) {
      $settingFile = CIVICRM_CONFDIR . '/civicrm.settings.php';
    }
    elseif (!empty($options['env']) && getenv($options['env']) && file_exists(getenv($options['env']))) {
      $settingFile = getenv($options['env']);
    }
    elseif (!empty($options['settingsFile']) && file_exists($options['settingsFile'])) {
      $settingFile = $options['settingsFile'];
    }
    elseif (!empty($options['search'])) {
      $settingFile = $this->findCivicrmSettingsPhp();
    }

    return $settingFile;
  }

  /**
   * Locate a civicrm.settings.php using normal directory structures.
   *
   * @param string $searchDir
   *   The directory from which to begin the upward search.
   * @return string $settingFile
   */
  protected function findCivicrmSettingsPhp($searchDir) {
    $cmsRoot = $this->options['cmsRootPath'];
    $cmsType = $this->options['cmsType'];
    $settingFile = NULL;
    switch ($cmsType) {
      case 'Backdrop':
        $settingFile = $this->findFirstFile(
           array_merge($this->findDrupalDirs($cmsRoot), array($cmsRoot)),
          'civicrm.settings.php'
        );
        break;

      case 'Drupal6':
      case 'Drupal8':
      case 'Drupal':
        $settingFile = $this->findFirstFile($this->findDrupalDirs($cmsRoot), 'civicrm.settings.php');
        break;

      case 'Joomla':
        // Note: Joomla technically has two copies of civicrm.settings.php with
        // slightly different values of CIVICRM_UF_BASEURL. It appears that Joomla
        // always used the admin copy for CLI/bin/extern scripts, so we do the
        // same. However, the arrangement seems gratuitous considering that WP
        // has the same frontend/backend split and does not need two copies of
        // civicrm.settings.php.

        $settingFile = $cmsRoot . 'administrator/components/com_civicrm/civicrm.settings.php';
        // $result =  $cmsRoot . 'components/com_civicrm/civicrm.settings.php';
        break;

      case 'Wordpress':
        $wpDirs = array(
          $cmsRoot . '/*/uploads/civicrm',
          $cmsRoot . '/*/plugins/civicrm',
          $cmsRoot . '/*/uploads/sites/*/civicrm',
          $cmsRoot . '/*/blogs.dir/*/files/civicrm',
        );
        $settingFile = $this->findFirstFile($wpDirs, 'civicrm.settings.php');
        break;
    }
    return $settingFile;
  }

  /**
   * @return $this
   * @throws \Exception
   */
  public function bootCivi($options = []) {
    // PRE-CONDITIONS: CMS has already been booted, and Civi is already installed.
    if (function_exists('civicrm_initialize')) {
      civicrm_initialize();
    }
    elseif (!defined('CIVICRM_SETTINGS_PATH')) {
      $this->options = $options = array_merge($this->options, $options);

      $settingFile = $this->getCivicrmSettingsPhp($options);
      if (empty($settingFile) || !file_exists($settingFile)) {
        trigger_error("Failed to locate civicrm.settings.php. Please boot with settingsFile, search, or CIVICRM_SETTINGS; or normalize your directory structure.", E_USER_ERROR);
        exit();
      }
      define('CIVICRM_SETTINGS_PATH', $settingFile);
    }

    if (defined('CIVICRM_SETTINGS_PATH')) {
      $error = @include_once CIVICRM_SETTINGS_PATH;
      if ($error == FALSE) {
        trigger_error("Could not load the CiviCRM settings file: {$settingFile}", E_USER_ERROR);
        exit();
      }

      if (PHP_SAPI === 'cli') {
        $_SERVER['SCRIPT_FILENAME'] = $this->options['cmsRootPath'] . '/index.php';
        $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
        $_SERVER['SERVER_SOFTWARE'] = NULL;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        if (ord($_SERVER['SCRIPT_NAME']) != 47) {
          $_SERVER['SCRIPT_NAME'] = '/' . $_SERVER['SCRIPT_NAME'];
        }
      }
    }
    // else if Joomla weirdness, do that
    else {
      throw new \Exception("This system does not appear to have CiviCRM");
    }

    global $civicrm_root;
    require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
    \CRM_Core_ClassLoader::singleton()->register();

    if (!empty($options['prefetch'])) {
      // I'm not sure why this is called explicitly during bootstrap
      // rather than lazily. However, it seems to be done by all
      // the existing bootstrap code. Perhaps initializing Config
      // has a side-effect of initializing other things?
      \CRM_Core_Config::singleton();
    }

    return $this;
  }

  public function bootBackdrop($cmsPath) {
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

    return $this;
  }

  public function bootDrupal($cmsPath) {
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

    return $this;
  }

  public function bootDrupal8($cmsRootPath) {
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

    return $this;
  }

  /**
   * TODO
   */
  public function bootJoomla($cmsRootPath) {
    return $this;
  }

  /**
   * @param string|array $dirs
   *   List of directories to check.
   * @param string|array $items
   *   List of globs to check in each directory.
   * @return null|string
   */
  protected function findFirstFile($dirs, $items) {
    $dirs = (array) $dirs;
    $items = (array) $items;
    foreach ($dirs as $dir) {
      foreach ($items as $item) {
        $matches = (array) glob("$dir/$item");
        if (isset($matches[0])) {
          return $matches[0];
        }
      }
    }
    return NULL;
  }

  /**
   * Get an ordered list of multisite dirs that might apply to this request.
   *
   * @param string $cmsRoot
   *   The root of the Drupal installation.
   * @return array
   */
  protected function findDrupalDirs($cmsRoot) {
    $dirs = array();
    $server = explode('.', implode('.', array_reverse(explode(':', rtrim($this->options['httpHost'], '.')))));
    for ($j = count($server); $j > 0; $j--) {
      $dirs[] = "$cmsRoot/sites/" . implode('.', array_slice($server, -$j));
    }
    $dirs[] = "$cmsRoot/sites/default";
    return $dirs;
  }

}

\Civi\Bootstrap::singleton()->bootCivi();
