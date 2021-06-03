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

use Civi\API\Request;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

class CoreUtil {

  /**
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
    $dao = self::getApiClass($entityName)::getInfo()['dao'] ?? NULL;
    if (!$dao) {
      return NULL;
    }
    $bao = str_replace("DAO", "BAO", $dao);
    // Check if this entity actually has a BAO. Fall back on the DAO if not.
    $file = strtr($bao, '_', '/') . '.php';
    return stream_resolve_include_path($file) ? $bao : $dao;
  }

  /**
   * @param $entityName
   * @return string|\Civi\Api4\Generic\AbstractEntity
   */
  public static function getApiClass($entityName) {
    if (strpos($entityName, 'Custom_') === 0) {
      $groupName = substr($entityName, 7);
      return self::isCustomEntity($groupName) ? 'Civi\Api4\CustomValue' : NULL;
    }
    // Because "Case" is a reserved php keyword
    $className = 'Civi\Api4\\' . ($entityName === 'Case' ? 'CiviCase' : $entityName);
    return class_exists($className) ? $className : NULL;
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
    // Real entities
    if ($entityName) {
      // Verify class exists
      return self::getApiClass($entityName) ? $entityName : NULL;
    }
    // Multi-value custom group pseudo-entities
    $customGroup = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $tableName, 'name', 'table_name');
    return self::isCustomEntity($customGroup) ? "Custom_$customGroup" : NULL;
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

  /**
   * Checks if a custom group exists and is multivalued
   *
   * @param $customGroupName
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private static function isCustomEntity($customGroupName) {
    return $customGroupName && \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupName, 'is_multiple', 'name');
  }

  /**
   * Check if current user is authorized to perform specified action on a given entity.
   *
   * @param string $entityName
   * @param string $actionName
   * @param array $record
   * @return bool
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkAccess(string $entityName, string $actionName, array $record) {
    $action = Request::create($entityName, $actionName, ['version' => 4]);
    // This checks gatekeeper permissions
    $granted = $action->isAuthorized();
    // For get actions, just run a get and ACLs will be applied to the query.
    // It's a cheap trick and not as efficient as not running the query at all,
    // but BAO::checkAccess doesn't consistently check permissions for the "get" action.
    if (is_a($action, '\Civi\Api4\Generic\DAOGetAction')) {
      $granted = $granted && $action->addSelect('id')->addWhere('id', '=', $record['id'])->execute()->count();
    }
    else {
      // If entity has a BAO, run the BAO::checkAccess function, which will call the hook
      $baoName = self::getBAOFromApiName($entityName);
      // CustomValue also requires the name of the group
      if ($baoName === 'CRM_Core_BAO_CustomValue') {
        $granted = \CRM_Core_BAO_CustomValue::checkAccess($actionName, $record, NULL, $granted, substr($entityName, 7));
      }
      elseif ($baoName) {
        $granted = $baoName::checkAccess($actionName, $record, NULL, $granted);
      }
      // Otherwise, call the hook directly
      else {
        \CRM_Utils_Hook::checkAccess($entityName, $actionName, $record, NULL, $granted);
      }
    }
    return $granted;
  }

}
