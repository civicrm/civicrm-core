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

/**
 * Business object for managing custom data groups.
 */
class CRM_Core_BAO_CustomGroup extends CRM_Core_DAO_CustomGroup implements \Civi\Core\HookInterface {

  /**
   * @param \Civi\Core\Event\PostEvent $e
   * @see CRM_Utils_Hook::post()
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $e): void {
    Civi::cache('metadata')->flush();
  }

  /**
   * FIXME: This function is too complex because it's trying to handle both api-style inputs and
   * quickform inputs. Needs to be deprecated and broken up.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Core_DAO_CustomGroup
   * @throws \Exception
   */
  public static function create(&$params) {
    // FIXME: This is needed by the form parsing code below
    if (empty($params['id'])) {
      $params += ['extends' => 'Contact'];
    }
    // create custom group dao, populate fields and then save.
    $group = new CRM_Core_DAO_CustomGroup();
    if (isset($params['title'])) {
      $group->title = $params['title'];
    }

    $extendsChildType = NULL;
    // lets allow user to pass direct child type value, CRM-6893
    if (!empty($params['extends_entity_column_value'])) {
      $extendsChildType = $params['extends_entity_column_value'];
    }
    if (!CRM_Utils_System::isNull($extendsChildType)) {
      $b = self::getMungedEntity($params['extends'], $params['extends_entity_column_id'] ?? NULL);
      $registeredSubTypes = self::getSubTypes()[$b];
      if (is_array($extendsChildType)) {
        foreach ($extendsChildType as $childType) {
          if (!array_key_exists($childType, $registeredSubTypes) && !in_array($childType, $registeredSubTypes, TRUE)) {
            throw new CRM_Core_Exception('Supplied Sub type is not valid for the specified entitiy');
          }
        }
      }
      else {
        if (!array_key_exists($extendsChildType, $registeredSubTypes) && !in_array($extendsChildType, $registeredSubTypes, TRUE)) {
          throw new CRM_Core_Exception('Supplied Sub type is not valid for the specified entitiy');
        }
        $extendsChildType = [$extendsChildType];
      }
      $extendsChildType = implode(CRM_Core_DAO::VALUE_SEPARATOR, $extendsChildType);
      if ($params['extends'] == 'Relationship') {
        $extendsChildType = str_replace(['_a_b', '_b_a'], [
          '',
          '',
        ], $extendsChildType);
      }
      if (substr($extendsChildType, 0, 1) != CRM_Core_DAO::VALUE_SEPARATOR) {
        $extendsChildType = CRM_Core_DAO::VALUE_SEPARATOR . $extendsChildType .
          CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }
    else {
      $extendsChildType = 'null';
    }
    $group->extends_entity_column_value = $extendsChildType;

    if (isset($params['id'])) {
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $params['id'], 'weight', 'id');
    }
    else {
      $oldWeight = 0;
    }
    $group->weight = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_CustomGroup', $oldWeight, CRM_Utils_Array::value('weight', $params, FALSE));
    $fields = [
      'style',
      'collapse_display',
      'collapse_adv_display',
      'help_pre',
      'help_post',
      'is_active',
      'is_multiple',
      'icon',
      'extends_entity_column_id',
      'extends',
    ];
    $current_db_version = CRM_Core_BAO_Domain::version();
    $is_public_version = version_compare($current_db_version, '4.7.19', '>=');
    if ($is_public_version) {
      $fields[] = 'is_public';
    }
    foreach ($fields as $field) {
      if (isset($params[$field])) {
        $group->$field = $params[$field];
      }
    }
    $group->max_multiple = isset($params['is_multiple']) ? (isset($params['max_multiple']) &&
      $params['max_multiple'] >= '0'
    ) ? $params['max_multiple'] : 'null' : 'null';

    $tableName = $tableNameNeedingIndexUpdate = NULL;
    if (isset($params['id'])) {
      $group->id = $params['id'];

      if (isset($params['is_multiple'])) {
        // check whether custom group was changed from single-valued to multiple-valued
        $isMultiple = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
          $params['id'],
          'is_multiple'
        );

        // dev/core#227 Fix issue where is_multiple in params maybe an empty string if checkbox is not rendered on the form.
        $paramsIsMultiple = empty($params['is_multiple']) ? 0 : 1;
        if ($paramsIsMultiple != $isMultiple) {
          $tableNameNeedingIndexUpdate = CRM_Core_DAO::getFieldValue(
            'CRM_Core_DAO_CustomGroup',
            $params['id'],
            'table_name'
          );
        }
      }
    }
    else {
      $group->created_id = $params['created_id'] ?? NULL;
      $group->created_date = $params['created_date'] ?? NULL;

      // Process name only during create, so it never changes
      if (!empty($params['name'])) {
        $group->name = CRM_Utils_String::munge($params['name']);
      }
      else {
        $group->name = CRM_Utils_String::munge($group->title);
      }

      self::validateCustomGroupName($group);

      if (isset($params['table_name'])) {
        $tableName = $params['table_name'];

        if (CRM_Core_DAO_AllCoreTables::isCoreTable($tableName)) {
          // Bad idea.  Prevent group creation because it might lead to a broken configuration.
          throw new CRM_Core_Exception(ts('Cannot create custom table because %1 is already a core table.', ['1' => $tableName]));
        }
      }
    }

    if (array_key_exists('is_reserved', $params)) {
      $group->is_reserved = $params['is_reserved'] ? 1 : 0;
    }
    $op = isset($params['id']) ? 'edit' : 'create';
    CRM_Utils_Hook::pre($op, 'CustomGroup', CRM_Utils_Array::value('id', $params), $params);

    // enclose the below in a transaction
    $transaction = new CRM_Core_Transaction();

    $group->save();
    if (!isset($params['id'])) {
      if (!isset($params['table_name'])) {
        $munged_title = strtolower(CRM_Utils_String::munge($group->title, '_', 13));
        $tableName = "civicrm_value_{$munged_title}_{$group->id}";
      }
      $group->table_name = $tableName;
      CRM_Core_DAO::setFieldValue('CRM_Core_DAO_CustomGroup',
        $group->id,
        'table_name',
        $tableName
      );

      // now create the table associated with this group
      self::createTable($group);
    }
    elseif ($tableNameNeedingIndexUpdate) {
      CRM_Core_BAO_SchemaHandler::changeUniqueToIndex($tableNameNeedingIndexUpdate, CRM_Utils_Array::value('is_multiple', $params));
    }

