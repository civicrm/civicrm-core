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

use Civi\Api4\MappingField;

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

  protected $_dedupeFields;

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
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function preProcess() {
    $this->_mapperFields = $this->getAvailableFields();
    $this->_importTableName = $this->get('importTableName');
    $this->_contactSubType = $this->get('contactSubType');
    //format custom field names, CRM-2676
    $contactType = $this->getContactType();

    $this->_contactType = $contactType;

    if ($this->isIgnoreDuplicates()) {
      //Mark Dedupe Rule Fields as required, since it's used in matching contact
      foreach (CRM_Contact_BAO_ContactType::basicTypes() as $cType) {
        $ruleParams = [
          'contact_type' => $cType,
          'used' => 'Unsupervised',
        ];
        $this->_dedupeFields[$cType] = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);
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

    $this->assign('highlightedFields', $this->getHighlightedFields());
    $this->_formattedFieldNames[$contactType] = $this->_mapperFields = array_merge($this->_mapperFields, $formattedFieldNames);

    $columnNames = $this->getColumnHeaders();
    $this->assign('showColNames', !empty($columnNames));

    $this->_columnCount = $this->getNumberOfColumns();
    $this->_columnNames = $columnNames;
    $this->assign('columnNames', $this->getColumnHeaders());
    //$this->_columnCount = $this->get( 'columnCount' );
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = array_values($this->getDataRows(2));
    $this->assign('dataValues', $this->_dataValues);
  }

  /**
   * Build the form object.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $savedMappingID = (int) $this->getSubmittedValue('savedMapping');
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
      $sel3['phone_ext'][$key] = &$phoneTypes;
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
        [$id, $first, $second] = explode('_', $key);
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
        if ($this->isIgnoreDuplicates() && !empty($this->_dedupeFields[$cType]) &&
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
          $sel4[$key]['phone_ext'][$k] = &$phoneTypes;
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
    $processor->setContactTypeByConstant($this->getSubmittedValue('contactType'));
    $processor->setContactSubType($this->get('contactSubType'));

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);

      if ($this->getSubmittedValue('savedMapping') && $processor->getFieldName($i)) {
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
   * @return array|true
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    $errors = [];
    if (!empty($fields['saveMapping'])) {
      $nameField = $fields['saveMappingName'] ?? NULL;
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
    return empty($errors) ? TRUE : $errors;
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
   * @throws \CRM_Core_Exception
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

      $fldName = $mapperKeys[$i][0] ?? NULL;
      $selOne = $mapperKeys[$i][1] ?? NULL;
      $selTwo = $mapperKeys[$i][2] ?? NULL;
      $selThree = $mapperKeys[$i][3] ?? NULL;
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
            if ($fldName === 'phone' || $fldName === 'phone_ext') {
              $parserParameters['mapperPhoneType'][$i] = $phoneTypes[$selTwo];
            }
            elseif ($fldName === 'im') {
              $parserParameters['mapperImProvider'][$i] = $imProviders[$selTwo];
            }
          }
        }
      }

      //relationship contact mapper info.
      [$id, $first, $second] = CRM_Utils_System::explode('_', $fldName, 3);
      if (($first === 'a' && $second === 'b') ||
        ($first === 'b' && $second === 'a')
      ) {
        $parserParameters['mapperRelated'][$i] = $this->_mapperFields[$fldName];
        if ($selOne) {
          if ($selOne === 'url') {
            $parserParameters['relatedContactWebsiteType'][$i] = $websiteTypes[$selTwo];
          }
          else {
            $parserParameters['relatedContactLocType'][$i] = $locationTypes[$selTwo] ?? NULL;
            if ($selThree) {
              if ($selOne === 'phone' || $selOne === 'phone_ext') {
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
      for ($i = 0; $i < $this->_columnCount; $i++) {
        $this->saveMappingField($params['mappingId'], $i, TRUE);
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

      foreach (array_keys($this->getColumnHeaders()) as $i) {
        $this->saveMappingField($saveMapping['id'], $i);
      }
      $this->set('savedMapping', $saveMapping['id']);
    }

    $parser = new CRM_Contact_Import_Parser_Contact($mapperKeysMain, $parserParameters['mapperLocType'], $parserParameters['mapperPhoneType'],
      $parserParameters['mapperImProvider'], $parserParameters['mapperRelated'], $parserParameters['relatedContactType'],
      $parserParameters['relatedContactDetails'], $parserParameters['relatedContactLocType'],
      $parserParameters['relatedContactPhoneType'], $parserParameters['relatedContactImProvider'],
      $parserParameters['mapperWebsiteType'], $parserParameters['relatedContactWebsiteType']
    );
    $parser->setUserJobID($this->getUserJobID());

    $parser->run($this->_importTableName,
      $mapper,
      CRM_Import_Parser::MODE_PREVIEW,
      NULL,
      '_id',
      '_status',
      (int) $this->getSubmittedValue('onDuplicate'),
      NULL, NULL, FALSE,
      CRM_Contact_Import_Parser_Contact::DEFAULT_TIMEOUT,
      $this->getSubmittedValue('contactSubType'),
      $this->getSubmittedValue('dedupe_rule_id')
    );
    return $parser;
  }

  /**
   * Save the mapping field.
   *
   * @param int $mappingID
   * @param int $columnNumber
   * @param bool $isUpdate
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function saveMappingField(int $mappingID, int $columnNumber, bool $isUpdate = FALSE): void {
    $fieldMapping = (array) $this->getSubmittedValue('mapper')[$columnNumber];
    $mappedField = $this->getMappedField($fieldMapping, $mappingID, $columnNumber);
    if ($isUpdate) {
      MappingField::update(FALSE)
        ->setValues($mappedField)
        ->addWhere('column_number', '=', $columnNumber)
        ->addWhere('mapping_id', '=', $mappingID)
        ->execute();
    }
    else {
      MappingField::create(FALSE)
        ->setValues($mappedField)->execute();
    }
  }

  /**
   * Get the field mapped to the savable format.
   *
   * @param array $fieldMapping
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   */
  protected function getMappedField(array $fieldMapping, int $mappingID, int $columnNumber): array {
    return (new CRM_Contact_Import_Parser_Contact())->getMappingFieldFromMapperInput($fieldMapping, $mappingID, $columnNumber);
  }

  /**
   * Did the user specify duplicates matching should not be attempted.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private function isIgnoreDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_NOCHECK;
  }

  /**
   * Did the user specify duplicates should be skipped and not imported.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  private function isSkipDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_SKIP;
  }

  /**
   * Get the fields to be highlighted in the UI.
   *
   * The highlighted fields are those used to match
   * to an existing contact.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  private function getHighlightedFields(): array {
    $entityFields = [
      'Individual' => ['first_name', 'last_name'],
      'Organization' => ['organization_name'],
      'Household' => ['household_name'],
    ];
    $highlightedFields = $entityFields[$this->getContactType()];
    $highlightedFields[] = 'email';
    $highlightedFields[] = 'external_identifier';
    if (!$this->isSkipDuplicates()) {
      $highlightedFields[] = 'id';
    }
    $customFields = CRM_Core_BAO_CustomField::getFields($this->getContactType());
    foreach ($customFields as $key => $attr) {
      if (!empty($attr['is_required'])) {
        $highlightedFields[] = "custom_$key";
      }
    }
    return $highlightedFields;
  }

}
