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
 * Business objects for managing custom data fields.
 */
class CRM_Core_BAO_CustomField extends CRM_Core_DAO_CustomField {

  /**
   * Array to hold (formatted) fields for import
   *
   * @var array
   */
  public static $_importFields = NULL;

  /**
   * Build and retrieve the list of data types and descriptions.
   *
   * @return array
   *   Data type => Description
   */
  public static function dataType() {
    return [
      [
        'id' => 'String',
        'name' => 'Alphanumeric',
        'label' => ts('Alphanumeric'),
      ],
      [
        'id' => 'Int',
        'name' => 'Integer',
        'label' => ts('Integer'),
      ],
      [
        'id' => 'Float',
        'name' => 'Number',
        'label' => ts('Number'),
      ],
      [
        'id' => 'Money',
        'name' => 'Money',
        'label' => ts('Money'),
      ],
      [
        'id' => 'Memo',
        'name' => 'Note',
        'label' => ts('Note'),
      ],
      [
        'id' => 'Date',
        'name' => 'Date',
        'label' => ts('Date'),
      ],
      [
        'id' => 'Boolean',
        'name' => 'Yes or No',
        'label' => ts('Yes or No'),
      ],
      [
        'id' => 'StateProvince',
        'name' => 'State/Province',
        'label' => ts('State/Province'),
      ],
      [
        'id' => 'Country',
        'name' => 'Country',
        'label' => ts('Country'),
      ],
      [
        'id' => 'File',
        'name' => 'File',
        'label' => ts('File'),
      ],
      [
        'id' => 'Link',
        'name' => 'Link',
        'label' => ts('Link'),
      ],
      [
        'id' => 'ContactReference',
        'name' => 'Contact Reference',
        'label' => ts('Contact Reference'),
      ],
    ];
  }

  /**
   * Build the map of custom field's data types and there respective Util type
   *
   * @return array
   *   Data data-type => CRM_Utils_Type
   */
  public static function dataToType() {
    return [
      'String' => CRM_Utils_Type::T_STRING,
      'Int' => CRM_Utils_Type::T_INT,
      'Money' => CRM_Utils_Type::T_MONEY,
      'Memo' => CRM_Utils_Type::T_LONGTEXT,
      'Float' => CRM_Utils_Type::T_FLOAT,
      'Date' => CRM_Utils_Type::T_DATE,
      'DateTime' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
      'Boolean' => CRM_Utils_Type::T_BOOLEAN,
      'StateProvince' => CRM_Utils_Type::T_INT,
      'File' => CRM_Utils_Type::T_STRING,
      'Link' => CRM_Utils_Type::T_STRING,
      'ContactReference' => CRM_Utils_Type::T_INT,
      'Country' => CRM_Utils_Type::T_INT,
    ];
  }

  /**
   * Deprecated in favor of writeRecords & APIv4
   *
   * @deprecated
   * @param array $params
   * @return CRM_Core_DAO_CustomField
   */
  public static function create($params) {
    $changeSerialize = self::getChangeSerialize($params);
    $customField = self::createCustomFieldRecord($params);
    // When deserializing a field, the update needs to run before the schema change
    if ($changeSerialize === 0) {
      CRM_Core_DAO::singleValueQuery(self::getAlterSerializeSQL($customField));
    }
    $op = empty($params['id']) ? 'add' : 'modify';
    self::createField($customField, $op);
    // When serializing a field, the update needs to run after the schema change
    if ($changeSerialize === 1) {
      CRM_Core_DAO::singleValueQuery(self::getAlterSerializeSQL($customField));
    }

    CRM_Utils_Hook::post(($op === 'add' ? 'create' : 'edit'), 'CustomField', $customField->id, $customField);

    CRM_Utils_System::flushCache();
    // Flush caches is not aggressive about clearing the specific cache we know we want to clear
    // so do it manually. Ideally we wouldn't need to clear others...
    Civi::cache('metadata')->clear();

    return $customField;
  }

  /**
   * Save multiple fields, now deprecated in favor of self::writeRecords.
   * https://lab.civicrm.org/dev/core/issues/1093
   * @deprecated
   *
   * @param array $bulkParams
   *   Array of arrays as would be passed into create
   * @param array $defaults
   *  Default parameters to be be merged into each of the params.
   *
   * @throws \CRM_Core_Exception
   */
  public static function bulkSave($bulkParams, $defaults = []) {
    CRM_Core_Error::deprecatedFunctionWarning(__CLASS__ . '::writeRecords');
    foreach ($bulkParams as $index => $fieldParams) {
      $bulkParams[$index] = array_merge($defaults, $fieldParams);
    }
    self::writeRecords($bulkParams);
  }

  /**
   * Create/update several fields at once in a mysql efficient way.
   *
   * @param array $records
   * @return CRM_Core_DAO_CustomField[]
   * @throws CRM_Core_Exception
   */
  public static function writeRecords(array $records): array {
    $addedColumns = $sql = $customFields = $pre = $post = [];
    foreach ($records as $index => $params) {
      CRM_Utils_Hook::pre(empty($params['id']) ? 'create' : 'edit', 'CustomField', $params['id'] ?? NULL, $params);

      $changeSerialize = self::getChangeSerialize($params);
      $customField = self::createCustomFieldRecord($params);
      // Serialize/deserialize sql must run after/before the table is altered
      if ($changeSerialize === 0) {
        $pre[] = self::getAlterSerializeSQL($customField);
      }
      if ($changeSerialize === 1) {
        $post[] = self::getAlterSerializeSQL($customField);
      }
      $fieldSQL = self::getAlterFieldSQL($customField, empty($params['id']) ? 'add' : 'modify');

      $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customField->custom_group_id, 'table_name');
      $sql[$tableName][] = $fieldSQL;
      $addedColumns[$tableName][] = $customField->column_name;
      $customFields[$index] = $customField;
    }

    foreach ($pre as $query) {
      CRM_Core_DAO::executeQuery($query);
    }

    foreach ($sql as $tableName => $statements) {
      // CRM-7007: do not i18n-rewrite this query
      CRM_Core_DAO::executeQuery("ALTER TABLE $tableName " . implode(', ', $statements), [], TRUE, NULL, FALSE, FALSE);

      if (CRM_Core_Config::singleton()->logging) {
        $logging = new CRM_Logging_Schema();
        $logging->fixSchemaDifferencesFor($tableName, ['ADD' => $addedColumns[$tableName]]);
      }

      Civi::service('sql_triggers')->rebuild($tableName, TRUE);
    }

    foreach ($post as $query) {
      CRM_Core_DAO::executeQuery($query);
    }

    CRM_Utils_System::flushCache();
    Civi::cache('metadata')->clear();

    foreach ($customFields as $index => $customField) {
      $op = empty($records[$index]['id']) ? 'create' : 'edit';
      // Theoretically a custom field could have custom fields! Trippy...
      if (!empty($records[$index]['custom']) && is_array($records[$index]['custom'])) {
        CRM_Core_BAO_CustomValueTable::store($records[$index]['custom'], static::$_tableName, $customField->id, $op);
      }
      CRM_Utils_Hook::post($op, 'CustomField', $customField->id, $customField);
    }
    return $customFields;
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

    CRM_Utils_System::flushCache();

