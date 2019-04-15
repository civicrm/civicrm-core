<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Business objects for managing custom data fields.
 */
class CRM_Core_BAO_CustomField extends CRM_Core_DAO_CustomField {

  /**
   * Array for valid combinations of data_type & descriptions.
   *
   * @var array
   */
  public static $_dataType = NULL;

  /**
   * Array for valid combinations of data_type & html_type.
   *
   * @var array
   */
  public static $_dataToHtml = NULL;

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
  public static function &dataType() {
    if (!(self::$_dataType)) {
      self::$_dataType = array(
        'String' => ts('Alphanumeric'),
        'Int' => ts('Integer'),
        'Float' => ts('Number'),
        'Money' => ts('Money'),
        'Memo' => ts('Note'),
        'Date' => ts('Date'),
        'Boolean' => ts('Yes or No'),
        'StateProvince' => ts('State/Province'),
        'Country' => ts('Country'),
        'File' => ts('File'),
        'Link' => ts('Link'),
        'ContactReference' => ts('Contact Reference'),
      );
    }
    return self::$_dataType;
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
   * Get data to html array.
   *
   * (Does this caching achieve anything?)
   *
   * @return array
   */
  public static function dataToHtml() {
    if (!self::$_dataToHtml) {
      self::$_dataToHtml = array(
        array(
          'Text' => 'Text',
          'Select' => 'Select',
          'Radio' => 'Radio',
          'CheckBox' => 'CheckBox',
          'Multi-Select' => 'Multi-Select',
          'Autocomplete-Select' => 'Autocomplete-Select',
        ),
        array('Text' => 'Text', 'Select' => 'Select', 'Radio' => 'Radio'),
        array('Text' => 'Text', 'Select' => 'Select', 'Radio' => 'Radio'),
        array('Text' => 'Text', 'Select' => 'Select', 'Radio' => 'Radio'),
        array('TextArea' => 'TextArea', 'RichTextEditor' => 'RichTextEditor'),
        array('Date' => 'Select Date'),
        array('Radio' => 'Radio'),
        array('StateProvince' => 'Select State/Province', 'Multi-Select' => 'Multi-Select State/Province'),
        array('Country' => 'Select Country', 'Multi-Select' => 'Multi-Select Country'),
        array('File' => 'File'),
        array('Link' => 'Link'),
        array('ContactReference' => 'Autocomplete-Select'),
      );
    }
    return self::$_dataToHtml;
  }

  /**
   * Takes an associative array and creates a custom field object.
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Core_DAO_CustomField
   */
  public static function create(&$params) {
    $origParams = array_merge(array(), $params);

    $op = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($op, 'CustomField', CRM_Utils_Array::value('id', $params), $params);

    if ($op == 'create') {
      if (!isset($params['column_name'])) {
        // if add mode & column_name not present, calculate it.
        $params['column_name'] = strtolower(CRM_Utils_String::munge($params['label'], '_', 32));
      }
      if (!isset($params['name'])) {
        $params['name'] = CRM_Utils_String::munge($params['label'], '_', 64);
      }
    }
    else {
      $params['column_name'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
        $params['id'],
        'column_name'
      );
    }
    $columnName = $params['column_name'];

    $indexExist = FALSE;
    //as during create if field is_searchable we had created index.
    if (!empty($params['id'])) {
      $indexExist = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $params['id'], 'is_searchable');
    }

    switch (CRM_Utils_Array::value('html_type', $params)) {
      case 'Select Date':
        if (empty($params['date_format'])) {
          $config = CRM_Core_Config::singleton();
          $params['date_format'] = $config->dateInputFormat;
        }
        break;

      case 'CheckBox':
      case 'Multi-Select':
        if (isset($params['default_checkbox_option'])) {
          $tempArray = array_keys($params['default_checkbox_option']);
          $defaultArray = array();
          foreach ($tempArray as $k => $v) {
            if ($params['option_value'][$v]) {
              $defaultArray[] = $params['option_value'][$v];
            }
          }

          if (!empty($defaultArray)) {
            // also add the separator before and after the value per new convention (CRM-1604)
            $params['default_value'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $defaultArray) . CRM_Core_DAO::VALUE_SEPARATOR;
          }
        }
        else {
          if (!empty($params['default_option']) && isset($params['option_value'][$params['default_option']])
          ) {
            $params['default_value'] = $params['option_value'][$params['default_option']];
          }
        }
        break;
    }

    $transaction = new CRM_Core_Transaction();

    $htmlType = CRM_Utils_Array::value('html_type', $params);
    $dataType = CRM_Utils_Array::value('data_type', $params);
    $allowedOptionTypes = array('String', 'Int', 'Float', 'Money');

    // create any option group & values if required
    if ($htmlType != 'Text' && in_array($dataType, $allowedOptionTypes)
    ) {
      //CRM-16659: if option_value then create an option group for this custom field.
      if ($params['option_type'] == 1 && (empty($params['option_group_id']) || !empty($params['option_value']))) {
        // first create an option group for this custom group
        $optionGroup = new CRM_Core_DAO_OptionGroup();
        $optionGroup->name = "{$columnName}_" . date('YmdHis');
        $optionGroup->title = $params['label'];
        $optionGroup->is_active = 1;
        // Don't set reserved as it's not a built-in option group and may be useful for other custom fields.
        $optionGroup->is_reserved = 0;
        $optionGroup->data_type = $dataType;
        $optionGroup->save();
        $params['option_group_id'] = $optionGroup->id;
        if (!empty($params['option_value']) && is_array($params['option_value'])) {
          foreach ($params['option_value'] as $k => $v) {
            if (strlen(trim($v))) {
              $optionValue = new CRM_Core_DAO_OptionValue();
              $optionValue->option_group_id = $optionGroup->id;
              $optionValue->label = $params['option_label'][$k];
              $optionValue->name = CRM_Utils_String::titleToVar($params['option_label'][$k]);
              switch ($dataType) {
                case 'Money':
                  $optionValue->value = CRM_Utils_Rule::cleanMoney($v);
                  break;

                case 'Int':
                  $optionValue->value = intval($v);
                  break;

                case 'Float':
                  $optionValue->value = floatval($v);
                  break;

                default:
                  $optionValue->value = trim($v);
              }

              $optionValue->weight = $params['option_weight'][$k];
              $optionValue->is_active = CRM_Utils_Array::value($k, $params['option_status'], FALSE);
              $optionValue->save();
            }
          }
        }
      }
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
        $defaultValue = self::getOptionGroupDefault($params['option_group_id'],
          $htmlType
        );

        if (!CRM_Utils_System::isNull(explode(CRM_Core_DAO::VALUE_SEPARATOR,
          $defaultValue
        ))
        ) {
          $params['default_value'] = $defaultValue;
        }
      }
    }

