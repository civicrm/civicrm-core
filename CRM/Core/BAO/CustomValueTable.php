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
class CRM_Core_BAO_CustomValueTable {

  /**
   * @param array $customParams
   * @param string $parentOperation Operation being taken on the parent entity.
   *   If we know the parent entity is doing an insert we can skip the
   *   ON DUPLICATE UPDATE - which improves performance and reduces deadlocks.
   *   - edit
   *   - create
   *
   * @throws Exception
   */
  public static function create($customParams, $parentOperation = NULL) {
    if (empty($customParams) ||
      !is_array($customParams)
    ) {
      return;
    }

    $paramFieldsExtendContactForEntities = [];
    $VS = CRM_Core_DAO::VALUE_SEPARATOR;

    foreach ($customParams as $tableName => $tables) {
      foreach ($tables as $fields) {
        $hookID = NULL;
        $entityID = NULL;
        $set = [];
        $params = [];
        $count = 1;

        $firstField = reset($fields);
        $entityID = (int) $firstField['entity_id'];
        $isMultiple = $firstField['is_multiple'];
        if (array_key_exists('id', $firstField)) {
          $sqlOP = "UPDATE $tableName ";
          $where = " WHERE  id = %{$count}";
          $params[$count] = [$firstField['id'], 'Integer'];
          $count++;
          $hookOP = 'edit';
        }
        else {
          $sqlOP = "INSERT INTO $tableName ";
          $where = NULL;
          $hookOP = 'create';
        }

        CRM_Utils_Hook::customPre(
          $hookOP,
          (int) $firstField['custom_group_id'],
          $entityID,
          $fields
        );

        foreach ($fields as $field) {
          // fix the value before we store it
          $serialize = $field['serialize'] ?? NULL;
          $value = $serialize ? CRM_Core_DAO::serializeField($field['value'], $serialize) : $field['value'];
          $type = $field['type'];

          switch ($type) {
            case 'StateProvince':
            case 'Country':
              $type = $serialize ? 'String' : 'Integer';
              if (!$value) {
                // CRM-3415
                // using type of timestamp allows us to sneak in a null into db
                // gross but effective hack
                $value = NULL;
                $type = 'Timestamp';
              }
              break;

            case 'File':
              if (!$field['file_id']) {
                $value = 'null';
                break;
              }

              // need to add/update civicrm_entity_file
              $entityFileDAO = new CRM_Core_DAO_EntityFile();
              $entityFileDAO->file_id = $field['file_id'];
              $entityFileDAO->find(TRUE);

              $entityFileDAO->entity_table = $field['table_name'];
              $entityFileDAO->entity_id = $field['entity_id'];
              $entityFileDAO->file_id = $field['file_id'];
              $entityFileDAO->save();
              $value = $field['file_id'];
              $type = 'String';
              break;

            case 'Date':
              $value = CRM_Utils_Date::isoToMysql($value);
              break;

            case 'Int':
              if (is_numeric($value)) {
                $type = 'Integer';
              }
              else {
                $type = 'Timestamp';
              }
              break;

            case 'ContactReference':
              if ($serialize) {
                $type = 'String';
                // Validate the string contains only integers and value-separators
                $validChars = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, $VS];
                if (str_replace($validChars, '', $value)) {
                  throw new CRM_Core_Exception('Contact ID must be of type Integer');
                }
                // Prevent saving an empty "array" which results in a fatal error on render.
                if ($value === '' || $value === $VS . $VS) {
                  $value = NULL;
                }
              }
              elseif ($value == NULL || $value === '') {
                $type = 'Timestamp';
                $value = NULL;
              }
              else {
                $type = 'Integer';
              }
              break;

            case 'EntityReference':
              $type = 'Integer';
              if ($value == NULL || $value === '') {
                $type = 'Timestamp';
                $value = NULL;
              }
              break;

            case 'RichTextEditor':
              $type = 'String';
              break;

            case 'Boolean':
              //fix for CRM-3290
              $value = CRM_Utils_String::strtoboolstr($value);
              if ($value === FALSE) {
                $type = 'Timestamp';
              }
              break;

            default:
              break;
          }
          if ($value === 'null') {
            // when unsetting a value to null, we don't need to validate the type
            // https://projectllr.atlassian.net/browse/VGQBMP-20
            $set[$field['column_name']] = $value;
          }
          else {
            $set[$field['column_name']] = "%{$count}";
            // The second parameter is the type of the db field, which
            // would be 'String' for a concatenated set of integers.
            // However, the god-forsaken timestamp hack also needs to be kept
            // if value is NULL.
            $params[$count] = [$value, ($value && $field['serialize']) ? 'String' : $type];
            $count++;
          }

          $fieldExtends = $field['extends'] ?? NULL;
          if (
            ($field['entity_table'] ?? NULL) === 'civicrm_contact'
            || $fieldExtends === 'Contact'
            || $fieldExtends === 'Individual'
            || $fieldExtends === 'Organization'
            || $fieldExtends === 'Household'
          ) {
            $paramFieldsExtendContactForEntities[$entityID]['custom_' . ($field['custom_field_id'] ?? '')] = $field['custom_field_id'] ?? NULL;
          }
        }

        if (!empty($set)) {
          $setClause = [];
          foreach ($set as $n => $v) {
            $setClause[] = "`$n` = $v";
          }
          $setClause = implode(',', $setClause);
          if (!$where) {
            // do this only for insert
            $set['entity_id'] = "%{$count}";
            $params[$count] = [$entityID, 'Integer'];
            $count++;

            $fieldNames = implode(',', CRM_Utils_Type::escapeAll(array_keys($set), 'MysqlColumnNameOrAlias'));
            $fieldValues = implode(',', array_values($set));
            $query = "$sqlOP ( $fieldNames ) VALUES ( $fieldValues )";
            // for multiple values we dont do on duplicate key update
            if (!$isMultiple && $parentOperation !== 'create') {
              $query .= " ON DUPLICATE KEY UPDATE $setClause";
            }
          }
          else {
            $query = "$sqlOP SET $setClause $where";
          }
          CRM_Core_DAO::executeQuery($query, $params);

          CRM_Utils_Hook::custom($hookOP,
            (int) $firstField['custom_group_id'],
            $entityID,
            $fields
          );
        }
      }
    }