    if (CRM_Utils_Array::value('overrideFKConstraint', $params) == 1) {
      $table = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
        $params['id'],
        'table_name'
      );
      CRM_Core_BAO_SchemaHandler::changeFKConstraint($table, self::mapTableName($params['extends']));
    }
    $transaction->commit();

    // reset the cache
    CRM_Utils_System::flushCache();

    if ($tableName) {
      CRM_Utils_Hook::post('create', 'CustomGroup', $group->id, $group);
    }
    else {
      CRM_Utils_Hook::post('edit', 'CustomGroup', $group->id, $group);
    }

    return $group;
  }

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Ensure group name does not conflict with an existing field
   *
   * @param CRM_Core_DAO_CustomGroup $group
   */
  public static function validateCustomGroupName(CRM_Core_DAO_CustomGroup $group) {
    $extends = in_array($group->extends, CRM_Contact_BAO_ContactType::basicTypes(TRUE)) ? 'Contact' : $group->extends;
    $extendsDAO = CRM_Core_DAO_AllCoreTables::getFullName($extends);
    if ($extendsDAO) {
      $fields = array_column($extendsDAO::fields(), 'name');
      if (in_array($group->name, $fields)) {
        $group->name .= '0';
      }
    }
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    // reset the cache
    Civi::cache('fields')->flush();
    // reset ACL and system caches.
    CRM_Core_BAO_Cache::resetCaches();

    if (!$is_active) {
      CRM_Core_BAO_UFField::setUFFieldStatus($id, $is_active);
    }

    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_CustomGroup', $id, 'is_active', $is_active);
  }

  /**
   * Determine if given entity (sub)type has any custom groups
   *
   * @param string $extends
   *   E.g. "Individual", "Activity".
   * @param int $columnId
   *   E.g. custom-group matching mechanism (usu NULL for matching on sub type-id); see extends_entity_column_id.
   * @param string $columnValue
   *   E.g. "Student" or "3" or "3\05"; see extends_entity_column_value.
   *
   * @return bool
   */
  public static function hasCustomGroup($extends, $columnId, $columnValue) {
    $dao = new CRM_Core_DAO_CustomGroup();
    $dao->extends = $extends;
    $dao->extends_entity_column_id = $columnId;
    $escapedValue = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Core_DAO::escapeString($columnValue) . CRM_Core_DAO::VALUE_SEPARATOR;
    $dao->whereAdd("extends_entity_column_value LIKE \"%$escapedValue%\"");
    //$dao->extends_entity_column_value = $columnValue;
    return (bool) $dao->find();
  }

  /**
   * Determine if there are any CustomGroups for the given $activityTypeId.
   * If none found, create one.
   *
   * @param int $activityTypeId
   *
   * @return bool
   *   TRUE if a group is found or created; FALSE on error
   */
  public static function autoCreateByActivityType($activityTypeId) {
    if (self::hasCustomGroup('Activity', NULL, $activityTypeId)) {
      return TRUE;
    }
    // everything
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE, FALSE);
    $params = [
      'version' => 3,
      'extends' => 'Activity',
      'extends_entity_column_id' => NULL,
      'extends_entity_column_value' => CRM_Utils_Array::implodePadded([$activityTypeId]),
      'title' => ts('%1 Questions', [1 => $activityTypes[$activityTypeId]]),
      'style' => 'Inline',
      'is_active' => 1,
    ];
    $result = civicrm_api('CustomGroup', 'create', $params);
    return !$result['is_error'];
  }

  /**
   * Get custom groups/fields data for type of entity in a tree structure representing group->field hierarchy
   * This may also include entity specific data values.
   *
   * An array containing all custom groups and their custom fields is returned.
   *
   * @param string $entityType
   *   Of the contact whose contact type is needed.
   * @param array $toReturn
   *   What data should be returned. ['custom_group' => ['id', 'name', etc.], 'custom_field' => ['id', 'label', etc.]]
   * @param int $entityID
   * @param int $groupID
   * @param array $subTypes
   * @param string $subName
   * @param bool $fromCache
   * @param bool $onlySubType
   *   Only return specified subtype or return specified subtype + unrestricted fields.
   * @param bool $returnAll
   *   Do not restrict by subtype at all. (The parameter feels a bit cludgey but is only used from the
   *   api - through which it is properly tested - so can be refactored with some comfort.)
   * @param bool|int $checkPermission
   *   Either a CRM_Core_Permission constant or FALSE to disable checks
   * @param string|int $singleRecord
   *   holds 'new' or id if view/edit/copy form for a single record is being loaded.
   * @param bool $showPublicOnly
   *
   * @return array
   *   Custom field 'tree'.
   *
   *   The returned array is keyed by group id and has the custom group table fields
   *   and a subkey 'fields' holding the specific custom fields.
   *   If entityId is passed in the fields keys have a subkey 'customValue' which holds custom data
   *   if set for the given entity. This is structured as an array of values with each one having the keys 'id', 'data'
   *
   * @todo - review this  - It also returns an array called 'info' with tables, select, from, where keys
   *   The reason for the info array in unclear and it could be determined from parsing the group tree after creation
   *   With caching the performance impact would be small & the function would be cleaner
   *
   * @throws \CRM_Core_Exception
   */
  public static function getTree(
    $entityType,
    $toReturn = [],
    $entityID = NULL,
    $groupID = NULL,
    $subTypes = [],
    $subName = NULL,
    $fromCache = TRUE,
    $onlySubType = NULL,
    $returnAll = FALSE,
    $checkPermission = CRM_Core_Permission::EDIT,
    $singleRecord = NULL,
    $showPublicOnly = FALSE
  ) {
    if ($checkPermission === TRUE) {
      CRM_Core_Error::deprecatedWarning('Unexpected TRUE passed to CustomGroup::getTree $checkPermission param.');
      $checkPermission = CRM_Core_Permission::EDIT;
    }
    if ($entityID) {
      $entityID = CRM_Utils_Type::escape($entityID, 'Integer');
    }
    if (!is_array($subTypes)) {
      if (empty($subTypes)) {
        $subTypes = [];
      }
      else {
        if (stristr($subTypes, ',')) {
          $subTypes = explode(',', $subTypes);
        }
        else {
          $subTypes = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($subTypes, CRM_Core_DAO::VALUE_SEPARATOR));
        }
      }
    }

    // create a new tree

    // legacy hardcoded list of data to return
    $tableData = [
      'custom_field' => [
        'id',
        'name',
        'label',
        'column_name',
        'data_type',
        'html_type',
        'default_value',
        'attributes',
        'is_required',
        'is_view',
        'help_pre',
        'help_post',
        'options_per_line',
        'start_date_years',
        'end_date_years',
        'date_format',
        'time_format',
        'option_group_id',
        'in_selector',
      ],
      'custom_group' => [
        'id',
        'name',
        'table_name',
        'title',
        'help_pre',
        'help_post',
        'collapse_display',
        'style',
        'is_multiple',
        'extends',
        'extends_entity_column_id',
        'extends_entity_column_value',
        'max_multiple',
      ],
    ];
    $current_db_version = CRM_Core_BAO_Domain::version();
    $is_public_version = version_compare($current_db_version, '4.7.19', '>=');
    $serialize_version = version_compare($current_db_version, '5.27.alpha1', '>=');
    if ($is_public_version) {
      $tableData['custom_group'][] = 'is_public';
    }
    if ($serialize_version) {
      $tableData['custom_field'][] = 'serialize';
    }
    if (!$toReturn || !is_array($toReturn)) {
      $toReturn = $tableData;
    }
    else {
      // Supply defaults and remove unknown array keys
      $toReturn = array_intersect_key(array_filter($toReturn) + $tableData, $tableData);
      // Merge in required fields that we must have
      $toReturn['custom_field'] = array_unique(array_merge($toReturn['custom_field'], ['id', 'column_name', 'data_type']));
      $toReturn['custom_group'] = array_unique(array_merge($toReturn['custom_group'], ['id', 'is_multiple', 'table_name', 'name']));
      // Validate return fields
      $toReturn['custom_field'] = array_intersect($toReturn['custom_field'], array_keys(CRM_Core_DAO_CustomField::fieldKeys()));
      $toReturn['custom_group'] = array_intersect($toReturn['custom_group'], array_keys(CRM_Core_DAO_CustomGroup::fieldKeys()));
    }

    // create select
    $select = [];
    foreach ($toReturn as $tableName => $tableColumn) {
      foreach ($tableColumn as $columnName) {
        $select[] = "civicrm_{$tableName}.{$columnName} as civicrm_{$tableName}_{$columnName}";
      }
    }
    $strSelect = "SELECT " . implode(', ', $select);

    // from, where, order by
    $strFrom = "
FROM     civicrm_custom_group
LEFT JOIN civicrm_custom_field ON (civicrm_custom_field.custom_group_id = civicrm_custom_group.id)
";

    // if entity is either individual, organization or household pls get custom groups for 'contact' too.
    if ($entityType == "Individual" || $entityType == 'Organization' ||
      $entityType == 'Household'
    ) {
      $in = "'$entityType', 'Contact'";
    }
    elseif (strpos($entityType, "'") !== FALSE) {
      // this allows the calling function to send in multiple entity types
      $in = $entityType;
    }
    else {
      // quote it
      $in = "'$entityType'";
    }

    $params = [];
    $sqlParamKey = 1;
    $subType = '';
    if (!empty($subTypes)) {
      foreach ($subTypes as $key => $subType) {
        $subTypeClauses[] = self::whereListHas("civicrm_custom_group.extends_entity_column_value", self::validateSubTypeByEntity($entityType, $subType));
      }
      $subTypeClause = '(' . implode(' OR ', $subTypeClauses) . ')';
      if (!$onlySubType) {
        $subTypeClause = '(' . $subTypeClause . '  OR civicrm_custom_group.extends_entity_column_value IS NULL )';
      }

      $strWhere = "
WHERE civicrm_custom_group.is_active = 1
  AND civicrm_custom_field.is_active = 1
  AND civicrm_custom_group.extends IN ($in)
  AND $subTypeClause
";
      if ($subName) {
        $strWhere .= " AND civicrm_custom_group.extends_entity_column_id = %{$sqlParamKey}";
        $params[$sqlParamKey] = [$subName, 'String'];
        $sqlParamKey = $sqlParamKey + 1;
      }
    }
    else {
      $strWhere = "
WHERE civicrm_custom_group.is_active = 1
  AND civicrm_custom_field.is_active = 1
  AND civicrm_custom_group.extends IN ($in)
";
      if (!$returnAll) {
        $strWhere .= "AND civicrm_custom_group.extends_entity_column_value IS NULL";
      }
    }

    if ($groupID > 0) {
      // since we want a specific group id we add it to the where clause
      $strWhere .= " AND civicrm_custom_group.id = %{$sqlParamKey}";
      $params[$sqlParamKey] = [$groupID, 'Integer'];
    }
    elseif (!$groupID) {
      // since groupID is false we need to show all Inline groups
      $strWhere .= " AND civicrm_custom_group.style = 'Inline'";
    }
    if ($checkPermission) {
      // ensure that the user has access to these custom groups
      $strWhere .= " AND " .
        CRM_Core_Permission::customGroupClause($checkPermission,
          'civicrm_custom_group.'
        );
    }

    if ($showPublicOnly && $is_public_version) {
      $strWhere .= "AND civicrm_custom_group.is_public = 1";
    }

    $orderBy = "
ORDER BY civicrm_custom_group.weight,
         civicrm_custom_group.title,
         civicrm_custom_field.weight,
         civicrm_custom_field.label
