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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
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
    $this->module = $module;
    $this->paths = $paths;
  }

  /**
   * Get a list of all upgrade steps (as class names).
   *
   * @return array
   *   Array(string $file => string $className).
   */
  public function getAllClasses() {
    $result = array();
    foreach ($this->paths as $path => $classPrefix) {
      if (is_dir($path)) {
        $files = CRM_Utils_File::findFiles($path, '*.up.php');
        foreach ($files as $file) {
          $result[$file] = $this->toClassName($path, $classPrefix, $file);
        }
      }
    }
    ksort($result);
    return $result;
  }

  /**
   * Get a list of all upgrade steps (as objects/instances).
   *
   * @return array
   *   Array(CRM_Upgrade_Incremental_Interface).
   */
  public function getAllObjects() {
    $result = array();
    foreach ($this->getAllClasses() as $file => $className) {
      $result[$className] = new $className();
    }
    return $result;
  }

  /**
   * @return array
   *   Array(CRM_Upgrade_Incremental_Interface).
   */
  public function getPendingObjects() {
    $result = array();
    foreach ($this->getAllClasses() as $file => $className) {
      if ($this->isPending($className)) {
        $result[] = new $className();
      }
    }
    return $result;
  }

  /**
   * @param string $className
   * @return bool
   */
  public function isPending($className) {
    return TRUE;
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

}
