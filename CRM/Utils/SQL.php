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
 * Just another collection of static utils functions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Utils_SQL {

  /**
   * Helper function for adding the permissioned subquery from one entity onto another
   *
   * @param string $entity
   * @param string $joinColumn
   * @return array
   */
  public static function mergeSubquery($entity, $joinColumn = 'id') {
    require_once 'api/v3/utils.php';
    $baoName = _civicrm_api3_get_BAO($entity);
    $bao = new $baoName();
    $clauses = $subclauses = array();
    foreach ((array) $bao->addSelectWhereClause() as $field => $vals) {
      if ($vals && $field == $joinColumn) {
        $clauses = array_merge($clauses, (array) $vals);
      }
      elseif ($vals) {
        $subclauses[] = "$field " . implode(" AND $field ", (array) $vals);
      }
    }
    if ($subclauses) {
      $clauses[] = "IN (SELECT `$joinColumn` FROM `" . $bao->tableName() . "` WHERE " . implode(' AND ', $subclauses) . ")";
    }
    return $clauses;
  }

  /**
   * Get current sqlModes of the session
   * @return array
   */
  public static function getSqlModes() {
    $sqlModes = explode(',', CRM_Core_DAO::singleValueQuery('SELECT @@sql_mode'));
    return $sqlModes;
  }

  /**
   * Checks if this system enforce the MYSQL mode ONLY_FULL_GROUP_BY.
   * This function should be named supportsAnyValueAndEnforcesFullGroupBY(),
   * but should be deprecated instead.
   *
   * @return mixed
   * @deprecated
   */
  public static function supportsFullGroupBy() {
    // CRM-21455 MariaDB 10.2 does not support ANY_VALUE
    $version = self::getDatabaseVersion();

    if (stripos($version, 'mariadb') !== FALSE) {
      return FALSE;
    }

    return version_compare($version, '5.7', '>=');
  }

  /**
   * Disable ONLY_FULL_GROUP_BY for MySQL versions lower then 5.7
   *
   * @return bool
   */
  public static function disableFullGroupByMode() {
    $sqlModes = self::getSqlModes();

    // Disable only_full_group_by mode for lower sql versions.
    if (!self::supportsFullGroupBy() || (!empty($sqlModes) && !in_array('ONLY_FULL_GROUP_BY', $sqlModes))) {
      if ($key = array_search('ONLY_FULL_GROUP_BY', $sqlModes)) {
        unset($sqlModes[$key]);
        CRM_Core_DAO::executeQuery("SET SESSION sql_mode = '" . implode(',', $sqlModes) . "'");
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * CHeck if ONLY_FULL_GROUP_BY is in the global sql_modes
   * @return bool
   */
  public static function isGroupByModeInDefault() {
    $sqlModes = explode(',', CRM_Core_DAO::singleValueQuery('SELECT @@global.sql_mode'));
    if (!in_array('ONLY_FULL_GROUP_BY', $sqlModes)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Is the Database set up to handle acceents.
   * @warning This function was introduced in attempt to determine the reason why the test getInternationalStrings was failing on ubu1604 but passing on ubu1204-5
   * This function should not be used as the basis of further work as the reasoning is not perfact and is giving false failures.
   * @return bool
   */
  public static function supportStorageOfAccents() {
    $charSetDB = CRM_Core_DAO::executeQuery("SHOW VARIABLES LIKE 'character_set_database'")->fetchAll();
    $charSet = $charSetDB[0]['Value'];
    if ($charSet == 'utf8') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Does the DB version support mutliple locks per
   *
   * https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html#function_get-lock
   *
   * As an interim measure we ALSO require CIVICRM_SUPPORT_MULTIPLE_LOCKS to be defined.
   *
   * This is a conservative measure to introduce the change which we expect to deprecate later.
   *
   * @todo we only check mariadb & mysql right now but maybe can add percona.
   */
  public static function supportsMultipleLocks() {
    if (!defined('CIVICRM_SUPPORT_MULTIPLE_LOCKS')) {
      return FALSE;
    }
    static $isSupportLocks = NULL;
    if (!isset($isSupportLocks)) {
      $version = self::getDatabaseVersion();
      if (stripos($version, 'mariadb') !== FALSE) {
        $isSupportLocks = version_compare($version, '10.0.2', '>=');
      }
      else {
        $isSupportLocks = version_compare($version, '5.7.5', '>=');
      }
    }

    return $isSupportLocks;
  }

  /**
   * Get the version string for the database.
   *
   * @return string
   */
  public static function getDatabaseVersion() {
    return CRM_Core_DAO::singleValueQuery('SELECT VERSION()');
  }

}