";

    // final query string
    $queryString = "$strSelect $strFrom $strWhere $orderBy";

    // lets see if we can retrieve the groupTree from cache
    $cacheString = $queryString;
    if ($groupID > 0) {
      $cacheString .= "_{$groupID}";
    }
    else {
      $cacheString .= "_Inline";
    }

    $cacheKey = "CRM_Core_DAO_CustomGroup_Query " . md5($cacheString);
    $multipleFieldGroupCacheKey = "CRM_Core_DAO_CustomGroup_QueryMultipleFields " . md5($cacheString);
    $cache = CRM_Utils_Cache::singleton();
    if ($fromCache) {
      $groupTree = $cache->get($cacheKey);
      $multipleFieldGroups = $cache->get($multipleFieldGroupCacheKey);
    }

    if (empty($groupTree)) {
      [$multipleFieldGroups, $groupTree] = self::buildGroupTree($entityType, $toReturn, $subTypes, $queryString, $params, $subType);

      $cache->set($cacheKey, $groupTree);
      $cache->set($multipleFieldGroupCacheKey, $multipleFieldGroups);
    }
    // entitySelectClauses is an array of select clauses for custom value tables which are not multiple
    // and have data for the given entities. $entityMultipleSelectClauses is the same for ones with multiple
    $entitySingleSelectClauses = $entityMultipleSelectClauses = $groupTree['info']['select'] = [];
    $singleFieldTables = [];
    // now that we have all the groups and fields, lets get the values
    // since we need to know the table and field names
    // add info to groupTree

    if (isset($groupTree['info']) && !empty($groupTree['info']) &&
      !empty($groupTree['info']['tables']) && $singleRecord != 'new'
    ) {
      $select = $from = $where = [];
      $groupTree['info']['where'] = NULL;

      foreach ($groupTree['info']['tables'] as $table => $fields) {
        $groupTree['info']['from'][] = $table;
        $select = [
          "{$table}.id as {$table}_id",
          "{$table}.entity_id as {$table}_entity_id",
        ];
        foreach ($fields as $column => $dontCare) {
          $select[] = "{$table}.{$column} as {$table}_{$column}";
        }
        $groupTree['info']['select'] = array_merge($groupTree['info']['select'], $select);
        if ($entityID) {
          $groupTree['info']['where'][] = "{$table}.entity_id = $entityID";
          if (in_array($table, $multipleFieldGroups) &&
            self::customGroupDataExistsForEntity($entityID, $table)
          ) {
            $entityMultipleSelectClauses[$table] = $select;
          }
          else {
            $singleFieldTables[] = $table;
            $entitySingleSelectClauses = array_merge($entitySingleSelectClauses, $select);
          }

        }
      }
      if ($entityID && !empty($singleFieldTables)) {
        self::buildEntityTreeSingleFields($groupTree, $entityID, $entitySingleSelectClauses, $singleFieldTables);
      }
      $multipleFieldTablesWithEntityData = array_keys($entityMultipleSelectClauses);
      if (!empty($multipleFieldTablesWithEntityData)) {
        self::buildEntityTreeMultipleFields($groupTree, $entityID, $entityMultipleSelectClauses, $multipleFieldTablesWithEntityData, $singleRecord);
      }

    }
    return $groupTree;
  }

  /**
   * Clean and validate the filter before it is used in a db query.
   *
   * @param string $entityType
   * @param string $subType
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected static function validateSubTypeByEntity($entityType, $subType) {
    $subType = trim($subType, CRM_Core_DAO::VALUE_SEPARATOR);
    if (is_numeric($subType)) {
      return $subType;
    }

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypeInfo(TRUE);
    $contactTypes = array_merge($contactTypes, ['Event' => 1]);

    if ($entityType != 'Contact' && !array_key_exists($entityType, $contactTypes)) {
      throw new CRM_Core_Exception('Invalid Entity Filter');
    }
    $subTypes = CRM_Contact_BAO_ContactType::subTypeInfo($entityType, TRUE);
    $subTypes = array_merge($subTypes, CRM_Event_PseudoConstant::eventType());
    // When you create a new contact type it gets saved in mixed case in the database.
    // Eg. "Service User" becomes "Service_User" in civicrm_contact_type.name
    // But that field does not differentiate case (eg. you can't add Service_User and service_user because mysql will report a duplicate error)
    // webform_civicrm and some other integrations pass in the name as lowercase to API3 Contact.duplicatecheck
    // Since we can't actually have two strings with different cases in the database perform a case-insensitive search here:
    $subTypes = array_change_key_case($subTypes, CASE_LOWER);
    if (!array_key_exists(mb_strtolower($subType), $subTypes)) {
      \Civi::log()->debug("entityType: {$entityType}; subType: {$subType}");
      throw new CRM_Core_Exception('Invalid Filter');
    }
    return $subType;
  }

  /**
   * Suppose you have a SQL column, $column, which includes a delimited list, and you want
   * a WHERE condition for rows that include $value. Use whereListHas().
   *
   * @param string $column
   * @param string $value
   * @param string $delimiter
   * @return string
   *   SQL condition.
   */
  private static function whereListHas($column, $value, $delimiter = CRM_Core_DAO::VALUE_SEPARATOR) {
    // ?
    $bareValue = trim($value, $delimiter);
    $escapedValue = CRM_Utils_Type::escape("%{$delimiter}{$bareValue}{$delimiter}%", 'String', FALSE);
    return "($column LIKE \"$escapedValue\")";
  }

  /**
   * Check whether the custom group has any data for the given entity.
   *
   * @param int $entityID
   *   Id of entity for whom we are checking data for.
   * @param string $table
   *   Table that we are checking.
   *
   * @param bool $getCount
   *
   * @return bool
   *   does this entity have data in this custom table
   */
  public static function customGroupDataExistsForEntity($entityID, $table, $getCount = FALSE) {
    $query = "
      SELECT count(id)
      FROM   $table
      WHERE  entity_id = $entityID
    ";
    $recordExists = CRM_Core_DAO::singleValueQuery($query);
    if ($getCount) {
      return $recordExists;
    }
    return (bool) $recordExists;
  }

  /**
   * Build the group tree for Custom fields which are not 'is_multiple'
   *
   * The combination of all these fields in one query with a 'using' join was not working for
   * multiple fields. These now have a new behaviour (one at a time) but the single fields still use this
   * mechanism as it seemed to be acceptable in this context
   *
   * @param array $groupTree
   *   (reference) group tree array which is being built.
   * @param int $entityID
   *   Id of entity for whom the tree is being build up.
   * @param array $entitySingleSelectClauses
   *   Array of select clauses relevant to the entity.
   * @param array $singleFieldTablesWithEntityData
   *   Array of tables in which this entity has data.
   */
  public static function buildEntityTreeSingleFields(&$groupTree, $entityID, $entitySingleSelectClauses, $singleFieldTablesWithEntityData) {
    $select = implode(', ', $entitySingleSelectClauses);
    $fromSQL = " (SELECT $entityID as entity_id ) as first ";
    foreach ($singleFieldTablesWithEntityData as $table) {
      $fromSQL .= "\nLEFT JOIN $table USING (entity_id)";
    }

    $query = "
      SELECT $select
      FROM $fromSQL
      WHERE first.entity_id = $entityID
    ";
    self::buildTreeEntityDataFromQuery($groupTree, $query, $singleFieldTablesWithEntityData);
  }

  /**
   * Build the group tree for Custom fields which are  'is_multiple'
   *
   * This is done one table at a time to avoid Cross-Joins resulting in too many rows being returned
   *
   * @param array $groupTree
   *   (reference) group tree array which is being built.
   * @param int $entityID
   *   Id of entity for whom the tree is being build up.
   * @param array $entityMultipleSelectClauses
   *   Array of select clauses relevant to the entity.
   * @param array $multipleFieldTablesWithEntityData
   *   Array of tables in which this entity has data.
   * @param string|int $singleRecord
   *   holds 'new' or id if view/edit/copy form for a single record is being loaded.
   */
  public static function buildEntityTreeMultipleFields(&$groupTree, $entityID, $entityMultipleSelectClauses, $multipleFieldTablesWithEntityData, $singleRecord = NULL) {
    foreach ($entityMultipleSelectClauses as $table => $selectClauses) {
      $select = implode(',', $selectClauses);
      $query = "
        SELECT $select
        FROM $table
        WHERE entity_id = $entityID
      ";
      if ($singleRecord) {
        $offset = $singleRecord - 1;
        $query .= " LIMIT {$offset}, 1";
      }
      self::buildTreeEntityDataFromQuery($groupTree, $query, [$table], $singleRecord);
    }
  }

  /**
   * Build the tree entity data - starting from a query retrieving the custom fields build the group
   * tree data for the relevant entity (entity is included in the query).
   *
   * This function represents shared code between the buildEntityTreeMultipleFields & the buildEntityTreeSingleFields function
   *
   * @param array $groupTree
   *   (reference) group tree array which is being built.
   * @param string $query
   * @param array $includedTables
   *   Tables to include - required because the function (for historical reasons).
   *   iterates through the group tree
   * @param string|int $singleRecord
   *   holds 'new' OR id if view/edit/copy form for a single record is being loaded.
   */
  public static function buildTreeEntityDataFromQuery(&$groupTree, $query, $includedTables, $singleRecord = NULL) {
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      foreach ($groupTree as $groupID => $group) {
        if ($groupID === 'info') {
          continue;
        }
        $table = $groupTree[$groupID]['table_name'];
        //working from the groupTree instead of the table list means we have to iterate & exclude.
        // this could possibly be re-written as other parts of the function have been refactored
        // for now we just check if the given table is to be included in this function
        if (!in_array($table, $includedTables)) {
          continue;
        }
        foreach ($group['fields'] as $fieldID => $dontCare) {
          self::buildCustomFieldData($dao, $groupTree, $table, $groupID, $fieldID, $singleRecord);
        }
      }
    }
  }

  /**
   * Build the entity-specific custom data into the group tree on a per-field basis
   *
   * @param object $dao
   *   Object representing the custom field to be populated into the groupTree.
   * @param array $groupTree
   *   (reference) the group tree being build.
   * @param string $table
   *   Table name.
   * @param int $groupID
   *   Custom group ID.
   * @param int $fieldID
   *   Custom field ID.
   * @param string|int $singleRecord
   *   holds 'new' or id if loading view/edit/copy for a single record.
   */
  public static function buildCustomFieldData($dao, &$groupTree, $table, $groupID, $fieldID, $singleRecord = NULL) {
    $column = $groupTree[$groupID]['fields'][$fieldID]['column_name'];
    $idName = "{$table}_id";
    $fieldName = "{$table}_{$column}";
    $dataType = $groupTree[$groupID]['fields'][$fieldID]['data_type'];
    if ($dataType == 'File') {
      if (isset($dao->$fieldName)) {
        $config = CRM_Core_Config::singleton();
        $fileDAO = new CRM_Core_DAO_File();
        $fileDAO->id = $dao->$fieldName;

        if ($fileDAO->find(TRUE)) {
          $entityIDName = "{$table}_entity_id";
          $fileHash = CRM_Core_BAO_File::generateFileHash($dao->$entityIDName, $fileDAO->id);
          $customValue['id'] = $dao->$idName;
          $customValue['data'] = $fileDAO->uri;
          $customValue['fid'] = $fileDAO->id;
          $customValue['fileURL'] = CRM_Utils_System::url('civicrm/file', "reset=1&id={$fileDAO->id}&eid={$dao->$entityIDName}&fcs=$fileHash");
          $customValue['displayURL'] = NULL;
          $deleteExtra = ts('Are you sure you want to delete attached file.');
          $deleteURL = [
            CRM_Core_Action::DELETE => [
              'name' => ts('Delete Attached File'),
              'url' => 'civicrm/file',
              'qs' => 'reset=1&id=%%id%%&eid=%%eid%%&fid=%%fid%%&action=delete&fcs=%%fcs%%',
              'extra' => 'onclick = "if (confirm( \'' . $deleteExtra
              . '\' ) ) this.href+=\'&amp;confirmed=1\'; else return false;"',
            ],
          ];
          $customValue['deleteURL'] = CRM_Core_Action::formLink($deleteURL,
            CRM_Core_Action::DELETE,
            [
              'id' => $fileDAO->id,
              'eid' => $dao->$entityIDName,
              'fid' => $fieldID,
              'fcs' => $fileHash,
            ],
            ts('more'),
            FALSE,
            'file.manage.delete',
            'File',
            $fileDAO->id
          );
          $customValue['deleteURLArgs'] = CRM_Core_BAO_File::deleteURLArgs($table, $dao->$entityIDName, $fileDAO->id);
          $customValue['fileName'] = CRM_Utils_File::cleanFileName(basename($fileDAO->uri));
          if ($fileDAO->mime_type == "image/jpeg" ||
            $fileDAO->mime_type == "image/pjpeg" ||
            $fileDAO->mime_type == "image/gif" ||
            $fileDAO->mime_type == "image/x-png" ||
            $fileDAO->mime_type == "image/png"
          ) {
            $customValue['displayURL'] = $customValue['fileURL'];
            $entityId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_EntityFile',
              $fileDAO->id,
              'entity_id',
              'file_id'
            );
            $customValue['imageURL'] = str_replace('persist/contribute', 'custom', $config->imageUploadURL) .
              $fileDAO->uri;
            [$path] = CRM_Core_BAO_File::path($fileDAO->id, $entityId);
            if ($path && file_exists($path)) {
              [$imageWidth, $imageHeight] = getimagesize($path);
              [$imageThumbWidth, $imageThumbHeight] = CRM_Contact_BAO_Contact::getThumbSize($imageWidth, $imageHeight);
              $customValue['imageThumbWidth'] = $imageThumbWidth;
              $customValue['imageThumbHeight'] = $imageThumbHeight;
            }
          }
        }
      }
      else {
        $customValue = [
          'id' => $dao->$idName,
          'data' => '',
        ];
      }
    }
    else {
      $customValue = [
        'id' => $dao->$idName,
        'data' => $dao->$fieldName,
      ];
    }

    if (!array_key_exists('customValue', $groupTree[$groupID]['fields'][$fieldID])) {
      $groupTree[$groupID]['fields'][$fieldID]['customValue'] = [];
    }
    if (empty($groupTree[$groupID]['fields'][$fieldID]['customValue']) && !empty($singleRecord)) {
      $groupTree[$groupID]['fields'][$fieldID]['customValue'] = [$singleRecord => $customValue];
    }
    elseif (empty($groupTree[$groupID]['fields'][$fieldID]['customValue'])) {
      $groupTree[$groupID]['fields'][$fieldID]['customValue'] = [1 => $customValue];
    }
    else {
      $groupTree[$groupID]['fields'][$fieldID]['customValue'][] = $customValue;
    }
  }

  /**
   * Get the group title.
   *
   * @param int $id
   *   Id of group.
   *
   * @return string
   *   title
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $id, 'title');
  }

  /**
   * Get custom group details for a group.
   *
   * An array containing custom group details (including their custom field) is returned.
   *
   * @param int $groupId
   *   Group id whose details are needed.
   * @param bool $searchable
   *   Is this field searchable.
   * @param array $extends
   *   Which table does it extend if any.
   *
   * @param bool $inSelector
   *
   * @return array
   *   array consisting of all group and field details
   */
  public static function &getGroupDetail($groupId = NULL, $searchable = NULL, &$extends = NULL, $inSelector = NULL) {
    // create a new tree
    $groupTree = [];

    // using tableData to build the queryString
    $tableData = [
      'civicrm_custom_field' => [
        'id',
        'label',
        'data_type',
        'html_type',
        'default_value',
        'attributes',
        'is_required',
        'help_pre',
        'help_post',
        'options_per_line',
        'is_searchable',
        'start_date_years',
        'end_date_years',
        'is_search_range',
        'date_format',
        'time_format',
        'note_columns',
        'note_rows',
        'column_name',
        'is_view',
        'option_group_id',
        'in_selector',
      ],
      'civicrm_custom_group' => [
        'id',
        'name',
        'title',
        'help_pre',
        'help_post',
        'collapse_display',
        'collapse_adv_display',
        'extends',
        'extends_entity_column_value',
        'table_name',
        'is_multiple',
      ],
    ];

    // create select
    $s = [];
    foreach ($tableData as $tableName => $tableColumn) {
      foreach ($tableColumn as $columnName) {
        $s[] = "{$tableName}.{$columnName} as {$tableName}_{$columnName}";
      }
    }
    $select = 'SELECT ' . implode(', ', $s);
    $params = [];
    // from, where, order by
    $from = " FROM civicrm_custom_field, civicrm_custom_group";
    $where = " WHERE civicrm_custom_field.custom_group_id = civicrm_custom_group.id
                            AND civicrm_custom_group.is_active = 1
                            AND civicrm_custom_field.is_active = 1 ";
    if ($groupId) {
      $params[1] = [$groupId, 'Integer'];
      $where .= " AND civicrm_custom_group.id = %1";
    }

    if ($searchable) {
      $where .= " AND civicrm_custom_field.is_searchable = 1";
    }

    if ($inSelector) {
      $where .= " AND civicrm_custom_field.in_selector = 1 AND civicrm_custom_group.is_multiple = 1 ";
    }

    if ($extends) {
      $clause = [];
      foreach ($extends as $e) {
        $clause[] = "civicrm_custom_group.extends = '$e'";
      }
      $where .= " AND ( " . implode(' OR ', $clause) . " ) ";

      //include case activities customdata if case is enabled
      if (in_array('Activity', $extends)) {
        $extendValues = implode(',', array_keys(CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE)));
        $where .= " AND ( civicrm_custom_group.extends_entity_column_value IS NULL OR REPLACE( civicrm_custom_group.extends_entity_column_value, %2, ' ') IN ($extendValues) ) ";
        $params[2] = [CRM_Core_DAO::VALUE_SEPARATOR, 'String'];
      }
    }

    // ensure that the user has access to these custom groups
    $where .= " AND " .
      CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW,
        'civicrm_custom_group.'
      );

    $orderBy = " ORDER BY civicrm_custom_group.weight, civicrm_custom_field.weight";

    // final query string
    $queryString = $select . $from . $where . $orderBy;

    // dummy dao needed
    $crmDAO = CRM_Core_DAO::executeQuery($queryString, $params);

    // process records
    while ($crmDAO->fetch()) {
      $groupId = $crmDAO->civicrm_custom_group_id;
      $fieldId = $crmDAO->civicrm_custom_field_id;

      // create an array for groups if it does not exist
      if (!array_key_exists($groupId, $groupTree)) {
        $groupTree[$groupId] = [];
        $groupTree[$groupId]['id'] = $groupId;

        foreach ($tableData['civicrm_custom_group'] as $v) {
          $fullField = "civicrm_custom_group_" . $v;

          if ($v == 'id' || is_null($crmDAO->$fullField)) {
            continue;
          }

          $groupTree[$groupId][$v] = $crmDAO->$fullField;
        }

        $groupTree[$groupId]['fields'] = [];
      }

      // add the fields now (note - the query row will always contain a field)
      $groupTree[$groupId]['fields'][$fieldId] = [];
      $groupTree[$groupId]['fields'][$fieldId]['id'] = $fieldId;

      foreach ($tableData['civicrm_custom_field'] as $v) {
        $fullField = "civicrm_custom_field_" . $v;
        if ($v == 'id' || is_null($crmDAO->$fullField)) {
          continue;
        }
        $groupTree[$groupId]['fields'][$fieldId][$v] = $crmDAO->$fullField;
      }
    }

    return $groupTree;
  }

  /**
   * @param $entityType
   * @param $path
   * @param string $cidToken
   *
   * @return array
   */
  public static function &getActiveGroups($entityType, $path, $cidToken = '%%cid%%') {
    // for Group's
    $customGroupDAO = new CRM_Core_DAO_CustomGroup();

    // get 'Tab' and 'Tab with table' groups
    $customGroupDAO->whereAdd("style IN ('Tab', 'Tab with table')");
    $customGroupDAO->whereAdd("is_active = 1");

    // add whereAdd for entity type
    self::_addWhereAdd($customGroupDAO, $entityType, $cidToken);

    $groups = [];

    $permissionClause = CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW, NULL, TRUE);
    $customGroupDAO->whereAdd($permissionClause);

    // order by weight
    $customGroupDAO->orderBy('weight');
    $customGroupDAO->find();

    // process each group with menu tab
    while ($customGroupDAO->fetch()) {
      $group = [];
      $group['id'] = $customGroupDAO->id;
      $group['path'] = $path;
      $group['title'] = "$customGroupDAO->title";
      $group['query'] = "reset=1&gid={$customGroupDAO->id}&cid={$cidToken}";
      $group['extra'] = ['gid' => $customGroupDAO->id];
      $group['table_name'] = $customGroupDAO->table_name;
      $group['is_multiple'] = $customGroupDAO->is_multiple;
      $group['icon'] = $customGroupDAO->icon;
      $groups[] = $group;
    }

    return $groups;
  }

  /**
   * Get the table name for the entity type
   * currently if entity type is 'Contact', 'Individual', 'Household', 'Organization'
   * tableName is 'civicrm_contact'
   *
   * @param string $entityType
   *   What entity are we extending here ?.
   *
   * @return string
   *
   *
   * @see _apachesolr_civiAttachments_dereference_file_parent
   */
  public static function getTableNameByEntityName($entityType) {
    switch ($entityType) {
      case 'Contact':
      case 'Individual':
      case 'Household':
      case 'Organization':
        return 'civicrm_contact';

      default:
        return CRM_Core_DAO_AllCoreTables::getTableForEntityName($entityType);
    }
  }

  /**
   * Get a list of custom groups which extend a given entity type.
   * If there are custom-groups which only apply to certain subtypes,
   * those WILL be included.
   *
   * @param string $entityType
   *
   * @return CRM_Core_DAO_CustomGroup
   */
  public static function getAllCustomGroupsByBaseEntity($entityType) {
    $customGroupDAO = new CRM_Core_DAO_CustomGroup();
    self::_addWhereAdd($customGroupDAO, $entityType, NULL, TRUE);
    return $customGroupDAO;
  }

  /**
   * Add the whereAdd clause for the DAO depending on the type of entity
   * the custom group is extending.
   *
   * @param object $customGroupDAO
   * @param string $entityType
   *   What entity are we extending here ?.
   *
   * @param int $entityID
   * @param bool $allSubtypes
   */
  private static function _addWhereAdd(&$customGroupDAO, $entityType, $entityID = NULL, $allSubtypes = FALSE) {
    $addSubtypeClause = FALSE;
    // This function isn't really accessible with user data but since the string
    // is not passed as a param to the query CRM_Core_DAO::escapeString seems like a harmless
    // precaution.
    $entityType = CRM_Core_DAO::escapeString($entityType);

    switch ($entityType) {
      case 'Contact':
        // if contact, get all related to contact
        $extendList = "'Contact','Individual','Household','Organization'";
        $customGroupDAO->whereAdd("extends IN ( $extendList )");
        if (!$allSubtypes) {
          $addSubtypeClause = TRUE;
        }
        break;

      case 'Individual':
      case 'Household':
      case 'Organization':
        // is I/H/O then get I/H/O and contact
        $extendList = "'Contact','$entityType'";
        $customGroupDAO->whereAdd("extends IN ( $extendList )");
        if (!$allSubtypes) {
          $addSubtypeClause = TRUE;
        }
        break;

      default:
        $customGroupDAO->whereAdd("extends IN ('$entityType')");
        break;
    }

    if ($addSubtypeClause) {
      $csType = is_numeric($entityID) ? CRM_Contact_BAO_Contact::getContactSubType($entityID) : FALSE;

      if (!empty($csType)) {
        $subtypeClause = [];
        foreach ($csType as $subtype) {
          $subtype = CRM_Core_DAO::VALUE_SEPARATOR . $subtype .
            CRM_Core_DAO::VALUE_SEPARATOR;
          $subtypeClause[] = "extends_entity_column_value LIKE '%{$subtype}%'";
        }
        $subtypeClause[] = "extends_entity_column_value IS NULL";
        $customGroupDAO->whereAdd("( " . implode(' OR ', $subtypeClause) .
          " )");
      }
      else {
        $customGroupDAO->whereAdd("extends_entity_column_value IS NULL");
      }
    }
  }

  /**
   * Delete the Custom Group.
   *
   * @param CRM_Core_BAO_CustomGroup $group
   *   Custom group object.
   * @param bool $force
   *   whether to force the deletion, even if there are custom fields.
   *
   * @return bool
   *   False if field exists for this group, true if group gets deleted.
   */
  public static function deleteGroup($group, $force = FALSE) {

    //check whether this contain any custom fields
    $customField = new CRM_Core_DAO_CustomField();
    $customField->custom_group_id = $group->id;
    $customField->find();

    // return early if there are custom fields and we're not
    // forcing the delete, otherwise delete the fields one by one
    while ($customField->fetch()) {
      if (!$force) {
        return FALSE;
      }
      CRM_Core_BAO_CustomField::deleteField($customField);
    }

    // drop the table associated with this custom group
    CRM_Core_BAO_SchemaHandler::dropTable($group->table_name);

    //delete  custom group
    $group->delete();

    CRM_Utils_Hook::post('delete', 'CustomGroup', $group->id, $group);

    return TRUE;
  }

  /**
   * Delete a record from supplied params.
   * API3 calls deleteGroup() which removes the related civicrm_value_X table.
   * This function does the same for API4.
   *
   * @param array $record
   *   'id' is required.
   * @return CRM_Core_DAO
   * @throws CRM_Core_Exception
   */
  public static function deleteRecord(array $record) {
    $table = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'table_name');
    $result = parent::deleteRecord($record);
    CRM_Core_BAO_SchemaHandler::dropTable($table);
    return $result;
  }

  /**
   * Set defaults.
   *
   * @param array $groupTree
   * @param array $defaults
   * @param bool $viewMode
   * @param bool $inactiveNeeded
   * @param int $action
   */
  public static function setDefaults(&$groupTree, &$defaults, $viewMode = FALSE, $inactiveNeeded = FALSE, $action = CRM_Core_Action::NONE) {
    foreach ($groupTree as $id => $group) {
      if (!isset($group['fields'])) {
        continue;
      }
      foreach ($group['fields'] as $field) {
        if (isset($field['element_value'])) {
          $value = $field['element_value'];
        }
        elseif (isset($field['default_value']) &&
          ($action != CRM_Core_Action::UPDATE ||
            // CRM-7548
            !array_key_exists('element_value', $field)
          )
        ) {
          $value = $viewMode ? NULL : $field['default_value'];
        }
        else {
          continue;
        }

        if (empty($field['element_name'])) {
          continue;
        }

        $elementName = $field['element_name'];
        $serialize = CRM_Core_BAO_CustomField::isSerialized($field);

        if ($serialize) {
          if ($field['data_type'] != 'Country' && $field['data_type'] != 'StateProvince' && $field['data_type'] != 'ContactReference') {
            $defaults[$elementName] = [];
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($field['id'], $inactiveNeeded);
            if ($viewMode) {
              $checkedData = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($value, 1, -1));
              if (isset($value)) {
                foreach ($customOption as $customValue => $customLabel) {
                  if (in_array($customValue, $checkedData)) {
                    if ($field['html_type'] == 'CheckBox') {
                      $defaults[$elementName][$customValue] = 1;
                    }
                    else {
                      $defaults[$elementName][$customValue] = $customValue;
                    }
                  }
                  else {
                    $defaults[$elementName][$customValue] = 0;
                  }
                }
              }
            }
            else {
              if (isset($field['customValue']['data'])) {
                $checkedData = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($field['customValue']['data'], 1, -1));
                foreach ($customOption as $val) {
                  if (in_array($val['value'], $checkedData)) {
                    if ($field['html_type'] == 'CheckBox') {
                      $defaults[$elementName][$val['value']] = 1;
                    }
                    else {
                      $defaults[$elementName][$val['value']] = $val['value'];
                    }
                  }
                  else {
                    $defaults[$elementName][$val['value']] = 0;
                  }
                }
              }
              else {
                // Values may be "array strings" or actual arrays. Handle both.
                if (is_array($value) && count($value)) {
                  CRM_Utils_Array::formatArrayKeys($value);
                  $checkedValue = $value;
                }
                else {
                  $checkedValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($value, 1, -1));
                }
                foreach ($customOption as $val) {
                  if (in_array($val['value'], $checkedValue)) {
                    if ($field['html_type'] == 'CheckBox') {
                      $defaults[$elementName][$val['value']] = 1;
                    }
                    else {
                      $defaults[$elementName][$val['value']] = $val['value'];
                    }
                  }
                }
              }
            }
          }
          else {
            if (isset($value)) {
              // Values may be "array strings" or actual arrays. Handle both.
              if (is_array($value) && count($value)) {
                CRM_Utils_Array::formatArrayKeys($value);
                $checkedValue = $value;
              }
              // Serialized values from db
              elseif ($value === '' || strpos($value, CRM_Core_DAO::VALUE_SEPARATOR) !== FALSE) {
                $checkedValue = CRM_Utils_Array::explodePadded($value);
              }
              // Comma-separated values e.g. from a select2 widget during reload on form error
              else {
                $checkedValue = explode(',', $value);
              }
              foreach ($checkedValue as $val) {
                if ($val) {
                  $defaults[$elementName][$val] = $val;
                }
              }
            }
          }
        }
        else {
          if ($field['data_type'] == 'Country') {
            if ($value) {
              $defaults[$elementName] = $value;
            }
            else {
              $config = CRM_Core_Config::singleton();
              $defaults[$elementName] = $config->defaultContactCountry;
            }
          }
          else {
            if ($field['data_type'] == "Float") {
              $defaults[$elementName] = (float) $value;
            }
            elseif ($field['data_type'] == 'Money' &&
              $field['html_type'] == 'Text'
            ) {
              $defaults[$elementName] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($value);
            }
            else {
              $defaults[$elementName] = $value;
            }
          }
        }
      }
    }
  }

  /**
   * Old function only called from one place...
   * @see CRM_Dedupe_Finder::formatParams
   *
   * @param array $groupTree
   * @param array $params
   * @param bool $skipFile
   */
  public static function postProcess(&$groupTree, &$params, $skipFile = FALSE) {
    // Get the Custom form values and groupTree
    foreach ($groupTree as $groupID => $group) {
      if ($groupID === 'info') {
        continue;
      }
      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];
        $serialize = CRM_Core_BAO_CustomField::isSerialized($field);

        // Reset all checkbox, radio and multiselect data
        if ($field['html_type'] == 'Radio' || $serialize) {
          $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = 'NULL';
        }

        $v = NULL;
        foreach ($params as $key => $val) {
          if (preg_match('/^custom_(\d+)_?(-?\d+)?$/', $key, $match) &&
            $match[1] == $field['id']
          ) {
            $v = $val;
          }
        }

        if (!isset($groupTree[$groupID]['fields'][$fieldId]['customValue'])) {
          // field exists in db so populate value from "form".
          $groupTree[$groupID]['fields'][$fieldId]['customValue'] = [];
        }

        // Serialize checkbox and multi-select data (using array keys for checkbox)
        if ($serialize) {
          $v = ($v && $field['html_type'] === 'Checkbox') ? array_keys($v) : $v;
          $v = $v ? CRM_Utils_Array::implodePadded($v) : NULL;
        }

        switch ($field['html_type']) {

          case 'Select Date':
            $date = CRM_Utils_Date::processDate($v);
            $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = $date;
            break;

          case 'File':
            if ($skipFile) {
              break;
            }

            // store the file in d/b
            $entityId = explode('=', $groupTree['info']['where'][0]);
            $fileParams = ['upload_date' => date('YmdHis')];

            if ($groupTree[$groupID]['fields'][$fieldId]['customValue']['fid']) {
              $fileParams['id'] = $groupTree[$groupID]['fields'][$fieldId]['customValue']['fid'];
            }
            if (!empty($v)) {
              $fileParams['uri'] = $v['name'];
              $fileParams['mime_type'] = $v['type'];
              CRM_Core_BAO_File::filePostProcess($v['name'],
                $groupTree[$groupID]['fields'][$fieldId]['customValue']['fid'],
                $groupTree[$groupID]['table_name'],
                trim($entityId[1]),
                FALSE,
                TRUE,
                $fileParams,
                'custom_' . $fieldId,
                $v['type']
              );
            }
            $defaults = [];
            $paramsFile = [
              'entity_table' => $groupTree[$groupID]['table_name'],
              'entity_id' => $entityId[1],
            ];

            CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_EntityFile',
              $paramsFile,
              $defaults
            );

            $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = $defaults['file_id'];
            break;

          default:
            $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = $v;
            break;
        }
      }
    }
  }

  /**
   * Generic function to build all the form elements for a specific group tree.
   *
   * @param CRM_Core_Form $form
   *   The form object.
   * @param array $groupTree
   *   The group tree object.
   * @param bool $inactiveNeeded
   *   Return inactive custom groups.
   * @param string $prefix
   *   Prefix for custom grouptree assigned to template.
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildQuickForm(&$form, &$groupTree, $inactiveNeeded = FALSE, $prefix = '') {
    $form->assign_by_ref("{$prefix}groupTree", $groupTree);

    foreach ($groupTree as $id => $group) {
      foreach ($group['fields'] as $field) {
        $required = $field['is_required'] ?? NULL;
        //fix for CRM-1620
        if ($field['data_type'] == 'File') {
          if (!empty($field['element_value']['data'])) {
            $required = 0;
          }
        }

        $fieldId = $field['id'];
        $elementName = $field['element_name'];
        CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, $required);
        if ($form->getAction() == CRM_Core_Action::VIEW) {
          $form->getElement($elementName)->freeze();
        }
      }
    }
  }

  /**
   * Extract the get params from the url, validate and store it in session.
   *
   * @param CRM_Core_Form $form
   *   The form object.
   * @param string $type
   *   The type of custom group we are using.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function extractGetParams(&$form, $type) {
    if (empty($_GET)) {
      return [];
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($type);
    $customValue = [];
    $htmlType = [
      'CheckBox',
      'Multi-Select',
      'Select',
      'Radio',
    ];

    foreach ($groupTree as $group) {
      if (!isset($group['fields'])) {
        continue;
      }
      foreach ($group['fields'] as $key => $field) {
        $fieldName = 'custom_' . $key;
        $value = CRM_Utils_Request::retrieve($fieldName, 'String', $form, FALSE, NULL, 'GET');

        if ($value) {
          $valid = FALSE;
          if (!in_array($field['html_type'], $htmlType) ||
            $field['data_type'] == 'Boolean'
          ) {
            $valid = CRM_Core_BAO_CustomValue::typecheck($field['data_type'], $value);
          }
          if (CRM_Core_BAO_CustomField::isSerialized($field)) {
            $value = str_replace("|", ",", $value);
            $mulValues = explode(',', $value);
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($key, TRUE);
            $val = [];
            foreach ($mulValues as $v1) {
              foreach ($customOption as $coID => $coValue) {
                if (strtolower(trim($coValue['label'])) ==
                  strtolower(trim($v1))
                ) {
                  $val[$coValue['value']] = 1;
                }
              }
            }
            if (!empty($val)) {
              $value = $val;
              $valid = TRUE;
            }
            else {
              $value = NULL;
            }
          }
          elseif ($field['html_type'] === 'Select' ||
            ($field['html_type'] === 'Radio' &&
              $field['data_type'] !== 'Boolean'
            )
          ) {
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($key, TRUE);
            foreach ($customOption as $customID => $coValue) {
              if (strtolower(trim($coValue['label'])) ==
                strtolower(trim($value))
              ) {
                $value = $coValue['value'];
                $valid = TRUE;
              }
            }
          }
          elseif ($field['data_type'] === 'Date') {
            $valid = CRM_Utils_Rule::date($value);
          }

          if ($valid) {
            $customValue[$fieldName] = $value;
          }
        }
      }
    }

    return $customValue;
  }

  /**
   * Check the type of custom field type (eg: Used for Individual, Contribution, etc)
   * this function is used to get the custom fields of a type (eg: Used for Individual, Contribution, etc )
   *
   * @param int $customFieldId
   *   Custom field id.
   * @param array $removeCustomFieldTypes
   *   Remove custom fields of a type eg: array("Individual") ;.
   *
   * @return bool
   *   false if it matches else true
   *
   * @throws \CRM_Core_Exception
   */
  public static function checkCustomField($customFieldId, &$removeCustomFieldTypes) {
    $query = 'SELECT cg.extends as extends
                  FROM civicrm_custom_group as cg, civicrm_custom_field as cf
                  WHERE cg.id = cf.custom_group_id
                    AND cf.id =' .
      CRM_Utils_Type::escape($customFieldId, 'Integer');

    $extends = CRM_Core_DAO::singleValueQuery($query);

    if (in_array($extends, $removeCustomFieldTypes)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get table name for extends.
   *
   * @param string $entityName
   *
   * @return string
   * @throws Exception
   */
  public static function mapTableName($entityName) {
    $options = array_column(self::getCustomGroupExtendsOptions(), 'table_name', 'id');
    if (isset($options[$entityName])) {
      return $options[$entityName];
    }
    throw new CRM_Core_Exception('Unknown error');
  }

  /**
   * @param $group
   *
   * @throws \Exception
   */
  public static function createTable($group) {
    $params = [
      'name' => $group->table_name,
      'is_multiple' => $group->is_multiple ? 1 : 0,
      'extends_name' => self::mapTableName($group->extends),
    ];

    $tableParams = CRM_Core_BAO_CustomField::defaultCustomTableSchema($params);

    CRM_Core_BAO_SchemaHandler::createTable($tableParams);
  }

  /**
   * Function returns formatted groupTree, so that form can be easily built in template
   *
   * @param array $groupTree
   * @param int $groupCount
   *   Group count by default 1, but can vary for multiple value custom data.
   * @param \CRM_Core_Form $form
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function formatGroupTree($groupTree, $groupCount = 1, &$form = NULL) {
    $formattedGroupTree = [];
    $uploadNames = $formValues = [];

    // retrieve qf key from url
    $qfKey = CRM_Utils_Request::retrieve('qf', 'String');

    // fetch submitted custom field values later use to set as a default values
    if ($qfKey) {
      $submittedValues = Civi::cache('customData')->get($qfKey);
    }

    foreach ($groupTree as $key => $value) {
      if ($key === 'info') {
        continue;
      }

      // add group information
      $formattedGroupTree[$key]['name'] = $value['name'] ?? NULL;
      $formattedGroupTree[$key]['title'] = $value['title'] ?? NULL;
      $formattedGroupTree[$key]['help_pre'] = $value['help_pre'] ?? NULL;
      $formattedGroupTree[$key]['help_post'] = $value['help_post'] ?? NULL;
      $formattedGroupTree[$key]['collapse_display'] = $value['collapse_display'] ?? NULL;
      $formattedGroupTree[$key]['collapse_adv_display'] = $value['collapse_adv_display'] ?? NULL;
      $formattedGroupTree[$key]['style'] = $value['style'] ?? NULL;

      // this params needed of building multiple values
      $formattedGroupTree[$key]['is_multiple'] = $value['is_multiple'] ?? NULL;
      $formattedGroupTree[$key]['extends'] = $value['extends'] ?? NULL;
      $formattedGroupTree[$key]['extends_entity_column_id'] = $value['extends_entity_column_id'] ?? NULL;
      $formattedGroupTree[$key]['extends_entity_column_value'] = $value['extends_entity_column_value'] ?? NULL;
      $formattedGroupTree[$key]['subtype'] = $value['subtype'] ?? NULL;
      $formattedGroupTree[$key]['max_multiple'] = $value['max_multiple'] ?? NULL;

      // Properties that might have been filtered out but which
      // should be present to avoid smarty e-notices.
      $expectedProperties = ['options_per_line', 'help_pre', 'help_post'];
      // add field information
      foreach ($value['fields'] as $k => $properties) {
        $properties = array_merge(array_fill_keys($expectedProperties, NULL), $properties);
        $properties['element_name'] = "custom_{$k}_-{$groupCount}";
        if (isset($properties['customValue']) &&
          !CRM_Utils_System::isNull($properties['customValue']) &&
          !isset($properties['element_value'])
        ) {
          if (isset($properties['customValue'][$groupCount])) {
            $properties['element_name'] = "custom_{$k}_{$properties['customValue'][$groupCount]['id']}";
            $formattedGroupTree[$key]['table_id'] = $properties['customValue'][$groupCount]['id'];
            if ($properties['data_type'] === 'File') {
              $properties['element_value'] = $properties['customValue'][$groupCount];
              $uploadNames[] = $properties['element_name'];
            }
            else {
              $properties['element_value'] = $properties['customValue'][$groupCount]['data'];
            }
          }
        }
        if ($value = CRM_Utils_Request::retrieve($properties['element_name'], 'String', $form, FALSE, NULL, 'POST')) {
          $formValues[$properties['element_name']] = $value;
        }
        elseif (isset($submittedValues[$properties['element_name']])) {
          $properties['element_value'] = $submittedValues[$properties['element_name']];
        }
        unset($properties['customValue']);
        $formattedGroupTree[$key]['fields'][$k] = $properties;
      }
    }

    if ($form) {
      if (count($formValues)) {
        $qf = $form->get('qfKey');
        $form->assign('qfKey', $qf);
        Civi::cache('customData')->set($qf, $formValues);
      }

      // hack for field type File
      $formUploadNames = $form->get('uploadNames');
      if (is_array($formUploadNames)) {
        $uploadNames = array_unique(array_merge($formUploadNames, $uploadNames));
      }

      $form->set('uploadNames', $uploadNames);
    }

    return $formattedGroupTree;
  }

  /**
   * Build custom data view.
   *
   * @param CRM_Core_Form|CRM_Core_Page $form
   *   Page object.
   * @param array $groupTree
   * @param bool $returnCount
   *   True if customValue count needs to be returned.
   * @param int $gID
   * @param null $prefix
   * @param int $customValueId
   * @param int $entityId
   * @param bool $checkEditPermission
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  public static function buildCustomDataView($form, $groupTree, $returnCount = FALSE, $gID = NULL, $prefix = NULL, $customValueId = NULL, $entityId = NULL, $checkEditPermission = FALSE) {
    // Filter out pesky extra info
    unset($groupTree['info']);

    $details = [];

    $editableGroups = [];
    if ($checkEditPermission) {
      $editableGroups = \CRM_Core_Permission::customGroup(CRM_Core_Permission::EDIT);
    }

    foreach ($groupTree as $key => $group) {

      foreach ($group['fields'] as $k => $properties) {
        $groupID = $group['id'];
        if (!empty($properties['customValue'])) {
          foreach ($properties['customValue'] as $values) {
            if (!empty($customValueId) && $customValueId != $values['id']) {
              continue;
            }
            $details[$groupID][$values['id']]['title'] = $group['title'] ?? NULL;
            $details[$groupID][$values['id']]['name'] = $group['name'] ?? NULL;
            $details[$groupID][$values['id']]['help_pre'] = $group['help_pre'] ?? NULL;
            $details[$groupID][$values['id']]['help_post'] = $group['help_post'] ?? NULL;
            $details[$groupID][$values['id']]['collapse_display'] = $group['collapse_display'] ?? NULL;
            $details[$groupID][$values['id']]['collapse_adv_display'] = $group['collapse_adv_display'] ?? NULL;
            $details[$groupID][$values['id']]['style'] = $group['style'] ?? NULL;
            $details[$groupID][$values['id']]['fields'][$k] = [
              'field_title' => $properties['label'] ?? NULL,
              'field_type' => $properties['html_type'] ?? NULL,
              'field_data_type' => $properties['data_type'] ?? NULL,
              'field_value' => CRM_Core_BAO_CustomField::displayValue($values['data'], $properties['id'], $entityId),
              'options_per_line' => $properties['options_per_line'] ?? NULL,
              'data' => $values['data'],
            ];
            // editable = whether this set contains any non-read-only fields
            if (!isset($details[$groupID][$values['id']]['editable'])) {
              $details[$groupID][$values['id']]['editable'] = FALSE;
            }
            if (empty($properties['is_view']) && in_array($key, $editableGroups)) {
              $details[$groupID][$values['id']]['editable'] = TRUE;
            }
            // also return contact reference contact id if user has view all or edit all contacts perm
            if ($details[$groupID][$values['id']]['fields'][$k]['field_data_type'] === 'ContactReference'
              && CRM_Core_Permission::check([['view all contacts', 'edit all contacts']])
            ) {
              $details[$groupID][$values['id']]['fields'][$k]['contact_ref_links'] = [];
              $path = CRM_Contact_DAO_Contact::getEntityPaths()['view'];
              foreach (CRM_Utils_Array::explodePadded($values['data'] ?? []) as $contactId) {
                $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'display_name');
                if ($displayName) {
                  $url = CRM_Utils_System::url(str_replace('[id]', $contactId, $path));
                  $details[$groupID][$values['id']]['fields'][$k]['contact_ref_links'][] = '<a href="' . $url . '" title="' . htmlspecialchars(ts('View Contact')) . '">' .
                    $displayName . '</a>';
                }
              }
            }
          }
        }
        else {
          $details[$groupID][0]['title'] = $group['title'] ?? NULL;
          $details[$groupID][0]['name'] = $group['name'] ?? NULL;
          $details[$groupID][0]['help_pre'] = $group['help_pre'] ?? NULL;
          $details[$groupID][0]['help_post'] = $group['help_post'] ?? NULL;
          $details[$groupID][0]['collapse_display'] = $group['collapse_display'] ?? NULL;
          $details[$groupID][0]['collapse_adv_display'] = $group['collapse_adv_display'] ?? NULL;
          $details[$groupID][0]['style'] = $group['style'] ?? NULL;
          $details[$groupID][0]['fields'][$k] = ['field_title' => $properties['label'] ?? NULL];
        }
      }
    }

    if ($returnCount) {
      // return a single value count if group id is passed to function
      // else return a groupId and count mapped array
      if (!empty($gID)) {
        return count($details[$gID]);
      }
      else {
        $countValue = [];
        foreach ($details as $key => $value) {
          $countValue[$key] = count($details[$key]);
        }
        return $countValue;
      }
    }
    else {
      $form->addExpectedSmartyVariables([
        'multiRecordDisplay',
        'groupId',
        'skipTitle',
      ]);
      $form->assign("{$prefix}viewCustomData", $details);
      return $details;
    }
  }

  /**
   * Get the custom group titles by custom field ids.
   *
   * @param array $fieldIds
   *   Array of custom field ids.
   *
   * @return array|NULL
   *   array consisting of groups and fields labels with ids.
   */
  public static function getGroupTitles($fieldIds) {
    if (!is_array($fieldIds) && empty($fieldIds)) {
      return NULL;
    }

    $groupLabels = [];
    $fIds = "(" . implode(',', $fieldIds) . ")";

    $query = "
SELECT  civicrm_custom_group.id as groupID, civicrm_custom_group.title as groupTitle,
        civicrm_custom_field.label as fieldLabel, civicrm_custom_field.id as fieldID
  FROM  civicrm_custom_group, civicrm_custom_field
 WHERE  civicrm_custom_group.id = civicrm_custom_field.custom_group_id
   AND  civicrm_custom_field.id IN {$fIds}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $groupLabels[$dao->fieldID] = [
        'fieldID' => $dao->fieldID,
        'fieldLabel' => $dao->fieldLabel,
        'groupID' => $dao->groupID,
        'groupTitle' => $dao->groupTitle,
      ];
    }

    return $groupLabels;
  }

  public static function dropAllTables() {
    $query = "SELECT table_name FROM civicrm_custom_group";
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $query = "DROP TABLE IF EXISTS {$dao->table_name}";
      CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
   * Check whether custom group is empty or not.
   *
   * @param int $gID
   *   Custom group id.
   *
   * @return bool|NULL
   *   true if empty otherwise false.
   */
  public static function isGroupEmpty($gID) {
    if (!$gID) {
      return NULL;
    }

    $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
      $gID,
      'table_name'
    );

    $query = "SELECT count(id) FROM {$tableName} WHERE id IS NOT NULL LIMIT 1";
    $value = CRM_Core_DAO::singleValueQuery($query);

    if (empty($value)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Deprecated function, use APIv4 getFields instead.
   *
   * @deprecated
   * @param array $types
   *   Var which should have the list appended.
   */
  public static function getExtendedObjectTypes(&$types = []) {
    $cache = Civi::cache('metadata');
    if (!$cache->has(__FUNCTION__)) {
      $objTypes = [];

      $extendObjs = \Civi\Api4\OptionValue::get(FALSE)
        ->addSelect('value', 'grouping')
        ->addWhere('option_group_id:name', '=', 'cg_extend_objects')
        ->addWhere('grouping', 'IS NOT EMPTY')
        ->addWhere('is_active', '=', TRUE)
        ->execute()->indexBy('value')->column('grouping');

      foreach ($extendObjs as $entityName => $grouping) {
        try {
          $objTypes[$entityName] = civicrm_api4($entityName, 'getFields', [
            'loadOptions' => TRUE,
            'where' => [['name', '=', $grouping]],
            'checkPermissions' => FALSE,
            'select' => ['options'],
          ], 0)['options'] ?? NULL;
        }
        catch (\Civi\API\Exception\NotImplementedException $e) {
          // Skip if component is disabled
        }
      }
      $cache->set(__FUNCTION__, $objTypes);
    }
    else {
      $objTypes = $cache->get(__FUNCTION__);
    }

    $types = array_merge($types, $objTypes);
  }

  /**
   * Loads pseudoconstant option values for the `extends_entity_column_value` field.
   *
   * @param $context
   * @param array $params
   * @param array $props
   * @return array
   */
  public static function getExtendsEntityColumnValueOptions($context, $params, $props = []) {
    // Requesting this option list only makes sense if the value of 'extends' is known or can be looked up
    if (!empty($props['id']) || !empty($props['name']) || !empty($props['extends']) || !empty($props['extends_entity_column_id'])) {
      $id = $props['id'] ?? NULL;
      $name = $props['name'] ?? NULL;
      $extends = $props['extends'] ?? NULL;
      $entityColumnId = $props['extends_entity_column_id'] ?? NULL;

      if (!array_key_exists('extends_entity_column_id', $props) && ($id || $name)) {
        $entityColumnId = CRM_Core_DAO::getFieldValue(parent::class, $id ?: $name, 'extends_entity_column_id', $id ? 'id' : 'name');
      }
      // If there is an entityColumnId (currently only used by Participants) use grouping from that sub-type.
      if ($entityColumnId) {
        $pseudoSelectors = array_column(self::getExtendsEntityColumnIdOptions(), NULL, 'id');
        $grouping = $pseudoSelectors[$entityColumnId]['grouping'];
        $extends = $pseudoSelectors[$entityColumnId]['extends'];
      }
      else {
        if (!$extends) {
          $extends = CRM_Core_DAO::getFieldValue(parent::class, $id ?: $name, 'extends', $id ? 'id' : 'name');
        }
        $allTypes = array_column(self::getCustomGroupExtendsOptions(), NULL, 'id');
        $grouping = $allTypes[$extends]['grouping'] ?? NULL;
      }
      if (!$grouping) {
        return [];
      }
      $getFieldsValues = [];
      // For contact types
      if (in_array($extends, CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE)) {
        $getFieldsValues['contact_type'] = $extends;
        $extends = 'Contact';
      }
      try {
        $field = civicrm_api4($extends, 'getFields', [
          'checkPermissions' => FALSE,
          'loadOptions' => ['id', 'name', 'label'],
          'where' => [['name', '=', $grouping]],
          'values' => $getFieldsValues,
        ])->first();
        if ($field['options']) {
          return $field['options'];
        }
        // This is to support the ParticipantEventName which groups by event_id, a field with no pseudoconstant
        elseif ($field['fk_entity']) {
          $fkFields = civicrm_api4($field['entity'], 'getFields', [
            'checkPermissions' => FALSE,
          ])->indexBy('name');
          $fkIdField = \Civi\Api4\Utils\CoreUtil::getIdFieldName($field['fk_entity']);
          $fkLabelField = \Civi\Api4\Utils\CoreUtil::getInfoItem($field['fk_entity'], 'label_field');
          $fkNameField = isset($fkFields['name']) ? 'name' : 'id';
          $select = [$fkIdField, $fkNameField, $fkLabelField];
          $where = [];
          if (isset($fkFields['is_active'])) {
            $where[] = ['is_active', '=', TRUE];
          }
          $fkEntities = civicrm_api4($field['fk_entity'], 'get', [
            'checkPermissions' => !(isset($params['check_permissions']) && !$params['check_permissions']),
            'select' => $select,
            'where' => $where,
          ]);
          $fkOptions = [];
          foreach ($fkEntities as $item) {
            $fkOptions[] = [
              'id' => $item[$fkIdField],
              'name' => $item[$fkNameField],
              'label' => $item[$fkLabelField],
            ];
          }
          return $fkOptions;
        }
      }
      catch (\Civi\API\Exception\NotImplementedException $e) {
        // Component disabled
        return [];
      }
    }
    return [];
  }

  /**
   * Loads pseudoconstant option values for the `extends_entity_column_id` field.
   *
   * @param string $context
   * @param array $params
   * @param array $props
   * @return array
   */
  public static function getExtendsEntityColumnIdOptions($context = NULL, $params = [], $props = []) {
    $ogId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'custom_data_type', 'id', 'name');
    $optionValues = CRM_Core_BAO_OptionValue::getOptionValuesArray($ogId);

    // There is no explicit link between the result in the custom_data_type group and the entity
    // they correspond to. So we rely on the convention of each option's 'name' beginning with
    // the name of the entity.
    $extendsEntities = array_column(self::getCustomGroupExtendsOptions(), 'id');
    // Sort enties by strlen to ensure the correct one is matched
    usort($extendsEntities, function($a, $b) {
      return strlen($b) <=> strlen($a);
    });
    // Match the entity which is at the beginning of the 'name'. Note: at the time of this writing there
    // are only 3 items in the option_group and they are all for "Participant". So we could just return
    // "Participant" but that would prevent extensions from creating enties with complex custom fields.
    $getEntityName = function($optionValueName) use ($extendsEntities) {
      foreach ($extendsEntities as $entityName) {
        if (strpos($optionValueName, $entityName) === 0) {
          return $entityName;
        }
      }
    };

    $result = [];
    foreach ($optionValues as $optionValue) {
      $result[] = [
        'id' => $optionValue['value'],
        'name' => $optionValue['name'],
        'label' => $optionValue['label'],
        'grouping' => $optionValue['grouping'] ?? NULL,
        // For internal use & filtering. Not returned by APIv4 getFields.options.
        'extends' => $getEntityName($optionValue['name']),
      ];
    }

    if (!empty($props['extends'])) {
      $result = CRM_Utils_Array::findAll($result, ['extends' => $props['extends']]);
    }
    return $result;
  }

  /**
   * @inheritDoc
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    // This is necessary in order to pass $props to the callback function
    if ($fieldName === 'extends_entity_column_value') {
      $options = self::getExtendsEntityColumnValueOptions($context, [], $props);
      return CRM_Core_PseudoConstant::formatArrayOptions($context, $options);
    }
    // Provides filtering by 'extends' entity
    if ($fieldName === 'extends_entity_column_id') {
      $options = self::getExtendsEntityColumnIdOptions($context, [], $props);
      return CRM_Core_PseudoConstant::formatArrayOptions($context, $options);
    }
    // Legacy support for APIv3 which also needs the ParticipantEventName etc pseudo-selectors
    if ($fieldName === 'extends' && ($props['version'] ?? NULL) == 3) {
      $options = CRM_Core_SelectValues::customGroupExtends();
      return CRM_Core_PseudoConstant::formatArrayOptions($context, $options);
    }
    return parent::buildOptions($fieldName, $context, $props);
  }

  /**
   * @param int $customGroupId
   * @param int $entityId
   *
   * @return bool
   */
  public static function hasReachedMaxLimit($customGroupId, $entityId) {
    // check whether the group is multiple
    $isMultiple = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'is_multiple');
    $isMultiple = (bool) $isMultiple;
    $hasReachedMax = FALSE;
    if ($isMultiple &&
      ($maxMultiple = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'max_multiple'))
    ) {
      if (!$maxMultiple) {
        $hasReachedMax = FALSE;
      }
      else {
        $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'table_name');
        // count the number of entries for a entity
        $sql = "SELECT COUNT(id) FROM {$tableName} WHERE entity_id = %1";
        $params = [1 => [$entityId, 'Integer']];
        $count = CRM_Core_DAO::singleValueQuery($sql, $params);

        if ($count >= $maxMultiple) {
          $hasReachedMax = TRUE;
        }
      }
    }
    return $hasReachedMax;
  }

  /**
   * @return array
   */
  public static function getMultipleFieldGroup() {
    CRM_Core_Error::deprecatedFunctionWarning('api');
    $multipleGroup = [];
    $dao = new CRM_Core_DAO_CustomGroup();
    $dao->is_multiple = 1;
    $dao->is_active = 1;
    $dao->find();
    while ($dao->fetch()) {
      $multipleGroup[$dao->id] = $dao->title;
    }
    return $multipleGroup;
  }

  /**
   * Build the metadata tree for the custom group.
   *
   * @param string $entityType
   * @param array $toReturn
   * @param array $subTypes
   * @param string $queryString
   * @param array $params
   * @param string $subType
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private static function buildGroupTree($entityType, $toReturn, $subTypes, $queryString, $params, $subType) {
    $groupTree = $multipleFieldGroups = [];
    $crmDAO = CRM_Core_DAO::executeQuery($queryString, $params);
    $customValueTables = [];

    // process records
    while ($crmDAO->fetch()) {
      // get the id's
      $groupID = $crmDAO->civicrm_custom_group_id;
      $fieldId = $crmDAO->civicrm_custom_field_id;
      if ($crmDAO->civicrm_custom_group_is_multiple) {
        $multipleFieldGroups[$groupID] = $crmDAO->civicrm_custom_group_table_name;
      }
      // create an array for groups if it does not exist
      if (!array_key_exists($groupID, $groupTree)) {
        $groupTree[$groupID] = [];
        $groupTree[$groupID]['id'] = $groupID;

        // populate the group information
        foreach ($toReturn['custom_group'] as $fieldName) {
          $fullFieldName = "civicrm_custom_group_$fieldName";
          if ($fieldName == 'id' ||
            is_null($crmDAO->$fullFieldName)
          ) {
            continue;
          }
          // CRM-5507
          // This is an old bit of code - per the CRM number & probably does not work reliably if
          // that one contact sub-type exists.
          if ($fieldName == 'extends_entity_column_value' && !empty($subTypes[0])) {
            $groupTree[$groupID]['subtype'] = self::validateSubTypeByEntity($entityType, $subType);
          }
          $groupTree[$groupID][$fieldName] = $crmDAO->$fullFieldName;
        }
        $groupTree[$groupID]['fields'] = [];

        $customValueTables[$crmDAO->civicrm_custom_group_table_name] = [];
      }

      // add the fields now (note - the query row will always contain a field)
      // we only reset this once, since multiple values come is as multiple rows
      if (!array_key_exists($fieldId, $groupTree[$groupID]['fields'])) {
        $groupTree[$groupID]['fields'][$fieldId] = [];
      }

      $customValueTables[$crmDAO->civicrm_custom_group_table_name][$crmDAO->civicrm_custom_field_column_name] = 1;
      $groupTree[$groupID]['fields'][$fieldId]['id'] = $fieldId;
      // populate information for a custom field
      foreach ($toReturn['custom_field'] as $fieldName) {
        $fullFieldName = "civicrm_custom_field_$fieldName";
        if ($fieldName == 'id' ||
          is_null($crmDAO->$fullFieldName)
        ) {
          continue;
        }
        $groupTree[$groupID]['fields'][$fieldId][$fieldName] = $crmDAO->$fullFieldName;
      }
    }

    if (!empty($customValueTables)) {
      $groupTree['info'] = ['tables' => $customValueTables];
    }
    return [$multipleFieldGroups, $groupTree];
  }

  /**
   * Use APIv4 getFields (or self::getExtendsEntityColumnValueOptions) instead of this beast.
   * @deprecated
   * @return array
   */
  public static function getSubTypes(): array {
    $sel2 = [];
    $activityType = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'search');

    $eventType = CRM_Core_OptionGroup::values('event_type');
    $campaignTypes = CRM_Campaign_PseudoConstant::campaignType();
    $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypes(FALSE);
    $participantRole = CRM_Core_OptionGroup::values('participant_role');

    asort($activityType);
    asort($eventType);
    asort($membershipType);
    asort($participantRole);

    $sel2['Event'] = $eventType;
    $sel2['Activity'] = $activityType;
    $sel2['Campaign'] = $campaignTypes;
    $sel2['Membership'] = $membershipType;
    $sel2['ParticipantRole'] = $participantRole;
    $sel2['ParticipantEventName'] = CRM_Event_PseudoConstant::event(NULL, FALSE, "( is_template != 1 )");
    $sel2['ParticipantEventType'] = $eventType;
    $sel2['Contribution'] = CRM_Contribute_PseudoConstant::financialType();
    $sel2['Relationship'] = CRM_Custom_Form_Group::getRelationshipTypes();

    $sel2['Individual'] = CRM_Contact_BAO_ContactType::subTypePairs('Individual', FALSE, NULL);
    $sel2['Household'] = CRM_Contact_BAO_ContactType::subTypePairs('Household', FALSE, NULL);
    $sel2['Organization'] = CRM_Contact_BAO_ContactType::subTypePairs('Organization', FALSE, NULL);

    CRM_Core_BAO_CustomGroup::getExtendedObjectTypes($sel2);
    return $sel2;
  }

  /**
   * Get the munged entity.
   *
   * This is the entity eg. Relationship or the name of the sub entity
   * e.g ParticipantRole.
   *
   * @param string $extends
   * @param int|null $extendsEntityColumn
   *
   * @return string
   */
  protected static function getMungedEntity($extends, $extendsEntityColumn = NULL) {
    if (!$extendsEntityColumn || $extendsEntityColumn === 'null') {
      return $extends;
    }
    return CRM_Core_OptionGroup::values('custom_data_type', FALSE, FALSE, FALSE, NULL, 'name')[$extendsEntityColumn];
  }

  /**
   * @param int $groupId
   * @param int $operation
   * @param int|null $userId
   */
  public static function checkGroupAccess($groupId, $operation = CRM_Core_Permission::EDIT, $userId = NULL): bool {
    $allowedGroups = CRM_Core_Permission::customGroup($operation, FALSE, $userId);
    return in_array($groupId, $allowedGroups);
  }

  /**
   * List all possible values for `CustomGroup.extends`.
   *
   * This includes the fake entities "Individual", "Organization", "Household"
   * but not the extra options from `custom_data_type` used on the form ("ParticipantStatus", etc).
   *
   * Returns a mix of hard-coded array and `cg_extend_objects` OptionValues.
   * The 'id' return key maps to the 'value' in `cg_extend_objects`.
   * The 'grouping' key refers to the entity field used to select a sub-type.
   * The 'table_name' key is for internal use (is not returned by getFields.loadOptions), and
   * maps to the 'name' in `cg_extend_objects`. We don't return it as the 'name' in getFields because
   * it is not always unique (since contact types are pseudo-entities in this list).
   *
   * @return array{id: string, label: string, grouping: string, table_name: string}[]
   */
  public static function getCustomGroupExtendsOptions() {
    $options = [
      [
        'id' => 'Activity',
        'label' => ts('Activities'),
        'grouping' => 'activity_type_id',
        'table_name' => 'civicrm_activity',
      ],
      [
        'id' => 'Relationship',
        'label' => ts('Relationships'),
        'grouping' => 'relationship_type_id',
        'table_name' => 'civicrm_relationship',
      ],
      [
        'id' => 'Contribution',
        'label' => ts('Contributions'),
        'grouping' => 'financial_type_id',
        'table_name' => 'civicrm_contribution',
      ],
      [
        'id' => 'ContributionRecur',
        'label' => ts('Recurring Contributions'),
        'grouping' => NULL,
        'table_name' => 'civicrm_contribution_recur',
      ],
      [
        'id' => 'Group',
        'label' => ts('Groups'),
        'grouping' => NULL,
        'table_name' => 'civicrm_group',
      ],
      [
        'id' => 'Membership',
        'label' => ts('Memberships'),
        'grouping' => 'membership_type_id',
        'table_name' => 'civicrm_membership',
      ],
      [
        'id' => 'Event',
        'label' => ts('Events'),
        'grouping' => 'event_type_id',
        'table_name' => 'civicrm_event',
      ],
      [
        'id' => 'Participant',
        'label' => ts('Participants'),
        'grouping' => NULL,
        'table_name' => 'civicrm_participant',
      ],
      [
        'id' => 'Pledge',
        'label' => ts('Pledges'),
        'grouping' => NULL,
        'table_name' => 'civicrm_pledge',
      ],
      [
        'id' => 'Address',
        'label' => ts('Addresses'),
        'grouping' => NULL,
        'table_name' => 'civicrm_address',
      ],
      [
        'id' => 'Campaign',
        'label' => ts('Campaigns'),
        'grouping' => 'campaign_type_id',
        'table_name' => 'civicrm_campaign',
      ],
      [
        'id' => 'Contact',
        'label' => ts('Contacts'),
        'grouping' => NULL,
        'table_name' => 'civicrm_contact',
      ],
    ];
    // `CustomGroup.extends` stores contact type as if it were an entity.
    foreach (CRM_Contact_BAO_ContactType::basicTypePairs(TRUE) as $contactType => $contactTypeLabel) {
      $options[] = [
        'id' => $contactType,
        'label' => $contactTypeLabel,
        'grouping' => 'contact_sub_type',
        'table_name' => 'civicrm_contact',
      ];
    }
    $ogId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'cg_extend_objects', 'id', 'name');
    $ogValues = CRM_Core_BAO_OptionValue::getOptionValuesArray($ogId);
    foreach ($ogValues as $ogValue) {
      $options[] = [
        'id' => $ogValue['value'],
        'label' => $ogValue['label'],
        'grouping' => $ogValue['grouping'] ?? NULL,
        'table_name' => $ogValue['name'],
      ];
    }
    return $options;
  }

}
