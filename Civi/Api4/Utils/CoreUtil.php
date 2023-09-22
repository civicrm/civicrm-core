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
use Civi\Api4\Generic\AbstractAction;
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
   * Returns API entity name given an BAO/DAO class name
   *
   * Returns null if the API has not been implemented
   *
   * @param $baoClassName
   * @return string|null
   */
  public static function getApiNameFromBAO($baoClassName) {
    $briefName = AllCoreTables::getBriefName($baoClassName);
    return $briefName && self::getApiClass($briefName) ? $briefName : NULL;
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
   * Get name of field(s) to display in search context
   * @param string $entityName
   * @return array
   */
  public static function getSearchFields(string $entityName): array {
    return self::getInfoItem($entityName, 'search_fields') ?: [];
  }

  /**
   * Get table name of given entity
   *
   * @param string $entityName
   *
   * @return string
   */
  public static function getTableName(string $entityName) {
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
    $operators[] = 'NOT CONTAINS';
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
   * @param int|null $userID
   *   Contact ID of the user we are testing, 0 for the anonymous user.
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function checkAccessRecord(AbstractAction $apiRequest, array $record, int $userID = NULL) {
    $userID = $userID ?? \CRM_Core_Session::getLoggedInContactID() ?? 0;

    // Super-admins always have access to everything
    if (\CRM_Core_Permission::check('all CiviCRM permissions and ACLs', $userID)) {
      return TRUE;
    }

    // For get actions, just run a get and ACLs will be applied to the query.
    // It's a cheap trick and not as efficient as not running the query at all,
    // but BAO::checkAccess doesn't consistently check permissions for the "get" action.
    if (is_a($apiRequest, '\Civi\Api4\Generic\AbstractGetAction')) {
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

  /**
   * @return array
   */
  public static function getSearchableOptions(): array {
    return [
      'primary' => ts('Primary'),
      'secondary' => ts('Secondary'),
      'bridge' => ts('Bridge'),
      'none' => ts('None'),
    ];
  }

  /**
   * Collect the 'type' values from every entity.
   *
   * @return array
   */
  public static function getEntityTypes(): array {
    $provider = \Civi::service('action_object_provider');
    $entityTypes = [];
    foreach ($provider->getEntities() as $entity) {
      foreach ($entity['type'] ?? [] as $type) {
        $entityTypes[$type] = $type;
      }
    }
    return $entityTypes;
  }

  /**
   * Get the suffixes supported by a given option group
   *
   * @param string|int $optionGroup
   *   OptionGroup id or name
   * @param string $key
   *   Is $optionGroup being passed as "id" or "name"
   * @return array
   */
  public static function getOptionValueFields($optionGroup, $key = 'name'): array {
    // Prevent crash during upgrade
    if (array_key_exists('option_value_fields', \CRM_Core_DAO_OptionGroup::getSupportedFields())) {
      $fields = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $optionGroup, 'option_value_fields', $key);
    }
    if (!isset($fields)) {
      return ['name', 'label', 'description'];
    }
    return explode(',', $fields);
  }

  /**
   * Transforms a raw option list (which could be either a flat or non-associative array)
   * into an APIv4-compatible format.
   *
   * @param array|bool $options
   * @param array|bool $format
   * @return array|bool
   */
  public static function formatOptionList($options, $format) {
    if (!$options || !is_array($options)) {
      return $options ?? FALSE;
    }

    $formatted = [];
    $first = reset($options);
    // Flat array requested
    if ($format === TRUE) {
      // Convert non-associative to flat array
      if (is_array($first) && isset($first['id'])) {
        foreach ($options as $option) {
          $formatted[$option['id']] = $option['label'] ?? $option['name'] ?? $option['id'];
        }
        return $formatted;
      }
      return $options;
    }
    // Non-associative array of multiple properties requested
    foreach ($options as $id => $option) {
      // Transform a flat list
      if (!is_array($option)) {
        $option = [
          'id' => $id,
          'name' => $id,
          'label' => $option,
        ];
      }
      $formatted[] = array_intersect_key($option, array_flip($format));
    }
    return $formatted;
  }

  /**
   * Gets info about all available sql functions
   * @return array
   */
  public static function getSqlFunctions(): array {
    $fns = [];
    $path = 'Civi/Api4/Query/SqlFunction*.php';
    // Search CiviCRM core + all active extensions
    $directories = [\Civi::paths()->getPath("[civicrm.root]/$path")];
    foreach (\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
      $directories[] = \CRM_Utils_File::addTrailingSlash(dirname($ext['filePath'])) . $path;
    }
    foreach ($directories as $directory) {
      foreach (glob($directory) as $file) {
        $matches = [];
        if (preg_match('/(SqlFunction[A-Z_]+)\.php$/', $file, $matches)) {
          $className = '\Civi\Api4\Query\\' . $matches[1];
          if (is_subclass_of($className, '\Civi\Api4\Query\SqlFunction')) {
            $fns[] = [
              'name' => $className::getName(),
              'title' => $className::getTitle(),
              'description' => $className::getDescription(),
              'params' => $className::getParams(),
              'category' => $className::getCategory(),
              'dataType' => $className::getDataType(),
              'options' => CoreUtil::formatOptionList($className::getOptions(), ['id', 'name', 'label']),
            ];
          }
        }
      }
    }
    return $fns;
  }

  /**
   * Sorts fields so that control fields are ordered before the fields they control.
   *
   * @param array[] $fields
   * @return void
   */
  public static function topSortFields(array &$fields): void {
    $indexedFields = array_column($fields, NULL, 'name');
    $needsSort = [];
    foreach ($indexedFields as $fieldName => $field) {
      if (!empty($field['input_attrs']['control_field']) && array_key_exists($field['input_attrs']['control_field'], $indexedFields)) {
        $needsSort[$fieldName] = [$field['input_attrs']['control_field']];
      }
    }
    // Only fire up the Topological sorter if we actually need it...
    if ($needsSort) {
      $fields = [];
      $sorter = new \MJS\TopSort\Implementations\FixedArraySort();
      foreach ($indexedFields as $fieldName => $field) {
        $sorter->add($fieldName, $needsSort[$fieldName] ?? []);
      }
      foreach ($sorter->sort() as $fieldName) {
        $fields[] = $indexedFields[$fieldName];
      }
    }
  }

}
