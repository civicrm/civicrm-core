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
   * @return string[]
   */
  public static function getOperators() {
    $operators = \CRM_Core_DAO::acceptedSQLOperators();
    $operators[] = 'CONTAINS';
    $operators[] = 'IS EMPTY';
    $operators[] = 'IS NOT EMPTY';
    return $operators;
  }

  /**
   * For a given API Entity, return the types of custom fields it supports and the column they join to.
   *
   * @param string $entityName
   * @return array|mixed|null
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getCustomGroupExtends(string $entityName) {
    // Custom_group.extends pretty much maps 1-1 with entity names, except for a couple oddballs (Contact, Participant).
    switch ($entityName) {
      case 'Contact':
        return [
          'extends' => array_merge(['Contact'], array_keys(\CRM_Core_SelectValues::contactType())),
          'column' => 'id',
        ];

      case 'Participant':
        return [
          'extends' => ['Participant', 'ParticipantRole', 'ParticipantEventName', 'ParticipantEventType'],
          'column' => 'id',
        ];

      case 'RelationshipCache':
        return [
          'extends' => ['Relationship'],
          'column' => 'relationship_id',
        ];
    }
    if (array_key_exists($entityName, \CRM_Core_SelectValues::customGroupExtends())) {
      return [
        'extends' => [$entityName],
        'column' => 'id',
      ];
    }
    return NULL;
  }

}