    //enable-disable CustomField
    CRM_Core_BAO_UFField::setUFField($id, $is_active);
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_CustomField', $id, 'is_active', $is_active);
  }

  /**
   * Get the field title.
   *
   * @param int $id
   *   Id of field.
   *
   * @return string
   *   name
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $id, 'label');
  }

  /**
   * @param string $context
   * @return array|bool
   */
  public function getOptions($context = NULL) {
    CRM_Core_DAO::buildOptionsContext($context);

    if (!$this->id) {
      return FALSE;
    }
    $cacheKey = "CRM_Core_BAO_CustomField_getOptions_{$this->id}_$context";
    $cache = CRM_Utils_Cache::singleton();
    $options = $cache->get($cacheKey);
    if (!isset($options)) {
      if (!$this->data_type || !$this->custom_group_id) {
        $this->find(TRUE);
      }

      // This will hold the list of options in format key => label
      $options = [];

      if (!empty($this->option_group_id)) {
        $options = CRM_Core_OptionGroup::valuesByID(
        $this->option_group_id, FALSE, FALSE, FALSE, $context == 'validate' ? 'name' : 'label', !($context == 'validate' || $context == 'get')
        );
      }
      elseif ($this->data_type === 'StateProvince') {
        $options = CRM_Core_PseudoConstant::stateProvince();
      }
      elseif ($this->data_type === 'Country') {
        $options = $context == 'validate' ? CRM_Core_PseudoConstant::countryIsoCode() : CRM_Core_PseudoConstant::country();
      }
      elseif ($this->data_type === 'Boolean') {
        $options = $context == 'validate' ? [0, 1] : CRM_Core_SelectValues::boolean();
      }
      CRM_Utils_Hook::customFieldOptions($this->id, $options, FALSE);
      CRM_Utils_Hook::fieldOptions($this->getEntity(), "custom_{$this->id}", $options, ['context' => $context]);
      $cache->set($cacheKey, $options);
    }
    return $options;
  }

  /**
   * @inheritDoc
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    $options = parent::buildOptions($fieldName, $context, $props);
    // This provides legacy support for APIv3, allowing no-longer-existent html types
    if ($fieldName == 'html_type' && isset($props['version']) && $props['version'] == 3) {
      $options['Multi-Select'] = 'Multi-Select';
      $options['Select Country'] = 'Select Country';
      $options['Multi-Select Country'] = 'Multi-Select Country';
      $options['Select State/Province'] = 'Select State/Province';
      $options['Multi-Select State/Province'] = 'Multi-Select State/Province';
    }
    return $options;
  }

  /**
   * Store and return an array of all active custom fields.
   *
   * @param string $customDataType
   *   Type of Custom Data; 'ANY' is a synonym for "all contact data types".
   * @param bool $showAll
   *   If true returns all fields (includes disabled fields).
   * @param bool $inline
   *   If true returns all inline fields (includes disabled fields).
   * @param int $customDataSubType
   *   Custom Data sub type value.
   * @param int $customDataSubName
   *   Custom Data sub name value.
   * @param bool $onlyParent
   *   Return only top level custom data, for eg, only Participant and ignore subname and subtype.
   * @param bool $onlySubType
   *   Return only custom data for subtype.
   * @param bool|int $checkPermission
   *   Either a CRM_Core_Permission constant or FALSE to disable checks
   *
   * @return array
   *   an array of active custom fields.
   * @throws \CRM_Core_Exception
   */
  public static function &getFields(
    $customDataType = 'Individual',
    $showAll = FALSE,
    $inline = FALSE,
    $customDataSubType = NULL,
    $customDataSubName = NULL,
    $onlyParent = FALSE,
    $onlySubType = FALSE,
    $checkPermission = CRM_Core_Permission::EDIT
  ) {
    if ($checkPermission === TRUE) {
      CRM_Core_Error::deprecatedWarning('Unexpected TRUE passed to CustomField::getFields $checkPermission param.');
      $checkPermission = CRM_Core_Permission::EDIT;
    }
    if (empty($customDataType)) {
      $customDataType = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes());
    }
    if ($customDataType === 'ANY') {
      // NULL should have been respected but the line above broke that.
      // boo for us not having enough unit tests back them.
      $customDataType = NULL;
    }
    if ($customDataType && !is_array($customDataType)) {

      if (in_array($customDataType, CRM_Contact_BAO_ContactType::subTypes(), TRUE)) {
        // This is the case when getFieldsForImport() requires fields
        // limited strictly to a subtype.
        $customDataSubType = $customDataType;
        $customDataType = CRM_Contact_BAO_ContactType::getBasicType($customDataType);
        $onlySubType = TRUE;
      }

      if (array_key_exists($customDataType, CRM_Core_SelectValues::customGroupExtends())) {
        // this makes the method flexible to support retrieving fields
        // for multiple extends value.
        $customDataType = [$customDataType];
      }
    }

    $customDataSubType = CRM_Utils_Array::explodePadded($customDataSubType);

    if (is_array($customDataType)) {
      $cacheKey = implode('_', $customDataType);
    }
    else {
      $cacheKey = $customDataType;
    }

    $cacheKey .= !empty($customDataSubType) ? ('_' . implode('_', $customDataSubType)) : '_0';
    $cacheKey .= $customDataSubName ? "{$customDataSubName}_" : '_0';
    $cacheKey .= $showAll ? '_1' : '_0';
    $cacheKey .= $inline ? '_1_' : '_0_';
    $cacheKey .= $onlyParent ? '_1_' : '_0_';
    $cacheKey .= $onlySubType ? '_1_' : '_0_';
    $cacheKey .= $checkPermission ? $checkPermission . CRM_Core_Session::getLoggedInContactID() . '_' : '_0_0_';
    $cacheKey .= '_' . CRM_Core_Config::domainID() . '_';

    $cgTable = CRM_Core_DAO_CustomGroup::getTableName();

    // also get the permission stuff here
    if ($checkPermission) {
      $permissionClause = CRM_Core_Permission::customGroupClause($checkPermission,
        "{$cgTable}."
      );
    }
    else {
      $permissionClause = '(1)';
    }

    // lets md5 permission clause and take first 8 characters
    $cacheKey .= substr(md5($permissionClause), 0, 8);

    if (strlen($cacheKey) > 40) {
      $cacheKey = md5($cacheKey);
    }

    if (!self::$_importFields ||
      CRM_Utils_Array::value($cacheKey, self::$_importFields) === NULL
    ) {
      if (!self::$_importFields) {
        self::$_importFields = [];
      }

      // check if we can retrieve from database cache
      $fields = Civi::Cache('fields')->get("custom importableFields $cacheKey");

      if ($fields === NULL) {

        $extends = '';
        if (is_array($customDataType)) {
          $value = NULL;
          foreach ($customDataType as $dataType) {
            if (array_key_exists($dataType, CRM_Core_SelectValues::customGroupExtends())) {
              if (in_array($dataType, CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE)) {
                $val = "'" . CRM_Utils_Type::escape($dataType, 'String') . "', 'Contact' ";
              }
              else {
                $val = "'" . CRM_Utils_Type::escape($dataType, 'String') . "'";
              }
              $value = $value ? $value . ", {$val}" : $val;
            }
          }
          if ($value) {
            $extends = "AND   $cgTable.extends IN ( $value ) ";
          }
        }

        if (!empty($customDataType) && empty($extends)) {
          // $customDataType specified a filter, but there is no corresponding SQL ($extends)
          self::$_importFields[$cacheKey] = [];
          return self::$_importFields[$cacheKey];
        }

        if ($onlyParent) {
          $extends .= " AND $cgTable.extends_entity_column_value IS NULL AND $cgTable.extends_entity_column_id IS NULL ";
        }
        // Temporary hack - in 5.27 a new field is added to civicrm_custom_field. There is a high
        // risk this function is called before the upgrade page can be reached and if
        // so it will potentially result in fatal error.
        $serializeField = CRM_Core_BAO_Domain::isDBVersionAtLeast('5.27.alpha1') ? "custom_field.serialize," : '';

        $query = "SELECT custom_field.id, custom_field.label,
                            $cgTable.title,
                            custom_field.data_type,
                            custom_field.html_type,
                            custom_field.default_value,
                            custom_field.options_per_line, custom_field.text_length,
                            custom_field.custom_group_id,
                            custom_field.is_required,
                            custom_field.column_name,
                            $cgTable.extends, custom_field.is_search_range,
                            $cgTable.extends_entity_column_value,
                            $cgTable.extends_entity_column_id,
                            custom_field.is_view,
                            custom_field.option_group_id,
                            custom_field.date_format,
                            custom_field.time_format,
                            $cgTable.is_multiple,
                            $serializeField
                            $cgTable.table_name,
                            og.name as option_group_name
                     FROM civicrm_custom_field custom_field
                     INNER JOIN $cgTable
                       ON custom_field.custom_group_id = $cgTable.id
                     LEFT JOIN civicrm_option_group og
                       ON custom_field.option_group_id = og.id
                     WHERE ( 1 ) ";

        if (!$showAll) {
          $query .= " AND custom_field.is_active = 1 AND $cgTable.is_active = 1 ";
        }

        if ($inline) {
          $query .= " AND $cgTable.style = 'Inline' ";
        }

        //get the custom fields for specific type in
        //combination with fields those support any type.
        if (!empty($customDataSubType)) {
          $subtypeClause = [];
          foreach ($customDataSubType as $subtype) {
            $subtype = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($subtype, 'String') . CRM_Core_DAO::VALUE_SEPARATOR;
            $subtypeClause[] = "$cgTable.extends_entity_column_value LIKE '%{$subtype}%'";
          }
          if (!$onlySubType) {
            $subtypeClause[] = "$cgTable.extends_entity_column_value IS NULL";
          }
          $query .= " AND ( " . implode(' OR ', $subtypeClause) . " )";
        }

        if ($customDataSubName) {
          $query .= " AND ( $cgTable.extends_entity_column_id = $customDataSubName ) ";
        }

        // also get the permission stuff here
        if ($checkPermission) {
          $permissionClause = CRM_Core_Permission::customGroupClause($checkPermission,
            "{$cgTable}.", TRUE
          );
        }
        else {
          $permissionClause = '(1)';
        }

        $query .= " $extends AND $permissionClause
                        ORDER BY $cgTable.weight, $cgTable.title,
                                 custom_field.weight, custom_field.label";

        $dao = CRM_Core_DAO::executeQuery($query);

        $fields = [];
        while (($dao->fetch()) != NULL) {
          $regexp = preg_replace('/[.,;:!?]/', '', '');
          $fields[$dao->id]['id'] = $dao->id;
          $fields[$dao->id]['label'] = $dao->label;
          // This seems broken, but not in a new way.
          $fields[$dao->id]['headerPattern'] = '/' . preg_quote($regexp, '/') . '/';
          // To support the consolidation of various functions & their expectations.
          $fields[$dao->id]['title'] = $dao->label;
          $fields[$dao->id]['custom_field_id']  = $dao->id;
          $fields[$dao->id]['groupTitle'] = $dao->title;
          $fields[$dao->id]['data_type'] = $dao->data_type;
          $fields[$dao->id]['name'] = 'custom_' . $dao->id;
          $fields[$dao->id]['type'] = CRM_Utils_Array::value($dao->data_type, self::dataToType());
          $fields[$dao->id]['html_type'] = $dao->html_type;
          $fields[$dao->id]['default_value'] = $dao->default_value;
          $fields[$dao->id]['text_length'] = $dao->text_length;
          $fields[$dao->id]['options_per_line'] = $dao->options_per_line;
          $fields[$dao->id]['custom_group_id'] = $dao->custom_group_id;
          $fields[$dao->id]['extends'] = $dao->extends;
          $fields[$dao->id]['is_search_range'] = $dao->is_search_range;
          $fields[$dao->id]['extends_entity_column_value'] = $dao->extends_entity_column_value;
          $fields[$dao->id]['extends_entity_column_id'] = $dao->extends_entity_column_id;
          $fields[$dao->id]['is_view'] = $dao->is_view;
          $fields[$dao->id]['is_multiple'] = $dao->is_multiple;
          $fields[$dao->id]['option_group_id'] = $dao->option_group_id;
          $fields[$dao->id]['date_format'] = $dao->date_format;
          $fields[$dao->id]['time_format'] = $dao->time_format;
          $fields[$dao->id]['is_required'] = $dao->is_required;
          $fields[$dao->id]['table_name'] = $dao->table_name;
          $fields[$dao->id]['column_name'] = $dao->column_name;
          $fields[$dao->id]['serialize'] = $serializeField ? $dao->serialize : (int) self::isSerialized($dao);
          $fields[$dao->id]['where'] = $dao->table_name . '.' . $dao->column_name;
          // Probably we should use a different fn to get the extends tables but this is a refactor so not changing that now.
          $fields[$dao->id]['extends_table'] = array_key_exists($dao->extends, CRM_Core_BAO_CustomQuery::$extendsMap) ? CRM_Core_BAO_CustomQuery::$extendsMap[$dao->extends] : '';
          if (in_array($dao->extends, CRM_Contact_BAO_ContactType::subTypes())) {
            // if $extends is a subtype, refer contact table
            $fields[$dao->id]['extends_table'] = 'civicrm_contact';
          }
          // Search table is used by query object searches..
          $fields[$dao->id]['search_table'] = ($fields[$dao->id]['extends_table'] == 'civicrm_contact') ? 'contact_a' : $fields[$dao->id]['extends_table'];
          self::getOptionsForField($fields[$dao->id], $dao->option_group_name);
        }

        Civi::cache('fields')->set("custom importableFields $cacheKey", $fields);
      }
      self::$_importFields[$cacheKey] = $fields;
    }

    return self::$_importFields[$cacheKey];
  }

  /**
   * Return field ids and names (with groups).
   *
   * NOTE: Despite this function's name, it is used both for IMPORT and EXPORT.
   * The $checkPermission variable should be set to VIEW for export and EDIT for import.
   *
   * @param int|string $contactType Contact type
   * @param bool $showAll
   *   If true returns all fields (includes disabled fields).
   * @param bool $onlyParent
   *   Return fields ONLY related to basic types.
   * @param bool $search
   *   When called from search and multiple records need to be returned.
   * @param bool|int $checkPermission
   *   Either a CRM_Core_Permission constant or FALSE to disable checks
   *
   * @param bool $withMultiple
   *
   * @return array
   */
  public static function getFieldsForImport(
    $contactType = 'Individual',
    $showAll = FALSE,
    $onlyParent = FALSE,
    $search = FALSE,
    $checkPermission = TRUE,
    $withMultiple = FALSE
  ) {
    if ($checkPermission === TRUE) {
      // TODO: Trigger deprecation notice for passing TRUE
      $checkPermission = CRM_Core_Permission::EDIT;
    }
    // Note: there are situations when we want getFieldsForImport() return fields related
    // ONLY to basic contact types, but NOT subtypes. And thats where $onlyParent is helpful
    $fields = &self::getFields($contactType,
      $showAll,
      FALSE,
      NULL,
      NULL,
      $onlyParent,
      FALSE,
      $checkPermission
    );

    $importableFields = [];
    foreach ($fields as $id => $values) {
      // for now we should not allow multiple fields in profile / export etc, hence unsetting
      if (!$search &&
        (!empty($values['is_multiple']) && !$withMultiple)
      ) {
        continue;
      }
      if (!empty($values['text_length'])) {
        $values['maxlength'] = (int) $values['text_length'];
      }

      /* generate the key for the fields array */

      $key = "custom_$id";
      $importableFields[$key] = $values;
      $importableFields[$key]['import'] = 1;
    }

    return $importableFields;
  }

  /**
   * Get the field id from an import key.
   *
   * @param string $key
   *   The key to parse.
   *
   * @param bool $all
   *
   * @return int|null
   *   The id (if exists)
   */
  public static function getKeyID($key, $all = FALSE) {
    $match = [];
    if (preg_match('/^custom_(\d+)_?(-?\d+)?$/', $key, $match)) {
      if (!$all) {
        return $match[1];
      }
      else {
        return [
          $match[1],
          CRM_Utils_Array::value(2, $match),
        ];
      }
    }
    return $all ? [NULL, NULL] : NULL;
  }

  /**
   * Use the cache to get all values of a specific custom field.
   *
   * @param int $fieldID
   *   The custom field ID.
   *
   * @return CRM_Core_BAO_CustomField
   *   The field object.
   * @throws CRM_Core_Exception
   */
  public static function getFieldObject($fieldID) {
    $field = new CRM_Core_BAO_CustomField();

    // check if we can get the field values from the system cache
    $cacheKey = "CRM_Core_DAO_CustomField_{$fieldID}";
    $cache = CRM_Utils_Cache::singleton();
    $fieldValues = $cache->get($cacheKey);
    if (empty($fieldValues)) {
      $field->id = $fieldID;
      if (!$field->find(TRUE)) {
        throw new CRM_Core_Exception('Cannot find Custom Field ' . $fieldID);
      }

      $fieldValues = [];
      CRM_Core_DAO::storeValues($field, $fieldValues);

      $cache->set($cacheKey, $fieldValues);
    }
    else {
      $field->copyValues($fieldValues);
    }

    return $field;
  }

  /**
   * Add a custom field to an existing form.
   *
   * @param CRM_Core_Form $qf
   *   Form object (reference).
   * @param string $elementName
   *   Name of the custom field.
   * @param int $fieldId
   * @param bool $useRequired
   *   True if required else false.
   * @param bool $search
   *   True if used for search else false.
   * @param string $label
   *   Label for custom field.
   * @return \HTML_QuickForm_Element|null
   * @throws \CRM_Core_Exception
   */
  public static function addQuickFormElement(
    $qf, $elementName, $fieldId, $useRequired = TRUE, $search = FALSE, $label = NULL
  ) {
    $field = self::getFieldObject($fieldId);
    $widget = $field->html_type;
    $element = NULL;
    $customFieldAttributes = [];

    if (!isset($label)) {
      $label = $field->label;
    }

    // DAO stores attributes as a string, but it's hard to manipulate and
    // CRM_Core_Form::add() wants them as an array.
    $fieldAttributes = self::attributesFromString($field->attributes ?? '');

    // Custom field HTML should indicate group+field name
    $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $field->custom_group_id);
    $fieldAttributes['data-crm-custom'] = $groupName . ':' . $field->name;

    // Fixed for Issue CRM-2183
    if ($widget == 'TextArea' && $search) {
      $widget = 'Text';
    }

    $placeholder = $search ? ts('- any %1 -', [1 => $label]) : ts('- select %1 -', [1 => $label]);

    if (in_array($widget, [
      'Select',
      'CheckBox',
      'Radio',
    ])) {
      $options = $field->getOptions($search ? 'search' : 'create');

      // Consolidate widget types to simplify the below switch statement
      if ($search) {
        $widget = 'Select';
      }

      // Search field is always multi-select
      if ($search || (self::isSerialized($field) && $widget === 'Select')) {
        $fieldAttributes['class'] = ltrim(($fieldAttributes['class'] ?? '') . ' huge');
        $fieldAttributes['multiple'] = 'multiple';
        $fieldAttributes['placeholder'] = $placeholder;
      }

      // Add data for popup link. Normally this is handled by CRM_Core_Form->addSelect
      $canEditOptions = CRM_Core_Permission::check('administer CiviCRM');
      if ($field->option_group_id && !$search && $canEditOptions) {
        $customFieldAttributes += [
          'data-api-entity' => $field->getEntity(),
          'data-api-field' => 'custom_' . $field->id,
          'data-option-edit-path' => 'civicrm/admin/options/' . CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $field->option_group_id),
        ];
        $fieldAttributes = array_merge($fieldAttributes, $customFieldAttributes);
      }
    }

    $rangeDataTypes = ['Int', 'Float', 'Money'];

    // at some point in time we might want to split the below into small functions

    switch ($widget) {
      case 'Text':
      case 'Link':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $fieldAttributes);
          $qf->add('text', $elementName . '_to', ts('To'), $fieldAttributes);
        }
        else {
          if ($field->text_length) {
            $fieldAttributes['maxlength'] = $field->text_length;
            if ($field->text_length < 20) {
              $fieldAttributes['size'] = $field->text_length;
            }
          }
          $element = $qf->add('text', $elementName, $label,
            $fieldAttributes,
            $useRequired && !$search
          );
        }
        break;

      case 'TextArea':
        $fieldAttributes['rows'] = $field->note_rows ?? 4;
        $fieldAttributes['cols'] = $field->note_columns ?? 60;

        if ($field->text_length) {
          $fieldAttributes['maxlength'] = $field->text_length;
        }
        $element = $qf->add('textarea',
          $elementName,
          $label,
          $fieldAttributes,
          $useRequired && !$search
        );
        break;

      case 'Select Date':
        //CRM-18379: Fix for date range of 'Select Date' custom field when include in profile.
        $minYear = isset($field->start_date_years) ? (date('Y') - $field->start_date_years) : NULL;
        $maxYear = isset($field->end_date_years) ? (date('Y') + $field->end_date_years) : NULL;

        $params = [
          'date' => $field->date_format,
          'minDate' => isset($minYear) ? $minYear . '-01-01' : NULL,
          //CRM-18487 - max date should be the last date of the year.
          'maxDate' => isset($maxYear) ? $maxYear . '-12-31' : NULL,
          'time' => $field->time_format ? $field->time_format * 12 : FALSE,
        ];
        if ($field->is_search_range && $search) {
          $qf->add('datepicker', $elementName . '_from', $label, $fieldAttributes + array('placeholder' => ts('From')), FALSE, $params);
          $qf->add('datepicker', $elementName . '_to', NULL, $fieldAttributes + array('placeholder' => ts('To')), FALSE, $params);
        }
        else {
          $element = $qf->add('datepicker', $elementName, $label, $fieldAttributes, $useRequired && !$search, $params);
        }
        break;

      case 'Radio':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $fieldAttributes);
          $qf->add('text', $elementName . '_to', ts('To'), $fieldAttributes);
        }
        else {
          $fieldAttributes = array_merge($fieldAttributes, $customFieldAttributes);
          if ($search || empty($useRequired)) {
            $fieldAttributes['allowClear'] = TRUE;
          }
          $qf->addRadio($elementName, $label, $options, $fieldAttributes, NULL, $useRequired);
        }
        break;

      // For all select elements
      case 'Select':
        $fieldAttributes['class'] = ltrim(($fieldAttributes['class'] ?? '') . ' crm-select2');

        if (empty($fieldAttributes['multiple'])) {
          $options = ['' => $placeholder] + $options;
        }
        $element = $qf->add('select', $elementName, $label, $options, $useRequired && !$search, $fieldAttributes);

        // Add and/or option for fields that store multiple values
        if ($search && self::isSerialized($field)) {
          $qf->addRadio($elementName . '_operator', '', [
            'or' => ts('Any'),
            'and' => ts('All'),
          ], [], NULL, FALSE, [
            'or' => ['title' => ts('Results may contain any of the selected options')],
            'and' => ['title' => ts('Results must have all of the selected options')],
          ]);
          $qf->setDefaults([$elementName . '_operator' => 'or']);
        }
        break;

      case 'CheckBox':
        $check = [];
        foreach ($options as $v => $l) {
          // TODO: I'm not sure if this is supposed to exclude whatever might be
          // in $field->attributes (available in array format as
          // $fieldAttributes).  Leaving as-is for now.
          $check[] = &$qf->addElement('advcheckbox', $v, NULL, $l, $customFieldAttributes);
        }

        $group = $element = $qf->addGroup($check, $elementName, $label);
        $optionEditKey = 'data-option-edit-path';
        if (isset($customFieldAttributes[$optionEditKey])) {
          $group->setAttribute($optionEditKey, $customFieldAttributes[$optionEditKey]);
        }

        if ($useRequired && !$search) {
          $qf->addRule($elementName, ts('%1 is a required field.', [1 => $label]), 'required');
        }
        break;

      case 'File':
        // we should not build upload file in search mode
        if ($search) {
          return NULL;
        }
        $element = $qf->add(
          strtolower($field->html_type),
          $elementName,
          $label,
          $fieldAttributes,
          $useRequired && !$search
        );
        $qf->addUploadElement($elementName);
        break;

      case 'RichTextEditor':
        $fieldAttributes['rows'] = $field->note_rows;
        $fieldAttributes['cols'] = $field->note_columns;
        if ($field->text_length) {
          $fieldAttributes['maxlength'] = $field->text_length;
        }
        $element = $qf->add('wysiwyg', $elementName, $label, $fieldAttributes, $useRequired && !$search);
        break;

      case 'Autocomplete-Select':
        static $customUrls = [];
        if ($field->data_type == 'ContactReference') {
          // break if contact does not have permission to access ContactReference
          if (!CRM_Core_Permission::check('access contact reference fields')) {
            break;
          }
          $fieldAttributes['class'] = ltrim(($fieldAttributes['class'] ?? '') . ' crm-form-contact-reference huge');
          $fieldAttributes['data-api-entity'] = 'Contact';
          if (!empty($field->serialize) || $search) {
            $fieldAttributes['multiple'] = TRUE;
          }
          $element = $qf->add('text', $elementName, $label, $fieldAttributes, $useRequired && !$search);

          $urlParams = "context=customfield&id={$field->id}";
          $idOfelement = $elementName;
          // dev/core#362 if in an onbehalf profile clean up the name to get rid of square brackets that break the select 2 js
          // However this caused regression https://lab.civicrm.org/dev/core/issues/619 so it has been hacked back to
          // only affecting on behalf - next time someone looks at this code it should be with a view to overhauling it
          // rather than layering on more hacks.
          if (substr($elementName, 0, 8) === 'onbehalf' && strpos($elementName, '[') && strpos($elementName, ']')) {
            $idOfelement = substr(substr($elementName, (strpos($elementName, '[') + 1)), 0, -1);
          }
          $customUrls[$idOfelement] = CRM_Utils_System::url('civicrm/ajax/contactref',
            $urlParams,
            FALSE, NULL, FALSE
          );

        }
        else {
          // FIXME: This won't work with customFieldOptions hook
          $fieldAttributes += [
            'entity' => 'OptionValue',
            'placeholder' => $placeholder,
            'multiple' => $search ? TRUE : !empty($field->serialize),
            'api' => [
              'params' => ['option_group_id' => $field->option_group_id, 'is_active' => 1],
            ],
          ];
          $element = $qf->addEntityRef($elementName, $label, $fieldAttributes, $useRequired && !$search);
        }

        $qf->assign('customUrls', $customUrls);
        break;
    }

    switch ($field->data_type) {
      case 'Int':
        // integers will have numeric rule applied to them.
        if ($field->is_search_range && $search && $widget != 'Select') {
          $qf->addRule($elementName . '_from', ts('%1 From must be an integer (whole number).', [1 => $label]), 'integer');
          $qf->addRule($elementName . '_to', ts('%1 To must be an integer (whole number).', [1 => $label]), 'integer');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be an integer (whole number).', [1 => $label]), 'integer');
        }
        break;

      case 'Float':
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must be a number (with or without decimal point).', [1 => $label]), 'numeric');
          $qf->addRule($elementName . '_to', ts('%1 To must be a number (with or without decimal point).', [1 => $label]), 'numeric');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be a number (with or without decimal point).', [1 => $label]), 'numeric');
        }
        break;

      case 'Money':
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must in proper money format. (decimal point/comma/space is allowed).', [1 => $label]), 'money');
          $qf->addRule($elementName . '_to', ts('%1 To must in proper money format. (decimal point/comma/space is allowed).', [1 => $label]), 'money');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be in proper money format. (decimal point/comma/space is allowed).', [1 => $label]), 'money');
        }
        break;

      case 'Link':
        $element->setAttribute('class', "url");
        $qf->addRule($elementName, ts('Enter a valid web address beginning with \'http://\' or \'https://\'.'), 'wikiURL');
        break;
    }
    if ($field->is_view && !$search) {
      $qf->freeze($elementName);
    }
    return $element;
  }

  /**
   * Take a string of HTML element attributes and turn it into an associative
   * array.
   *
   * @param string $attrString
   *   The attributes as a string, e.g. `rows=3 cols=40`.
   *
   * @return array
   *   The attributes as an array, e.g. `['rows' => 3, 'cols' => 40]`.
   */
  public static function attributesFromString($attrString) {
    $attributes = [];
    foreach (explode(' ', $attrString) as $at) {
      if (strpos($at, '=')) {
        [$k, $v] = explode('=', $at);
        $attributes[$k] = trim($v, ' "');
      }
    }
    return $attributes;
  }

  /**
   * Delete the Custom Field.
   *
   * @param object $field
   *   The field object.
   */
  public static function deleteField($field) {
    CRM_Utils_System::flushCache();

    // first delete the custom option group and values associated with this field
    if ($field->option_group_id) {
      //check if option group is related to any other field, if
      //not delete the option group and related option values
      self::checkOptionGroup($field->option_group_id);
    }
    // next drop the column from the custom value table
    self::createField($field, 'delete');

    $field->delete();
    CRM_Core_BAO_UFField::delUFField($field->id);
    CRM_Core_BAO_Mapping::removeFieldFromMapping('custom_' . $field->id);
    CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_CustomField');

    CRM_Utils_Hook::post('delete', 'CustomField', $field->id, $field);
  }

  /**
   * @param string|int|array|null $value
   * @param CRM_Core_BAO_CustomField|int|array|string $field
   * @param int $entityId
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  public static function displayValue($value, $field, $entityId = NULL) {
    $field = is_array($field) ? $field['id'] : $field;
    $fieldId = is_object($field) ? $field->id : (int) str_replace('custom_', '', $field);

    if (!$fieldId) {
      throw new CRM_Core_Exception('CRM_Core_BAO_CustomField::displayValue requires a field id');
    }

    if (!is_a($field, 'CRM_Core_BAO_CustomField')) {
      $field = self::getFieldObject($fieldId);
    }

    $fieldInfo = ['options' => $field->getOptions()] + (array) $field;

    $displayValue = self::formatDisplayValue($value, $fieldInfo, $entityId);

    // Call hook to alter display value.
    CRM_Utils_Hook::alterCustomFieldDisplayValue($displayValue, $value, $entityId, $fieldInfo);

    return $displayValue;
  }

  /**
   * Lower-level logic for rendering a custom field value
   *
   * @param string|array $value
   * @param array $field
   * @param int|null $entityId
   *
   * @return string
   */
  private static function formatDisplayValue($value, $field, $entityId = NULL) {

    if (self::isSerialized($field) && !is_array($value)) {
      $value = CRM_Utils_Array::explodePadded($value);
    }
    // CRM-12989 fix
    if ($field['html_type'] == 'CheckBox' && $value) {
      $value = CRM_Utils_Array::convertCheckboxFormatToArray($value);
    }

    $display = is_array($value) ? implode(', ', $value) : (string) $value;

    switch ($field['html_type']) {

      case 'Select':
      case 'Autocomplete-Select':
      case 'Radio':
      case 'CheckBox':
        if ($field['data_type'] == 'ContactReference' && (is_array($value) || is_numeric($value))) {
          // Issue #2939 - guard against passing empty values to CRM_Core_DAO::getFieldValue(), which would throw an exception
          if (empty($value)) {
            $display = '';
          }
          else {
            $displayNames = [];
            foreach ((array) $value as $contactId) {
              $displayNames[] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'display_name');
            }
            $display = implode(', ', $displayNames);
          }
        }
        elseif ($field['data_type'] == 'ContactReference') {
          $display = $value;
        }
        elseif (is_array($value)) {
          $v = [];
          foreach ($value as $key => $val) {
            $v[] = $field['options'][$val] ?? NULL;
          }
          $display = implode(', ', $v);
        }
        else {
          $display = CRM_Utils_Array::value($value, $field['options'], '');
          // For float type (see Number and Money) $value would be decimal like
          // 1.00 (because it is stored in db as decimal), while options array
          // key would be integer like 1. In this case expression on line above
          // would return empty string (default value), despite the fact that
          // matching option exists in the array.
          // In such cases we could just get intval($value) and fetch matching
          // option again, but this would not work if key is float like 5.6.
          // So we need to truncate trailing zeros to make it work as expected.
          if ($display === '' && strpos(($value ?? ''), '.') !== FALSE) {
            // Use round() to truncate trailing zeros, e.g:
            // 10.00 -> 10, 10.60 -> 10.6, 10.69 -> 10.69.
            $value = (string) round($value, 5);
            $display = $field['options'][$value] ?? '';
          }
        }
        break;

      case 'Select Date':
        $customFormat = NULL;

        // FIXME: Are there any legitimate reasons why $value would be an array?
        // Or should we throw an exception here if it is?
        $value = is_array($value) ? CRM_Utils_Array::first($value) : $value;

        $actualPHPFormats = CRM_Utils_Date::datePluginToPHPFormats();
        $format = $field['date_format'] ?? NULL;

        if ($format) {
          if (array_key_exists($format, $actualPHPFormats)) {
            $customTimeFormat = (array) $actualPHPFormats[$format];
            switch (CRM_Utils_Array::value('time_format', $field)) {
              case 1:
                $customTimeFormat[] = 'g:iA';
                break;

              case 2:
                $customTimeFormat[] = 'G:i';
                break;

              default:
                //If time is not selected remove time from value.
                $value = $value ? date('Y-m-d', strtotime($value)) : '';
            }
            $customFormat = implode(" ", $customTimeFormat);
          }
        }
        $display = CRM_Utils_Date::processDate($value, NULL, FALSE, $customFormat);
        break;

      case 'File':
        // In the context of displaying a profile, show file/image
        if ($value) {
          if ($entityId) {
            if (CRM_Utils_Rule::positiveInteger($value)) {
              $fileId = $value;
            }
            else {
              $fileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File', $value, 'id', 'uri');
            }
            $url = self::getFileURL($entityId, $field['id'], $fileId);
            if ($url) {
              $display = $url['file_url'];
            }
          }
          else {
            // In other contexts show a paperclip icon
            if (CRM_Utils_Rule::integer($value)) {
              $icons = CRM_Core_BAO_File::paperIconAttachment('*', $value);

              $file_description = '';
              try {
                $file_description = civicrm_api3('File', 'getvalue', ['return' => "description", 'id' => $value]);
              }
              catch (CRM_Core_Exception $dontcare) {
                // don't care
              }

              $display = "{$icons[$value]}{$file_description}";
            }
            else {
              //CRM-18396, if filename is passed instead
              $display = $value;
            }
          }
        }
        break;

      case 'Link':
        $display = $display ? "<a href=\"$display\" target=\"_blank\">$display</a>" : $display;
        break;

      case 'TextArea':
        $display = nl2br($display);
        break;

      case 'Text':
        if ($field['data_type'] == 'Money' && isset($value)) {
          // $value can also be an array(while using IN operator from search builder or api).
          foreach ((array) $value as $val) {
            $disp[] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($val);
          }
          $display = implode(', ', $disp);
        }
        elseif ($field['data_type'] == 'Float' && isset($value)) {
          // $value can also be an array(while using IN operator from search builder or api).
          foreach ((array) $value as $val) {
            $disp[] = CRM_Utils_Number::formatLocaleNumeric($val);
          }
          $display = implode(', ', $disp);
        }
        break;
    }
    return $display;
  }

  /**
   * Set default values for custom data used in profile.
   *
   * @param int $customFieldId
   *   Custom field id.
   * @param string $elementName
   *   Custom field name.
   * @param array $defaults
   *   Associated array of fields.
   * @param int $contactId
   *   Contact id.
   * @param int $mode
   *   Profile mode.
   * @param mixed $value
   *   If passed - dont fetch value from db,.
   *                               just format the given value
   */
  public static function setProfileDefaults(
    $customFieldId,
    $elementName,
    &$defaults,
    $contactId = NULL,
    $mode = NULL,
    $value = NULL
  ) {
    //get the type of custom field
    $customField = new CRM_Core_BAO_CustomField();
    $customField->id = $customFieldId;
    $customField->find(TRUE);

    if (!$contactId) {
      if ($mode == CRM_Profile_Form::MODE_CREATE) {
        $value = $customField->default_value;
      }
    }
    else {
      if (!isset($value)) {
        $info = self::getTableColumnGroup($customFieldId);
        $query = "SELECT {$info[0]}.{$info[1]} as value FROM {$info[0]} WHERE {$info[0]}.entity_id = {$contactId}";
        $result = CRM_Core_DAO::executeQuery($query);
        if ($result->fetch()) {
          $value = $result->value;
        }
      }

      if ($customField->data_type == 'Country') {
        if (!$value) {
          $config = CRM_Core_Config::singleton();
          if ($config->defaultContactCountry) {
            $value = CRM_Core_BAO_Country::defaultContactCountry();
          }
        }
      }
    }

    //set defaults if mode is registration
    if (!trim($value) &&
      ($value !== 0) &&
      (!in_array($mode, [CRM_Profile_Form::MODE_EDIT, CRM_Profile_Form::MODE_SEARCH]))
    ) {
      $value = $customField->default_value;
    }

    if ($customField->data_type == 'Money' && isset($value)) {
      $value = number_format($value, 2);
    }
    if (self::isSerialized($customField) && $value) {
      $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldId, FALSE);
      $defaults[$elementName] = [];
      $checkedValue = CRM_Utils_Array::explodePadded($value);
      foreach ($customOption as $val) {
        if (in_array($val['value'], $checkedValue)) {
          if ($customField->html_type == 'CheckBox') {
            $defaults[$elementName][$val['value']] = 1;
          }
          else {
            $defaults[$elementName][$val['value']] = $val['value'];
          }
        }
      }
    }
    else {
      $defaults[$elementName] = $value;
    }
  }

  /**
   * Get file url.
   *
   * @param int $contactID
   * @param int $cfID
   * @param int $fileID
   * @param bool $absolute
   *
   * @param string $multiRecordWhereClause
   *
   * @return array
   */
  public static function getFileURL($contactID, $cfID, $fileID = NULL, $absolute = FALSE, $multiRecordWhereClause = NULL) {
    if ($contactID) {
      if (!$fileID) {
        $params = ['id' => $cfID];
        $defaults = [];
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $defaults);
        $columnName = $defaults['column_name'];

        //table name of custom data
        $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
          $defaults['custom_group_id'],
          'table_name', 'id'
        );

        //query to fetch id from civicrm_file
        if ($multiRecordWhereClause) {
          $query = "SELECT {$columnName} FROM {$tableName} where entity_id = {$contactID} AND {$multiRecordWhereClause}";
        }
        else {
          $query = "SELECT {$columnName} FROM {$tableName} where entity_id = {$contactID}";
        }
        $fileID = CRM_Core_DAO::singleValueQuery($query);
      }

      $result = [];
      if ($fileID) {
        $fileType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
          $fileID,
          'mime_type',
          'id'
        );
        $result['file_id'] = $fileID;

        if ($fileType == 'image/jpeg' ||
          $fileType == 'image/pjpeg' ||
          $fileType == 'image/gif' ||
          $fileType == 'image/x-png' ||
          $fileType == 'image/png'
        ) {
          $entityId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_EntityFile',
            $fileID,
            'entity_id',
            'file_id'
          );
          [$path] = CRM_Core_BAO_File::path($fileID, $entityId);
          $fileHash = CRM_Core_BAO_File::generateFileHash($entityId, $fileID);
          $url = CRM_Utils_System::url('civicrm/file',
            "reset=1&id=$fileID&eid=$entityId&fcs=$fileHash",
            $absolute, NULL, TRUE, TRUE
          );
          $result['file_url'] = CRM_Utils_File::getFileURL($path, $fileType, $url);
        }
        // for non image files
        else {
          $uri = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
            $fileID,
            'uri'
          );
          $fileHash = CRM_Core_BAO_File::generateFileHash($contactID, $fileID);
          $url = CRM_Utils_System::url('civicrm/file',
            "reset=1&id=$fileID&eid=$contactID&fcs=$fileHash",
            $absolute, NULL, TRUE, TRUE
          );
          $result['file_url'] = CRM_Utils_File::getFileURL($uri, $fileType, $url);
        }
      }
      return $result;
    }
  }

  /**
   * Format custom fields before inserting.
   *
   * @param int $customFieldId
   *   Custom field id.
   * @param array $customFormatted
   *   Formatted array.
   * @param mixed $value
   *   Value of custom field.
   * @param string $customFieldExtend
   *   Custom field extends.
   * @param int $customValueId
   *   Custom option value id.
   * @param int $entityId
   *   Entity id (contribution, membership...).
   * @param bool $inline
   *   Consider inline custom groups only.
   * @param bool $checkPermission
   *   If false, do not include permissioning clause.
   * @param bool $includeViewOnly
   *   If true, fields marked 'View Only' are included. Required for APIv3.
   *
   * @return array|NULL
   *   formatted custom field array
   */
  public static function formatCustomField(
    $customFieldId, &$customFormatted, $value,
    $customFieldExtend, $customValueId = NULL,
    $entityId = NULL,
    $inline = FALSE,
    $checkPermission = TRUE,
    $includeViewOnly = FALSE
  ) {
    //get the custom fields for the entity
    //subtype and basic type
    $customDataSubType = NULL;
    if ($customFieldExtend) {
      // This is the case when getFieldsForImport() requires fields
      // of subtype and its parent.CRM-5143
      // CRM-16065 - Custom field set data not being saved if contact has more than one contact sub type
      $customDataSubType = array_intersect(CRM_Contact_BAO_ContactType::subTypes(), (array) $customFieldExtend);
      if (!empty($customDataSubType) && is_array($customDataSubType)) {
        $customFieldExtend = CRM_Contact_BAO_ContactType::getBasicType($customDataSubType);
        if (is_array($customFieldExtend)) {
          $customFieldExtend = array_unique(array_values($customFieldExtend));
        }
      }
    }

    $customFields = CRM_Core_BAO_CustomField::getFields($customFieldExtend,
      FALSE,
      $inline,
      $customDataSubType,
      NULL,
      FALSE,
      FALSE,
      $checkPermission ? CRM_Core_Permission::EDIT : FALSE
    );

    if (!array_key_exists($customFieldId, $customFields)) {
      return NULL;
    }

    // return if field is a 'code' field
    if (!$includeViewOnly && !empty($customFields[$customFieldId]['is_view'])) {
      return NULL;
    }

    [$tableName, $columnName, $groupID] = self::getTableColumnGroup($customFieldId);

    if (!$customValueId &&
      // we always create new entites for is_multiple unless specified
      !$customFields[$customFieldId]['is_multiple'] &&
      $entityId
    ) {
      $query = "
SELECT id
  FROM $tableName
 WHERE entity_id={$entityId}";

      $customValueId = CRM_Core_DAO::singleValueQuery($query);
    }

    //fix checkbox, now check box always submits values
    if ($customFields[$customFieldId]['html_type'] == 'CheckBox') {
      if ($value) {
        // Note that only during merge this is not an array, and you can directly use value
        if (is_array($value)) {
          $selectedValues = [];
          foreach ($value as $selId => $val) {
            if ($val) {
              $selectedValues[] = $selId;
            }
          }
          if (!empty($selectedValues)) {
            $value = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
                $selectedValues
              ) . CRM_Core_DAO::VALUE_SEPARATOR;
          }
          else {
            $value = '';
          }
        }
      }
    }
    elseif (self::isSerialized($customFields[$customFieldId])) {
      // Select2 v3 returns a comma-separated string.
      if ($customFields[$customFieldId]['html_type'] == 'Autocomplete-Select' && is_string($value)) {
        $value = explode(',', $value);
      }

      $value = $value ? CRM_Utils_Array::implodePadded($value) : '';
    }

    if (self::isSerialized($customFields[$customFieldId]) &&
      $customFields[$customFieldId]['data_type'] == 'String' &&
      !empty($customFields[$customFieldId]['text_length']) &&
      !empty($value)
    ) {
      // lets make sure that value is less than the length, else we'll
      // be losing some data, CRM-7481
      if (strlen($value) >= $customFields[$customFieldId]['text_length']) {
        // need to do a few things here

        // 1. lets find a new length
        $newLength = $customFields[$customFieldId]['text_length'];
        $minLength = strlen($value);
        while ($newLength < $minLength) {
          $newLength = $newLength * 2;
        }

        // set the custom field meta data to have a length larger than value
        // alter the custom value table column to match this length
        CRM_Core_BAO_SchemaHandler::alterFieldLength($customFieldId, $tableName, $columnName, $newLength);
      }
    }

    $date = NULL;
    if ($customFields[$customFieldId]['data_type'] == 'Date') {
      if (!CRM_Utils_System::isNull($value)) {
        $format = $customFields[$customFieldId]['date_format'];
        $date = CRM_Utils_Date::processDate($value, NULL, FALSE, 'YmdHis', $format);
      }
      $value = $date;
    }

    if ($customFields[$customFieldId]['data_type'] == 'Float' ||
      $customFields[$customFieldId]['data_type'] == 'Money'
    ) {
      if (!$value) {
        $value = 0;
      }

      if ($customFields[$customFieldId]['data_type'] == 'Money') {
        $value = CRM_Utils_Rule::cleanMoney($value);
      }
    }

    if (($customFields[$customFieldId]['data_type'] == 'StateProvince' ||
        $customFields[$customFieldId]['data_type'] == 'Country'
      ) &&
      empty($value)
    ) {
      // CRM-3415
      $value = 0;
    }

    $fileID = NULL;

    if ($customFields[$customFieldId]['data_type'] == 'File') {
      if (empty($value)) {
        return;
      }

      $config = CRM_Core_Config::singleton();

      // If we are already passing the file id as a value then retrieve and set the file data
      if (CRM_Utils_Rule::integer($value)) {
        $fileDAO = new CRM_Core_DAO_File();
        $fileDAO->id = $value;
        $fileDAO->find(TRUE);
        if ($fileDAO->N) {
          $fileID = $value;
          $fName = $fileDAO->uri;
          $mimeType = $fileDAO->mime_type;
        }
      }
      elseif (empty($value['name'])) {
        // Happens when calling the API to update custom fields values, but the filename
        // is empty, for an existing entity (in a specific case, was from a d7-webform
        // that was updating a relationship with a File customfield, so $value['id'] was
        // not empty, but the filename was empty.
        return;
      }
      else {
        $fName = $value['name'];
        $mimeType = $value['type'];
      }

      $filename = pathinfo($fName, PATHINFO_BASENAME);

      // rename this file to go into the secure directory only if
      // user has uploaded new file not existing verfied on the basis of $fileID
      if (empty($fileID) && !rename($fName, $config->customFileUploadDir . $filename)) {
        CRM_Core_Error::statusBounce(ts('Could not move custom file to custom upload directory'));
      }

      if ($customValueId && empty($fileID)) {
        $query = "
SELECT $columnName
  FROM $tableName
 WHERE id = %1";
        $params = [1 => [$customValueId, 'Integer']];
        $fileID = CRM_Core_DAO::singleValueQuery($query, $params);
      }

      $fileDAO = new CRM_Core_DAO_File();

      if ($fileID) {
        $fileDAO->id = $fileID;
      }

      $fileDAO->uri = $filename;
      $fileDAO->mime_type = $mimeType;
      $fileDAO->upload_date = date('YmdHis');
      $fileDAO->save();
      $fileID = $fileDAO->id;
      $value = $filename;
    }

    if (!is_array($customFormatted)) {
      $customFormatted = [];
    }

    if (!array_key_exists($customFieldId, $customFormatted)) {
      $customFormatted[$customFieldId] = [];
    }

    $index = -1;
    if ($customValueId) {
      $index = $customValueId;
    }

    if (!array_key_exists($index, $customFormatted[$customFieldId])) {
      $customFormatted[$customFieldId][$index] = [];
    }
    $customFormatted[$customFieldId][$index] = [
      'id' => $customValueId > 0 ? $customValueId : NULL,
      'value' => $value,
      'type' => $customFields[$customFieldId]['data_type'],
      'custom_field_id' => $customFieldId,
      'custom_group_id' => $groupID,
      'table_name' => $tableName,
      'column_name' => $columnName,
      'file_id' => $fileID,
      // is_multiple refers to the custom group, serialize refers to the field.
      'is_multiple' => $customFields[$customFieldId]['is_multiple'],
      'serialize' => $customFields[$customFieldId]['serialize'],
    ];

    //we need to sort so that custom fields are created in the order of entry
    krsort($customFormatted[$customFieldId]);
    return $customFormatted;
  }

  /**
   * Get default custom table schema.
   *
   * @param array $params
   *
   * @return array
   */
  public static function defaultCustomTableSchema($params) {
    // add the id and extends_id
    $collation = CRM_Core_BAO_SchemaHandler::getInUseCollation();
    $characterSet = 'utf8';
    if (stripos($collation, 'utf8mb4') !== FALSE) {
      $characterSet = 'utf8mb4';
    }
    $table = [
      'name' => $params['name'],
      'is_multiple' => $params['is_multiple'],
      'attributes' => "ENGINE=InnoDB DEFAULT CHARACTER SET {$characterSet} COLLATE {$collation}",
      'fields' => [
        [
          'name' => 'id',
          'type' => 'int unsigned',
          'primary' => TRUE,
          'required' => TRUE,
          'attributes' => 'AUTO_INCREMENT',
          'comment' => 'Default MySQL primary key',
        ],
        [
          'name' => 'entity_id',
          'type' => 'int unsigned',
          'required' => TRUE,
          'comment' => 'Table that this extends',
          'fk_table_name' => $params['extends_name'],
          'fk_field_name' => 'id',
          'fk_attributes' => 'ON DELETE CASCADE',
        ],
      ],
    ];

    // If on MySQL 5.6 include ROW_FORMAT=DYNAMIC to fix unit tests
    $databaseVersion = CRM_Utils_SQL::getDatabaseVersion();
    if (version_compare($databaseVersion, '5.7', '<') && version_compare($databaseVersion, '5.6', '>=')) {
      $table['attributes'] = $table['attributes'] . ' ROW_FORMAT=DYNAMIC';
    }

    if (!$params['is_multiple']) {
      $table['indexes'] = [
        [
          'unique' => TRUE,
          'field_name_1' => 'entity_id',
        ],
      ];
    }
    return $table;
  }

  /**
   * Create custom field.
   *
   * @param CRM_Core_DAO_CustomField $field
   * @param string $operation
   */
  public static function createField($field, $operation) {
    $sql = str_repeat(' ', 8);
    $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $field->custom_group_id, 'table_name');
    $sql .= "ALTER TABLE " . $tableName;
    $sql .= self::getAlterFieldSQL($field, $operation);

    // CRM-7007: do not i18n-rewrite this query
    CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);

    $config = CRM_Core_Config::singleton();
    if ($config->logging) {
      // CRM-16717 not sure why this was originally limited to add.
      // For example custom tables can have field length changes - which need to flow through to logging.
      // Are there any modifies we DON'T was to call this function for (& shouldn't it be clever enough to cope?)
      if ($operation === 'add' || $operation === 'modify') {
        $logging = new CRM_Logging_Schema();
        $logging->fixSchemaDifferencesFor($tableName, [trim(strtoupper($operation)) => [$field->column_name]]);
      }
    }

    Civi::service('sql_triggers')->rebuild($tableName, TRUE);
  }

  /**
   * @param CRM_Core_DAO_CustomField $field
   * @param string $operation add|modify|delete
   *
   * @return bool
   */
  public static function getAlterFieldSQL($field, $operation) {
    $params = self::prepareCreateParams($field, $operation);
    // Let's suppress the required flag, since that can cause an sql issue... for unknown reasons since we are calling
    // a function only used by Custom Field creation...
    $params['required'] = FALSE;
    return CRM_Core_BAO_SchemaHandler::getFieldAlterSQL($params);
  }

  /**
   * Get query to reformat existing values for a field when changing its serialize attribute
   *
   * @param CRM_Core_DAO_CustomField $field
   * @return string
   */
  private static function getAlterSerializeSQL($field) {
    $table = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $field->custom_group_id, 'table_name');
    $col = $field->column_name;
    $sp = CRM_Core_DAO::VALUE_SEPARATOR;
    if ($field->serialize) {
      return "UPDATE `$table` SET `$col` = CONCAT('$sp', `$col`, '$sp') WHERE `$col` IS NOT NULL AND `$col` NOT LIKE '$sp%' AND `$col` != ''";
    }
    else {
      return "UPDATE `$table` SET `$col` = SUBSTRING_INDEX(SUBSTRING(`$col`, 2), '$sp', 1) WHERE `$col` LIKE '$sp%'";
    }
  }

  /**
   * Determine whether it would be safe to move a field.
   *
   * @param int $fieldID
   *   FK to civicrm_custom_field.
   * @param int $newGroupID
   *   FK to civicrm_custom_group.
   *
   * @return array
   *   array(string) or TRUE
   */
  public static function _moveFieldValidate($fieldID, $newGroupID) {
    $errors = [];

    $field = new CRM_Core_DAO_CustomField();
    $field->id = $fieldID;
    if (!$field->find(TRUE)) {
      $errors['fieldID'] = ts('Invalid ID for custom field');
      return $errors;
    }

    $oldGroup = new CRM_Core_DAO_CustomGroup();
    $oldGroup->id = $field->custom_group_id;
    if (!$oldGroup->find(TRUE)) {
      $errors['fieldID'] = ts('Invalid ID for old custom group');
      return $errors;
    }

    $newGroup = new CRM_Core_DAO_CustomGroup();
    $newGroup->id = $newGroupID;
    if (!$newGroup->find(TRUE)) {
      $errors['newGroupID'] = ts('Invalid ID for new custom group');
      return $errors;
    }

    $query = "
SELECT     b.id
FROM       civicrm_custom_field a
INNER JOIN civicrm_custom_field b
WHERE      a.id = %1
AND        a.label = b.label
AND        b.custom_group_id = %2
";
    $params = [
      1 => [$field->id, 'Integer'],
      2 => [$newGroup->id, 'Integer'],
    ];
    $count = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($count > 0) {
      $errors['newGroupID'] = ts('A field of the same label exists in the destination group');
    }

    $tableName = $oldGroup->table_name;
    $columnName = $field->column_name;

    $query = "
SELECT count(*)
FROM   $tableName
WHERE  $columnName is not null
";
    $count = CRM_Core_DAO::singleValueQuery($query);
    if ($count > 0) {
      $query = "
SELECT extends
FROM   civicrm_custom_group
WHERE  id IN ( %1, %2 )
";
      $params = [
        1 => [$oldGroup->id, 'Integer'],
        2 => [$newGroup->id, 'Integer'],
      ];

      $dao = CRM_Core_DAO::executeQuery($query, $params);
      $extends = [];
      while ($dao->fetch()) {
        $extends[] = $dao->extends;
      }
      if ($extends[0] != $extends[1]) {
        $errors['newGroupID'] = ts('The destination group extends a different entity type.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Move a custom data field from one group (table) to another.
   *
   * @param int $fieldID
   *   FK to civicrm_custom_field.
   * @param int $newGroupID
   *   FK to civicrm_custom_group.
   *
   * @throws CRM_Core_Exception
   */
  public static function moveField($fieldID, $newGroupID) {
    $validation = self::_moveFieldValidate($fieldID, $newGroupID);
    if (TRUE !== $validation) {
      throw new CRM_Core_Exception(implode(' ', $validation));
    }
    $field = new CRM_Core_DAO_CustomField();
    $field->id = $fieldID;
    $field->find(TRUE);

    $newGroup = new CRM_Core_DAO_CustomGroup();
    $newGroup->id = $newGroupID;
    $newGroup->find(TRUE);

    $oldGroup = new CRM_Core_DAO_CustomGroup();
    $oldGroup->id = $field->custom_group_id;
    $oldGroup->find(TRUE);

    $add = clone$field;
    $add->custom_group_id = $newGroup->id;
    self::createField($add, 'add');

    $sql = "INSERT INTO {$newGroup->table_name} (entity_id, `{$field->column_name}`)
            SELECT entity_id, `{$field->column_name}` FROM {$oldGroup->table_name}
            ON DUPLICATE KEY UPDATE `{$field->column_name}` = {$oldGroup->table_name}.`{$field->column_name}`
            ";
    CRM_Core_DAO::executeQuery($sql);

    $del = clone$field;
    $del->custom_group_id = $oldGroup->id;
    self::createField($del, 'delete');

    $add->save();

    CRM_Utils_System::flushCache();
  }

  /**
   * Create an option value for a custom field option group ID.
   *
   * @param array $params
   * @param string $value
   * @param \CRM_Core_DAO_OptionGroup $optionGroup
   * @param string $index
   * @param string $dataType
   */
  protected static function createOptionValue(&$params, $value, CRM_Core_DAO_OptionGroup $optionGroup, $index, $dataType) {
    if (strlen(trim($value))) {
      $optionValue = new CRM_Core_DAO_OptionValue();
      $optionValue->option_group_id = $optionGroup->id;
      $optionValue->label = $params['option_label'][$index];
      $optionValue->name = $params['option_name'][$index] ?? CRM_Utils_String::titleToVar($params['option_label'][$index]);
      switch ($dataType) {
        case 'Money':
          $optionValue->value = CRM_Utils_Rule::cleanMoney($value);
          break;

        case 'Int':
          $optionValue->value = intval($value);
          break;

        case 'Float':
          $optionValue->value = floatval($value);
          break;

        default:
          $optionValue->value = trim($value);
      }

      $optionValue->weight = $params['option_weight'][$index];
      $optionValue->is_active = $params['option_status'][$index] ?? FALSE;
      $optionValue->description = $params['option_description'][$index] ?? NULL;
      $optionValue->color = $params['option_color'][$index] ?? NULL;
      $optionValue->icon = $params['option_icon'][$index] ?? NULL;
      $optionValue->save();
    }
  }

  /**
   * Prepare for the create operation.
   *
   * Munge params, create the option values if needed.
   *
   * This could be called by a single create or a batchCreate.
   *
   * @param array $params
   *
   * @return array
   */
  protected static function prepareCreate($params) {
    $op = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($op, 'CustomField', CRM_Utils_Array::value('id', $params), $params);
    $params['is_append_field_id_to_column_name'] = !isset($params['column_name']);
    if ($op === 'create') {
      CRM_Core_DAO::setCreateDefaults($params, self::getDefaults());
      if (!isset($params['column_name'])) {
        // if add mode & column_name not present, calculate it.
        $params['column_name'] = strtolower(CRM_Utils_String::munge($params['label'], '_', 32));
      }
      if (!isset($params['name'])) {
        $params['name'] = CRM_Utils_String::munge($params['label'], '_', 64);
      }
    }
    else {
      $params['column_name'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $params['id'], 'column_name');
    }

    $htmlType = $params['html_type'] ?? NULL;
    $dataType = $params['data_type'] ?? NULL;

    if ($htmlType === 'Select Date' && empty($params['date_format'])) {
      $params['date_format'] = Civi::settings()->get('dateInputFormat');
    }

    // Checkboxes are always serialized in current schema
    if ($htmlType == 'CheckBox') {
      $params['serialize'] = CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND;
    }

    if (!empty($params['serialize'])) {
      if (isset($params['default_checkbox_option'])) {
        $defaultArray = [];
        foreach (array_keys($params['default_checkbox_option']) as $k => $v) {
          if ($params['option_value'][$v]) {
            $defaultArray[] = $params['option_value'][$v];
          }
        }

        if (!empty($defaultArray)) {
          // also add the separator before and after the value per new convention (CRM-1604)
          $params['default_value'] = CRM_Utils_Array::implodePadded($defaultArray);
        }
      }
      else {
        if (!empty($params['default_option']) && isset($params['option_value'][$params['default_option']])) {
          $params['default_value'] = $params['option_value'][$params['default_option']];
        }
      }
    }

    // create any option group & values if required
    $allowedOptionTypes = ['String', 'Int', 'Float', 'Money'];
    if ($htmlType !== 'Text' && in_array($dataType, $allowedOptionTypes, TRUE)) {
      //CRM-16659: if option_value then create an option group for this custom field.
      // An option_type of 2 would be a 'message' from the form layer not to handle
      // the option_values key. If not set then it is not ignored.
      $optionsType = (int) ($params['option_type'] ?? 0);
      if (($optionsType !== 2 && empty($params['id']))
        && (empty($params['option_group_id']) && !empty($params['option_value'])
        )
      ) {
        // first create an option group for this custom group
        $customGroupTitle = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $params['custom_group_id'], 'title');
        $optionGroup = CRM_Core_DAO_OptionGroup::writeRecord([
          'title' => "$customGroupTitle :: {$params['label']}",
          // Don't set reserved as it's not a built-in option group and may be useful for other custom fields.
          'is_reserved' => 0,
          'data_type' => $dataType,
        ]);
        $params['option_group_id'] = $optionGroup->id;
        if (!empty($params['option_value']) && is_array($params['option_value'])) {
          foreach ($params['option_value'] as $k => $v) {
            self::createOptionValue($params, $v, $optionGroup, $k, $dataType);
          }
        }
      }
    }

    // Remove option group IDs from fields changed to Text html_type.
    if ($htmlType == 'Text') {
      $params['option_group_id'] = '';
    }

    // check for orphan option groups
    if (!empty($params['option_group_id'])) {
      if (!empty($params['id'])) {
        self::fixOptionGroups($params['id'], $params['option_group_id']);
      }

      // if we do not have a default value
      // retrieve it from one of the other custom fields which use this option group
      if (empty($params['default_value'])) {
        //don't insert only value separator as default value, CRM-4579
        $defaultValue = self::getOptionGroupDefault($params['option_group_id'], !empty($params['serialize']));

        if ($defaultValue !== NULL && !CRM_Utils_System::isNull(explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaultValue))) {
          $params['default_value'] = $defaultValue;
        }
      }
    }

    // Set default textarea attributes
    if ($op == 'create' && !isset($params['attributes']) && $htmlType == 'TextArea') {
      $params['attributes'] = 'rows=4, cols=60';
    }
    return $params;
  }

  /**
   * Create database entry for custom field and related option groups.
   *
   * @param array $params
   *
   * @return CRM_Core_DAO_CustomField
   */
  protected static function createCustomFieldRecord($params) {
    $transaction = new CRM_Core_Transaction();
    $params = self::prepareCreate($params);

    $customField = new CRM_Core_DAO_CustomField();
    $customField->copyValues($params);
    $customField->save();

    //create/drop the index when we toggle the is_searchable flag
    $op = empty($params['id']) ? 'add' : 'modify';
    if ($op !== 'modify') {
      if ($params['is_append_field_id_to_column_name']) {
        $params['column_name'] .= "_{$customField->id}";
      }
      $customField->column_name = $params['column_name'];
      $customField->save();
    }

    // complete transaction - note that any table alterations include an implicit commit so this is largely meaningless.
    $transaction->commit();

    // make sure all values are present in the object for further processing
    $customField->find(TRUE);

    return $customField;
  }

  /**
   * @param $params
   * @return int|null
   */
  protected static function getChangeSerialize($params) {
    if (isset($params['serialize']) && !empty($params['id'])) {
      if ($params['serialize'] != CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $params['id'], 'serialize')) {
        return (int) $params['serialize'];
      }
    }
    return NULL;
  }

  /**
   * Move custom data from one contact to another.
   *
   * This is currently the start of a refactoring. The theory is each
   * entity could have a 'move' function with a DAO default one to fall back on.
   *
   * At the moment this only does a small part of the process - ie deleting a file field that
   * is about to be overwritten. However, the goal is the whole process around the move for
   * custom data should be in here.
   *
   * This is currently called by the merge class but it makes sense that api could
   * expose move actions as moving (e.g) contributions feels like a common
   * ask that should be handled by the form layer.
   *
   * @param int $oldContactID
   * @param int $newContactID
   * @param int[] $fieldIDs
   *   Optional list field ids to move.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function move($oldContactID, $newContactID, $fieldIDs) {
    if (empty($fieldIDs)) {
      return;
    }
    $fields = civicrm_api3('CustomField', 'get', ['id' => ['IN' => $fieldIDs], 'return' => ['custom_group_id.is_multiple', 'custom_group_id.table_name', 'column_name', 'data_type'], 'options' => ['limit' => 0]])['values'];
    $return = [];
    foreach ($fieldIDs as $fieldID) {
      $return[] = 'custom_' . $fieldID;
    }
    $oldContact = civicrm_api3('Contact', 'getsingle', ['id' => $oldContactID, 'return' => $return]);
    $newContact = civicrm_api3('Contact', 'getsingle', ['id' => $newContactID, 'return' => $return]);

    // The moveAllBelongings function has functionality to move custom fields. It doesn't work very well...
    // @todo handle all fields here but more immediately Country since that is broken at the moment.
    $fieldTypesNotHandledInMergeAttempt = ['File'];
    foreach ($fields as $field) {
      $isMultiple = !empty($field['custom_group_id.is_multiple']);
      if ($field['data_type'] === 'File' && !$isMultiple) {
        if (!empty($oldContact['custom_' . $field['id']]) && !empty($newContact['custom_' . $field['id']])) {
          CRM_Core_BAO_File::deleteFileReferences($oldContact['custom_' . $field['id']], $oldContactID, $field['id']);
        }
        if (!empty($oldContact['custom_' . $field['id']])) {
          CRM_Core_DAO::executeQuery("
            UPDATE civicrm_entity_file
            SET entity_id = $newContactID
            WHERE file_id = {$oldContact['custom_' . $field['id']]}"
          );
        }
      }
      if (in_array($field['data_type'], $fieldTypesNotHandledInMergeAttempt) && !$isMultiple) {
        CRM_Core_DAO::executeQuery(
          "INSERT INTO {$field['custom_group_id.table_name']} (entity_id, `{$field['column_name']}`)
          VALUES ($newContactID, {$oldContact['custom_' . $field['id']]})
          ON DUPLICATE KEY UPDATE
          `{$field['column_name']}` = {$oldContact['custom_' . $field['id']]}
        ");
      }
    }
  }

  /**
   * Get the database table name and column name for a custom field.
   *
   * @param int $fieldID
   *   The fieldID of the custom field.
   *
   * @return array
   *   fatal is fieldID does not exists, else array of tableName, columnName
   * @throws \CRM_Core_Exception
   */
  public static function getTableColumnGroup($fieldID): array {
    global $tsLocale;
    // check if we can get the field values from the system cache
    $cacheKey = "CRM_Core_DAO_CustomField_CustomGroup_TableColumn_{$fieldID}_$tsLocale";
    if (Civi::cache('metadata')->has($cacheKey)) {
      return Civi::cache('metadata')->get($cacheKey);
    }

    $query = '
SELECT cg.table_name, cf.column_name, cg.id
FROM   civicrm_custom_group cg,
     civicrm_custom_field cf
WHERE  cf.custom_group_id = cg.id
AND    cf.id = %1';
    $params = [1 => [$fieldID, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if (!$dao->fetch()) {
      throw new CRM_Core_Exception('Cannot find table and column information for Custom Field ' . $fieldID);
    }
    $fieldValues = [$dao->table_name, $dao->column_name, $dao->id];
    Civi::cache('metadata')->set($cacheKey, $fieldValues);

    return $fieldValues;
  }

  /**
   * Get custom option groups.
   *
   * @deprecated Use the API OptionGroup.get
   *
   * @param array $includeFieldIds
   *   Ids of custom fields for which option groups must be included.
   *
   * Currently this is required in the cases where option groups are to be included
   * for inactive fields : CRM-5369
   *
   * @return mixed
   */
  public static function customOptionGroup($includeFieldIds = NULL) {
    static $customOptionGroup = NULL;

    $cacheKey = (empty($includeFieldIds)) ? 'onlyActive' : 'force';
    if ($cacheKey == 'force') {
      $customOptionGroup[$cacheKey] = NULL;
    }

    if (empty($customOptionGroup[$cacheKey])) {
      $whereClause = '( g.is_active = 1 AND f.is_active = 1 )';

      //support for single as well as array format.
      if (!empty($includeFieldIds)) {
        if (is_array($includeFieldIds)) {
          $includeFieldIds = implode(',', $includeFieldIds);
        }
        $whereClause .= "OR f.id IN ( $includeFieldIds )";
      }

      $query = "
    SELECT  g.id, g.title
      FROM  civicrm_option_group g
INNER JOIN  civicrm_custom_field f ON ( g.id = f.option_group_id )
     WHERE  {$whereClause}";

      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $customOptionGroup[$cacheKey][$dao->id] = $dao->title;
      }
    }

    return $customOptionGroup[$cacheKey];
  }

  /**
   * Get defaults for new entity.
   *
   * @return array
   */
  public static function getDefaults() {
    return [
      'is_required' => FALSE,
      'is_searchable' => FALSE,
      'in_selector' => FALSE,
      'is_search_range' => FALSE,
      //CRM-15792 - Custom field gets disabled if is_active not set
      // this would ideally be a mysql default.
      'is_active' => TRUE,
      'is_view' => FALSE,
    ];
  }

  /**
   * Fix orphan groups.
   *
   * @param int $customFieldId
   *   Custom field id.
   * @param int $optionGroupId
   *   Option group id.
   */
  public static function fixOptionGroups($customFieldId, $optionGroupId) {
    // check if option group belongs to any custom Field else delete
    // get the current option group
    $currentOptionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
      $customFieldId,
      'option_group_id'
    );
    // get the updated option group
    // if both are same return
    if (!$currentOptionGroupId || $currentOptionGroupId == $optionGroupId) {
      return;
    }

    // check if option group is related to any other field
    self::checkOptionGroup($currentOptionGroupId);
  }

  /**
   * Check if option group is related to more than one custom field.
   *
   * @param int $optionGroupId
   *   Option group id.
   */
  public static function checkOptionGroup($optionGroupId) {
    $query = "
SELECT count(*)
FROM   civicrm_custom_field
WHERE  option_group_id = {$optionGroupId}";

    $count = CRM_Core_DAO::singleValueQuery($query);

    if ($count < 2) {
      //delete the option group
      CRM_Core_BAO_OptionGroup::del($optionGroupId);
    }
  }

  /**
   * Get option group default.
   *
   * @param int $optionGroupId
   * @param bool $serialize
   *
   * @return null|string
   */
  public static function getOptionGroupDefault($optionGroupId, $serialize) {
    $query = "
SELECT   default_value, serialize
FROM     civicrm_custom_field
WHERE    option_group_id = {$optionGroupId}
AND      default_value IS NOT NULL";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if ($dao->serialize == $serialize) {
        return $dao->default_value;
      }
      $defaultValue = $dao->default_value;
    }

    // Convert serialization
    if (isset($defaultValue) && $serialize) {
      return CRM_Utils_Array::implodePadded([$defaultValue]);
    }
    elseif (isset($defaultValue)) {
      return CRM_Utils_Array::explodePadded($defaultValue)[0];
    }
    return NULL;
  }

  /**
   * Post process function.
   *
   * @param array $params
   * @param int $entityID
   * @param string $customFieldExtends
   * @param bool $inline
   * @param bool $checkPermissions
   *
   * @return array
   */
  public static function postProcess(
    &$params,
    $entityID,
    $customFieldExtends,
    $inline = FALSE,
    $checkPermissions = TRUE
  ) {
    $customData = [];

    foreach ($params as $key => $value) {
      if ($customFieldInfo = CRM_Core_BAO_CustomField::getKeyID($key, TRUE)) {

        // for autocomplete transfer hidden value instead of label
        if ($params[$key] && isset($params[$key . '_id'])) {
          $value = $params[$key . '_id'];
        }

        // we need to append time with date
        if ($params[$key] && isset($params[$key . '_time'])) {
          $value .= ' ' . $params[$key . '_time'];
        }

        CRM_Core_BAO_CustomField::formatCustomField($customFieldInfo[0],
          $customData,
          $value,
          $customFieldExtends,
          $customFieldInfo[1],
          $entityID,
          $inline,
          $checkPermissions
        );
      }
    }
    return $customData;
  }

  /**
   * Get custom field ID from field/group name/title.
   *
   * @param string $fieldName Field name or label
   * @param string|null $groupName (Optional) Group name or label
   * @param bool $fullString Whether to return "custom_123" or "123"
   *
   * @return string|int|null
   * @throws \CRM_Core_Exception
   */
  public static function getCustomFieldID($fieldName, $groupName = NULL, $fullString = FALSE) {
    $cacheKey = $groupName . '.' . $fieldName;
    if (!isset(Civi::$statics['CRM_Core_BAO_CustomField'][$cacheKey])) {
      $customFieldParams = [
        'name' => $fieldName,
        'label' => $fieldName,
        'options' => ['or' => [["name", "label"]]],
      ];

      if ($groupName) {
        $customFieldParams['custom_group_id.name'] = $groupName;
        $customFieldParams['custom_group_id.title'] = $groupName;
        $customFieldParams['options'] = ['or' => [["name", "label"], ["custom_group_id.name", "custom_group_id.title"]]];
      }

      $field = civicrm_api3('CustomField', 'get', $customFieldParams);

      if (empty($field['id'])) {
        Civi::$statics['CRM_Core_BAO_CustomField'][$cacheKey]['id'] = NULL;
        Civi::$statics['CRM_Core_BAO_CustomField'][$cacheKey]['string'] = NULL;
      }
      else {
        Civi::$statics['CRM_Core_BAO_CustomField'][$cacheKey]['id'] = $field['id'];
        Civi::$statics['CRM_Core_BAO_CustomField'][$cacheKey]['string'] = 'custom_' . $field['id'];
      }
    }

    if ($fullString) {
      return Civi::$statics['CRM_Core_BAO_CustomField'][$cacheKey]['string'];
    }
    return Civi::$statics['CRM_Core_BAO_CustomField'][$cacheKey]['id'];
  }

  /**
   * Given ID of a custom field, return its name as well as the name of the custom group it belongs to.
   *
   * @param array $ids
   *
   * @return array
   */
  public static function getNameFromID($ids) {
    if (is_array($ids)) {
      $ids = implode(',', $ids);
    }
    $sql = "
SELECT     f.id, f.name AS field_name, f.label AS field_label, g.name AS group_name, g.title AS group_title
FROM       civicrm_custom_field f
INNER JOIN civicrm_custom_group g ON f.custom_group_id = g.id
WHERE      f.id IN ($ids)";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $result = [];
    while ($dao->fetch()) {
      $result[$dao->id] = [
        'field_name' => $dao->field_name,
        'field_label' => $dao->field_label,
        'group_name' => $dao->group_name,
        'group_title' => $dao->group_title,
      ];
    }
    return $result;
  }

  /**
   * Validate custom data.
   *
   * @param array $params
   *   Custom data submitted.
   *   ie array( 'custom_1' => 'validate me' );
   *
   * @return array
   *   validation errors.
   */
  public static function validateCustomData($params) {
    $errors = [];
    if (!is_array($params) || empty($params)) {
      return $errors;
    }

    //pick up profile fields.
    $profileFields = [];
    $ufGroupId = $params['ufGroupId'] ?? NULL;
    if ($ufGroupId) {
      $profileFields = CRM_Core_BAO_UFGroup::getFields($ufGroupId,
        FALSE,
        CRM_Core_Action::VIEW
      );
    }

    //lets start w/ params.
    foreach ($params as $key => $value) {
      $customFieldID = self::getKeyID($key);
      if (!$customFieldID) {
        continue;
      }

      //load the structural info for given field.
      $field = new CRM_Core_DAO_CustomField();
      $field->id = $customFieldID;
      if (!$field->find(TRUE)) {
        continue;
      }
      $dataType = $field->data_type;

      $profileField = CRM_Utils_Array::value($key, $profileFields, []);
      $fieldTitle = $profileField['title'] ?? NULL;
      $isRequired = $profileField['is_required'] ?? NULL;
      if (!$fieldTitle) {
        $fieldTitle = $field->label;
      }

      //no need to validate.
      if (CRM_Utils_System::isNull($value) && !$isRequired) {
        continue;
      }

      //lets validate first for required field.
      if ($isRequired && CRM_Utils_System::isNull($value)) {
        $errors[$key] = ts('%1 is a required field.', [1 => $fieldTitle]);
        continue;
      }

      //now time to take care of custom field form rules.
      $ruleName = $errorMsg = NULL;
      switch ($dataType) {
        case 'Int':
          $ruleName = 'integer';
          $errorMsg = ts('%1 must be an integer (whole number).',
            array(1 => $fieldTitle)
          );
          break;

        case 'Money':
          $ruleName = 'money';
          $errorMsg = ts('%1 must in proper money format. (decimal point/comma/space is allowed).',
            array(1 => $fieldTitle)
          );
          break;

        case 'Float':
          $ruleName = 'numeric';
          $errorMsg = ts('%1 must be a number (with or without decimal point).',
            array(1 => $fieldTitle)
          );
          break;

        case 'Link':
          $ruleName = 'wikiURL';
          $errorMsg = ts('%1 must be valid Website.',
            array(1 => $fieldTitle)
          );
          break;
      }

      if ($ruleName && !CRM_Utils_System::isNull($value)) {
        $valid = FALSE;
        $funName = "CRM_Utils_Rule::{$ruleName}";
        if (is_callable($funName)) {
          $valid = call_user_func($funName, $value);
        }
        if (!$valid) {
          $errors[$key] = $errorMsg;
        }
      }
    }

    return $errors;
  }

  /**
   * Is this field a multi record field.
   *
   * @param int $customId
   *
   * @return bool
   */
  public static function isMultiRecordField($customId) {
    $isMultipleWithGid = FALSE;
    if (!is_numeric($customId)) {
      $customId = self::getKeyID($customId);
    }
    if (is_numeric($customId)) {
      $sql = "SELECT cg.id cgId
 FROM civicrm_custom_group cg
 INNER JOIN civicrm_custom_field cf
 ON cg.id = cf.custom_group_id
WHERE cf.id = %1 AND cg.is_multiple = 1";
      $params[1] = [$customId, 'Integer'];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        if ($dao->cgId) {
          $isMultipleWithGid = $dao->cgId;
        }
      }
    }

    return $isMultipleWithGid;
  }

  /**
   * Does this field type have any select options?
   *
   * @param array $field
   *
   * @return bool
   */
  public static function hasOptions($field) {
    // Fields retrieved via api are an array, or from the dao are an object. We'll accept either.
    $field = (array) $field;
    // This will include boolean fields with Yes/No options.
    if (in_array($field['html_type'], ['Radio', 'CheckBox'])) {
      return TRUE;
    }
    // Do this before the "Select" string search because date fields have a "Select Date" html_type
    // and contactRef fields have an "Autocomplete-Select" html_type - contacts are an FK not an option list.
    if (in_array($field['data_type'], ['ContactReference', 'Date'])) {
      return FALSE;
    }
    if (strpos($field['html_type'], 'Select') !== FALSE) {
      return TRUE;
    }
    return !empty($field['option_group_id']);
  }

  /**
   * Does this field store a serialized string?
   *
   * @param CRM_Core_DAO_CustomField|array $field
   *
   * @return bool
   */
  public static function isSerialized($field) {
    // Fields retrieved via api are an array, or from the dao are an object. We'll accept either.
    $html_type = is_object($field) ? $field->html_type : $field['html_type'];
    // APIv3 has a "legacy" mode where it returns old-style html_type of "Multi-Select"
    // If anyone is using this function in conjunction with legacy api output, we'll accomodate:
    if ($html_type === 'CheckBox' || strpos($html_type, 'Multi') !== FALSE) {
      return TRUE;
    }
    // Otherwise this is the new standard as of 5.27
    return is_object($field) ? !empty($field->serialize) : !empty($field['serialize']);
  }

  /**
   * Get api entity for this field
   *
   * @return string
   */
  public function getEntity() {
    $entity = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->custom_group_id, 'extends');
    return in_array($entity, CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE) ? 'Contact' : $entity;
  }

  /**
   * Set pseudoconstant properties for field metadata.
   *
   * @param array $field
   * @param string|null $optionGroupName
   */
  private static function getOptionsForField(&$field, $optionGroupName) {
    if ($optionGroupName) {
      $field['pseudoconstant'] = [
        'optionGroupName' => $optionGroupName,
        'optionEditPath' => 'civicrm/admin/options/' . $optionGroupName,
      ];
    }
    elseif ($field['data_type'] == 'Boolean') {
      $field['pseudoconstant'] = [
        'callback' => 'CRM_Core_SelectValues::boolean',
      ];
    }
    elseif ($field['data_type'] == 'Country') {
      $field['pseudoconstant'] = [
        'table' => 'civicrm_country',
        'keyColumn' => 'id',
        'labelColumn' => 'name',
        'nameColumn' => 'iso_code',
      ];
    }
    elseif ($field['data_type'] == 'StateProvince') {
      $field['pseudoconstant'] = [
        'table' => 'civicrm_state_province',
        'keyColumn' => 'id',
        'labelColumn' => 'name',
      ];
    }
  }

  /**
   * Prepare params for the create operation.
   *
   * @param CRM_Core_DAO_CustomField $field
   * @param string $operation
   *   add|modify|delete
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function prepareCreateParams($field, $operation): array {
    $tableName = CRM_Core_DAO::getFieldValue(
      'CRM_Core_DAO_CustomGroup',
      $field->custom_group_id,
      'table_name'
    );

    $params = [
      'table_name' => $tableName,
      'operation' => $operation,
      'name' => $field->column_name,
      'type' => CRM_Core_BAO_CustomValueTable::fieldToSQLType(
        $field->data_type,
        $field->text_length
      ),
      'required' => $field->is_required,
      'searchable' => $field->is_searchable,
    ];

    // For adding/dropping FK constraints
    $params['fkName'] = CRM_Core_BAO_SchemaHandler::getIndexName($tableName, $field->column_name);

    $fkFields = [
      'Country' => 'civicrm_country',
      'StateProvince' => 'civicrm_state_province',
      'ContactReference' => 'civicrm_contact',
      'File' => 'civicrm_file',
    ];
    if (isset($fkFields[$field->data_type])) {
      // Serialized fields store value-separated strings which are incompatible with FK constraints
      if (!$field->serialize) {
        $params['fk_table_name'] = $fkFields[$field->data_type];
        $params['fk_field_name'] = 'id';
        $params['fk_attributes'] = 'ON DELETE SET NULL';
      }
    }
    if ($field->serialize) {
      // Ensure length is at least 255, but allow it to go higher.
      $text_length = intval($field->text_length) < 255 ? 255 : $field->text_length;
      $params['type'] = 'varchar(' . $text_length . ')';
    }
    if (isset($field->default_value)) {
      $params['default'] = "'{$field->default_value}'";
    }
    return $params;
  }

}
