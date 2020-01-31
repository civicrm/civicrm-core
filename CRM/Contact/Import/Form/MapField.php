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
 * This class gets the name of the file to upload.
 */
class CRM_Contact_Import_Form_MapField extends CRM_Import_Form_MapField {

  use CRM_Contact_Import_MetadataTrait;

  /**
   * An array of all contact fields with
   * formatted custom field names.
   *
   * @var array
   */
  protected $_formattedFieldNames;

  /**
   * On duplicate.
   *
   * @var int
   */
  public $_onDuplicate;

  protected $_dedupeFields;

  protected static $customFields;

  /**
   * Attempt to match header labels with our mapper fields.
   *
   * FIXME: This is essentially the same function as parent::defaultFromHeader
   *
   * @param string $columnName name of column header
   *
   * @return string
   */
  public function defaultFromColumnName($columnName) {

    if (!preg_match('/^[a-z0-9 ]$/i', $columnName)) {
      if ($columnKey = array_search($columnName, $this->getFieldTitles())) {
        $this->_fieldUsed[$columnKey] = TRUE;
        return $columnKey;
      }
    }

    foreach ($this->getHeaderPatterns() as $key => $re) {
      // Skip empty key/patterns
      if (!$key || !$re || strlen("$re") < 5) {
        continue;
      }

      if (preg_match($re, $columnName)) {
        $this->_fieldUsed[$key] = TRUE;
        return $key;
      }
    }
    return '';
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $dataSource = $this->get('dataSource');
    $skipColumnHeader = $this->get('skipColumnHeader');
    $this->_mapperFields = $this->get('fields');
    $this->_importTableName = $this->get('importTableName');
    $this->_onDuplicate = $this->get('onDuplicate');
    $highlightedFields = [];
    $highlightedFields[] = 'email';
    $highlightedFields[] = 'external_identifier';
    //format custom field names, CRM-2676
    switch ($this->get('contactType')) {
      case CRM_Import_Parser::CONTACT_INDIVIDUAL:
        $contactType = 'Individual';
        $highlightedFields[] = 'first_name';
        $highlightedFields[] = 'last_name';
        break;

      case CRM_Import_Parser::CONTACT_HOUSEHOLD:
        $contactType = 'Household';
        $highlightedFields[] = 'household_name';
        break;

      case CRM_Import_Parser::CONTACT_ORGANIZATION:
        $contactType = 'Organization';
        $highlightedFields[] = 'organization_name';
        break;
    }
    $this->_contactType = $contactType;
    if ($this->_onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
      unset($this->_mapperFields['id']);
    }
    else {
      $highlightedFields[] = 'id';
    }

    if ($this->_onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK) {
      //Mark Dedupe Rule Fields as required, since it's used in matching contact
      foreach (['Individual', 'Household', 'Organization'] as $cType) {
        $ruleParams = [
          'contact_type' => $cType,
          'used' => 'Unsupervised',
        ];
        $this->_dedupeFields[$cType] = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);
      }

      //Modify mapper fields title if fields are present in dedupe rule
      if (is_array($this->_dedupeFields[$contactType])) {
        foreach ($this->_dedupeFields[$contactType] as $val) {
          if ($valTitle = CRM_Utils_Array::value($val, $this->_mapperFields)) {
            $this->_mapperFields[$val] = $valTitle . ' (match to contact)';
          }
        }
      }
    }
    // retrieve and highlight required custom fields
    $formattedFieldNames = $this->formatCustomFieldName($this->_mapperFields);
    self::$customFields = CRM_Core_BAO_CustomField::getFields($this->_contactType);
    foreach (self::$customFields as $key => $attr) {
      if (!empty($attr['is_required'])) {
        $highlightedFields[] = "custom_$key";
      }
    }
    $this->assign('highlightedFields', $highlightedFields);
    $this->_formattedFieldNames[$contactType] = $this->_mapperFields = array_merge($this->_mapperFields, $formattedFieldNames);

    $columnNames = [];
    //get original col headers from csv if present.
    if ($dataSource == 'CRM_Import_DataSource_CSV' && $skipColumnHeader) {
      $columnNames = $this->get('originalColHeader');
    }
    else {
      // get the field names from the temp. DB table
      $dao = new CRM_Core_DAO();
      $db = $dao->getDatabaseConnection();

      $columnsQuery = "SHOW FIELDS FROM $this->_importTableName
                         WHERE Field NOT LIKE '\_%'";
      $columnsResult = $db->query($columnsQuery);
      while ($row = $columnsResult->fetchRow(DB_FETCHMODE_ASSOC)) {
        $columnNames[] = $row['Field'];
      }
    }

