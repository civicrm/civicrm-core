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

use Civi\Api4\CustomGroup;
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
      return 'CRM_Contact_BAO_Contact';
    }
    return \_civicrm_api3_get_BAO($entityName);
  }

  /**
   * Get table name of given Custom group
   *
   * @param string $customGroupName
   *
   * @return string
   */
  public static function getCustomTableByName($customGroupName) {
    return CustomGroup::get()
      ->addSelect('table_name')
      ->addWhere('name', '=', $customGroupName)
      ->execute()
      ->first()['table_name'];
  }

  /**
   * Given a sql table name, return the name of the api entity.
   *
   * @param $tableName
   * @return string
   */
  public static function getApiNameFromTableName($tableName) {
    return AllCoreTables::getBriefName(AllCoreTables::getClassForTable($tableName));
  }

}
