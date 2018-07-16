<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Business object for managing custom data groups.
 */
class CRM_Core_BAO_CustomGroup extends CRM_Core_DAO_CustomGroup {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Takes an associative array and creates a custom group object.
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Core_DAO_CustomGroup
   */
  public static function create(&$params) {
    // create custom group dao, populate fields and then save.
    $group = new CRM_Core_DAO_CustomGroup();
    if (isset($params['title'])) {
      $group->title = $params['title'];
    }

    $extends = CRM_Utils_Array::value('extends', $params, []);
    $extendsEntity = CRM_Utils_Array::value(0, $extends);

    $participantEntities = array(
      'ParticipantRole',
      'ParticipantEventName',
      'ParticipantEventType',
    );

    if (in_array($extendsEntity, $participantEntities)) {
      $group->extends = 'Participant';
    }
    else {
      $group->extends = $extendsEntity;
    }

    $group->extends_entity_column_id = 'null';
    if (in_array($extendsEntity, $participantEntities)
    ) {
      $group->extends_entity_column_id = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $extendsEntity, 'value', 'name');
    }

    //this is format when form get submit.
    $extendsChildType = CRM_Utils_Array::value(1, $extends);
    //lets allow user to pass direct child type value, CRM-6893
    if (!empty($params['extends_entity_column_value'])) {
      $extendsChildType = $params['extends_entity_column_value'];
    }
    if (!CRM_Utils_System::isNull($extendsChildType)) {
      $extendsChildType = implode(CRM_Core_DAO::VALUE_SEPARATOR, $extendsChildType);
      if (CRM_Utils_Array::value(0, $extends) == 'Relationship') {
        $extendsChildType = str_replace(array('_a_b', '_b_a'), array(
          '',
          '',
        ), $extendsChildType);
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
    $fields = array(
      'style',
      'collapse_display',
      'collapse_adv_display',
      'help_pre',
      'help_post',
      'is_active',
      'is_multiple',
    );
    $current_db_version = CRM_Core_DAO::singleValueQuery("SELECT version FROM civicrm_domain WHERE id = " . CRM_Core_Config::domainID());
    $is_public_version = $current_db_version >= '4.7.19' ? 1 : 0;
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
        //check whether custom group was changed from single-valued to multiple-valued
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
      $group->created_id = CRM_Utils_Array::value('created_id', $params);
      $group->created_date = CRM_Utils_Array::value('created_date', $params);

      // we do this only once, so name never changes
      if (isset($params['name'])) {
        $group->name = CRM_Utils_String::munge($params['name'], '_', 64);
      }
      else {
        $group->name = CRM_Utils_String::munge($group->title, '_', 64);
      }

      if (isset($params['table_name'])) {
        $tableName = $params['table_name'];

        if (CRM_Core_DAO_AllCoreTables::isCoreTable($tableName)) {
          // Bad idea.  Prevent group creation because it might lead to a broken configuration.
          CRM_Core_Error::fatal(ts("Cannot create custom table because %1 is already a core table.", array('1' => $tableName)));
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
      CRM_Core_BAO_SchemaHandler::changeFKConstraint($table, self::mapTableName($extendsEntity));
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
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_CustomGroup
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomGroup', $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    // reset the cache
    CRM_Core_BAO_Cache::deleteGroup('contact fields');

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
    return $dao->find() ? TRUE : FALSE;
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
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE, FALSE); // everything
    $params = array(
      'version' => 3,
      'extends' => 'Activity',
      'extends_entity_column_id' => NULL,
      'extends_entity_column_value' => CRM_Utils_Array::implodePadded(array($activityTypeId)),
      'title' => ts('%1 Questions', array(1 => $activityTypes[$activityTypeId])),
      'style' => 'Inline',
      'is_active' => 1,
    );
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
   *
   * @param bool $checkPermission
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
   */
  public static function getTree(
    $entityType,
    $toReturn = array(),
    $entityID = NULL,
    $groupID = NULL,
    $subTypes = array(),
    $subName = NULL,
    $fromCache = TRUE,
    $onlySubType = NULL,
    $returnAll = FALSE,
    $checkPermission = TRUE,
    $singleRecord = NULL,
    $showPublicOnly = FALSE
  ) {
    if ($entityID) {
      $entityID = CRM_Utils_Type::escape($entityID, 'Integer');
    }
    if (!is_array($subTypes)) {
      if (empty($subTypes)) {
        $subTypes = array();
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
    $strWhere = $orderBy = '';

    // legacy hardcoded list of data to return
    $tableData = array(
      'custom_field' => array(
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
      ),
      'custom_group' => array(
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
      ),
    );
    $current_db_version = CRM_Core_DAO::singleValueQuery("SELECT version FROM civicrm_domain WHERE id = " . CRM_Core_Config::domainID());
    $is_public_version = $current_db_version >= '4.7.19' ? 1 : 0;
    if ($is_public_version) {
      $tableData['custom_group'][] = 'is_public';
    }
    if (!$toReturn || !is_array($toReturn)) {
      $toReturn = $tableData;
    }
    else {
      // Supply defaults and remove unknown array keys
      $toReturn = array_intersect_key(array_filter($toReturn) + $tableData, $tableData);
      // Merge in required fields that we must have
      $toReturn['custom_field'] = array_unique(array_merge($toReturn['custom_field'], array('id', 'column_name', 'data_type')));
      $toReturn['custom_group'] = array_unique(array_merge($toReturn['custom_group'], array('id', 'is_multiple', 'table_name', 'name')));
      // Validate return fields
      $toReturn['custom_field'] = array_intersect($toReturn['custom_field'], array_keys(CRM_Core_DAO_CustomField::fieldKeys()));
      $toReturn['custom_group'] = array_intersect($toReturn['custom_group'], array_keys(CRM_Core_DAO_CustomGroup::fieldKeys()));
    }

    // create select
    $select = array();
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

    $params = array();
    $sqlParamKey = 1;
    if (!empty($subTypes)) {
      foreach ($subTypes as $key => $subType) {
        $subTypeClauses[] = self::whereListHas("civicrm_custom_group.extends_entity_column_value", self::validateSubTypeByEntity($entityType, $subType));
      }
      $subTypeClause = '(' .  implode(' OR ', $subTypeClauses) . ')';
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
        $params[$sqlParamKey] = array($subName, 'String');
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
      $params[$sqlParamKey] = array($groupID, 'Integer');
    }
    elseif (!$groupID) {
      // since groupID is false we need to show all Inline groups
      $strWhere .= " AND civicrm_custom_group.style = 'Inline'";
    }
    if ($checkPermission) {
      // ensure that the user has access to these custom groups
      $strWhere .= " AND " .
        CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW,
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
    $tablesWithEntityData = array();
    if ($fromCache) {
      $groupTree = $cache->get($cacheKey);
      $multipleFieldGroups = $cache->get($multipleFieldGroupCacheKey);
    }

    if (empty($groupTree)) {
      $groupTree = $multipleFieldGroups = array();
      $crmDAO = CRM_Core_DAO::executeQuery($queryString, $params);
      $customValueTables = array();

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
          $groupTree[$groupID] = array();
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
          $groupTree[$groupID]['fields'] = array();

          $customValueTables[$crmDAO->civicrm_custom_group_table_name] = array();
        }

        // add the fields now (note - the query row will always contain a field)
        // we only reset this once, since multiple values come is as multiple rows
        if (!array_key_exists($fieldId, $groupTree[$groupID]['fields'])) {
          $groupTree[$groupID]['fields'][$fieldId] = array();
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
        $groupTree['info'] = array('tables' => $customValueTables);
      }

      $cache->set($cacheKey, $groupTree);
      $cache->set($multipleFieldGroupCacheKey, $multipleFieldGroups);
    }
    //entitySelectClauses is an array of select clauses for custom value tables which are not multiple
    // and have data for the given entities. $entityMultipleSelectClauses is the same for ones with multiple
    $entitySingleSelectClauses = $entityMultipleSelectClauses = $groupTree['info']['select'] = array();
    $singleFieldTables = array();
    // now that we have all the groups and fields, lets get the values
    // since we need to know the table and field names
    // add info to groupTree

    if (isset($groupTree['info']) && !empty($groupTree['info']) &&
      !empty($groupTree['info']['tables']) && $singleRecord != 'new'
    ) {
      $select = $from = $where = array();
      $groupTree['info']['where'] = NULL;

      foreach ($groupTree['info']['tables'] as $table => $fields) {
        $groupTree['info']['from'][] = $table;
        $select = array(
          "{$table}.id as {$table}_id",
          "{$table}.entity_id as {$table}_entity_id",
        );
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
   * @throws \CiviCRM_API3_Exception
   */
  protected static function validateSubTypeByEntity($entityType, $subType) {
    $subType = trim($subType, CRM_Core_DAO::VALUE_SEPARATOR);
    if (is_numeric($subType)) {
      return $subType;
    }

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypeInfo(TRUE);
    $contactTypes = array_merge($contactTypes, array('Event' => 1));

    if ($entityType != 'Contact' && !array_key_exists($entityType, $contactTypes)) {
      throw new CRM_Core_Exception('Invalid Entity Filter');
    }
    $subTypes = CRM_Contact_BAO_ContactType::subTypeInfo($entityType, TRUE);
    $subTypes = array_merge($subTypes, CRM_Event_PseudoConstant::eventType());
    if (!array_key_exists($subType, $subTypes)) {
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
  static private function whereListHas($column, $value, $delimiter = CRM_Core_DAO::VALUE_SEPARATOR) {
    $bareValue = trim($value, $delimiter); // ?
    $escapedValue = CRM_Utils_Type::escape("%{$delimiter}{$bareValue}{$delimiter}%", 'String', FALSE);
    return "($column LIKE \"$escapedValue\")";
  }

  /**
   * Check whether the custom group has any data for the given entity.
   *
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
  static public function customGroupDataExistsForEntity($entityID, $table, $getCount = FALSE) {
    $query = "
      SELECT count(id)
      FROM   $table
      WHERE  entity_id = $entityID
    ";
    $recordExists = CRM_Core_DAO::singleValueQuery($query);
    if ($getCount) {
      return $recordExists;
    }
    return $recordExists ? TRUE : FALSE;
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
  static public function buildEntityTreeSingleFields(&$groupTree, $entityID, $entitySingleSelectClauses, $singleFieldTablesWithEntityData) {
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
   * @param varchar $singleRecord
   *   holds 'new' or id if view/edit/copy form for a single record is being loaded.
   */
  static public function buildEntityTreeMultipleFields(&$groupTree, $entityID, $entityMultipleSelectClauses, $multipleFieldTablesWithEntityData, $singleRecord = NULL) {
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
      self::buildTreeEntityDataFromQuery($groupTree, $query, array($table), $singleRecord);
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
   * @param varchar $singleRecord
   *   holds 'new' OR id if view/edit/copy form for a single record is being loaded.
   */
  static public function buildTreeEntityDataFromQuery(&$groupTree, $query, $includedTables, $singleRecord = NULL) {
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
   * @param varchar $singleRecord
   *   holds 'new' or id if loading view/edit/copy for a single record.
   */
  static public function buildCustomFieldData($dao, &$groupTree, $table, $groupID, $fieldID, $singleRecord = NULL) {
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
          $customValue['id'] = $dao->$idName;
          $customValue['data'] = $fileDAO->uri;
          $customValue['fid'] = $fileDAO->id;
          $customValue['fileURL'] = CRM_Utils_System::url('civicrm/file', "reset=1&id={$fileDAO->id}&eid={$dao->$entityIDName}");
          $customValue['displayURL'] = NULL;
          $deleteExtra = ts('Are you sure you want to delete attached file.');
          $deleteURL = array(
            CRM_Core_Action::DELETE => array(
              'name' => ts('Delete Attached File'),
              'url' => 'civicrm/file',
              'qs' => 'reset=1&id=%%id%%&eid=%%eid%%&fid=%%fid%%&action=delete',
              'extra' => 'onclick = "if (confirm( \'' . $deleteExtra
              . '\' ) ) this.href+=\'&amp;confirmed=1\'; else return false;"',
            ),
          );
          $customValue['deleteURL'] = CRM_Core_Action::formLink($deleteURL,
            CRM_Core_Action::DELETE,
            array(
              'id' => $fileDAO->id,
              'eid' => $dao->$entityIDName,
              'fid' => $fieldID,
            ),
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
            list($path) = CRM_Core_BAO_File::path($fileDAO->id, $entityId, NULL, NULL);
            if ($path && file_exists($path)) {
              list($imageWidth, $imageHeight) = getimagesize($path);
              list($imageThumbWidth, $imageThumbHeight) = CRM_Contact_BAO_Contact::getThumbSize($imageWidth, $imageHeight);
              $customValue['imageThumbWidth'] = $imageThumbWidth;
              $customValue['imageThumbHeight'] = $imageThumbHeight;
            }
          }
        }
      }
      else {
        $customValue = array(
          'id' => $dao->$idName,
          'data' => '',
        );
      }
    }
    else {
      $customValue = array(
        'id' => $dao->$idName,
        'data' => $dao->$fieldName,
      );
    }

    if (!array_key_exists('customValue', $groupTree[$groupID]['fields'][$fieldID])) {
      $groupTree[$groupID]['fields'][$fieldID]['customValue'] = array();
    }
    if (empty($groupTree[$groupID]['fields'][$fieldID]['customValue']) && !empty($singleRecord)) {
      $groupTree[$groupID]['fields'][$fieldID]['customValue'] = array($singleRecord => $customValue);
    }
    elseif (empty($groupTree[$groupID]['fields'][$fieldID]['customValue'])) {
      $groupTree[$groupID]['fields'][$fieldID]['customValue'] = array(1 => $customValue);
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
   * @param null $inSelector
   *
   * @return array
   *   array consisting of all group and field details
   */
  public static function &getGroupDetail($groupId = NULL, $searchable = NULL, &$extends = NULL, $inSelector = NULL) {
    // create a new tree
    $groupTree = array();
    $select = $from = $where = $orderBy = '';

    $tableData = array();

    // using tableData to build the queryString
    $tableData = array(
      'civicrm_custom_field' => array(
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
      ),
      'civicrm_custom_group' => array(
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
      ),
    );

    // create select
    $s = array();
    foreach ($tableData as $tableName => $tableColumn) {
      foreach ($tableColumn as $columnName) {
        $s[] = "{$tableName}.{$columnName} as {$tableName}_{$columnName}";
      }
    }
    $select = 'SELECT ' . implode(', ', $s);
    $params = array();
    // from, where, order by
    $from = " FROM civicrm_custom_field, civicrm_custom_group";
    $where = " WHERE civicrm_custom_field.custom_group_id = civicrm_custom_group.id
                            AND civicrm_custom_group.is_active = 1
                            AND civicrm_custom_field.is_active = 1 ";
    if ($groupId) {
      $params[1] = array($groupId, 'Integer');
      $where .= " AND civicrm_custom_group.id = %1";
    }

    if ($searchable) {
      $where .= " AND civicrm_custom_field.is_searchable = 1";
    }

    if ($inSelector) {
      $where .= " AND civicrm_custom_field.in_selector = 1 AND civicrm_custom_group.is_multiple = 1 ";
    }

    if ($extends) {
      $clause = array();
      foreach ($extends as $e) {
        $clause[] = "civicrm_custom_group.extends = '$e'";
      }
      $where .= " AND ( " . implode(' OR ', $clause) . " ) ";

      //include case activities customdata if case is enabled
      if (in_array('Activity', $extends)) {
        $extendValues = implode(',', array_keys(CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE)));
        $where .= " AND ( civicrm_custom_group.extends_entity_column_value IS NULL OR REPLACE( civicrm_custom_group.extends_entity_column_value, %2, ' ') IN ($extendValues) ) ";
        $params[2] = array(CRM_Core_DAO::VALUE_SEPARATOR, 'String');
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
        $groupTree[$groupId] = array();
        $groupTree[$groupId]['id'] = $groupId;

        foreach ($tableData['civicrm_custom_group'] as $v) {
          $fullField = "civicrm_custom_group_" . $v;

          if ($v == 'id' || is_null($crmDAO->$fullField)) {
            continue;
          }

          $groupTree[$groupId][$v] = $crmDAO->$fullField;
        }

        $groupTree[$groupId]['fields'] = array();
      }

      // add the fields now (note - the query row will always contain a field)
      $groupTree[$groupId]['fields'][$fieldId] = array();
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

    $groups = array();

    $permissionClause = CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW, NULL, TRUE);
    $customGroupDAO->whereAdd($permissionClause);

    // order by weight
    $customGroupDAO->orderBy('weight');
    $customGroupDAO->find();

    // process each group with menu tab
    while ($customGroupDAO->fetch()) {
      $group = array();
      $group['id'] = $customGroupDAO->id;
      $group['path'] = $path;
      $group['title'] = "$customGroupDAO->title";
      $group['query'] = "reset=1&gid={$customGroupDAO->id}&cid={$cidToken}";
      $group['extra'] = array('gid' => $customGroupDAO->id);
      $group['table_name'] = $customGroupDAO->table_name;
      $group['is_multiple'] = $customGroupDAO->is_multiple;
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
    $tableName = '';
    switch ($entityType) {
      case 'Contact':
      case 'Individual':
      case 'Household':
      case 'Organization':
        $tableName = 'civicrm_contact';
        break;

      case 'Contribution':
        $tableName = 'civicrm_contribution';
        break;

      case 'Group':
        $tableName = 'civicrm_group';
        break;

      // DRAFTING: Verify if we cannot make it pluggable

      case 'Activity':
        $tableName = 'civicrm_activity';
        break;

      case 'Relationship':
        $tableName = 'civicrm_relationship';
        break;

      case 'Membership':
        $tableName = 'civicrm_membership';
        break;

      case 'Participant':
        $tableName = 'civicrm_participant';
        break;

      case 'Event':
        $tableName = 'civicrm_event';
        break;

      case 'Grant':
        $tableName = 'civicrm_grant';
        break;

      // need to add cases for Location, Address
    }

    return $tableName;
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
        $subtypeClause = array();
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
        if (CRM_Utils_Array::value('element_value', $field) !== NULL) {
          $value = $field['element_value'];
        }
        elseif (CRM_Utils_Array::value('default_value', $field) !== NULL &&
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

        switch ($field['html_type']) {
          case 'Multi-Select':
          case 'CheckBox':
            $defaults[$elementName] = array();
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
            break;

          case 'Multi-Select Country':
          case 'Multi-Select State/Province':
            if (isset($value)) {
              $checkedValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
              foreach ($checkedValue as $val) {
                if ($val) {
                  $defaults[$elementName][$val] = $val;
                }
              }
            }
            break;

          case 'Select Country':
            if ($value) {
              $defaults[$elementName] = $value;
            }
            else {
              $config = CRM_Core_Config::singleton();
              $defaults[$elementName] = $config->defaultContactCountry;
            }
            break;

          default:
            if ($field['data_type'] == "Float") {
              $defaults[$elementName] = (float) $value;
            }
            elseif ($field['data_type'] == 'Money' &&
              $field['html_type'] == 'Text'
            ) {
              $defaults[$elementName] = CRM_Utils_Money::format($value, NULL, '%a');
            }
            else {
              $defaults[$elementName] = $value;
            }
        }
      }
    }
  }

  /**
   * PostProcess function.
   *
   * @param array $groupTree
   * @param array $params
   * @param bool $skipFile
   */
  public static function postProcess(&$groupTree, &$params, $skipFile = FALSE) {
    // Get the Custom form values and groupTree
    // first reset all checkbox and radio data
    foreach ($groupTree as $groupID => $group) {
      if ($groupID === 'info') {
        continue;
      }
      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];

        //added Multi-Select option in the below if-statement
        if ($field['html_type'] == 'CheckBox' ||
          $field['html_type'] == 'Radio' ||
          $field['html_type'] == 'Multi-Select'
        ) {
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
          $groupTree[$groupID]['fields'][$fieldId]['customValue'] = array();
        }

        switch ($groupTree[$groupID]['fields'][$fieldId]['html_type']) {

          //added for CheckBox

          case 'CheckBox':
            if (!empty($v)) {
              $customValue = array_keys($v);
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = CRM_Core_DAO::VALUE_SEPARATOR
                . implode(CRM_Core_DAO::VALUE_SEPARATOR, $customValue)
                . CRM_Core_DAO::VALUE_SEPARATOR;
            }
            else {
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = NULL;
            }
            break;

          case 'Multi-Select':
            if (!empty($v)) {
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = CRM_Core_DAO::VALUE_SEPARATOR
                . implode(CRM_Core_DAO::VALUE_SEPARATOR, $v)
                . CRM_Core_DAO::VALUE_SEPARATOR;
            }
            else {
              $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = NULL;
            }
            break;

          case 'Select Date':
            $date = CRM_Utils_Date::processDate($v);
            $groupTree[$groupID]['fields'][$fieldId]['customValue']['data'] = $date;
            break;

          case 'File':
            if ($skipFile) {
              continue;
            }

            //store the file in d/b
            $entityId = explode('=', $groupTree['info']['where'][0]);
            $fileParams = array('upload_date' => date('YmdHis'));

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
            $defaults = array();
            $paramsFile = array(
              'entity_table' => $groupTree[$groupID]['table_name'],
              'entity_id' => $entityId[1],
            );

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
   */
  public static function buildQuickForm(&$form, &$groupTree, $inactiveNeeded = FALSE, $prefix = '') {
    $form->assign_by_ref("{$prefix}groupTree", $groupTree);

    foreach ($groupTree as $id => $group) {
      CRM_Core_ShowHideBlocks::links($form, $group['title'], '', '');
      foreach ($group['fields'] as $field) {
        $required = CRM_Utils_Array::value('is_required', $field);
        //fix for CRM-1620
        if ($field['data_type'] == 'File') {
          if (!empty($field['element_value']['data'])) {
            $required = 0;
          }
        }

        $fieldId = $field['id'];
        $elementName = $field['element_name'];
        CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, $required);
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
   */
  public static function extractGetParams(&$form, $type) {
    if (empty($_GET)) {
      return array();
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($type);
    $customValue = array();
    $htmlType = array(
      'CheckBox',
      'Multi-Select',
      'Select',
      'Radio',
    );

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
          if ($field['html_type'] == 'CheckBox' ||
            $field['html_type'] == 'Multi-Select'
          ) {
            $value = str_replace("|", ",", $value);
            $mulValues = explode(',', $value);
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($key, TRUE);
            $val = array();
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
          elseif ($field['html_type'] == 'Select' ||
            ($field['html_type'] == 'Radio' &&
              $field['data_type'] != 'Boolean'
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
          elseif ($field['data_type'] == 'Date') {
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
   */
  public static function checkCustomField($customFieldId, &$removeCustomFieldTypes) {
    $query = "SELECT cg.extends as extends
                  FROM civicrm_custom_group as cg, civicrm_custom_field as cf
                  WHERE cg.id = cf.custom_group_id
                    AND cf.id =" .
      CRM_Utils_Type::escape($customFieldId, 'Integer');

    $extends = CRM_Core_DAO::singleValueQuery($query);

    if (in_array($extends, $removeCustomFieldTypes)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $table
   *
   * @return string
   * @throws Exception
   */
  public static function mapTableName($table) {
    switch ($table) {
      case 'Contact':
      case 'Individual':
      case 'Household':
      case 'Organization':
        return 'civicrm_contact';

      case 'Activity':
        return 'civicrm_activity';

      case 'Group':
        return 'civicrm_group';

      case 'Contribution':
        return 'civicrm_contribution';

      case 'ContributionRecur':
        return 'civicrm_contribution_recur';

      case 'Relationship':
        return 'civicrm_relationship';

      case 'Event':
        return 'civicrm_event';

      case 'Membership':
        return 'civicrm_membership';

      case 'Participant':
      case 'ParticipantRole':
      case 'ParticipantEventName':
      case 'ParticipantEventType':
        return 'civicrm_participant';

      case 'Grant':
        return 'civicrm_grant';

      case 'Pledge':
        return 'civicrm_pledge';

      case 'Address':
        return 'civicrm_address';

      case 'Campaign':
        return 'civicrm_campaign';

      default:
        $query = "
SELECT IF( EXISTS(SELECT name FROM civicrm_contact_type WHERE name like %1), 1, 0 )";
        $qParams = array(1 => array($table, 'String'));
        $result = CRM_Core_DAO::singleValueQuery($query, $qParams);

        if ($result) {
          return 'civicrm_contact';
        }
        else {
          $extendObjs = CRM_Core_OptionGroup::values('cg_extend_objects', FALSE, FALSE, FALSE, NULL, 'name');
          if (array_key_exists($table, $extendObjs)) {
            return $extendObjs[$table];
          }
          CRM_Core_Error::fatal();
        }
    }
  }

  /**
   * @param $group
   */
  public static function createTable($group) {
    $params = array(
      'name' => $group->table_name,
      'is_multiple' => $group->is_multiple ? 1 : 0,
      'extends_name' => self::mapTableName($group->extends),
    );

    $tableParams = CRM_Core_BAO_CustomField::defaultCustomTableSchema($params);

    CRM_Core_BAO_SchemaHandler::createTable($tableParams);
  }

  /**
   * Function returns formatted groupTree, sothat form can be easily build in template
   *
   * @param array $groupTree
   * @param int $groupCount
   *   Group count by default 1, but can varry for multiple value custom data.
   * @param object $form
   *
   * @return array
   */
  public static function formatGroupTree(&$groupTree, $groupCount = 1, &$form = NULL) {
    $formattedGroupTree = array();
    $uploadNames = $formValues = array();

    // retrieve qf key from url
    $qfKey = CRM_Utils_Request::retrieve('qf', 'String');

    // fetch submitted custom field values later use to set as a default values
    if ($qfKey) {
      $submittedValues = CRM_Core_BAO_Cache::getItem('custom data', $qfKey);
    }

    foreach ($groupTree as $key => $value) {
      if ($key === 'info') {
        continue;
      }

      // add group information
      $formattedGroupTree[$key]['name'] = CRM_Utils_Array::value('name', $value);
      $formattedGroupTree[$key]['title'] = CRM_Utils_Array::value('title', $value);
      $formattedGroupTree[$key]['help_pre'] = CRM_Utils_Array::value('help_pre', $value);
      $formattedGroupTree[$key]['help_post'] = CRM_Utils_Array::value('help_post', $value);
      $formattedGroupTree[$key]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $value);
      $formattedGroupTree[$key]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $value);
      $formattedGroupTree[$key]['style'] = CRM_Utils_Array::value('style', $value);

      // this params needed of bulding multiple values
      $formattedGroupTree[$key]['is_multiple'] = CRM_Utils_Array::value('is_multiple', $value);
      $formattedGroupTree[$key]['extends'] = CRM_Utils_Array::value('extends', $value);
      $formattedGroupTree[$key]['extends_entity_column_id'] = CRM_Utils_Array::value('extends_entity_column_id', $value);
      $formattedGroupTree[$key]['extends_entity_column_value'] = CRM_Utils_Array::value('extends_entity_column_value', $value);
      $formattedGroupTree[$key]['subtype'] = CRM_Utils_Array::value('subtype', $value);
      $formattedGroupTree[$key]['max_multiple'] = CRM_Utils_Array::value('max_multiple', $value);

      // add field information
      foreach ($value['fields'] as $k => $properties) {
        $properties['element_name'] = "custom_{$k}_-{$groupCount}";
        if (isset($properties['customValue']) &&
          !CRM_Utils_System::isNull($properties['customValue']) &&
          !isset($properties['element_value'])
        ) {
          if (isset($properties['customValue'][$groupCount])) {
            $properties['element_name'] = "custom_{$k}_{$properties['customValue'][$groupCount]['id']}";
            $formattedGroupTree[$key]['table_id'] = $properties['customValue'][$groupCount]['id'];
            if ($properties['data_type'] == 'File') {
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
        CRM_Core_BAO_Cache::setItem($formValues, 'custom data', $qf);
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
   *
   * @return array|int
   * @throws \Exception
   */
  public static function buildCustomDataView(&$form, &$groupTree, $returnCount = FALSE, $gID = NULL, $prefix = NULL, $customValueId = NULL, $entityId = NULL) {
    $details = array();
    foreach ($groupTree as $key => $group) {
      if ($key === 'info') {
        continue;
      }

      foreach ($group['fields'] as $k => $properties) {
        $groupID = $group['id'];
        if (!empty($properties['customValue'])) {
          foreach ($properties['customValue'] as $values) {
            if (!empty($customValueId) && $customValueId != $values['id']) {
              continue;
            }
            $details[$groupID][$values['id']]['title'] = CRM_Utils_Array::value('title', $group);
            $details[$groupID][$values['id']]['name'] = CRM_Utils_Array::value('name', $group);
            $details[$groupID][$values['id']]['help_pre'] = CRM_Utils_Array::value('help_pre', $group);
            $details[$groupID][$values['id']]['help_post'] = CRM_Utils_Array::value('help_post', $group);
            $details[$groupID][$values['id']]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $group);
            $details[$groupID][$values['id']]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $group);
            $details[$groupID][$values['id']]['style'] = CRM_Utils_Array::value('style', $group);
            $details[$groupID][$values['id']]['fields'][$k] = array(
              'field_title' => CRM_Utils_Array::value('label', $properties),
              'field_type' => CRM_Utils_Array::value('html_type', $properties),
              'field_data_type' => CRM_Utils_Array::value('data_type', $properties),
              'field_value' => CRM_Core_BAO_CustomField::displayValue($values['data'], $properties['id'], $entityId),
              'options_per_line' => CRM_Utils_Array::value('options_per_line', $properties),
            );
            // editable = whether this set contains any non-read-only fields
            if (!isset($details[$groupID][$values['id']]['editable'])) {
              $details[$groupID][$values['id']]['editable'] = FALSE;
            }
            if (empty($properties['is_view'])) {
              $details[$groupID][$values['id']]['editable'] = TRUE;
            }
            // also return contact reference contact id if user has view all or edit all contacts perm
            if ((CRM_Core_Permission::check('view all contacts') ||
                CRM_Core_Permission::check('edit all contacts'))
              &&
              $details[$groupID][$values['id']]['fields'][$k]['field_data_type'] ==
              'ContactReference'
            ) {
              $details[$groupID][$values['id']]['fields'][$k]['contact_ref_id'] = CRM_Utils_Array::value('data', $values);
            }
          }
        }
        else {
          $details[$groupID][0]['title'] = CRM_Utils_Array::value('title', $group);
          $details[$groupID][0]['name'] = CRM_Utils_Array::value('name', $group);
          $details[$groupID][0]['help_pre'] = CRM_Utils_Array::value('help_pre', $group);
          $details[$groupID][0]['help_post'] = CRM_Utils_Array::value('help_post', $group);
          $details[$groupID][0]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $group);
          $details[$groupID][0]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $group);
          $details[$groupID][0]['style'] = CRM_Utils_Array::value('style', $group);
          $details[$groupID][0]['fields'][$k] = array('field_title' => CRM_Utils_Array::value('label', $properties));
        }
      }
    }

    if ($returnCount) {
      //return a single value count if group id is passed to function
      //else return a groupId and count mapped array
      if (!empty($gID)) {
        return count($details[$gID]);
      }
      else {
        $countValue = array();
        foreach ($details as $key => $value) {
          $countValue[$key] = count($details[$key]);
        }
        return $countValue;
      }
    }
    else {
      $form->assign_by_ref("{$prefix}viewCustomData", $details);
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

    $groupLabels = array();
    $fIds = "(" . implode(',', $fieldIds) . ")";

    $query = "
SELECT  civicrm_custom_group.id as groupID, civicrm_custom_group.title as groupTitle,
        civicrm_custom_field.label as fieldLabel, civicrm_custom_field.id as fieldID
  FROM  civicrm_custom_group, civicrm_custom_field
 WHERE  civicrm_custom_group.id = civicrm_custom_field.custom_group_id
   AND  civicrm_custom_field.id IN {$fIds}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $groupLabels[$dao->fieldID] = array(
        'fieldID' => $dao->fieldID,
        'fieldLabel' => $dao->fieldLabel,
        'groupID' => $dao->groupID,
        'groupTitle' => $dao->groupTitle,
      );
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
   * Get the list of types for objects that a custom group extends to.
   *
   * @param array $types
   *   Var which should have the list appended.
   *
   * @return array
   *   Array of types.
   */
  public static function getExtendedObjectTypes(&$types = array()) {
    static $flag = FALSE, $objTypes = array();

    if (!$flag) {
      $extendObjs = array();
      CRM_Core_OptionValue::getValues(array('name' => 'cg_extend_objects'), $extendObjs);

      foreach ($extendObjs as $ovId => $ovValues) {
        if ($ovValues['description']) {
          // description is expected to be a callback func to subtypes
          list($callback, $args) = explode(';', trim($ovValues['description']));

          if (empty($args)) {
            $args = array();
          }

          if (!is_array($args)) {
            CRM_Core_Error::fatal('Arg is not of type array');
          }

          list($className) = explode('::', $callback);
          require_once str_replace('_', DIRECTORY_SEPARATOR, $className) .
            '.php';

          $objTypes[$ovValues['value']] = call_user_func_array($callback, $args);
        }
      }
      $flag = TRUE;
    }

    $types = array_merge($types, $objTypes);
    return $objTypes;
  }

  /**
   * @param int $customGroupId
   * @param int $entityId
   *
   * @return bool
   */
  public static function hasReachedMaxLimit($customGroupId, $entityId) {
    //check whether the group is multiple
    $isMultiple = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'is_multiple');
    $isMultiple = ($isMultiple) ? TRUE : FALSE;
    $hasReachedMax = FALSE;
    if ($isMultiple &&
      ($maxMultiple = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'max_multiple'))
    ) {
      if (!$maxMultiple) {
        $hasReachedMax = FALSE;
      }
      else {
        $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'table_name');
        //count the number of entries for a entity
        $sql = "SELECT COUNT(id) FROM {$tableName} WHERE entity_id = %1";
        $params = array(1 => array($entityId, 'Integer'));
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
    $multipleGroup = array();
    $dao = new CRM_Core_DAO_CustomGroup();
    $dao->is_multiple = 1;
    $dao->is_active = 1;
    $dao->find();
    while ($dao->fetch()) {
      $multipleGroup[$dao->id] = $dao->title;
    }
    return $multipleGroup;
  }

}
