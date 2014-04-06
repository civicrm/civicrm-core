<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_ClassLoader {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  static function &singleton($force = FALSE) {
    if ($force || self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_ClassLoader();
    }
    return self::$_singleton;
  }

  /**
   * @var bool TRUE if previously registered
   */
  protected $_registered;

  protected function __construct() {
    $this->_registered = FALSE;
  }

  /**
   * Registers this instance as an autoloader.
   *
   * @param Boolean $prepend Whether to prepend the autoloader or not
   *
   * @api
   */
  function register($prepend = FALSE) {
    if ($this->_registered) {
      return;
    }
    $civicrm_base_path = dirname(dirname(__DIR__));

    require_once dirname(dirname(__DIR__)) . '/packages/vendor/autoload.php';

    // we do this to prevent a autoloader errors with joomla / 3rd party packages
    // use absolute path since we dont know the content of include_path as yet
    // CRM-11304

    // since HTML Purifier could potentially be loaded / used by other modules / components
    // lets check it its already loaded
    // we also check if the bootstrap file exists since during install of a drupal distro profile
    // the files might not exists, in which case we skip loading the file
    // if you change the below, please test on Joomla and also PCP pages
    $includeHTMLPurifier = TRUE;
    $htmlPurifierPath = "$civicrm_base_path/packages/IDS/vendors/htmlpurifier/HTMLPurifier/Bootstrap.php";
    if (
      class_exists('HTMLPurifier_Bootstrap') ||
      !file_exists($htmlPurifierPath)
    ) {
      $includeHTMLPurifier = FALSE;
    }
    else {
      require_once $htmlPurifierPath;
    }

    // TODO Remove this autoloader. For civicrm-core and civicrm-packages, the composer autoloader works fine.
    // Extensions rely on include_path-based autoloading
    spl_autoload_register(array($this, 'loadClass'), TRUE, $prepend);
    if ($includeHTMLPurifier) {
      spl_autoload_register(array('HTMLPurifier_Bootstrap', 'autoload'), TRUE, $prepend);
    }

    $this->_registered = TRUE;
    $packages_path = implode(DIRECTORY_SEPARATOR, array($civicrm_base_path, 'packages'));
    $include_paths = array(
      '.',
      $civicrm_base_path,
      $packages_path
    );
    $include_paths = implode(PATH_SEPARATOR, $include_paths);
    set_include_path($include_paths . PATH_SEPARATOR . get_include_path());
    require_once "$civicrm_base_path/packages/vendor/autoload.php";
  }

  function loadClass($class) {
    if (
      // Only load classes that clearly belong to CiviCRM.
      0 === strncmp($class, 'CRM_', 4) &&
      // Do not load PHP 5.3 namespaced classes.
      // (in a future version, maybe)
      FALSE === strpos($class, '\\')
    ) {
      $file = strtr($class, '_', '/') . '.php';
      // There is some question about the best way to do this.
      // "require_once" is nice because it's simple and throws
      // intelligible errors.  The down side is that autoloaders
      // down the chain cannot try to find the file if we fail.
      require_once ($file);
    }
  }
}
