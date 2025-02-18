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
use Civi\Api4\Service\Schema\SchemaMap;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

class CoreUtil {

  public static function entityExists(string $entityName): bool {
    return (bool) self::getInfoItem($entityName, 'name');
  }

  /**
   * @param $entityName
   *
   * @return \CRM_Core_DAO|string
   *   The BAO name for use in static calls. Return doc block is hacked to allow
   *   auto-completion of static methods
   */
  public static function getBAOFromApiName($entityName): ?string {
    // TODO: It would be nice to just call self::getInfoItem($entityName, 'dao')
    // but that currently causes test failures, probably due to early-bootstrap issues.
    if ($entityName === 'CustomValue' || str_starts_with($entityName, 'Custom_')) {
      $dao = \Civi\Api4\CustomValue::getInfo()['dao'];
    }
    else {
      $dao = AllCoreTables::getDAONameForEntity($entityName);
    }
    if (!$dao && self::isContact($entityName)) {
      $dao = 'CRM_Contact_DAO_Contact';
    }
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
  public static function getApiNameFromBAO($baoClassName): ?string {
    $briefName = AllCoreTables::getEntityNameForClass($baoClassName);
    return $briefName && self::getApiClass($briefName) ? $briefName : NULL;
  }

  /**
   * @param string $entityName
   * @return string|\Civi\Api4\Generic\AbstractEntity|null
   */
  public static function getApiClass(string $entityName): ?string {
    $className = 'Civi\Api4\\' . $entityName;
    if (class_exists($className)) {
      return $className;
    }
    return self::getInfoItem($entityName, 'class');
  }

  /**
   * Returns TRUE if `entityName` is 'Contact', 'Individual', 'Organization' or 'Household'
   */
  public static function isContact(string $entityName): bool {
    return $entityName === 'Contact' || in_array($entityName, \CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE);
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
    $entities = $provider->getEntities();
    return $entities[$entityName][$keyToReturn] ?? NULL;
  }

  /**
   * Check if entity is of given type.
   *
   * @param string $entityName
   *   e.g. 'Contact'
   * @param string $entityType
   *   e.g. 'SortableEntity'
   * @return bool
   */
  public static function isType(string $entityName, string $entityType): bool {
    $entityTypes = (array) self::getInfoItem($entityName, 'type');
    return in_array($entityType, $entityTypes, TRUE);
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
   * @return string|null
   */
  public static function getTableName(string $entityName): ?string {
    return self::getInfoItem($entityName, 'table_name');
  }

  /**
   * Given a sql table name, return the name of the api entity.
   *
   * @param string $tableName
   * @return string|NULL
   */
  public static function getApiNameFromTableName($tableName): ?string {
    $provider = \Civi::service('action_object_provider');
    foreach ($provider->getEntities() as $entityName => $info) {
      if (($info['table_name'] ?? NULL) === $tableName) {
        return $entityName;
      }
    }
    return NULL;
  }

  public static function getCustomGroupName(string $entityName): ?string {
    return str_starts_with($entityName, 'Custom_') ? substr($entityName, 7) : NULL;
  }

  /**
   * @return string[]
   */
  public static function getOperators(): array {
    $operators = \CRM_Core_DAO::acceptedSQLOperators();
    $operators[] = 'CONTAINS';
    $operators[] = 'NOT CONTAINS';
    $operators[] = 'IS EMPTY';
    $operators[] = 'IS NOT EMPTY';
    $operators[] = 'REGEXP';
    $operators[] = 'NOT REGEXP';
    $operators[] = 'REGEXP BINARY';
    $operators[] = 'NOT REGEXP BINARY';
    return $operators;
  }

  /**
   * For a given API Entity, return the types of custom fields it supports and the column they join to.
   *
   * Sort of the inverse of this function:
   * @see \CRM_Core_BAO_CustomGroup::getEntityForGroup
   *
   * @param string $entityName
   * @return array{extends: array, column: string, grouping: mixed}|null
   */
  public static function getCustomGroupExtends(string $entityName): ?array {
    $contactTypes = \CRM_Contact_BAO_ContactType::basicTypes();
    // Custom_group.extends pretty much maps 1-1 with entity names, except for Contact.
    if (in_array($entityName, $contactTypes, TRUE)) {
      return [
        'extends' => ['Contact', $entityName],
        'column' => 'id',
        'grouping' => ['contact_type', 'contact_sub_type'],
      ];
    }
    switch ($entityName) {
      case 'Contact':
        return [
          'extends' => array_merge(['Contact'], $contactTypes),
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
   * @deprecated since 5.71 will be removed around 5.81
   *
   * @param $customGroupName
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function isCustomEntity($customGroupName): bool {
    \CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_CustomGroup::getAll');
    return $customGroupName && \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupName, 'is_multiple', 'name');
  }

  /**
   * Check if current user is authorized to perform specified action on a given entity.
   *
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @param array $record
   * @param int|null $userID
   *   Contact ID of the user we are testing, 0 for the anonymous user.
   * @return bool|null
   * @throws \CRM_Core_Exception
   */
  public static function checkAccessRecord(AbstractAction $apiRequest, array $record, ?int $userID = NULL): ?bool {
    $userID ??= \CRM_Core_Session::getLoggedInContactID() ?? 0;
    $idField = self::getIdFieldName($apiRequest->getEntityName());

    // For get actions, just run a get and ACLs will be applied to the query.
    // It's a cheap trick and not as efficient as not running the query at all,
    // but authorizeRecord doesn't consistently check permissions for the "get" action.
    if (is_a($apiRequest, '\Civi\Api4\Generic\AbstractGetAction')) {
      return (bool) $apiRequest->addSelect($idField)->addWhere($idField, '=', $record[$idField])->execute()->count();
    }

    $event = new \Civi\Api4\Event\AuthorizeRecordEvent($apiRequest, $record, $userID);
    \Civi::dispatcher()->dispatch('civi.api4.authorizeRecord', $event);

    // $bao::_checkAccess() is deprecated in favor of `civi.api4.authorizeRecord` event.
    if ($event->isAuthorized() === NULL) {
      $baoName = self::getBAOFromApiName($apiRequest->getEntityName());
      if ($baoName && method_exists($baoName, '_checkAccess')) {
        \CRM_Core_Error::deprecatedWarning("$baoName::_checkAccess is deprecated and should be replaced with 'civi.api4.authorizeRecord' event listener.");
        $authorized = $baoName::_checkAccess($event->getEntityName(), $event->getActionName(), $event->getRecord(), $event->getUserID());
        $event->setAuthorized($authorized);
      }
    }
    return $event->isAuthorized() ?? TRUE;
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
  public static function getSchemaMap(): SchemaMap {
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
  public static function getRefCount(string $entityName, $entityId): array {
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
   * Gets total number of references
   *
   * @param string $entityName
   * @param $entityId
   * @return int
   * @throws NotImplementedException
   */
  public static function getRefCountTotal(string $entityName, $entityId): int {
    $total = 0;
    foreach ((array) self::getRefCount($entityName, $entityId) as $ref) {
      $total += $ref['count'] ?? 0;
    }
    return $total;
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

  public static function formatViewValue(string $entityName, string $fieldName, array $values, string $action = 'get') {
    if (!isset($values[$fieldName]) || $values[$fieldName] === '') {
      return '';
    }
    $params = [
      'action' => $action,
      'where' => [['name', '=', $fieldName]],
      'loadOptions' => ['id', 'label'],
      'checkPermissions' => FALSE,
      'values' => $values,
    ];
    $fieldInfo = civicrm_api4($entityName, 'getFields', $params)->single();
    $dataType = $fieldInfo['data_type'] ?? NULL;
    if (!empty($fieldInfo['options'])) {
      return FormattingUtil::replacePseudoconstant(array_column($fieldInfo['options'], 'label', 'id'), $values[$fieldName]);
    }
    elseif ($dataType === 'Boolean') {
      return $values[$fieldName] ? ts('Yes') : ts('No');
    }
    elseif ($dataType === 'Date' || $dataType === 'Timestamp') {
      $values[$fieldName] = \CRM_Utils_Date::customFormat($values[$fieldName]);
    }
    if (is_array($values[$fieldName])) {
      $values[$fieldName] = implode(', ', $values[$fieldName]);
    }
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

  /**
   * Strips leading namespace from a classname
   * @param string $className
   * @return string
   */
  public static function stripNamespace(string $className): string {
    $slashPos = strrpos($className, '\\');
    return $slashPos === FALSE ? $className : substr($className, $slashPos + 1);
  }

}
