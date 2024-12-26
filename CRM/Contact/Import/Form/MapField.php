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

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'contact_import';
  }

  /**
   * An array of all contact fields with
   * formatted custom field names.
   *
   * @var array
   */
  protected $_formattedFieldNames;

  protected $_dedupeFields;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function preProcess(): void {
    // Don't mess up the fields for related contacts
    $this->shouldSortMapperFields = FALSE;

    parent::preProcess();
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
          $valTitle = $this->_mapperFields[$val] ?? NULL;
          if ($valTitle) {
            $this->_mapperFields[$val] = $valTitle . ' (match to contact)';
          }
        }
      }
    }
    // retrieve and highlight required custom fields
    $formattedFieldNames = $this->formatCustomFieldName($this->_mapperFields);

    $this->_formattedFieldNames[$contactType] = $this->_mapperFields = array_merge($this->_mapperFields, $formattedFieldNames);
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);
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

    $phoneTypes = CRM_Core_DAO_Phone::buildOptions('phone_type_id');
    $imProviders = CRM_Core_DAO_IM::buildOptions('provider_id');
    $websiteTypes = CRM_Core_DAO_Website::buildOptions('website_type_id');

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
        [, $first, $second] = explode('_', $key);
        $relatedContactType = $this->getRelatedContactType($key);
        //CRM-5125 for contact subtype specific RelationshipTypes
        $relatedContactSubType = $this->getRelatedContactSubType($key);
      }
      else {
        $first = $second = NULL;
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
              $valTitle = $this->_formattedFieldNames[$relatedContactType][$val] ?? NULL;
              if ($valTitle) {
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
        $this->assign('highlightedRelFields', json_encode($highlightedRelFields));
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
    $processor->setMetadata($this->getParser()->getFieldsMetadata());
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
        $defaultField = $processor->getSavedQuickformDefaultsForColumn($i);
        if (!array_key_exists($defaultField[0], $this->_mapperFields)) {
          $defaultField = ['do_not_import'];
          CRM_Core_Session::setStatus(ts('Data was configured to be imported to column %1 but it is not available. The field has been set to "%2"', [1 => $columnHeader, 2 => $this->_mapperFields['do_not_import']]));
        }
        $defaults["mapper[$i]"] = $defaultField;
        $last_key = array_key_last($defaults["mapper[$i]"]) ?? 0;
      }
      else {
        if ($this->getSubmittedValue('skipColumnHeader')) {
          $defaults["mapper[$i]"][0] = $this->guessMappingBasedOnColumns($columnHeader);
        }
        else {
          $defaults["mapper[$i]"][0] = 'do_not_import';
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
        $this->saveMappingField($this->getSavedMappingID(), $i, TRUE);
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
      $this->updateUserJobMetadata('MapField', ['mapping_id' => $saveMapping['id']]);

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
    return ['Primary' => ts('Primary')] + CRM_Core_DAO_Address::buildOptions('location_type_id');
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