    // since we need to save option group id :)
    if (!isset($params['attributes']) && strtolower($htmlType) == 'textarea') {
      $params['attributes'] = 'rows=4, cols=60';
    }

    $customField = new CRM_Core_DAO_CustomField();
    $customField->copyValues($params);
    if ($op == 'create') {
      $customField->is_required = CRM_Utils_Array::value('is_required', $params, FALSE);
      $customField->is_searchable = CRM_Utils_Array::value('is_searchable', $params, FALSE);
      $customField->in_selector = CRM_Utils_Array::value('in_selector', $params, FALSE);
      $customField->is_search_range = CRM_Utils_Array::value('is_search_range', $params, FALSE);
      //CRM-15792 - Custom field gets disabled if is_active not set
      $customField->is_active = CRM_Utils_Array::value('is_active', $params, TRUE);
      $customField->is_view = CRM_Utils_Array::value('is_view', $params, FALSE);
    }
    $customField->save();

    // make sure all values are present in the object for further processing
    $customField->find(TRUE);

    $triggerRebuild = CRM_Utils_Array::value('triggerRebuild', $params, TRUE);
    //create/drop the index when we toggle the is_searchable flag
    if ($op == 'edit') {
      self::createField($customField, 'modify', $indexExist, $triggerRebuild);
    }
    else {
      if (!isset($origParams['column_name'])) {
        $columnName .= "_{$customField->id}";
        $params['column_name'] = $columnName;
      }
      $customField->column_name = $columnName;
      $customField->save();
      // make sure all values are present in the object
      $customField->find(TRUE);

      $indexExist = FALSE;
      self::createField($customField, 'add', $indexExist, $triggerRebuild);
    }

    // complete transaction
    $transaction->commit();

    CRM_Utils_Hook::post($op, 'CustomField', $customField->id, $customField);

    CRM_Utils_System::flushCache();

