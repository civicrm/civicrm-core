<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for Site Url.
 */
class CRM_Admin_Form_Setting_UF extends CRM_Admin_Form_Setting {

  protected $_settings = array();

  protected $_uf = NULL;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $this->_uf = $config->userFramework;
    $this->_settings['syncCMSEmail'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;

    if ($this->_uf == 'WordPress') {
      $this->_settings['wpBasePage'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;
    }

    CRM_Utils_System::setTitle(
      ts('Settings - %1 Integration', array(1 => $this->_uf))
    );

    if ($config->userSystem->is_drupal) {
      $this->_settings['userFrameworkUsersTableName'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;
    }

    // find out if drupal has its database prefixed
    global $databases;
    $drupal_prefix = '';
    if (isset($databases['default']['default']['prefix'])) {
      if (is_array($databases['default']['default']['prefix'])) {
        $drupal_prefix = $databases['default']['default']['prefix']['default'];
      }
      else {
        $drupal_prefix = $databases['default']['default']['prefix'];
      }
    }

    if (
      function_exists('module_exists') &&
      module_exists('views') &&
      (
        $config->dsn != $config->userFrameworkDSN || !empty($drupal_prefix)
      )
    ) {
      $dsnArray = DB::parseDSN($config->dsn);
      $tableNames = CRM_Core_DAO::getTableNames();
      $tablePrefixes = '';
      if ($config->userFramework === 'Backdrop') {
        // Pre-empt Backdrop's DSN translation so that it doesn't clobber our table prefixes.
        $tablePrefixes .= $this->exportStaticFunction('normalizeBackdropDsn', '_settings_db_array');
        $tablePrefixes .= 'if (!empty($database)) { $databases[\'default\'][\'default\'] = _settings_db_array($database); unset($database); }';
        $tablePrefixes .= "\n\n";
      }
      $tablePrefixes .= '$databases[\'default\'][\'default\'][\'prefix\']= array(';
      $tablePrefixes .= "\n  'default' => '$drupal_prefix',"; // add default prefix: the drupal database prefix
      $prefix = "";
      if ($config->dsn != $config->userFrameworkDSN) {
        $prefix = "`{$dsnArray['database']}`.";
      }
      foreach ($tableNames as $tableName) {
        $tablePrefixes .= "\n  '" . str_pad($tableName . "'", 41) . " => '{$prefix}',";
      }
      $tablePrefixes .= "\n);";
      $this->assign('tablePrefixes', $tablePrefixes);
    }

    parent::buildQuickForm();
  }

  /**
   * @param string $staticName
   *   Name of an existing static function.
   * @param string $globalName
   *   Name of the new global function.
   * @return string
   *   PHP code of the global function
   */
  public function exportStaticFunction($staticName, $globalName) {
    $func = new ReflectionMethod(__CLASS__, $staticName);
    $filename = $func->getFileName();
    $startLine = $func->getStartLine() - 1;
    $endLine = $func->getEndLine();
    $lines = file($filename);
    $lines[$startLine] = preg_replace('/^\s*(public\s*|private\s*|final\s*|static\s*)*\s*function\s+(\w+)/', 'function ' . $globalName, $lines[$startLine]);
    return implode("", array_slice($lines, $startLine, $endLine - $startLine));
  }

  /**
   * By default, Backdrop's settings.php sets a global string `$database`, which
   * is later converted to array `$databases`. However, that will trample any
   * tablePrefixes that we set. We need it converted sooner.
   */
  public static function normalizeBackdropDsn($database) {
    $database_parts = parse_url($database);
    if (!$database_parts) {
      trigger_error('The database setting could not be parsed. Please check the $database setting in settings.php.', E_USER_ERROR);
    }
    return array(
      'driver' => $database_parts['scheme'],
      'database' => rawurldecode(substr($database_parts['path'], 1)),
      'username' => isset($database_parts['user']) ? rawurldecode($database_parts['user']) : '',
      'password' => isset($database_parts['pass']) ? rawurldecode($database_parts['pass']) : '',
      'host' => $database_parts['host'],
      'port' => isset($database_parts['port']) ? $database_parts['port'] : NULL,
      'prefix' => !empty($database_prefix) ? $database_prefix : '',
    );
  }

}
