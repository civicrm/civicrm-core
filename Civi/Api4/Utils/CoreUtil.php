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

namespace Civi\Api4\Utils;

use Civi\API\Exception\NotImplementedException;
use Civi\API\Exception\UnauthorizedException;
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
    $dao = AllCoreTables::getFullName($entityName);
    return $dao ? AllCoreTables::getBAOClassName($dao) : NULL;
  }

  /**
   * @param $entityName
   * @return string|\Civi\Api4\Generic\AbstractEntity
   */
  public static function getApiClass($entityName) {
    $className = 'Civi\Api4\\' . $entityName;
    if (class_exists($className)) {
      return $className;
    }
    return self::getInfoItem($entityName, 'class');
  }

  /**
   * Get a piece of metadata about an entity
   *
   * @param string $entityName
   * @param string $keyToReturn
   * @return mixed
   */
  public static function getInfoItem(string $entityName, string $keyToReturn) {
    $provider = \Civi::service('action_object_provider');
    return $provider->getEntities()[$entityName][$keyToReturn] ?? NULL;
  }

  /**
   * Get name of unique identifier, typically "id"
   * @param string $entityName
   * @return string
   */
  public static function getIdFieldName(string $entityName): string {
    return self::getInfoItem($entityName, 'primary_key')[0] ?? 'id';
  }

  /**
   * Get table name of given entity
   *
   * @param string $entityName
   *
   * @return string
   */
  public static function getTableName($entityName) {
    return self::getInfoItem($entityName, 'table_name');
  }

  /**
   * Given a sql table name, return the name of the api entity.
   *
   * @param $tableName
   * @return string|NULL
   */
  public static function getApiNameFromTableName($tableName) {
    $provider = \Civi::service('action_object_provider');
    foreach ($provider->getEntities() as $entityName => $info) {
      if (($info['table_name'] ?? NULL) === $tableName) {
        return $entityName;
      }
    }
    return NULL;
  }

  /**
   * @return string[]
   */
  public static function getOperators() {
    $operators = \CRM_Core_DAO::acceptedSQLOperators();
    $operators[] = 'CONTAINS';
    $operators[] = 'IS EMPTY';
    $operators[] = 'IS NOT EMPTY';
    $operators[] = 'REGEXP';
    $operators[] = 'NOT REGEXP';
    return $operators;
  }

  /**
   * For a given API Entity, return the types of custom fields it supports and the column they join to.
   *
   * @param string $entityName
   * @return array{extends: array, column: string, grouping: mixed}|null
   */
  public static function getCustomGroupExtends(string $entityName) {
    // Custom_group.extends pretty much maps 1-1 with entity names, except for Contact.
    switch ($entityName) {
      case 'Contact':
        return [
          'extends' => array_merge(['Contact'], array_keys(\CRM_Core_SelectValues::contactType())),
          'column' => 'id',
          'grouping' => ['contact_type', 'contact_sub_type'],
        ];

      case 'RelationshipCache':
        return [
          'extends' => ['Relationship'],
          'column' => 'relationship_id',
          'grouping' => 'relationship_type_id',
        ];
    }
    $customGroupExtends = array_column(\CRM_Core_BAO_CustomGroup::getCustomGroupExtendsOptions(), NULL, 'id');
    $extendsSubGroups = \CRM_Core_BAO_CustomGroup::getExtendsEntityColumnIdOptions();
    if (array_key_exists($entityName, $customGroupExtends)) {
      return [
        'extends' => [$entityName],
        'column' => 'id',
        'grouping' => ($customGroupExtends[$entityName]['grouping'] ?: array_column(\CRM_Utils_Array::findAll($extendsSubGroups, ['extends' => $entityName]), 'grouping', 'id')) ?: NULL,
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
  public static function isCustomEntity($customGroupName) {
    return $customGroupName && \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupName, 'is_multiple', 'name');
  }

  /**
   * Check if current user is authorized to perform specified action on a given entity.
   *
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @param array $record
   * @param int|string $userID
   *   Contact ID of the user we are testing,. 0 for the anonymous user.
   * @return bool
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkAccessRecord(\Civi\Api4\Generic\AbstractAction $apiRequest, array $record, int $userID) {

    // Super-admins always have access to everything
    if (\CRM_Core_Permission::check('all CiviCRM permissions and ACLs', $userID)) {
      return TRUE;
    }

    // For get actions, just run a get and ACLs will be applied to the query.
    // It's a cheap trick and not as efficient as not running the query at all,
    // but BAO::checkAccess doesn't consistently check permissions for the "get" action.
    if (is_a($apiRequest, '\Civi\Api4\Generic\DAOGetAction')) {
      return (bool) $apiRequest->addSelect('id')->addWhere('id', '=', $record['id'])->execute()->count();
    }

    $event = new \Civi\Api4\Event\AuthorizeRecordEvent($apiRequest, $record, $userID);
    \Civi::dispatcher()->dispatch('civi.api4.authorizeRecord', $event);

    // Note: $bao::_checkAccess() is a quasi-listener. TODO: Convert to straight-up listener.
    if ($event->isAuthorized() === NULL) {
      $baoName = self::getBAOFromApiName($apiRequest->getEntityName());
      if ($baoName && method_exists($baoName, '_checkAccess')) {
        $authorized = $baoName::_checkAccess($event->getEntityName(), $event->getActionName(), $event->getRecord(), $event->getUserID());
        $event->setAuthorized($authorized);
      }
      else {
        $event->setAuthorized(TRUE);
      }
    }
    return $event->isAuthorized();
  }

  /**
   * If the permissions of record $A are based on record $B, then use `checkAccessDelegated($B...)`
   * to make see if access to $B is permitted.
   *
   * @param string $entityName
   * @param string $actionName
   * @param array $record
   * @param int $userID
   *   Contact ID of the user we are testing, or 0 for the anonymous user.
   *
   * @return bool
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function checkAccessDelegated(string $entityName, string $actionName, array $record, int $userID) {
    $apiRequest = Request::create($entityName, $actionName, ['version' => 4]);
    // First check gatekeeper permissions via the kernel
    $kernel = \Civi::service('civi_api_kernel');
    try {
      [$actionObjectProvider] = $kernel->resolve($apiRequest);
      $kernel->authorize($actionObjectProvider, $apiRequest);
    }
    catch (UnauthorizedException $e) {
      return FALSE;
    }
    // Gatekeeper permission check passed, now check fine-grained permission
    return static::checkAccessRecord($apiRequest, $record, $userID);
  }

  /**
   * @return \Civi\Api4\Service\Schema\SchemaMap
   */
  public static function getSchemaMap() {
    $cache = \Civi::cache('metadata');
    $schemaMap = $cache->get('api4.schema.map');
    if (!$schemaMap) {
      $schemaMap = \Civi::service('schema_map_builder')->build();
      $cache->set('api4.schema.map', $schemaMap);
    }
    return $schemaMap;
  }

  /**
   * Fetches database references + those returned by hook
   *
   * @see \CRM_Utils_Hook::referenceCounts()
   * @param string $entityName
   * @param int $entityId
   * @return array{name: string, type: string, count: int, table: string|null, key: string|null}[]
   * @throws NotImplementedException
   */
  public static function getRefCount(string $entityName, $entityId) {
    $daoName = self::getInfoItem($entityName, 'dao');
    if (!$daoName) {
      throw new NotImplementedException("Cannot getRefCount for $entityName - dao not found.");
    }
    /** @var \CRM_Core_DAO $dao */
    $dao = new $daoName();
    $dao->id = $entityId;
    return $dao->getReferenceCounts();
  }

}
