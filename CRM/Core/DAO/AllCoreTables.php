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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Core_DAO_AllCoreTables {

  static private $tables = NULL;
  static private $daoToClass = NULL;
  static private $entityTypes = NULL;

  static public function init($fresh = FALSE) {
    static $init = FALSE;
    if ($init && !$fresh) { return;
    }

    $file = preg_replace('/\.php$/', '.data.php', __FILE__);
    $entityTypes = require $file;
    CRM_Utils_Hook::entityTypes($entityTypes);

    self::$entityTypes = array();
    self::$tables = array();
    self::$daoToClass = array();
    foreach ($entityTypes as $entityType) {
      self::registerEntityType($entityType['name'], $entityType['class'], $entityType['table']);
    }

    $init = TRUE;
  }

  /**
   * (Quasi-Private) Do not call externally (except for unit-testing)
   */
  static public function registerEntityType($daoName, $className, $tableName) {
    self::$daoToClass[$daoName] = $className;
    self::$tables[$tableName] = $className;
    self::$entityTypes[$className] = array(
      'name' => $daoName,
      'class' => $className,
      'table' => $tableName,
    );
  }

  static public function get() {
    self::init();
    return self::$entityTypes;
  }

  static public function tables() {
    self::init();
    return self::$tables;
  }

  static public function daoToClass() {
    self::init();
    return self::$daoToClass;
  }

  static public function getCoreTables() {
    return self::tables();
  }

  static public function isCoreTable($tableName) {
    return FALSE !== array_search($tableName, self::tables());
  }

  static public function getCanonicalClassName($className) {
    return str_replace('_BAO_', '_DAO_', $className);
  }

  static public function getClasses() {
    return array_values(self::daoToClass());
  }

  static public function getClassForTable($tableName) {
    return CRM_Utils_Array::value($tableName, self::tables());
  }

  static public function getFullName($daoName) {
    return CRM_Utils_Array::value($daoName, self::daoToClass());
  }

  static public function getBriefName($className) {
    return CRM_Utils_Array::value($className, array_flip(self::daoToClass()));
  }

  /**
   * @param string $className DAO or BAO name
   * @return string|FALSE SQL table name
   */
  static public function getTableForClass($className) {
    return array_search(self::getCanonicalClassName($className), self::tables());
  }

  static public function reinitializeCache($fresh = FALSE) {
    self::init($fresh);
  }

}