    $showColNames = TRUE;
    if ($dataSource === 'CRM_Import_DataSource_CSV' && !$skipColumnHeader) {
      $showColNames = FALSE;
    }
    $this->assign('showColNames', $showColNames);

    $this->_columnCount = count($columnNames);
    $this->_columnNames = $columnNames;
    $this->assign('columnNames', $columnNames);
    //$this->_columnCount = $this->get( 'columnCount' );
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $this->assign('dataValues', $this->_dataValues);
    $this->assign('rowDisplayCount', 2);
  }

  /**
   * Build the form object.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    $savedMappingID = (int) $this->get('savedMapping');
    $this->buildSavedMappingFields($savedMappingID);

    $this->addFormRule(['CRM_Contact_Import_Form_MapField', 'formRule']);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);
    $hasColumnNames = !empty($this->_columnNames);
    $hasLocationTypes = $this->get('fieldTypes');

    $this->_location_types = ['Primary' => ts('Primary')] + CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();

    // Pass default location to js
    if ($defaultLocationType) {
      $this->assign('defaultLocationType', $defaultLocationType->id);
      $this->assign('defaultLocationTypeLabel', $this->_location_types[$defaultLocationType->id]);
    }

    /* Initialize all field usages to false */
    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }

    $sel1 = $this->_mapperFields;
    $sel2[''] = NULL;

    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $websiteTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');

    foreach ($this->_location_types as $key => $value) {
      $sel3['phone'][$key] = &$phoneTypes;
      //build array for IM service provider type for contact
      $sel3['im'][$key] = &$imProviders;
    }

    $sel4 = NULL;

    // store and cache all relationship types
    $contactRelation = new CRM_Contact_DAO_RelationshipType();
    $contactRelation->find();
    while ($contactRelation->fetch()) {
      $contactRelationCache[$contactRelation->id] = [];
      $contactRelationCache[$contactRelation->id]['contact_type_a'] = $contactRelation->contact_type_a;
      $contactRelationCache[$contactRelation->id]['contact_sub_type_a'] = $contactRelation->contact_sub_type_a;
      $contactRelationCache[$contactRelation->id]['contact_type_b'] = $contactRelation->contact_type_b;
      $contactRelationCache[$contactRelation->id]['contact_sub_type_b'] = $contactRelation->contact_sub_type_b;
    }
    $highlightedFields = $highlightedRelFields = [];

    $highlightedFields['email'] = 'All';
    $highlightedFields['external_identifier'] = 'All';
    $highlightedFields['first_name'] = 'Individual';
    $highlightedFields['last_name'] = 'Individual';
    $highlightedFields['household_name'] = 'Household';
    $highlightedFields['organization_name'] = 'Organization';

    foreach ($mapperKeys as $key) {
      // check if there is a _a_b or _b_a in the key
      if (strpos($key, '_a_b') || strpos($key, '_b_a')) {
        list($id, $first, $second) = explode('_', $key);
      }
      else {
        $id = $first = $second = NULL;
      }
      if (($first === 'a' && $second === 'b') || ($first === 'b' && $second === 'a')) {
        $cType = $contactRelationCache[$id]["contact_type_{$second}"];

        //CRM-5125 for contact subtype specific relationshiptypes
        $cSubType = NULL;
        if (!empty($contactRelationCache[$id]["contact_sub_type_{$second}"])) {
          $cSubType = $contactRelationCache[$id]["contact_sub_type_{$second}"];
        }

        if (!$cType) {
          $cType = 'All';
        }

        $relatedFields = CRM_Contact_BAO_Contact::importableFields($cType);
        unset($relatedFields['']);
        $values = [];
        foreach ($relatedFields as $name => $field) {
          $values[$name] = $field['title'];
          if (isset($hasLocationTypes[$name])) {
            $sel3[$key][$name] = $this->_location_types;
          }
          elseif ($name === 'url') {
            $sel3[$key][$name] = $websiteTypes;
          }
          else {
            $sel3[$name] = NULL;
          }
        }

        //fix to append custom group name to field name, CRM-2676
        if (empty($this->_formattedFieldNames[$cType]) || $cType == $this->_contactType) {
          $this->_formattedFieldNames[$cType] = $this->formatCustomFieldName($values);
        }

        $this->_formattedFieldNames[$cType] = array_merge($values, $this->_formattedFieldNames[$cType]);

        //Modified the Relationship fields if the fields are
        //present in dedupe rule
        if ($this->_onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK && !empty($this->_dedupeFields[$cType]) &&
          is_array($this->_dedupeFields[$cType])
        ) {
          static $cTypeArray = [];
          if ($cType != $this->_contactType && !in_array($cType, $cTypeArray)) {
            foreach ($this->_dedupeFields[$cType] as $val) {
              if ($valTitle = CRM_Utils_Array::value($val, $this->_formattedFieldNames[$cType])) {
                $this->_formattedFieldNames[$cType][$val] = $valTitle . ' (match to contact)';
              }
            }
            $cTypeArray[] = $cType;
          }
        }

        foreach ($highlightedFields as $k => $v) {
          if ($v == $cType || $v === 'All') {
            $highlightedRelFields[$key][] = $k;
          }
        }
        $this->assign('highlightedRelFields', $highlightedRelFields);
        $sel2[$key] = $this->_formattedFieldNames[$cType];

        if (!empty($cSubType)) {
          //custom fields for sub type
          $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($cSubType);

          if (!empty($subTypeFields)) {
            $subType = NULL;
            foreach ($subTypeFields as $customSubTypeField => $details) {
              $subType[$customSubTypeField] = $details['title'];
              $sel2[$key] = array_merge($sel2[$key], $this->formatCustomFieldName($subType));
            }
          }
        }

        foreach ($this->_location_types as $k => $value) {
          $sel4[$key]['phone'][$k] = &$phoneTypes;
          //build array of IM service provider for related contact
          $sel4[$key]['im'][$k] = &$imProviders;
        }
      }
      else {
        $options = NULL;
        if (!empty($hasLocationTypes[$key])) {
          $options = $this->_location_types;
        }
        elseif ($key === 'url') {
          $options = $websiteTypes;
        }
        $sel2[$key] = $options;
      }
    }

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;
    //used to warn for mismatch column count or mismatch mapping
    CRM_Core_Session::singleton()->setStatus(NULL);
    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingID($savedMappingID);
    $processor->setFormName($formName);
    $processor->setMetadata($this->getContactImportMetadata());
    $processor->setContactTypeByConstant($this->get('contactType'));
    $processor->setContactSubType($this->get('contactSubType'));

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);

      if ($this->get('savedMapping') && $processor->getFieldName($i)) {
        $defaults["mapper[$i]"] = $processor->getSavedQuickformDefaultsForColumn($i);
        $js .= $processor->getQuickFormJSForField($i);
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_0_');\n";
        if ($hasColumnNames) {
          // do array search first to see if has mapped key
          $columnKey = array_search($this->_columnNames[$i], $this->getFieldTitles());
          if (isset($this->_fieldUsed[$columnKey])) {
            $defaults["mapper[$i]"] = $columnKey;
            $this->_fieldUsed[$key] = TRUE;
          }
          else {
            // Infer the default from the column names if we have them
            $defaults["mapper[$i]"] = [
              $this->defaultFromColumnName($this->_columnNames[$i]),
              0,
            ];
          }
        }
        else {
          // Otherwise guess the default from the form of the data
          $defaults["mapper[$i]"] = [
            $this->defaultFromData($this->getDataPatterns(), $i),
            //                     $defaultLocationType->id
            0,
          ];
        }
      }
      $sel->setOptions([$sel1, $sel2, $sel3, $sel4]);
    }

    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    //set warning if mismatch in more than
    if (isset($mappingName) &&
      ($this->_columnCount != count($mappingName))
    ) {
      CRM_Core_Session::singleton()->setStatus(ts('The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.'));
    }

    $this->setDefaults($defaults);

    $this->addButtons([
      [
        'type' => 'back',
        'name' => ts('Previous'),
      ],
      [
        'type' => 'next',
        'name' => ts('Continue'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    $errors = [];
    if (!empty($fields['saveMapping'])) {
      $nameField = CRM_Utils_Array::value('saveMappingName', $fields);
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        $mappingTypeId = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Contact');
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, $mappingTypeId)) {
          $errors['saveMappingName'] = ts('Duplicate Import Mapping Name');
        }
      }
    }
    $template = CRM_Core_Smarty::singleton();
    if (!empty($fields['saveMapping'])) {
      $template->assign('isCheked', TRUE);
    }

    if (!empty($errors)) {
      $_flag = 1;
      $assignError = new CRM_Core_Page();
      $assignError->assign('mappingDetailsError', $_flag);
      return $errors;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Process the mapped fields and map it into the uploaded file.
   */
  public function postProcess() {
    $params = $this->controller->exportValues('MapField');

    //reload the mapfield if load mapping is pressed
    if (!empty($params['savedMapping'])) {
      $this->set('savedMapping', $params['savedMapping']);
      $this->controller->resetPage($this->_name);
      return;
    }
    $mapperKeys = $this->controller->exportValue($this->_name, 'mapper');

    $parser = $this->submit($params, $mapperKeys);

    // add all the necessary variables to the form
    $parser->set($this);
  }

  /**
   * Format custom field name.
   *
   * Combine group and field name to avoid conflict.
   *
   * @param array $fields
   *
   * @return array
   */
  public function formatCustomFieldName($fields) {
    //CRM-2676, replacing the conflict for same custom field name from different custom group.
    $fieldIds = $formattedFieldNames = [];
    foreach ($fields as $key => $value) {
      if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $fieldIds[] = $customFieldId;
      }
    }

    if (!empty($fieldIds) && is_array($fieldIds)) {
      $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles($fieldIds);

      if (!empty($groupTitles)) {
        foreach ($groupTitles as $fId => $values) {
          $key = "custom_{$fId}";
          $groupTitle = $values['groupTitle'];
          $formattedFieldNames[$key] = $fields[$key] . ' :: ' . $groupTitle;
        }
      }
    }

    return $formattedFieldNames;
  }

  /**
   * Main submit function.
   *
   * Extracted to add testing & start refactoring.
   *
   * @param $params
   * @param $mapperKeys
   *
   * @return \CRM_Contact_Import_Parser_Contact
   * @throws \CiviCRM_API3_Exception
   */
  public function submit($params, $mapperKeys) {
    $mapper = $mapperKeysMain = $locations = [];
    $parserParameters = CRM_Contact_Import_Parser_Contact::getParameterForParser($this->_columnCount);

    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $websiteTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');
    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $locationTypes['Primary'] = ts('Primary');

    for ($i = 0; $i < $this->_columnCount; $i++) {

      $fldName = CRM_Utils_Array::value(0, $mapperKeys[$i]);
      $selOne = CRM_Utils_Array::value(1, $mapperKeys[$i]);
      $selTwo = CRM_Utils_Array::value(2, $mapperKeys[$i]);
      $selThree = CRM_Utils_Array::value(3, $mapperKeys[$i]);
      $mapper[$i] = $this->_mapperFields[$mapperKeys[$i][0]];
      $mapperKeysMain[$i] = $fldName;

      //need to differentiate non location elements.
      if ($selOne && (is_numeric($selOne) || $selOne === 'Primary')) {
        if ($fldName === 'url') {
          $parserParameters['mapperWebsiteType'][$i] = $websiteTypes[$selOne];
        }
        else {
          $locations[$i] = $locationTypes[$selOne];
          $parserParameters['mapperLocType'][$i] = $selOne;
          if ($selTwo && is_numeric($selTwo)) {
            if ($fldName === 'phone') {
              $parserParameters['mapperPhoneType'][$i] = $phoneTypes[$selTwo];
            }
            elseif ($fldName === 'im') {
              $parserParameters['mapperImProvider'][$i] = $imProviders[$selTwo];
            }
          }
        }
      }

      //relationship contact mapper info.
      list($id, $first, $second) = CRM_Utils_System::explode('_', $fldName, 3);
      if (($first === 'a' && $second === 'b') ||
        ($first === 'b' && $second === 'a')
      ) {
        $parserParameters['mapperRelated'][$i] = $this->_mapperFields[$fldName];
        if ($selOne) {
          if ($selOne === 'url') {
            $parserParameters['relatedContactWebsiteType'][$i] = $websiteTypes[$selTwo];
          }
          else {
            $parserParameters['relatedContactLocType'][$i] = CRM_Utils_Array::value($selTwo, $locationTypes);
            if ($selThree) {
              if ($selOne === 'phone') {
                $parserParameters['relatedContactPhoneType'][$i] = $phoneTypes[$selThree];
              }
              elseif ($selOne === 'im') {
                $parserParameters['relatedContactImProvider'][$i] = $imProviders[$selThree];
              }
            }
          }

          //get the related contact type.
          $relationType = new CRM_Contact_DAO_RelationshipType();
          $relationType->id = $id;
          $relationType->find(TRUE);
          $parserParameters['relatedContactType'][$i] = $relationType->{"contact_type_$second"};
          $parserParameters['relatedContactDetails'][$i] = $this->_formattedFieldNames[$parserParameters['relatedContactType'][$i]][$selOne];
        }
      }
    }

    $this->set('columnNames', $this->_columnNames);
    $this->set('websites', $parserParameters['mapperWebsiteType']);
    $this->set('locations', $locations);
    $this->set('phones', $parserParameters['mapperPhoneType']);
    $this->set('ims', $parserParameters['mapperImProvider']);
    $this->set('related', $parserParameters['mapperRelated']);
    $this->set('relatedContactType', $parserParameters['relatedContactType']);
    $this->set('relatedContactDetails', $parserParameters['relatedContactDetails']);
    $this->set('relatedContactLocType', $parserParameters['relatedContactLocType']);
    $this->set('relatedContactPhoneType', $parserParameters['relatedContactPhoneType']);
    $this->set('relatedContactImProvider', $parserParameters['relatedContactImProvider']);
    $this->set('relatedContactWebsiteType', $parserParameters['relatedContactWebsiteType']);
    $this->set('mapper', $mapper);

    // store mapping Id to display it in the preview page
    $this->set('loadMappingId', CRM_Utils_Array::value('mappingId', $params));

    //Updating Mapping Records
    if (!empty($params['updateMapping'])) {

      $mappingFields = new CRM_Core_DAO_MappingField();
      $mappingFields->mapping_id = $params['mappingId'];
      $mappingFields->find();

      $mappingFieldsId = [];
      while ($mappingFields->fetch()) {
        if ($mappingFields->id) {
          $mappingFieldsId[$mappingFields->column_number] = $mappingFields->id;
        }
      }

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $updateMappingFields = new CRM_Core_DAO_MappingField();
        $updateMappingFields->id = CRM_Utils_Array::value($i, $mappingFieldsId);
        $updateMappingFields->mapping_id = $params['mappingId'];
        $updateMappingFields->column_number = $i;

        $mapperKeyParts = explode('_', $mapperKeys[$i][0], 3);
        $id = isset($mapperKeyParts[0]) ? $mapperKeyParts[0] : NULL;
        $first = isset($mapperKeyParts[1]) ? $mapperKeyParts[1] : NULL;
        $second = isset($mapperKeyParts[2]) ? $mapperKeyParts[2] : NULL;
        if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {
          $updateMappingFields->relationship_type_id = $id;
          $updateMappingFields->relationship_direction = "{$first}_{$second}";
          $updateMappingFields->name = ucwords(str_replace("_", " ", $mapperKeys[$i][1]));
          // get phoneType id and provider id separately
          // before updating mappingFields of phone and IM for related contact, CRM-3140
          if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'url') {
            $updateMappingFields->website_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
          else {
            if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'phone') {
              $updateMappingFields->phone_type_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
            }
            elseif (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'im') {
              $updateMappingFields->im_provider_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
            }
            $updateMappingFields->location_type_id = isset($mapperKeys[$i][2]) && is_numeric($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
        }
        else {
          $updateMappingFields->name = $mapper[$i];
          $updateMappingFields->relationship_type_id = 'NULL';
          $updateMappingFields->relationship_type_direction = 'NULL';
          // to store phoneType id and provider id separately
          // before updating mappingFields for phone and IM, CRM-3140
          if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'url') {
            $updateMappingFields->website_type_id = isset($mapperKeys[$i][1]) ? $mapperKeys[$i][1] : NULL;
          }
          else {
            if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
              $updateMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
            }
            elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
              $updateMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
            }
            $locationTypeID = $parserParameters['mapperLocType'][$i];
            // location_type_id is NULL for non-location fields, and for Primary location.
            $updateMappingFields->location_type_id = is_numeric($locationTypeID) ? $locationTypeID : 'null';
          }
        }
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (!empty($params['saveMapping'])) {
      $mappingParams = [
        'name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => 'Import Contact',
      ];

      $saveMapping = civicrm_api3('Mapping', 'create', $mappingParams);

      $contactType = $this->get('contactType');
      switch ($contactType) {
        case CRM_Import_Parser::CONTACT_INDIVIDUAL:
          $cType = 'Individual';
          break;

        case CRM_Import_Parser::CONTACT_HOUSEHOLD:
          $cType = 'Household';
          break;

        case CRM_Import_Parser::CONTACT_ORGANIZATION:
          $cType = 'Organization';
      }

      $mappingID = NULL;
      for ($i = 0; $i < $this->_columnCount; $i++) {
        $mappingID = $this->saveMappingField($mapperKeys, $saveMapping, $cType, $i, $mapper, $parserParameters);
      }
      $this->set('savedMapping', $mappingID);
    }

    $parser = new CRM_Contact_Import_Parser_Contact($mapperKeysMain, $parserParameters['mapperLocType'], $parserParameters['mapperPhoneType'],
      $parserParameters['mapperImProvider'], $parserParameters['mapperRelated'], $parserParameters['relatedContactType'],
      $parserParameters['relatedContactDetails'], $parserParameters['relatedContactLocType'],
      $parserParameters['relatedContactPhoneType'], $parserParameters['relatedContactImProvider'],
      $parserParameters['mapperWebsiteType'], $parserParameters['relatedContactWebsiteType']
    );

    $primaryKeyName = $this->get('primaryKeyName');
    $statusFieldName = $this->get('statusFieldName');
    $parser->run($this->_importTableName,
      $mapper,
      CRM_Import_Parser::MODE_PREVIEW,
      $this->get('contactType'),
      $primaryKeyName,
      $statusFieldName,
      $this->_onDuplicate,
      NULL, NULL, FALSE,
      CRM_Contact_Import_Parser::DEFAULT_TIMEOUT,
      $this->get('contactSubType'),
      $this->get('dedupe')
    );
    return $parser;
  }

  /**
   * @param $mapperKeys
   * @param array $saveMapping
   * @param string $cType
   * @param int $i
   * @param array $mapper
   * @param array $parserParameters
   *
   * @return int
   */
  protected function saveMappingField($mapperKeys, array $saveMapping, string $cType, int $i, array $mapper, array $parserParameters): int {
    $saveMappingFields = new CRM_Core_DAO_MappingField();
    $saveMappingFields->mapping_id = $saveMapping['id'];
    $saveMappingFields->contact_type = $cType;
    $saveMappingFields->column_number = $i;

    $mapperKeyParts = explode('_', $mapperKeys[$i][0], 3);
    $id = isset($mapperKeyParts[0]) ? $mapperKeyParts[0] : NULL;
    $first = isset($mapperKeyParts[1]) ? $mapperKeyParts[1] : NULL;
    $second = isset($mapperKeyParts[2]) ? $mapperKeyParts[2] : NULL;
    if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {
      $saveMappingFields->name = ucwords(str_replace("_", " ", $mapperKeys[$i][1]));
      $saveMappingFields->relationship_type_id = $id;
      $saveMappingFields->relationship_direction = "{$first}_{$second}";
      // to get phoneType id and provider id separately
      // before saving mappingFields of phone and IM for related contact, CRM-3140
      if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'url') {
        $saveMappingFields->website_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
      }
      else {
        if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'phone') {
          $saveMappingFields->phone_type_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
        }
        elseif (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'im') {
          $saveMappingFields->im_provider_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
        }
        $saveMappingFields->location_type_id = (isset($mapperKeys[$i][2]) && $mapperKeys[$i][2] !== 'Primary') ? $mapperKeys[$i][2] : NULL;
      }
    }
    else {
      $saveMappingFields->name = $mapper[$i];
      $locationTypeID = $parserParameters['mapperLocType'][$i];
      // to get phoneType id and provider id separately
      // before saving mappingFields of phone and IM, CRM-3140
      if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'url') {
        $saveMappingFields->website_type_id = isset($mapperKeys[$i][1]) ? $mapperKeys[$i][1] : NULL;
      }
      else {
        if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
          $saveMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
        }
        elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
          $saveMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
        }
        $saveMappingFields->location_type_id = is_numeric($locationTypeID) ? $locationTypeID : NULL;
      }
      $saveMappingFields->relationship_type_id = NULL;
    }
    $saveMappingFields->save();
    return $saveMappingFields->mapping_id;
  }

}
