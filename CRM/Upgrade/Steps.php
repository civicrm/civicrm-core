<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 * The `CRM_Upgrade_Steps` class tracks upgrade files (eg `CRM/Upgrade/Steps/*.up.php`
 * or `CRM/Upgrade/Steps/*.mysql.tpl`). It can report a list of pending upgrade steps
 * and toggle the "executed" flag.
 */
class CRM_Upgrade_Steps {

  /**
   * @var string
   */
  private $module;

  /**
   * @var array
   *   Array(string $fullPath => string $classPrefix).
   */
  private $paths;

  /**
   * CRM_Upgrade_Steps constructor.
   *
   * @param string $module
   * @param array $paths
   *   Array(string $fullPath => string $classPrefix).
   */
  public function __construct($module, $paths = array()) {
    if (!self::findCreateTable()) {
      CRM_Core_Error::fatal(ts('Failed to find or create upgrade table'));
    }
    $this->module = $module;
    $this->paths = $paths;
  }

  /**
   * Get a list of all upgrade steps (as objects/instances).
   *
   * @return array
   *   Array(CRM_Upgrade_Incremental_Interface).
   */
  public function getAllObjects() {
    $result = array();
    foreach ($this->paths as $path => $classPrefix) {
      if (is_dir($path)) {
        $phpFiles = CRM_Utils_File::findFiles($path, '*.up.php');
        foreach ($phpFiles as $phpFile) {
          $className = $this->toClassName($path, $classPrefix, $phpFile);
          $result[$phpFile] = new $className();
        }
        $sqlFiles = CRM_Utils_File::findFiles($path, '*.mysql.tpl');
        foreach ($sqlFiles as $sqlFile) {
          $result[$sqlFile] = new CRM_Upgrade_Incremental_SqlStep($sqlFile,
             'SqlStep:' . CRM_Utils_File::relativize($sqlFile, CRM_Utils_File::addTrailingSlash($path))
          );
        }
      }
    }
    ksort($result);
    return $result;
  }

  /**
   * @return array
   *   Array(CRM_Upgrade_Incremental_Interface).
   */
  public function getPendingObjects() {
    $result = array();
    foreach ($this->getAllObjects() as $upgrade) {
      /** @var CRM_Upgrade_Incremental_Interface $upgrade */
      if (!$this->isExecuted($upgrade->getName())) {
        $result[] = $upgrade;
      }
    }
    return $result;
  }

  /**
   * Determine whether the step has been executed before.
   *
   * @param string $name
   * @return bool
   */
  public function isExecuted($name) {
    return (bool) CRM_Core_DAO::singleValueQuery(
      'SELECT count(*) FROM civicrm_upgrade WHERE module = %1 AND name = %2',
      array(
        1 => array($this->module, 'String'),
        2 => array($name, 'String'),
      )
    );
  }

  /**
   * Specify whether the step has been executed before.
   *
   * @param string $name
   * @param bool $value
   * @return $this
   */
  public function setExecuted($name, $value) {
    $sqlParams = array(
      1 => array($this->module, 'String'),
      2 => array($name, 'String'),
    );
    if ($value) {
      CRM_Core_DAO::executeQuery('INSERT IGNORE INTO civicrm_upgrade (module, name) VALUES (%1,%2)', $sqlParams);
    }
    else {
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_upgrade WHERE module = %1 AND name = %2', $sqlParams);
    }
    return $this;
  }

  /**
   * @param string $absPath
   *   Base search path.
   * @param $classPrefix
   *   Class prefix of all files in the path.
   * @param $absFile
   *   File path.
   * @return string
   */
  protected function toClassName($absPath, $classPrefix, $absFile) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $absPath = strtr($absPath, '\\', '/');
      $absFile = strtr($absFile, '\\', '/');
    }

    $relFile = CRM_Utils_File::relativize($absFile, CRM_Utils_File::addTrailingSlash($absPath, '/'));
    $relFile = preg_replace('/\.up\.php$/', '', $relFile);
    return $classPrefix . strtr($relFile, '/', '_');
  }

  /**
   * Ensure that the required SQL table exists.
   *
   * @return bool
   *   TRUE if table now exists
   */
  private static function findCreateTable() {
    $checkTableSql = "show tables like 'civicrm_upgrade'";
    $foundName = CRM_Core_DAO::singleValueQuery($checkTableSql);
    if ($foundName == 'civicrm_upgrade') {
      return TRUE;
    }

    $fileName = dirname(__FILE__) . '/../../sql/civicrm_upgrade.mysql';

    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, $fileName);

    // Make sure it succeeded
    $foundName = CRM_Core_DAO::singleValueQuery($checkTableSql);
    return ($foundName == 'civicrm_upgrade');
  }

}
