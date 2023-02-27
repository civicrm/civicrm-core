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

use Civi\Api4\RelationshipType;

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
  public function defaultFromColumnName(string $columnName): string {

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
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function preProcess(): void {
    $this->_mapperFields = $this->getAvailableFields();
    //format custom field names, CRM-2676
    $contactType = $this->getContactType();

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

    $this->_formattedFieldNames[$contactType] = $this->_mapperFields = array_merge($this->_mapperFields, $formattedFieldNames);
    $this->assignMapFieldVariables();
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();

    $this->addFormRule(['CRM_Contact_Import_Form_MapField', 'formRule']);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);
    $hasColumnNames = !empty($this->getDataSourceObject()->getColumnHeaders());

    $this->getLocationTypes();
    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
    $this->assign('defaultLocationType', $defaultLocationType->id);
    $this->assign('defaultLocationTypeLabel', $this->getLocationTypeLabel($defaultLocationType->id));

    /* Initialize all field usages to false */
    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }

    $sel1 = $this->_mapperFields;
    $sel2[''] = NULL;

    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $websiteTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');

    foreach ($this->getLocationTypes() as $key => $value) {
      $sel3['phone'][$key] = &$phoneTypes;
      $sel3['phone_ext'][$key] = &$phoneTypes;
      //build array for IM service provider type for contact
      $sel3['im'][$key] = &$imProviders;
    }

    $sel4 = NULL;

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
        $relatedContactType = $this->getRelatedContactType($key);
        //CRM-5125 for contact subtype specific RelationshipTypes
        $relatedContactSubType = $this->getRelatedContactSubType($key);
      }
      else {
        $id = $first = $second = NULL;
      }
      if (($first === 'a' && $second === 'b') || ($first === 'b' && $second === 'a')) {
        $relatedFields = CRM_Contact_BAO_Contact::importableFields($relatedContactType);
        unset($relatedFields['']);
        $values = [];
        foreach ($relatedFields as $name => $field) {
          $values[$name] = $field['title'];
          if ($this->isLocationTypeRequired($name)) {
            $sel3[$key][$name] = $this->getLocationTypes();
          }
          elseif ($name === 'url') {
            $sel3[$key][$name] = $websiteTypes;
          }
          else {
            $sel3[$name] = NULL;
          }
        }

        //fix to append custom group name to field name, CRM-2676
        if (empty($this->_formattedFieldNames[$relatedContactType]) || $relatedContactType === $this->getContactType()) {
          $this->_formattedFieldNames[$relatedContactType] = $this->formatCustomFieldName($values);
        }

        $this->_formattedFieldNames[$relatedContactType] = array_merge($values, $this->_formattedFieldNames[$relatedContactType]);

        //Modified the Relationship fields if the fields are
        //present in dedupe rule
        if ($this->isIgnoreDuplicates() && !empty($this->_dedupeFields[$relatedContactType]) &&
          is_array($this->_dedupeFields[$relatedContactType])
        ) {
          static $cTypeArray = [];
          if ($relatedContactType !== $this->getContactType() && !in_array($relatedContactType, $cTypeArray)) {
            foreach ($this->_dedupeFields[$relatedContactType] as $val) {
              if ($valTitle = CRM_Utils_Array::value($val, $this->_formattedFieldNames[$relatedContactType])) {
                $this->_formattedFieldNames[$relatedContactType][$val] = $valTitle . ' (match to contact)';
              }
            }
            $cTypeArray[] = $relatedContactType;
          }
        }

        foreach ($highlightedFields as $k => $v) {
          if ($v === $relatedContactType || $v === 'All') {
            $highlightedRelFields[$key][] = $k;
          }
        }
        $this->assign('highlightedRelFields', $highlightedRelFields);
        $sel2[$key] = $this->_formattedFieldNames[$relatedContactType];

        if (!empty($relatedContactSubType)) {
          //custom fields for sub type
          $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($relatedContactSubType);

          if (!empty($subTypeFields)) {
            $subType = NULL;
            foreach ($subTypeFields as $customSubTypeField => $details) {
              $subType[$customSubTypeField] = $details['title'];
              $sel2[$key] = array_merge($sel2[$key], $this->formatCustomFieldName($subType));
            }
          }
        }

        foreach ($this->getLocationTypes() as $k => $value) {
          $sel4[$key]['phone'][$k] = &$phoneTypes;
          $sel4[$key]['phone_ext'][$k] = &$phoneTypes;
          //build array of IM service provider for related contact
          $sel4[$key]['im'][$k] = &$imProviders;
        }
      }
      else {
        $options = NULL;
        if ($this->isLocationTypeRequired($key)) {
          $options = $this->getLocationTypes();
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
    $processor->setMappingID((int) $this->getSubmittedValue('savedMapping'));
    $processor->setFormName($formName);
    $processor->setMetadata($this->getContactImportMetadata());
    $processor->setContactType($this->getSubmittedValue('contactType'));
    $processor->setContactSubType($this->getSubmittedValue('contactSubType'));
    $mapper = $this->getSubmittedValue('mapper');

    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);

      // Don't set any defaults if we are going to the next page.
      // ... or coming back.
      // But do add the js.
      if (!empty($mapper)) {
        $last_key = array_key_last($mapper[$i]);
      }
      elseif ($this->getSubmittedValue('savedMapping') && $processor->getFieldName($i)) {
        $defaults["mapper[$i]"] = $processor->getSavedQuickformDefaultsForColumn($i);
        $last_key = array_key_last($defaults["mapper[$i]"]) ?? 0;
      }
      else {
        if ($hasColumnNames) {
          // do array search first to see if has mapped key
          $columnKey = array_search($columnHeader, $this->getFieldTitles(), TRUE);
          if (isset($this->_fieldUsed[$columnKey])) {
            $defaults["mapper[$i]"] = [$columnKey];
            $this->_fieldUsed[$key] = TRUE;
          }
          else {
            // Infer the default from the column names if we have them
            $defaults["mapper[$i]"] = [
              $this->defaultFromColumnName($columnHeader),
            ];
          }
        }
        $last_key = array_key_last($defaults["mapper[$i]"]) ?? 0;
      }
      // Call swapOptions on the deepest select element to hide the empty select lists above it.
      // But we don't need to hide anything above $sel4.
      if ($last_key < 3) {
        $js .= "swapOptions($formName, 'mapper[$i]', $last_key, 4, 'hs_mapper_0_');\n";
      }
      $sel->setOptions([$sel1, $sel2, $sel3, $sel4]);
    }

    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    $this->setDefaults($defaults);

    $this->addFormButtons();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return bool
   *   list of errors to be posted back to the form
   */
  public static function formRule(array $fields): bool {
    if (!empty($fields['saveMapping'])) {
      // todo - this is nonsensical - sane js is better. PR to fix got stale but
      // is here https://github.com/civicrm/civicrm-core/pull/23950
      CRM_Core_Smarty::singleton()->assign('isCheked', TRUE);
    }
    return TRUE;
  }

  /**
   * Process the mapped fields and map it into the uploaded file.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = $this->controller->exportValues('MapField');
    $this->updateUserJobMetadata('submitted_values', $this->getSubmittedValues());
    $this->submit($params);
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
  public function formatCustomFieldName(array $fields): array {
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
          $key = "custom_$fId";
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
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  public function submit(array $params): void {
    // store mapping Id to display it in the preview page
    $this->set('loadMappingId', CRM_Utils_Array::value('mappingId', $params));

    //Updating Mapping Records
    if (!empty($params['updateMapping'])) {
      foreach (array_keys($this->getColumnHeaders()) as $i) {
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

    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($this->getUserJobID());
    $parser->validate();
  }

  /**
   * Did the user specify duplicates matching should not be attempted.
   *
   * @return bool
   */
  private function isIgnoreDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_NOCHECK;
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
  protected function getHighlightedFields(): array {
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

  /**
   * Get an array of fields with TRUE or FALSE to reflect need for location type.
   *
   * e.g ['first_name' => FALSE, 'email' => TRUE, 'street_address' => TRUE']
   *
   * @param string $name
   *
   * @return bool
   */
  private function isLocationTypeRequired(string $name): bool {
    if (!isset(Civi::$statics[__CLASS__]['location_fields'])) {
      Civi::$statics[__CLASS__]['location_fields'] = (new CRM_Contact_Import_Parser_Contact())->setUserJobID($this->getUserJobID())->getFieldsWhichSupportLocationTypes();
    }
    return (bool) (Civi::$statics[__CLASS__]['location_fields'][$name] ?? FALSE);
  }

  /**
   * @return \CRM_Contact_Import_Parser_Contact
   */
  protected function getParser(): CRM_Contact_Import_Parser_Contact {
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($this->getUserJobID());
    return $parser;
  }

  /**
   * Get the location types for import, including the pseudo-type 'Primary'.
   *
   * @return array
   */
  protected function getLocationTypes(): array {
    return ['Primary' => ts('Primary')] + CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
  }

  /**
   * Get the location types for import, including the pseudo-type 'Primary'.
   *
   * @param int|string $type
   *   Location Type ID or 'Primary'.
   * @return string
   */
  protected function getLocationTypeLabel($type): string {
    return $this->getLocationTypes()[$type];
  }

  /**
   * Get the type of the related contact.
   *
   * @param string $key
   *
   * @return string
   */
  protected function getRelatedContactType(string $key): string {
    $relationship = $this->getRelationshipType($key);
    if (strpos($key, '_a_b')) {
      return $relationship['contact_type_b'] ?: 'All';
    }
    return $relationship['contact_type_a'] ?: 'All';
  }

  /**
   * Get the sub_type of the related contact.
   *
   * @param string $key
   *
   * @return string|null
   */
  protected function getRelatedContactSubType(string $key): ?string {
    $relationship = $this->getRelationshipType($key);
    if (strpos($key, '_a_b')) {
      return $relationship['contact_sub_type_b'];
    }
    return $relationship['contact_sub_type_a'];
  }

  /**
   * Get the relationship type.
   *
   * @param string $key
   *   e.g 5_a_b for relationship ID 5 in an a-b direction.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function getRelationshipType(string $key): array {
    $relationshipTypeID = str_replace(['_a_b', '_b_a'], '', $key);
    if (!isset(Civi::$statics[__CLASS__]['relationship_type'][$relationshipTypeID])) {
      Civi::$statics[__CLASS__]['relationship_type'][$relationshipTypeID] = RelationshipType::get(FALSE)
        ->addWhere('id', '=', $relationshipTypeID)
        ->addSelect('contact_type_a', 'contact_type_b', 'contact_sub_type_a', 'contact_sub_type_b')
        ->execute()->first();
    }
    return Civi::$statics[__CLASS__]['relationship_type'][$relationshipTypeID];
  }

}
