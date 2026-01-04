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

use Civi\Api4\Utils\CoreUtil;

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
  private static function create($customParams, $parentOperation = NULL) {
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
              if (!empty($field['id'])) {
                self::deleteFile($field);
              }
              if (!$field['file_id']) {
                $value = 'null';
                break;
              }
              $value = $field['file_id'];
              $type = 'Integer';
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
              else {
                $type = 'Integer';
              }
              // An empty value should be stored as NULL
              if (!$value) {
                $type = 'Timestamp';
                $value = NULL;
              }
              break;

            case 'EntityReference':
              $type = self::getDataTypeForField($field['custom_field_id'], $type);
              // An empty value should be stored as NULL
              if (!$value) {
                $type = 'Timestamp';
                $value = NULL;
              }
              break;

            case 'RichTextEditor':
              $type = 'String';
              break;

            case 'Boolean':
              if ($field['html_type'] === 'Toggle') {
                $value = (int) $value;
              }
              else {
                // fix for CRM-3290
                $value = CRM_Utils_String::strtoboolstr($value);
              }
              if ($value === FALSE) {
                $type = 'Timestamp';
              }
              break;

            default:
              // An empty value should be stored as NULL
              if (!isset($value) || $value === '') {
                $type = 'Timestamp';
                $value = NULL;
              }
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
   * Most entities have an Int id field, but non-database ones
   *   eg. Afform have a String "name" field as primary key
   *
   * @param string $entityName
   *
   * @return string
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public static function getDataTypeForPrimaryKey(string $entityName): string {
    $primaryKey = CoreUtil::getIdFieldName($entityName);
    $type = 'Integer';
    // Todo: Maybe we shouldn't assume every field named "id" is an integer
    if ($primaryKey && $primaryKey !== 'id') {
      // Todo: Use Civi::entity() once Afform is converted to EFv2
      $type = civicrm_api4($entityName, 'getFields', [
        'where' => [
          ['name', '=', $primaryKey],
        ],
        'checkPermissions' => FALSE,
        'select' => [
          'data_type',
        ],
      ], 0)['data_type'] ?? $type;
    }
    return $type;
  }

  /**
   * Get the actual data type for a customField based on the entity metadata
   *
   * @param int $customFieldID
   * @param string $type
   *
   * @return string
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getDataTypeForField(int $customFieldID, string $type): string {
    if ($type !== 'EntityReference') {
      return $type;
    }
    $customField = CRM_Core_BAO_CustomField::getField($customFieldID);
    if (!isset($customField)) {
      return 'Integer';
    }
    return self::getDataTypeForPrimaryKey($customField['fk_entity']);
  }

  /**
   * Given a field return the mysql data type associated with it.
   *
   * @param string $type
   * @param int|null $maxLength
   * @param bool $isSerialized (serialized fields must always have a textual mysql field)
   * @param string|null $fkEntity The entity that the CustomField (CustomGroup) extends. eg. Activity
   *
   * @return string
   *   the mysql data store placeholder
   */
  public static function fieldToSQLType(string $type, $maxLength = NULL, bool $isSerialized = FALSE, ?string $fkEntity = NULL) {
    if ($fkEntity) {
      $type = self::getDataTypeForPrimaryKey($fkEntity);
    }

    if ($isSerialized) {
      // Always use 'text' for serialized fields as their length cannot be known
      return 'text';
    }
    switch ($type) {
      case 'String':
        $maxLength = $maxLength ?: 255;
        return "varchar($maxLength)";

      case 'Link':
        // URLs can be up to 2047 characters
        // according to https://www.sitemaps.org/protocol.html#locdef
        $maxLength = $maxLength ?: 2047;
        return "varchar($maxLength)";

      case 'Boolean':
        return 'boolean';

      case 'Int':
        return 'int';

      // the below three are FK's, and have constraints added to them
      case 'Integer':
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
          'html_type' => $customValue['html_type'],
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
   * @param string $entityTable
   * @param int $entityID
   * @param ?string $customFieldExtends
   *   Can be null for multivalued fields
   * @param ?string $parentOperation
   */
  public static function postProcess(array &$params, string $entityTable, int $entityID, ?string $customFieldExtends, ?string $parentOperation = NULL) {
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
    }

    $cond = ['is_active' => TRUE];
    if ($entityType) {
      $cond['extends'] = $entityType;
    }
    // If no entity or field ids given, assume "Contact"
    elseif (empty($fieldIDs)) {
      $cond['extends'] = 'Contact';
    }

    $limit = $orderBy = '';
    if (!empty($DTparams['rowCount']) && $DTparams['rowCount'] > 0) {
      $limit = " LIMIT " . CRM_Utils_Type::escape($DTparams['offset'], 'Integer') . ", " . CRM_Utils_Type::escape($DTparams['rowCount'], 'Integer');
    }
    if (!empty($DTparams['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($DTparams['sort'], 'String');
    }

    // First find all the fields that extend this type of entity.
    $select = $fields = $isMultiple = [];

    $customGroups = CRM_Core_BAO_CustomGroup::getAll($cond);
    foreach ($customGroups as $customGroup) {
      foreach ($customGroup['fields'] as $customField) {
        if ($fieldIDs && !in_array($customField['id'], $fieldIDs)) {
          continue;
        }
        if (!array_key_exists($customGroup['table_name'], $select)) {
          $fields[$customGroup['table_name']] = [];
          $select[$customGroup['table_name']] = [];
        }
        $fields[$customGroup['table_name']][] = $customField['id'];
        $select[$customGroup['table_name']][] = "`{$customField['column_name']}` AS custom_{$customField['id']}";
        $isMultiple[$customGroup['table_name']] = $customGroup['is_multiple'];
        $file[$customGroup['table_name']][$customField['id']] = $customField['data_type'];
      }
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
        $isSerialized = CRM_Core_BAO_CustomField::isSerialized($fieldInfo);
        if (is_array($fieldValue['value']) && $isSerialized) {
          $fieldValue['value'] = CRM_Utils_Array::implodePadded($fieldValue['value']);
        }
        if ($isSerialized) {
          // Serialized numbers are stored as value-separated strings
          $dataType = 'String';
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

        $entity = CRM_Core_BAO_CustomGroup::getEntityFromExtends($fieldInfo['custom_group']['extends']);
        $cvParam = [
          'entity_table' => CRM_Core_DAO_AllCoreTables::getTableForEntityName($entity),
          'entity_id' => $params['entityID'],
          'value' => $fieldValue['value'],
          'type' => $dataType,
          'html_type' => $fieldInfo['html_type'],
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

  /**
   * Delete orphaned files from disk when updating custom file fields
   */
  private static function deleteFile(array $field) {
    $sql = CRM_Utils_SQL_Select::from($field['table_name'])
      ->select($field['column_name'])
      ->where("id = #id", ['#id' => $field['id']])
      ->toSQL();
    $fileId = CRM_Core_DAO::singleValueQuery($sql);
    if ($fileId && $fileId != ($field['file_id'] ?? NULL)) {
      $refCount = \Civi\Api4\Utils\CoreUtil::getRefCountTotal('File', $fileId);
      if ($refCount <= 1) {
        \Civi\Api4\File::delete(FALSE)
          ->addWhere('id', '=', $fileId)
          ->execute();
      }
    }
  }

}
