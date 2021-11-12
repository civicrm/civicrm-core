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
use Civi\API\Request;
use Civi\Api4\Entity;
use Civi\Api4\Event\CreateApi4RequestEvent;
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
    $e = new CreateApi4RequestEvent($entityName);
    \Civi::dispatcher()->dispatch('civi.api4.createRequest', $e);
    return $e->className;
  }

  /**
   * Get a piece of metadata about an entity
   *
   * @param string $entityName
   * @param string $keyToReturn
   * @return mixed
   */
  public static function getInfoItem(string $entityName, string $keyToReturn) {
    // Because this function might be called thousands of times per request, read directly
    // from the cache set by Apiv4 Entity.get to avoid the processing overhead of the API wrapper.
    $cached = \Civi::cache('metadata')->get('api4.entities.info');
    if ($cached) {
      $info = $cached[$entityName] ?? NULL;
    }
    // If the cache is empty, calling Entity.get will populate it and we'll use it next time.
    else {
      $info = Entity::get(FALSE)
        ->addWhere('name', '=', $entityName)
        ->addSelect($keyToReturn)
        ->execute()->first();
    }
    return $info ? $info[$keyToReturn] ?? NULL : NULL;
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
    $operators[] = 'REGEXP';
    $operators[] = 'NOT REGEXP';
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
    // TODO: Should probably emit civi.api.authorize for checking guardian permission; but in APIv4 with std cfg, this is de-facto equivalent.
    if (!$apiRequest->isAuthorized()) {
      return FALSE;
    }
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
