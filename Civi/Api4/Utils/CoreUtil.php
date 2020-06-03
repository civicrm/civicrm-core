<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Utils;

use CRM_Core_DAO_AllCoreTables as AllCoreTables;

require_once 'api/v3/utils.php';

class CoreUtil {

  /**
   * todo this class should not rely on api3 code
   *
   * @param $entityName
   *
   * @return \CRM_Core_DAO|string
   *   The BAO name for use in static calls. Return doc block is hacked to allow
   *   auto-completion of static methods
   */
  public static function getBAOFromApiName($entityName) {
    if ($entityName === 'CustomValue' || strpos($entityName, 'Custom_') === 0) {
      return 'CRM_Core_BAO_CustomValue';
    }
    return \_civicrm_api3_get_BAO($entityName);
  }

  /**
   * Get table name of given entity
   *
   * @param string $entityName
   *
   * @return string
   */
  public static function getTableName($entityName) {
    if (strpos($entityName, 'Custom_') === 0) {
      $customGroup = substr($entityName, 7);
      return \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroup, 'table_name', 'name');
    }
    return AllCoreTables::getTableForEntityName($entityName);
  }

  /**
   * Given a sql table name, return the name of the api entity.
   *
   * @param $tableName
   * @return string|NULL
   */
  public static function getApiNameFromTableName($tableName) {
    $entityName = AllCoreTables::getBriefName(AllCoreTables::getClassForTable($tableName));
    if (!$entityName) {
      $customGroup = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $tableName, 'name', 'table_name');
      $entityName = $customGroup ? "Custom_$customGroup" : NULL;
    }
    return $entityName;
  }

  /**
   * Given an expression like "activity_date_time IS previous.month", rewrite to
   * an equivalent expression "activity_date_time BETWEEN {start-of-previous-month} AND {end-of-previous-month}.
   *
   * @param string $fieldName
   *   Ex: 'activity_date_time'
   * @param string $criteria
   *   Ex: ['IS' => 'previous.month']
   * @return array|NULL
   *   Array(string $newOperator, mixed $newCriteria).
   *   Ex: ['BETWEEN' => ['2020-05-01', '2020-06-01']]
   */
  public static function rewriteIsCriteria($fieldName, $criteria) {
    if ($criteria === 'null' || $criteria === 'NULL') {
      return ['IS NULL' => ''];
    }
    elseif ($criteria === 'not null' || $criteria === 'NOT NULL') {
      return ['IS NOT NULL' => ''];
    }

    $relDateFilters = \CRM_Core_OptionGroup::values('relative_date_filters');
    if (isset($relDateFilters[$criteria])) {
      list ($dateFrom, $dateTo) = \CRM_Utils_Date::getFromTo($criteria, NULL, NULL);
      return ['BETWEEN' => [\CRM_Utils_Date::mysqlToIso($dateFrom), \CRM_Utils_Date::mysqlToIso($dateTo)]];
    }

    return NULL;
  }

}
