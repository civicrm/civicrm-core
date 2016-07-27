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

  private static $tables = NULL;
  private static $daoToClass = NULL;
  private static $entityTypes = NULL;

  public static function init($fresh = FALSE) {
    static $init = FALSE;
    if ($init && !$fresh) {
      return;
    }

    $file = preg_replace('/\.php$/', '.data.php', __FILE__);
    $entityTypes = require $file;
    CRM_Utils_Hook::entityTypes($entityTypes);

    self::$entityTypes = array();
    self::$tables = array();
    self::$daoToClass = array();
    foreach ($entityTypes as $entityType) {
      self::registerEntityType($entityType['name'], $entityType['class'],
        $entityType['table']);
    }

    $init = TRUE;
  }

  /**
   * (Quasi-Private) Do not call externally (except for unit-testing)
   */
  public static function registerEntityType($daoName, $className, $tableName) {
    self::$daoToClass[$daoName] = $className;
    self::$tables[$tableName] = $className;
    self::$entityTypes[$className] = array(
      'name' => $daoName,
      'class' => $className,
      'table' => $tableName,
    );
  }

  /**
   * @return array
   *   Ex: $result['CRM_Contact_DAO_Contact']['table'] == 'civicrm_contact';
   */
  public static function get() {
    self::init();
    return self::$entityTypes;
  }

  /**
   * @return array
   *   List of SQL table names.
   */
  public static function tables() {
    self::init();
    return self::$tables;
  }

  /**
   * @return array
   *   Mapping from brief-names to class-names.
   *   Ex: $result['Contact'] == 'CRM_Contact_DAO_Contact'.
   */
  public static function daoToClass() {
    self::init();
    return self::$daoToClass;
  }

  /**
   * @return array
   *   Mapping from table-names to class-names.
   *   Ex: $result['civicrm_contact'] == 'CRM_Contact_DAO_Contact'.
   */
  public static function getCoreTables() {
    return self::tables();
  }

  /**
   * Determine whether $tableName is a core table.
   *
   * @param string $tableName
   * @return bool
   */
  public static function isCoreTable($tableName) {
    return FALSE !== array_search($tableName, self::tables());
  }

  public static function getCanonicalClassName($className) {
    return str_replace('_BAO_', '_DAO_', $className);
  }

  /**
   * @return array
   *   List of class names.
   */
  public static function getClasses() {
    return array_values(self::daoToClass());
  }

  public static function getClassForTable($tableName) {
    return CRM_Utils_Array::value($tableName, self::tables());
  }

  /**
   * Given a brief-name, determine the full class-name.
   *
   * @param string $daoName
   *   Ex: 'Contact'.
   * @return string|NULL
   *   Ex: 'CRM_Contact_DAO_Contact'.
   */
  public static function getFullName($daoName) {
    return CRM_Utils_Array::value($daoName, self::daoToClass());
  }

  /**
   * Given a full class-name, determine the brief-name.
   *
   * @param string $className
   *   Ex: 'CRM_Contact_DAO_Contact'.
   * @return string|NULL
   *   Ex: 'Contact'.
   */
  public static function getBriefName($className) {
    return CRM_Utils_Array::value($className, array_flip(self::daoToClass()));
  }

  /**
   * @param string $className DAO or BAO name
   * @return string|FALSE SQL table name
   */
  public static function getTableForClass($className) {
    return array_search(self::getCanonicalClassName($className),
      self::tables());
  }

  public static function reinitializeCache($fresh = FALSE) {
    self::init($fresh);
  }

}
