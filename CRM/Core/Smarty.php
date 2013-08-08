<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Fix for bug CRM-392. Not sure if this is the best fix or it will impact
 * other similar PEAR packages. doubt it
 */
if (!class_exists('Smarty')) {
  require_once 'Smarty/Smarty.class.php';
}

/**
 *
 */
class CRM_Core_Smarty extends Smarty {
  CONST
    // use print.tpl and bypass the CMS. Civi prints a valid html file
    PRINT_PAGE = 1,
    // this and all the below bypasses the CMS html surronding it and assumes we will embed this within other pages
    PRINT_SNIPPET = 2,
    // sends the generated html to the chosen pdf engine
    PRINT_PDF = 3,
    // this options also skips the enclosing form html and does not
    // generate any of the hidden fields, most notably qfKey
    // this is typically used in ajax scripts to embed form snippets based on user choices
    PRINT_NOFORM = 4,
    // this prints a complete form and also generates a qfKey, can we replace this with
    // snippet = 2?? Does the constant _NOFFORM do anything?
    PRINT_QFKEY = 5,
    // this sends the output back in json
    PRINT_JSON = 6;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * class constructor
   *
   * @return CRM_Core_Smarty
   * @access private
   */
  private function __construct() {
    parent::__construct();
  }

  private function initialize( ) {
    $config = CRM_Core_Config::singleton();

    if (isset($config->customTemplateDir) && $config->customTemplateDir) {
      $this->template_dir = array_merge(array($config->customTemplateDir),
        $config->templateDir
      );
    }
    else {
      $this->template_dir = $config->templateDir;
    }
    $this->compile_dir = $config->templateCompileDir;

    // check and ensure it is writable
    // else we sometime suppress errors quietly and this results
    // in blank emails etc
    if (!is_writable($this->compile_dir)) {
      echo "CiviCRM does not have permission to write temp files in {$this->compile_dir}, Exiting";
      exit();
    }

    //Check for safe mode CRM-2207
    if (ini_get('safe_mode')) {
      $this->use_sub_dirs = FALSE;
    }
    else {
      $this->use_sub_dirs = TRUE;
    }

    $customPluginsDir = NULL;
    if (isset($config->customPHPPathDir)) {
      $customPluginsDir =
        $config->customPHPPathDir . DIRECTORY_SEPARATOR .
        'CRM'                     . DIRECTORY_SEPARATOR .
        'Core'                    . DIRECTORY_SEPARATOR .
        'Smarty'                  . DIRECTORY_SEPARATOR .
        'plugins'                 . DIRECTORY_SEPARATOR;
      if (!file_exists($customPluginsDir)) {
        $customPluginsDir = NULL;
      }
    }

    if ($customPluginsDir) {
      $this->plugins_dir = array($customPluginsDir, $config->smartyDir . 'plugins', $config->pluginsDir);
    }
    else {
      $this->plugins_dir = array($config->smartyDir . 'plugins', $config->pluginsDir);
    }

    // add the session and the config here
    $session = CRM_Core_Session::singleton();

    $this->assign_by_ref('config', $config);
    $this->assign_by_ref('session', $session);

    // check default editor and assign to template
    $defaultWysiwygEditor = $session->get('defaultWysiwygEditor');
    if (!$defaultWysiwygEditor && !CRM_Core_Config::isUpgradeMode()) {
      $defaultWysiwygEditor = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'editor_id'
      );
      // For logged-in users, store it in session to reduce db calls
      if ($session->get('userID')) {
        $session->set('defaultWysiwygEditor', $defaultWysiwygEditor);
      }
    }

    $this->assign('defaultWysiwygEditor', $defaultWysiwygEditor);

    global $tsLocale;
    $this->assign('tsLocale', $tsLocale);

    // CRM-7163 hack: we don’t display langSwitch on upgrades anyway
    if (!CRM_Core_Config::isUpgradeMode()) {
      $this->assign('langSwitch', CRM_Core_I18n::languages(TRUE));
    }

    $this->register_function('crmURL', array('CRM_Utils_System', 'crmURL'));
  }

  /**
   * Static instance provider.
   *
   * Method providing static instance of SmartTemplate, as
   * in Singleton pattern.
   */
  static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Core_Smarty( );
      self::$_singleton->initialize( );

      self::registerStringResource();
    }
    return self::$_singleton;
  }

  /**
   * executes & returns or displays the template results
   *
   * @param string $resource_name
   * @param string $cache_id
   * @param string $compile_id
   * @param boolean $display
   */
  function fetch($resource_name, $cache_id = NULL, $compile_id = NULL, $display = FALSE) {
    return parent::fetch($resource_name, $cache_id, $compile_id, $display);
  }

  function appendValue($name, $value) {
    $currentValue = $this->get_template_vars($name);
    if (!$currentValue) {
      $this->assign($name, $value);
    }
    else {
      if (strpos($currentValue, $value) === FALSE) {
        $this->assign($name, $currentValue . $value);
      }
    }
  }

  function clearTemplateVars() {
    foreach (array_keys($this->_tpl_vars) as $key) {
      if ($key == 'config' || $key == 'session') {
        continue;
      }
      unset($this->_tpl_vars[$key]);
    }
  }

  static function registerStringResource() {
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();
  }

  function addTemplateDir($path) {
    if ( is_array( $this->template_dir ) ) {
      array_unshift( $this->template_dir, $path );
    } else {
      $this->template_dir = array( $path, $this->template_dir );
    }

  }
}