    return $customField;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_CustomField
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $defaults);
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
    if (!$this->data_type || !$this->custom_group_id) {
      $this->find(TRUE);
    }

    // This will hold the list of options in format key => label
    $options = [];

    if (!empty($this->option_group_id)) {
      $options = CRM_Core_OptionGroup::valuesByID(
        $this->option_group_id,
        FALSE,
        FALSE,
        FALSE,
        'label',
        !($context == 'validate' || $context == 'get')
      );
    }
    elseif ($this->data_type === 'StateProvince') {
      $options = CRM_Core_Pseudoconstant::stateProvince();
    }
    elseif ($this->data_type === 'Country') {
      $options = $context == 'validate' ? CRM_Core_Pseudoconstant::countryIsoCode() : CRM_Core_Pseudoconstant::country();
    }
    elseif ($this->data_type === 'Boolean') {
      $options = $context == 'validate' ? array(0, 1) : CRM_Core_SelectValues::boolean();
    }
    CRM_Utils_Hook::customFieldOptions($this->id, $options, FALSE);
    CRM_Utils_Hook::fieldOptions($this->getEntity(), "custom_{$this->id}", $options, array('context' => $context));
    return $options;
  }

  /**
   * Store and return an array of all active custom fields.
   *
   * @param string $customDataType
   *   Type of Custom Data; empty is a synonym for "all contact data types".
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
   * @param bool $checkPermission
   *   If false, do not include permissioning clause.
   *
   * @return array
   *   an array of active custom fields.
   */
  public static function &getFields(
    $customDataType = 'Individual',
    $showAll = FALSE,
    $inline = FALSE,
    $customDataSubType = NULL,
    $customDataSubName = NULL,
    $onlyParent = FALSE,
    $onlySubType = FALSE,
    $checkPermission = TRUE
  ) {
    if (empty($customDataType)) {
      $customDataType = array('Contact', 'Individual', 'Organization', 'Household');
    }
    if ($customDataType && !is_array($customDataType)) {

      if (in_array($customDataType, CRM_Contact_BAO_ContactType::subTypes())) {
        // This is the case when getFieldsForImport() requires fields
        // limited strictly to a subtype.
        $customDataSubType = $customDataType;
        $customDataType = CRM_Contact_BAO_ContactType::getBasicType($customDataType);
        $onlySubType = TRUE;
      }

      if (in_array($customDataType, array_keys(CRM_Core_SelectValues::customGroupExtends()))) {
        // this makes the method flexible to support retrieving fields
        // for multiple extends value.
        $customDataType = array($customDataType);
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
    $cacheKey .= $checkPermission ? '_1_' : '_0_';

    $cgTable = CRM_Core_DAO_CustomGroup::getTableName();

    // also get the permission stuff here
    if ($checkPermission) {
      $permissionClause = CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW,
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
        self::$_importFields = array();
      }

      // check if we can retrieve from database cache
      $fields = CRM_Core_BAO_Cache::getItem('contact fields', "custom importableFields $cacheKey");

      if ($fields === NULL) {
        $cfTable = self::getTableName();

        $extends = '';
        if (is_array($customDataType)) {
          $value = NULL;
          foreach ($customDataType as $dataType) {
            if (in_array($dataType, array_keys(CRM_Core_SelectValues::customGroupExtends()))) {
              if (in_array($dataType, array('Individual', 'Household', 'Organization'))) {
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
          self::$_importFields[$cacheKey] = array();
          return self::$_importFields[$cacheKey];
        }

        if ($onlyParent) {
          $extends .= " AND $cgTable.extends_entity_column_value IS NULL AND $cgTable.extends_entity_column_id IS NULL ";
        }

        $query = "SELECT $cfTable.id, $cfTable.label,
                            $cgTable.title,
                            $cfTable.data_type,
                            $cfTable.html_type,
                            $cfTable.default_value,
                            $cfTable.options_per_line, $cfTable.text_length,
                            $cfTable.custom_group_id,
                            $cfTable.is_required,
                            $cfTable.column_name,
                            $cgTable.extends, $cfTable.is_search_range,
                            $cgTable.extends_entity_column_value,
                            $cgTable.extends_entity_column_id,
                            $cfTable.is_view,
                            $cfTable.option_group_id,
                            $cfTable.date_format,
                            $cfTable.time_format,
                            $cgTable.is_multiple,
                            $cgTable.table_name,
                            og.name as option_group_name
                     FROM $cfTable
                     INNER JOIN $cgTable
                       ON $cfTable.custom_group_id = $cgTable.id
                     LEFT JOIN civicrm_option_group og
                       ON $cfTable.option_group_id = og.id
                     WHERE ( 1 ) ";

        if (!$showAll) {
          $query .= " AND $cfTable.is_active = 1 AND $cgTable.is_active = 1 ";
        }

        if ($inline) {
          $query .= " AND $cgTable.style = 'Inline' ";
        }

        //get the custom fields for specific type in
        //combination with fields those support any type.
        if (!empty($customDataSubType)) {
          $subtypeClause = array();
          foreach ($customDataSubType as $subtype) {
            $subtype = CRM_Core_DAO::VALUE_SEPARATOR . $subtype . CRM_Core_DAO::VALUE_SEPARATOR;
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
          $permissionClause = CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW,
            "{$cgTable}.", TRUE
          );
        }
        else {
          $permissionClause = '(1)';
        }

        $query .= " $extends AND $permissionClause
                        ORDER BY $cgTable.weight, $cgTable.title,
                                 $cfTable.weight, $cfTable.label";

        $dao = CRM_Core_DAO::executeQuery($query);

        $fields = array();
        while (($dao->fetch()) != NULL) {
          $fields[$dao->id]['label'] = $dao->label;
          $fields[$dao->id]['groupTitle'] = $dao->title;
          $fields[$dao->id]['data_type'] = $dao->data_type;
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
          self::getOptionsForField($fields[$dao->id], $dao->option_group_name);
        }

        CRM_Core_BAO_Cache::setItem($fields,
          'contact fields',
          "custom importableFields $cacheKey"
        );
      }
      self::$_importFields[$cacheKey] = $fields;
    }

    return self::$_importFields[$cacheKey];
  }

  /**
   * Return the field ids and names (with groups) for import purpose.
   *
   * @param int|string $contactType Contact type
   * @param bool $showAll
   *   If true returns all fields (includes disabled fields).
   * @param bool $onlyParent
   *   Return fields ONLY related to basic types.
   * @param bool $search
   *   When called from search and multiple records need to be returned.
   * @param bool $checkPermission
   *   If false, do not include permissioning clause.
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

    $importableFields = array();
    foreach ($fields as $id => $values) {
      // for now we should not allow multiple fields in profile / export etc, hence unsetting
      if (!$search &&
        (!empty($values['is_multiple']) && !$withMultiple)
      ) {
        continue;
      }

      /* generate the key for the fields array */

      $key = "custom_$id";

      $regexp = preg_replace('/[.,;:!?]/', '', CRM_Utils_Array::value(0, $values));
      $importableFields[$key] = array(
        'name' => $key,
        'type' => CRM_Utils_Array::value(CRM_Utils_Array::value('data_type', $values), self::dataToType()),
        'title' => CRM_Utils_Array::value('label', $values),
        'headerPattern' => '/' . preg_quote($regexp, '/') . '/',
        'import' => 1,
        'custom_field_id' => $id,
        'options_per_line' => CRM_Utils_Array::value('options_per_line', $values),
        'text_length' => CRM_Utils_Array::value('text_length', $values, 255),
        'data_type' => CRM_Utils_Array::value('data_type', $values),
        'html_type' => CRM_Utils_Array::value('html_type', $values),
        'is_search_range' => CRM_Utils_Array::value('is_search_range', $values),
      );

      // CRM-6681, pass date and time format when html_type = Select Date
      if (CRM_Utils_Array::value('html_type', $values) == 'Select Date') {
        $importableFields[$key]['date_format'] = CRM_Utils_Array::value('date_format', $values);
        $importableFields[$key]['time_format'] = CRM_Utils_Array::value('time_format', $values);
      }
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
    $match = array();
    if (preg_match('/^custom_(\d+)_?(-?\d+)?$/', $key, $match)) {
      if (!$all) {
        return $match[1];
      }
      else {
        return array(
          $match[1],
          CRM_Utils_Array::value(2, $match),
        );
      }
    }
    return $all ? array(NULL, NULL) : NULL;
  }

  /**
   * Use the cache to get all values of a specific custom field.
   *
   * @param int $fieldID
   *   The custom field ID.
   *
   * @return CRM_Core_BAO_CustomField
   *   The field object.
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
        CRM_Core_Error::fatal();
      }

      $fieldValues = array();
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
   * @throws \CiviCRM_API3_Exception
   */
  public static function addQuickFormElement(
    $qf, $elementName, $fieldId, $useRequired = TRUE, $search = FALSE, $label = NULL
  ) {
    $field = self::getFieldObject($fieldId);
    $widget = $field->html_type;
    $element = NULL;
    $customFieldAttributes = array();

    // Custom field HTML should indicate group+field name
    $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $field->custom_group_id);
    $dataCrmCustomVal = $groupName . ':' . $field->name;
    $dataCrmCustomAttr = 'data-crm-custom="' . $dataCrmCustomVal . '"';
    $field->attributes .= $dataCrmCustomAttr;

    // Fixed for Issue CRM-2183
    if ($widget == 'TextArea' && $search) {
      $widget = 'Text';
    }

    $placeholder = $search ? ts('- any -') : ($useRequired ? ts('- select -') : ts('- none -'));

    // FIXME: Why are select state/country separate widget types?
    $isSelect = (in_array($widget, array(
      'Select',
      'Multi-Select',
      'Select State/Province',
      'Multi-Select State/Province',
      'Select Country',
      'Multi-Select Country',
      'CheckBox',
      'Radio',
    )));

    if ($isSelect) {
      $options = $field->getOptions($search ? 'search' : 'create');

      // Consolidate widget types to simplify the below switch statement
      if ($search || (strpos($widget, 'Select') !== FALSE)) {
        $widget = 'Select';
      }

      $customFieldAttributes['data-crm-custom'] = $dataCrmCustomVal;
      $selectAttributes = array('class' => 'crm-select2');

      // Search field is always multi-select
      if ($search || strpos($field->html_type, 'Multi') !== FALSE) {
        $selectAttributes['class'] .= ' huge';
        $selectAttributes['multiple'] = 'multiple';
        $selectAttributes['placeholder'] = $placeholder;
      }

      // Add data for popup link. Normally this is handled by CRM_Core_Form->addSelect
      $isSupportedWidget = in_array($widget, ['Select', 'Radio']);
      $canEditOptions = CRM_Core_Permission::check('administer CiviCRM');
      if ($field->option_group_id && !$search && $isSelect && $canEditOptions) {
        $customFieldAttributes += array(
          'data-api-entity' => $field->getEntity(),
          'data-api-field' => 'custom_' . $field->id,
          'data-option-edit-path' => 'civicrm/admin/options/' . CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $field->option_group_id),
        );
        $selectAttributes += $customFieldAttributes;
      }
    }

    $rangeDataTypes = ['Int', 'Float', 'Money'];

    if (!isset($label)) {
      $label = $field->label;
    }

    // at some point in time we might want to split the below into small functions

    switch ($widget) {
      case 'Text':
      case 'Link':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $field->attributes);
          $qf->add('text', $elementName . '_to', ts('To'), $field->attributes);
        }
        else {
          if ($field->text_length) {
            $field->attributes .= ' maxlength=' . $field->text_length;
            if ($field->text_length < 20) {
              $field->attributes .= ' size=' . $field->text_length;
            }
          }
          $element = $qf->add('text', $elementName, $label,
            $field->attributes,
            $useRequired && !$search
          );
        }
        break;

      case 'TextArea':
        $attributes = $dataCrmCustomAttr;
        if ($field->note_rows) {
          $attributes .= 'rows=' . $field->note_rows;
        }
        else {
          $attributes .= 'rows=4';
        }
        if ($field->note_columns) {
          $attributes .= ' cols=' . $field->note_columns;
        }
        else {
          $attributes .= ' cols=60';
        }
        if ($field->text_length) {
          $attributes .= ' maxlength=' . $field->text_length;
        }
        $element = $qf->add('textarea',
          $elementName,
          $label,
          $attributes,
          $useRequired && !$search
        );
        break;

      case 'Select Date':
        $attr = array('data-crm-custom' => $dataCrmCustomVal);
        //CRM-18379: Fix for date range of 'Select Date' custom field when include in profile.
        $minYear = isset($field->start_date_years) ? (date('Y') - $field->start_date_years) : NULL;
        $maxYear = isset($field->end_date_years) ? (date('Y') + $field->end_date_years) : NULL;

        $params = array(
          'date' => $field->date_format,
          'minDate' => isset($minYear) ? $minYear . '-01-01' : NULL,
          //CRM-18487 - max date should be the last date of the year.
          'maxDate' => isset($maxYear) ? $maxYear . '-12-31' : NULL,
          'time' => $field->time_format ? $field->time_format * 12 : FALSE,
        );
        if ($field->is_search_range && $search) {
          $qf->add('datepicker', $elementName . '_from', $label, $attr + array('placeholder' => ts('From')), FALSE, $params);
          $qf->add('datepicker', $elementName . '_to', NULL, $attr + array('placeholder' => ts('To')), FALSE, $params);
        }
        else {
          $element = $qf->add('datepicker', $elementName, $label, $attr, $useRequired && !$search, $params);
        }
        break;

      case 'Radio':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $field->attributes);
          $qf->add('text', $elementName . '_to', ts('To'), $field->attributes);
        }
        else {
          $choice = array();
          parse_str($field->attributes, $radioAttributes);
          $radioAttributes = array_merge($radioAttributes, $customFieldAttributes);

          foreach ($options as $v => $l) {
            $choice[] = $qf->createElement('radio', NULL, '', $l, (string) $v, $radioAttributes);
          }
          $element = $qf->addGroup($choice, $elementName, $label);
          $optionEditKey = 'data-option-edit-path';
          if (isset($selectAttributes[$optionEditKey])) {
            $element->setAttribute($optionEditKey, $selectAttributes[$optionEditKey]);
          }

          if ($useRequired && !$search) {
            $qf->addRule($elementName, ts('%1 is a required field.', array(1 => $label)), 'required');
          }
          else {
            $element->setAttribute('allowClear', TRUE);
          }
        }
        break;

      // For all select elements
      case 'Select':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $field->attributes);
          $qf->add('text', $elementName . '_to', ts('To'), $field->attributes);
        }
        else {
          if (empty($selectAttributes['multiple'])) {
            $options = array('' => $placeholder) + $options;
          }
          $element = $qf->add('select', $elementName, $label, $options, $useRequired && !$search, $selectAttributes);

          // Add and/or option for fields that store multiple values
          if ($search && self::isSerialized($field)) {

            $operators = array(
              $qf->createElement('radio', NULL, '', ts('Any'), 'or', array('title' => ts('Results may contain any of the selected options'))),
              $qf->createElement('radio', NULL, '', ts('All'), 'and', array('title' => ts('Results must have all of the selected options'))),
            );
            $qf->addGroup($operators, $elementName . '_operator');
            $qf->setDefaults(array($elementName . '_operator' => 'or'));
          }
        }
        break;

      case 'CheckBox':
        $check = array();
        foreach ($options as $v => $l) {
          $check[] = &$qf->addElement('advcheckbox', $v, NULL, $l, $customFieldAttributes);
        }

        $group = $element = $qf->addGroup($check, $elementName, $label);
        $optionEditKey = 'data-option-edit-path';
        if (isset($customFieldAttributes[$optionEditKey])) {
          $group->setAttribute($optionEditKey, $customFieldAttributes[$optionEditKey]);
        }

        if ($useRequired && !$search) {
          $qf->addRule($elementName, ts('%1 is a required field.', array(1 => $label)), 'required');
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
          $field->attributes,
          $useRequired && !$search
        );
        $qf->addUploadElement($elementName);
        break;

      case 'RichTextEditor':
        $attributes = array(
          'rows' => $field->note_rows,
          'cols' => $field->note_columns,
          'data-crm-custom' => $dataCrmCustomVal,
        );
        if ($field->text_length) {
          $attributes['maxlength'] = $field->text_length;
        }
        $element = $qf->add('wysiwyg', $elementName, $label, $attributes, $useRequired && !$search);
        break;

      case 'Autocomplete-Select':
        static $customUrls = array();
        // Fixme: why is this a string in the first place??
        $attributes = array();
        if ($field->attributes) {
          foreach (explode(' ', $field->attributes) as $at) {
            if (strpos($at, '=')) {
              list($k, $v) = explode('=', $at);
              $attributes[$k] = trim($v, ' "');
            }
          }
        }
        if ($field->data_type == 'ContactReference') {
          // break if contact does not have permission to access ContactReference
          if (!CRM_Core_Permission::check('access contact reference fields')) {
            break;
          }
          $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] . ' ' : '') . 'crm-form-contact-reference huge';
          $attributes['data-api-entity'] = 'Contact';
          $element = $qf->add('text', $elementName, $label, $attributes, $useRequired && !$search);

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
          $attributes += array(
            'entity' => 'OptionValue',
            'placeholder' => $placeholder,
            'multiple' => $search,
            'api' => array(
              'params' => array('option_group_id' => $field->option_group_id, 'is_active' => 1),
            ),
          );
          $element = $qf->addEntityRef($elementName, $label, $attributes, $useRequired && !$search);
        }

        $qf->assign('customUrls', $customUrls);
        break;
    }

    switch ($field->data_type) {
      case 'Int':
        // integers will have numeric rule applied to them.
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must be an integer (whole number).', array(1 => $label)), 'integer');
          $qf->addRule($elementName . '_to', ts('%1 To must be an integer (whole number).', array(1 => $label)), 'integer');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be an integer (whole number).', array(1 => $label)), 'integer');
        }
        break;

      case 'Float':
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must be a number (with or without decimal point).', array(1 => $label)), 'numeric');
          $qf->addRule($elementName . '_to', ts('%1 To must be a number (with or without decimal point).', array(1 => $label)), 'numeric');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be a number (with or without decimal point).', array(1 => $label)), 'numeric');
        }
        break;

      case 'Money':
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must in proper money format. (decimal point/comma/space is allowed).', array(1 => $label)), 'money');
          $qf->addRule($elementName . '_to', ts('%1 To must in proper money format. (decimal point/comma/space is allowed).', array(1 => $label)), 'money');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be in proper money format. (decimal point/comma/space is allowed).', array(1 => $label)), 'money');
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
    CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_CustomField');

    CRM_Utils_Hook::post('delete', 'CustomField', $field->id, $field);
  }

  /**
   * @param string|int|array|null $value
   * @param CRM_Core_BAO_CustomField|int|array|string $field
   * @param int $entityId
   *
   * @return string
   * @throws \Exception
   */
  public static function displayValue($value, $field, $entityId = NULL) {
    $field = is_array($field) ? $field['id'] : $field;
    $fieldId = is_object($field) ? $field->id : (int) str_replace('custom_', '', $field);

    if (!$fieldId) {
      throw new Exception('CRM_Core_BAO_CustomField::displayValue requires a field id');
    }

    if (!is_a($field, 'CRM_Core_BAO_CustomField')) {
      $field = self::getFieldObject($fieldId);
    }

    $fieldInfo = array('options' => $field->getOptions()) + (array) $field;

    return self::formatDisplayValue($value, $fieldInfo, $entityId);
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
      case 'Select Country':
      case 'Select State/Province':
      case 'CheckBox':
      case 'Multi-Select':
      case 'Multi-Select State/Province':
      case 'Multi-Select Country':
        if ($field['data_type'] == 'ContactReference' && $value) {
          if (is_numeric($value)) {
            $display = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'display_name');
          }
          else {
            $display = $value;
          }
        }
        elseif (is_array($value)) {
          $v = array();
          foreach ($value as $key => $val) {
            $v[] = CRM_Utils_Array::value($val, $field['options']);
          }
          $display = implode(', ', $v);
        }
        else {
          $display = CRM_Utils_Array::value($value, $field['options'], '');
        }
        break;

      case 'Select Date':
        $customFormat = NULL;

        // FIXME: Are there any legitimate reasons why $value would be an array?
        // Or should we throw an exception here if it is?
        $value = is_array($value) ? CRM_Utils_Array::first($value) : $value;

        $actualPHPFormats = CRM_Utils_Date::datePluginToPHPFormats();
        $format = CRM_Utils_Array::value('date_format', $field);

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
              $display = $icons[$value];
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
            $disp[] = CRM_Utils_Money::format($val, NULL, NULL, TRUE);
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
            $value = $config->defaultContactCountry();
          }
        }
      }
    }

    //set defaults if mode is registration
    if (!trim($value) &&
      ($value !== 0) &&
      (!in_array($mode, array(CRM_Profile_Form::MODE_EDIT, CRM_Profile_Form::MODE_SEARCH)))
    ) {
      $value = $customField->default_value;
    }

    if ($customField->data_type == 'Money' && isset($value)) {
      $value = number_format($value, 2);
    }
    switch ($customField->html_type) {
      case 'CheckBox':
      case 'Multi-Select':
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldId, FALSE);
        $defaults[$elementName] = array();
        $checkedValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          substr($value, 1, -1)
        );
        foreach ($customOption as $val) {
          if (in_array($val['value'], $checkedValue)) {
            if ($customField->html_type == 'CheckBox') {
              $defaults[$elementName][$val['value']] = 1;
            }
            elseif ($customField->html_type == 'Multi-Select') {
              $defaults[$elementName][$val['value']] = $val['value'];
            }
          }
        }
        break;

      case 'Autocomplete-Select':
        if ($customField->data_type == 'ContactReference') {
          if (is_numeric($value)) {
            $defaults[$elementName . '_id'] = $value;
            $defaults[$elementName] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'sort_name');
          }
        }
        else {
          $defaults[$elementName] = $value;
        }
        break;

      default:
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
        $params = array('id' => $cfID);
        $defaults = array();
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

      $result = array();
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
          list($path) = CRM_Core_BAO_File::path($fileID, $entityId);
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
      $checkPermission
    );

    if (!array_key_exists($customFieldId, $customFields)) {
      return NULL;
    }

    // return if field is a 'code' field
    if (!$includeViewOnly && !empty($customFields[$customFieldId]['is_view'])) {
      return NULL;
    }

    list($tableName, $columnName, $groupID) = self::getTableColumnGroup($customFieldId);

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
          $selectedValues = array();
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

    if ($customFields[$customFieldId]['html_type'] == 'Multi-Select') {
      if ($value) {
        $value = CRM_Utils_Array::implodePadded($value);
      }
      else {
        $value = '';
      }
    }

    if (($customFields[$customFieldId]['html_type'] == 'Multi-Select' ||
        $customFields[$customFieldId]['html_type'] == 'CheckBox'
      ) &&
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
        $params = array(1 => array($customValueId, 'Integer'));
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
      $customFormatted = array();
    }

    if (!array_key_exists($customFieldId, $customFormatted)) {
      $customFormatted[$customFieldId] = array();
    }

    $index = -1;
    if ($customValueId) {
      $index = $customValueId;
    }

    if (!array_key_exists($index, $customFormatted[$customFieldId])) {
      $customFormatted[$customFieldId][$index] = array();
    }
    $customFormatted[$customFieldId][$index] = array(
      'id' => $customValueId > 0 ? $customValueId : NULL,
      'value' => $value,
      'type' => $customFields[$customFieldId]['data_type'],
      'custom_field_id' => $customFieldId,
      'custom_group_id' => $groupID,
      'table_name' => $tableName,
      'column_name' => $columnName,
      'file_id' => $fileID,
      'is_multiple' => $customFields[$customFieldId]['is_multiple'],
    );

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
    $table = array(
      'name' => $params['name'],
      'is_multiple' => $params['is_multiple'],
      'attributes' => "ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci",
      'fields' => array(
        array(
          'name' => 'id',
          'type' => 'int unsigned',
          'primary' => TRUE,
          'required' => TRUE,
          'attributes' => 'AUTO_INCREMENT',
          'comment' => 'Default MySQL primary key',
        ),
        array(
          'name' => 'entity_id',
          'type' => 'int unsigned',
          'required' => TRUE,
          'comment' => 'Table that this extends',
          'fk_table_name' => $params['extends_name'],
          'fk_field_name' => 'id',
          'fk_attributes' => 'ON DELETE CASCADE',
        ),
      ),
    );

    if (!$params['is_multiple']) {
      $table['indexes'] = array(
        array(
          'unique' => TRUE,
          'field_name_1' => 'entity_id',
        ),
      );
    }
    return $table;
  }

  /**
   * Create custom field.
   *
   * @param CRM_Core_DAO_CustomField $field
   * @param string $operation
   * @param bool $indexExist
   * @param bool $triggerRebuild
   */
  public static function createField($field, $operation, $indexExist = FALSE, $triggerRebuild = TRUE) {
    $tableName = CRM_Core_DAO::getFieldValue(
      'CRM_Core_DAO_CustomGroup',
      $field->custom_group_id,
      'table_name'
    );

    $params = array(
      'table_name' => $tableName,
      'operation' => $operation,
      'name' => $field->column_name,
      'type' => CRM_Core_BAO_CustomValueTable::fieldToSQLType(
        $field->data_type,
        $field->text_length
      ),
      'required' => $field->is_required,
      'searchable' => $field->is_searchable,
    );

    if ($operation == 'delete') {
      $fkName = "{$tableName}_{$field->column_name}";
      if (strlen($fkName) >= 48) {
        $fkName = substr($fkName, 0, 32) . '_' . substr(md5($fkName), 0, 16);
      }
      $params['fkName'] = $fkName;
    }
    if ($field->data_type == 'Country' && $field->html_type == 'Select Country') {
      $params['fk_table_name'] = 'civicrm_country';
      $params['fk_field_name'] = 'id';
      $params['fk_attributes'] = 'ON DELETE SET NULL';
    }
    elseif ($field->data_type == 'Country' && $field->html_type == 'Multi-Select Country') {
      $params['type'] = 'varchar(255)';
    }
    elseif ($field->data_type == 'StateProvince' && $field->html_type == 'Select State/Province') {
      $params['fk_table_name'] = 'civicrm_state_province';
      $params['fk_field_name'] = 'id';
      $params['fk_attributes'] = 'ON DELETE SET NULL';
    }
    elseif ($field->data_type == 'StateProvince' && $field->html_type == 'Multi-Select State/Province') {
      $params['type'] = 'varchar(255)';
    }
    elseif ($field->data_type == 'File') {
      $params['fk_table_name'] = 'civicrm_file';
      $params['fk_field_name'] = 'id';
      $params['fk_attributes'] = 'ON DELETE SET NULL';
    }
    elseif ($field->data_type == 'ContactReference') {
      $params['fk_table_name'] = 'civicrm_contact';
      $params['fk_field_name'] = 'id';
      $params['fk_attributes'] = 'ON DELETE SET NULL';
    }
    if (isset($field->default_value)) {
      $params['default'] = "'{$field->default_value}'";
    }

    CRM_Core_BAO_SchemaHandler::alterFieldSQL($params, $indexExist, $triggerRebuild);
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
    $errors = array();

    $field = new CRM_Core_DAO_CustomField();
    $field->id = $fieldID;
    if (!$field->find(TRUE)) {
      $errors['fieldID'] = 'Invalid ID for custom field';
      return $errors;
    }

    $oldGroup = new CRM_Core_DAO_CustomGroup();
    $oldGroup->id = $field->custom_group_id;
    if (!$oldGroup->find(TRUE)) {
      $errors['fieldID'] = 'Invalid ID for old custom group';
      return $errors;
    }

    $newGroup = new CRM_Core_DAO_CustomGroup();
    $newGroup->id = $newGroupID;
    if (!$newGroup->find(TRUE)) {
      $errors['newGroupID'] = 'Invalid ID for new custom group';
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
    $params = array(
      1 => array($field->id, 'Integer'),
      2 => array($newGroup->id, 'Integer'),
    );
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
    $count = CRM_Core_DAO::singleValueQuery($query,
      CRM_Core_DAO::$_nullArray
    );
    if ($count > 0) {
      $query = "
SELECT extends
FROM   civicrm_custom_group
WHERE  id IN ( %1, %2 )
";
      $params = array(
        1 => array($oldGroup->id, 'Integer'),
        2 => array($newGroup->id, 'Integer'),
      );

      $dao = CRM_Core_DAO::executeQuery($query, $params);
      $extends = array();
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
   */
  public static function moveField($fieldID, $newGroupID) {
    $validation = self::_moveFieldValidate($fieldID, $newGroupID);
    if (TRUE !== $validation) {
      CRM_Core_Error::fatal(implode(' ', $validation));
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

    $sql = "INSERT INTO {$newGroup->table_name} (entity_id, {$field->column_name})
            SELECT entity_id, {$field->column_name} FROM {$oldGroup->table_name}
            ON DUPLICATE KEY UPDATE {$field->column_name} = {$oldGroup->table_name}.{$field->column_name}
            ";
    CRM_Core_DAO::executeQuery($sql);

    $del = clone$field;
    $del->custom_group_id = $oldGroup->id;
    self::createField($del, 'delete');

    $add->save();

    CRM_Utils_System::flushCache();
  }

  /**
   * Get the database table name and column name for a custom field.
   *
   * @param int $fieldID
   *   The fieldID of the custom field.
   * @param bool $force
   *   Force the sql to be run again (primarily used for tests).
   *
   * @return array
   *   fatal is fieldID does not exists, else array of tableName, columnName
   */
  public static function getTableColumnGroup($fieldID, $force = FALSE) {
    $cacheKey = "CRM_Core_DAO_CustomField_CustomGroup_TableColumn_{$fieldID}";
    $cache = CRM_Utils_Cache::singleton();
    $fieldValues = $cache->get($cacheKey);
    if (empty($fieldValues) || $force) {
      $query = "
SELECT cg.table_name, cf.column_name, cg.id
FROM   civicrm_custom_group cg,
       civicrm_custom_field cf
WHERE  cf.custom_group_id = cg.id
AND    cf.id = %1";
      $params = array(1 => array($fieldID, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);

      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }
      $dao->free();
      $fieldValues = array($dao->table_name, $dao->column_name, $dao->id);
      $cache->set($cacheKey, $fieldValues);
    }
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
    if ($currentOptionGroupId == $optionGroupId) {
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
   * @param string $htmlType
   *
   * @return null|string
   */
  public static function getOptionGroupDefault($optionGroupId, $htmlType) {
    $query = "
SELECT   default_value, html_type
FROM     civicrm_custom_field
WHERE    option_group_id = {$optionGroupId}
AND      default_value IS NOT NULL
ORDER BY html_type";

    $dao = CRM_Core_DAO::executeQuery($query);
    $defaultValue = NULL;
    $defaultHTMLType = NULL;
    while ($dao->fetch()) {
      if ($dao->html_type == $htmlType) {
        return $dao->default_value;
      }
      if ($defaultValue == NULL) {
        $defaultValue = $dao->default_value;
        $defaultHTMLType = $dao->html_type;
      }
    }

    // some conversions are needed if either the old or new has a html type which has potential
    // multiple default values.
    if (($htmlType == 'CheckBox' || $htmlType == 'Multi-Select') &&
      ($defaultHTMLType != 'CheckBox' && $defaultHTMLType != 'Multi-Select')
    ) {
      $defaultValue = CRM_Core_DAO::VALUE_SEPARATOR . $defaultValue . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    elseif (($defaultHTMLType == 'CheckBox' || $defaultHTMLType == 'Multi-Select') &&
      ($htmlType != 'CheckBox' && $htmlType != 'Multi-Select')
    ) {
      $defaultValue = substr($defaultValue, 1, -1);
      $values = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        substr($defaultValue, 1, -1)
      );
      $defaultValue = $values[0];
    }

    return $defaultValue;
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
    $customData = array();

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
   * @throws \CiviCRM_API3_Exception
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
    $result = array();
    while ($dao->fetch()) {
      $result[$dao->id] = array(
        'field_name' => $dao->field_name,
        'field_label' => $dao->field_label,
        'group_name' => $dao->group_name,
        'group_title' => $dao->group_title,
      );
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
    $errors = array();
    if (!is_array($params) || empty($params)) {
      return $errors;
    }

    //pick up profile fields.
    $profileFields = array();
    $ufGroupId = CRM_Utils_Array::value('ufGroupId', $params);
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

      $profileField = CRM_Utils_Array::value($key, $profileFields, array());
      $fieldTitle = CRM_Utils_Array::value('title', $profileField);
      $isRequired = CRM_Utils_Array::value('is_required', $profileField);
      if (!$fieldTitle) {
        $fieldTitle = $field->label;
      }

      //no need to validate.
      if (CRM_Utils_System::isNull($value) && !$isRequired) {
        continue;
      }

      //lets validate first for required field.
      if ($isRequired && CRM_Utils_System::isNull($value)) {
        $errors[$key] = ts('%1 is a required field.', array(1 => $fieldTitle));
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
      $params[1] = array($customId, 'Integer');
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
   * @param array $field
   *
   * @return bool
   */
  public static function isSerialized($field) {
    // Fields retrieved via api are an array, or from the dao are an object. We'll accept either.
    $field = (array) $field;
    // FIXME: Currently the only way to know if data is serialized is by looking at the html_type. It would be cleaner to decouple this.
    return ($field['html_type'] == 'CheckBox' || strpos($field['html_type'], 'Multi') !== FALSE);
  }

  /**
   * Get api entity for this field
   *
   * @return string
   */
  public function getEntity() {
    $entity = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->custom_group_id, 'extends');
    return in_array($entity, array('Individual', 'Household', 'Organization')) ? 'Contact' : $entity;
  }

  /**
   * Set pseudoconstant properties for field metadata.
   *
   * @param array $field
   * @param string|null $optionGroupName
   */
  private static function getOptionsForField(&$field, $optionGroupName) {
    if ($optionGroupName) {
      $field['pseudoconstant'] = array(
        'optionGroupName' => $optionGroupName,
        'optionEditPath' => 'civicrm/admin/options/' . $optionGroupName,
      );
    }
    elseif ($field['data_type'] == 'Boolean') {
      $field['pseudoconstant'] = array(
        'callback' => 'CRM_Core_SelectValues::boolean',
      );
    }
    elseif ($field['data_type'] == 'Country') {
      $field['pseudoconstant'] = array(
        'table' => 'civicrm_country',
        'keyColumn' => 'id',
        'labelColumn' => 'name',
        'nameColumn' => 'iso_code',
      );
    }
    elseif ($field['data_type'] == 'StateProvince') {
      $field['pseudoconstant'] = array(
        'table' => 'civicrm_state_province',
        'keyColumn' => 'id',
        'labelColumn' => 'name',
      );
    }
  }

}