    if (!empty($paramFieldsExtendContactForEntities)) {
      CRM_Contact_BAO_Contact::updateGreetingsOnTokenFieldChange($paramFieldsExtendContactForEntities, ['contact_id' => $entityID]);
    }
  }

  /**
   * Given a field return the mysql data type associated with it.
   *
   * @param string $type
   * @param int $maxLength
   *
   * @return string
   *   the mysql data store placeholder
   */
  public static function fieldToSQLType($type, $maxLength = 255) {
    if (!isset($maxLength) ||
      !is_numeric($maxLength) ||
      $maxLength <= 0
    ) {
      $maxLength = 255;
    }

    switch ($type) {
      case 'String':
      case 'Link':
        return "varchar($maxLength)";

      case 'Boolean':
        return 'boolean';

      case 'Int':
        return 'int';

      // the below three are FK's, and have constraints added to them

      case 'ContactReference':
      case 'EntityReference':
      case 'StateProvince':
      case 'Country':
      case 'File':
        return 'int unsigned';

      case 'Float':
        return 'double';

      case 'Money':
        return 'decimal(20,2)';

      case 'Memo':
      case 'RichTextEditor':
        return 'text';

      case 'Date':
        return 'datetime';

      default:
        throw new CRM_Core_Exception('Invalid Field Type');
    }
  }

  /**
   * @param array $params
   * @param $entityTable
   * @param int $entityID
   * @param string $parentOperation Operation being taken on the parent entity.
   *   If we know the parent entity is doing an insert we can skip the
   *   ON DUPLICATE UPDATE - which improves performance and reduces deadlocks.
   *   - edit
   *   - create
   */
  public static function store($params, $entityTable, $entityID, $parentOperation = NULL) {
    $cvParams = [];
    foreach ($params as $fieldID => $param) {
      foreach ($param as $index => $customValue) {
        $cvParam = [
          'entity_table' => $entityTable,
          'entity_id' => $entityID,
          'value' => $customValue['value'],
          'type' => $customValue['type'],
          'custom_field_id' => $customValue['custom_field_id'],
          'custom_group_id' => $customValue['custom_group_id'],
          'table_name' => $customValue['table_name'],
          'column_name' => $customValue['column_name'],
          // is_multiple refers to the custom group, serialize refers to the field.
          'is_multiple' => (int) ($customValue['is_multiple'] ?? CRM_Core_BAO_CustomGroup::getGroup(['id' => $customValue['custom_group_id']])['is_multiple']),
          'serialize' => $customValue['serialize'] ?? (int) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customValue['custom_field_id'], 'serialize'),
          'file_id' => $customValue['file_id'],
        ];

        // Fix Date type to be timestamp, since that is how we store in db.
        if ($cvParam['type'] == 'Date') {
          $cvParam['type'] = 'Timestamp';
        }

        if (!empty($customValue['id'])) {
          $cvParam['id'] = $customValue['id'];
        }
        elseif (empty($cvParam['is_multiple']) && !empty($entityID)) {
          // dev/core#3000 Ensure that if we are not dealing with multiple record custom data and for some reason have got here without getting the id of the record in the custom table for this entityId let us give it one last shot
          $rowId = CRM_Core_DAO::singleValueQuery("SELECT id FROM {$cvParam['table_name']} WHERE entity_id = %1", [1 => [$entityID, 'Integer']]);
          if (!empty($rowId)) {
            $cvParam['id'] = $rowId;
          }
        }
        if (!array_key_exists($customValue['table_name'], $cvParams)) {
          $cvParams[$customValue['table_name']] = [];
        }

        if (!array_key_exists($index, $cvParams[$customValue['table_name']])) {
          $cvParams[$customValue['table_name']][$index] = [];
        }

        $cvParams[$customValue['table_name']][$index][] = $cvParam;
      }
    }
    if (!empty($cvParams)) {
      self::create($cvParams, $parentOperation);
    }
  }

  /**
   * Post process function.
   *
   * @param array $params
   * @param $entityTable
   * @param int $entityID
   * @param $customFieldExtends
   * @param $parentOperation
   */
  public static function postProcess(&$params, $entityTable, $entityID, $customFieldExtends, $parentOperation = NULL) {
    $customData = CRM_Core_BAO_CustomField::postProcess($params,
      $entityID,
      $customFieldExtends
    );

    if (!empty($customData)) {
      self::store($customData, $entityTable, $entityID, $parentOperation);
    }
  }

  /**
   * Return an array of all custom values associated with an entity.
   *
   * @param int $entityID
   *   Identification number of the entity.
   * @param string $entityType
   *   Type of entity that the entityID corresponds to, specified.
   *                                   as a string with format "'<EntityName>'". Comma separated
   *                                   list may be used to specify OR matches. Allowable values
   *                                   are enumerated types in civicrm_custom_group.extends field.
   *                                   Optional. Default value assumes entityID references a
   *                                   contact entity.
   * @param array $fieldIDs
   *   Optional list of fieldIDs that we want to retrieve. If this.
   *                                   is set the entityType is ignored
   *
   * @param bool $formatMultiRecordField
   * @param array $DTparams - CRM-17810 dataTable params for the multiValued custom fields.
   *
   * @return array
   *   Array of custom values for the entity with key=>value
   *                                   pairs specified as civicrm_custom_field.id => custom value.
   *                                   Empty array if no custom values found.
   * @throws CRM_Core_Exception
   */
  public static function getEntityValues($entityID, $entityType = NULL, $fieldIDs = NULL, $formatMultiRecordField = FALSE, $DTparams = NULL) {
    if (!$entityID) {
      // adding this here since an empty contact id could have serious repurcussions
      // like looping forever
      throw new CRM_Core_Exception('Please file an issue with the backtrace');
      return NULL;
    }

    $cond = [];
    if ($entityType) {
      $cond[] = "cg.extends IN ( '$entityType' )";
    }
    if ($fieldIDs &&
      is_array($fieldIDs)
    ) {
      $fieldIDList = implode(',', $fieldIDs);
      $cond[] = "cf.id IN ( $fieldIDList )";
    }
    if (empty($cond)) {
      $contactTypes = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes(TRUE));
      $cond[] = "cg.extends IN ( '" . implode("', '", $contactTypes) . "' )";
    }
    $cond = implode(' AND ', $cond);

    $limit = $orderBy = '';
    if (!empty($DTparams['rowCount']) && $DTparams['rowCount'] > 0) {
      $limit = " LIMIT " . CRM_Utils_Type::escape($DTparams['offset'], 'Integer') . ", " . CRM_Utils_Type::escape($DTparams['rowCount'], 'Integer');
    }
    if (!empty($DTparams['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($DTparams['sort'], 'String');
    }

    // First find all the fields that extend this type of entity.
    $query = "
SELECT cg.table_name,
       cg.id as groupID,
       cg.is_multiple,
       cf.column_name,
       cf.id as fieldID,
       cf.data_type as fieldDataType
FROM   civicrm_custom_group cg,
       civicrm_custom_field cf
WHERE  cf.custom_group_id = cg.id
AND    cg.is_active = 1
AND    cf.is_active = 1
AND    $cond
";
    $dao = CRM_Core_DAO::executeQuery($query);

    $select = $fields = $isMultiple = [];

    while ($dao->fetch()) {
      if (!array_key_exists($dao->table_name, $select)) {
        $fields[$dao->table_name] = [];
        $select[$dao->table_name] = [];
      }
      $fields[$dao->table_name][] = $dao->fieldID;
      $select[$dao->table_name][] = "{$dao->column_name} AS custom_{$dao->fieldID}";
      $isMultiple[$dao->table_name] = (bool) $dao->is_multiple;
      $file[$dao->table_name][$dao->fieldID] = $dao->fieldDataType;
    }

    $result = $sortedResult = [];
    foreach ($select as $tableName => $clauses) {
      if (!empty($DTparams['sort'])) {
        $query = CRM_Core_DAO::executeQuery("SELECT id FROM {$tableName} WHERE entity_id = {$entityID}");
        $count = 1;
        while ($query->fetch()) {
          $sortedResult["{$query->id}"] = $count;
          $count++;
        }
      }

      $query = "SELECT SQL_CALC_FOUND_ROWS id, " . implode(', ', $clauses) . " FROM $tableName WHERE entity_id = $entityID {$orderBy} {$limit}";
      $dao = CRM_Core_DAO::executeQuery($query);
      if (!empty($DTparams)) {
        $result['count'] = CRM_Core_DAO::singleValueQuery('SELECT FOUND_ROWS()');
      }
      while ($dao->fetch()) {
        foreach ($fields[$tableName] as $fieldID) {
          $fieldName = "custom_{$fieldID}";
          if ($isMultiple[$tableName]) {
            if ($formatMultiRecordField) {
              $result["{$dao->id}"]["{$fieldID}"] = $dao->$fieldName;
            }
            else {
              $result["{$fieldID}_{$dao->id}"] = $dao->$fieldName;
            }
          }
          else {
            $result[$fieldID] = $dao->$fieldName;
          }
        }
      }
    }
    if (!empty($sortedResult)) {
      $result['sortedResult'] = $sortedResult;
    }
    return $result;
  }

  /**
   * Take in an array of entityID, custom_XXX => value
   * and set the value in the appropriate table. Should also be able
   * to set the value to null. Follows api parameter/return conventions
   *
   * @array $params
   *
   * @param array $params
   *
   * @throws Exception
   * @return array
   */
  public static function setValues(&$params) {
    // For legacy reasons, accept this param in either format
    if (empty($params['entityID']) && !empty($params['entity_id'])) {
      $params['entityID'] = $params['entity_id'];
    }

    if (!isset($params['entityID']) || !CRM_Utils_Type::validate($params['entityID'], 'Integer', FALSE)) {
      throw new CRM_Core_Exception(ts('entity_id needs to be set and of type Integer'));
    }

    // first collect all the id/value pairs. The format is:
    // custom_X => value or custom_X_VALUEID => value (for multiple values), VALUEID == -1, -2 etc for new insertions
    $fieldValues = [];
    foreach ($params as $n => $v) {
      $customFieldInfo = CRM_Core_BAO_CustomField::getKeyID($n, TRUE);
      if ($customFieldInfo[0]) {
        $fieldId = (int) $customFieldInfo[0];
        $id = -1;
        if ($customFieldInfo[1]) {
          $id = (int) $customFieldInfo[1];
        }
        $fieldValues[$fieldId][] = [
          'value' => $v,
          'id' => $id,
        ];
      }
    }

    $cvParams = [];
    foreach ($fieldValues as $fieldId => $fieldVals) {
      $fieldInfo = CRM_Core_BAO_CustomField::getField($fieldId);
      if (!$fieldInfo) {
        throw new CRM_Core_Exception("Custom field with id $fieldId not found.");
      }
      $dataType = $fieldInfo['data_type'] == 'Date' ? 'Timestamp' : $fieldInfo['data_type'];
      foreach ($fieldVals as $fieldValue) {
        // Serialize array values
        if (is_array($fieldValue['value']) && CRM_Core_BAO_CustomField::isSerialized($fieldInfo)) {
          $fieldValue['value'] = CRM_Utils_Array::implodePadded($fieldValue['value']);
        }
        // Format null values correctly
        if ($fieldValue['value'] === NULL || $fieldValue['value'] === '') {
          switch ($dataType) {
            case 'String':
            case 'Int':
            case 'Link':
            case 'Boolean':
              $fieldValue['value'] = '';
              break;

            case 'Timestamp':
              $fieldValue['value'] = NULL;
              break;

            case 'StateProvince':
            case 'Country':
            case 'Money':
            case 'Float':
              $fieldValue['value'] = (int) 0;
              break;
          }
        }
        // Ensure that value is of the right data type
        elseif (CRM_Utils_Type::escape($fieldValue['value'], $dataType, FALSE) === NULL) {
          throw new CRM_Core_Exception(ts('value: %1 is not of the right field data type: %2',
            [
              1 => $fieldValue['value'],
              2 => $fieldInfo['data_type'],
            ]
          ));
        }

        $cvParam = [
          'entity_id' => $params['entityID'],
          'value' => $fieldValue['value'],
          'type' => $dataType,
          'custom_field_id' => $fieldInfo['id'],
          'custom_group_id' => $fieldInfo['custom_group']['id'],
          'table_name' => $fieldInfo['custom_group']['table_name'],
          'column_name' => $fieldInfo['column_name'],
          'is_multiple' => $fieldInfo['custom_group']['is_multiple'],
          'serialize' => $fieldInfo['serialize'],
          'extends' => $fieldInfo['custom_group']['extends'],
        ];

        if (!empty($params['id'])) {
          $cvParam['id'] = $params['id'];
        }

        if ($cvParam['type'] == 'File') {
          $cvParam['file_id'] = $fieldValue['value'];
        }

        if (!array_key_exists($cvParam['table_name'], $cvParams)) {
          $cvParams[$cvParam['table_name']] = [];
        }

        if (!array_key_exists($fieldValue['id'], $cvParams[$cvParam['table_name']])) {
          $cvParams[$cvParam['table_name']][$fieldValue['id']] = [];
        }

        if ($fieldValue['id'] > 0) {
          $cvParam['id'] = $fieldValue['id'];
        }
        $cvParams[$cvParam['table_name']][$fieldValue['id']][] = $cvParam;
      }
    }

    if (!empty($cvParams)) {
      self::create($cvParams);
      return ['is_error' => 0, 'result' => 1];
    }

    throw new CRM_Core_Exception(ts('Unknown error'));
  }

  /**
   * Take in an array of entityID, custom_ID
   * and gets the value from the appropriate table.
   *
   * To get the values of custom fields with IDs 13 and 43 for contact ID 1327, use:
   * $params = array( 'entityID' => 1327, 'custom_13' => 1, 'custom_43' => 1 );
   *
   * Entity Type will be inferred by the custom fields you request
   * Specify $params['entityType'] if you do not supply any custom fields to return
   * and entity type is other than Contact
   *
   * @array $params
   *
   * @param array $params
   *
   * @throws Exception
   * @return array
   */
  public static function getValues($params) {
    if (empty($params)) {
      return NULL;
    }
    if (!isset($params['entityID']) ||
      CRM_Utils_Type::escape($params['entityID'],
        'Integer', FALSE
      ) === NULL
    ) {
      return CRM_Core_Error::createAPIError(ts('entityID needs to be set and of type Integer'));
    }

    // first collect all the ids. The format is:
    // custom_ID
    $fieldIDs = [];
    foreach ($params as $n => $v) {
      $key = $idx = NULL;
      if (substr($n, 0, 7) == 'custom_') {
        $idx = substr($n, 7);
        if (CRM_Utils_Type::escape($idx, 'Integer', FALSE) === NULL) {
          return CRM_Core_Error::createAPIError(ts('field ID needs to be of type Integer for index %1',
            [1 => $idx]
          ));
        }
        $fieldIDs[] = (int) $idx;
      }
    }

    $default = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes(TRUE));
    $type = $params['entityType'] ?? NULL;
    if (!$type || in_array($params['entityType'], $default)) {
      $type = NULL;
    }
    else {
      $entities = CRM_Core_SelectValues::customGroupExtends();
      if (!array_key_exists($type, $entities)) {
        if (in_array($type, $entities)) {
          $type = $entities[$type];
          if (in_array($type, $default)) {
            $type = NULL;
          }
        }
        else {
          return CRM_Core_Error::createAPIError(ts('Invalid entity type') . ': "' . $type . '"');
        }
      }
    }

    $values = self::getEntityValues($params['entityID'],
      $type,
      $fieldIDs
    );
    if (empty($values)) {
      // note that this behaviour is undesirable from an API point of view - it should return an empty array
      // since this is also called by the merger code & not sure the consequences of changing
      // are just handling undoing this in the api layer. ie. converting the error back into a success
      $result = [
        'is_error' => 1,
        'error_message' => 'No values found for the specified entity ID and custom field(s).',
      ];
      return $result;
    }
    else {
      $result = [
        'is_error' => 0,
        'entityID' => $params['entityID'],
      ];
      foreach ($values as $id => $value) {
        $result["custom_{$id}"] = $value;
      }
      return $result;
    }
  }

}
