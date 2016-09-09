<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Core_ClassLoader {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   */
  private static $_singleton = NULL;

  /**
   * The classes in CiviTest have ucky, non-standard naming.
   *
   * @var array
   *   Array(string $className => string $filePath).
   */
  private $civiTestClasses;

  /**
   * @param bool $force
   *
   * @return object
   */
  public static function &singleton($force = FALSE) {
    if ($force || self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_ClassLoader();
    }
    return self::$_singleton;
  }

  /**
   * @var bool TRUE if previously registered
   */
  protected $_registered;

  /**
   */
  protected function __construct() {
    $this->_registered = FALSE;
    $this->civiTestClasses = array(
      'CiviCaseTestCase',
      'CiviDBAssert',
      'CiviMailUtils',
      'CiviReportTestCase',
      'CiviSeleniumTestCase',
      'CiviTestSuite',
      'CiviUnitTestCase',
      'CiviEndToEndTestCase',
      'Contact',
      'ContributionPage',
      'Custom',
      'Event',
      'Membership',
      'Participant',
      'PaypalPro',
    );
  }

  /**
   * Registers this instance as an autoloader.
   *
   * @param bool $prepend
   *   Whether to prepend the autoloader or not.
   *
   * @api
   */
  public function register($prepend = FALSE) {
    if ($this->_registered) {
      return;
    }
    $civicrm_base_path = dirname(dirname(__DIR__));

    require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

    // we do this to prevent a autoloader errors with joomla / 3rd party packages
    // use absolute path since we dont know the content of include_path as yet
    // CRM-11304
    // TODO Remove this autoloader. For civicrm-core and civicrm-packages, the composer autoloader works fine.
    // Extensions rely on include_path-based autoloading
    spl_autoload_register(array($this, 'loadClass'), TRUE, $prepend);
    $this->initHtmlPurifier($prepend);

    $this->_registered = TRUE;
    $packages_path = implode(DIRECTORY_SEPARATOR, array($civicrm_base_path, 'packages'));
    $include_paths = array(
      '.',
      $civicrm_base_path,
      $packages_path,
    );
    $include_paths = implode(PATH_SEPARATOR, $include_paths);
    set_include_path($include_paths . PATH_SEPARATOR . get_include_path());
    require_once "$civicrm_base_path/vendor/autoload.php";
  }

  /**
   * Initialize HTML purifier class.
   *
   * @param string $prepend
   */
  public function initHtmlPurifier($prepend) {
    if (class_exists('HTMLPurifier_Bootstrap')) {
      // HTMLPurifier is already initialized, e.g. by the Drupal module.
      return;
    }

    $htmlPurifierPath = $this->getHtmlPurifierPath();

    if (FALSE === $htmlPurifierPath) {
      // No HTMLPurifier available, e.g. during installation.
      return;
    }
    require_once $htmlPurifierPath;
    spl_autoload_register(array('HTMLPurifier_Bootstrap', 'autoload'), TRUE, $prepend);
  }

  /**
   * @return string|false
   *   Path to the file where the class HTMLPurifier_Bootstrap is defined, or
   *   FALSE, if such a file does not exist.
   */
  private function getHtmlPurifierPath() {
    if (function_exists('libraries_get_path')
      && ($path = libraries_get_path('htmlpurifier'))
      && file_exists($file = $path . '/library/HTMLPurifier/Bootstrap.php')
    ) {
      // We are in Drupal 7, and the HTMLPurifier module is installed.
      // Use Drupal's HTMLPurifier path, to avoid conflicts.
      // @todo Verify that we are really in Drupal 7, and not in some other
      // environment that happens to provide a 'libraries_get_path()' function.
      return $file;
    }

    // we do this to prevent a autoloader errors with joomla / 3rd party packages
    // Use absolute path, since we don't know the content of include_path yet.
    // CRM-11304
    $file = dirname(__FILE__) . '/../../packages/IDS/vendors/htmlpurifier/HTMLPurifier/Bootstrap.php';
    if (file_exists($file)) {
      return $file;
    }

    return FALSE;
  }

  /**
   * @param $class
   */
  public function loadClass($class) {
    if (
      // Only load classes that clearly belong to CiviCRM.
      // Note: api/v3 does not use classes, but api_v3's test-suite does
      (0 === strncmp($class, 'CRM_', 4) || 0 === strncmp($class, 'api_v3_', 7) || 0 === strncmp($class, 'WebTest_', 8) || 0 === strncmp($class, 'E2E_', 4)) &&
      // Do not load PHP 5.3 namespaced classes.
      // (in a future version, maybe)
      FALSE === strpos($class, '\\')
    ) {
      $file = strtr($class, '_', '/') . '.php';
      // There is some question about the best way to do this.
      // "require_once" is nice because it's simple and throws
      // intelligible errors.
      if (FALSE != stream_resolve_include_path($file)) {
        require_once $file;
      }
    }
    elseif (in_array($class, $this->civiTestClasses)) {
      $file = "tests/phpunit/CiviTest/{$class}.php";
      if (FALSE != stream_resolve_include_path($file)) {
        require_once $file;
      }
    }
    elseif ($class === 'CiviSeleniumSettings') {
      if (!empty($GLOBALS['_CV'])) {
        require_once 'tests/phpunit/CiviTest/CiviSeleniumSettings.auto.php';
      }
      elseif (CRM_Utils_File::isIncludable('tests/phpunit/CiviTest/CiviSeleniumSettings.php')) {
        require_once 'tests/phpunit/CiviTest/CiviSeleniumSettings.php';
      }
    }
  }

}
