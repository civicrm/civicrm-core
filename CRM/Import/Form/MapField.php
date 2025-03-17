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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Mapping;
use Civi\Api4\MappingField;

/**
 * This class gets the name of the file to upload.
 *
 * TODO: CRM-11254 - There's still a lot of duplicate code in the 5 child classes that should be moved here
 */
abstract class CRM_Import_Form_MapField extends CRM_Import_Forms {

  /**
   * Does the form layer convert field names to support QuickForm widgets.
   *
   * (e.g) if 'yes' we swap
   * `soft_credit.external_identifier` to `soft_credit__external_identifier`
   * because the contribution form would break on the . as it would treat it as
   * javascript.
   *
   * @var bool
   */
  protected bool $supportsDoubleUnderscoreFields = TRUE;

  /**
   * Mapper fields
   *
   * @var array
   */
  protected $_mapperFields;

  /**
   * @var bool
   */
  protected $shouldSortMapperFields = TRUE;

  /**
   * Column headers, if we have them
   *
   * @var array
   */
  protected $_columnHeaders;

  /**
   * An array of booleans to keep track of whether a field has been used in
   * form building already.
   *
   * @var array
   */
  protected $_fieldUsed;

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Match Fields');
  }

  /**
   * Shared preProcess code.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->addExpectedSmartyVariables(['highlightedRelFields', 'initHideBoxes']);
    $this->assign('columnNames', $this->getColumnHeaders());
    $this->assign('showColumnNames', $this->getSubmittedValue('skipColumnHeader') || $this->getSubmittedValue('dataSource') !== 'CRM_Import_DataSource');
    $this->assign('highlightedFields', json_encode($this->getHighlightedFields()));
    $this->assign('dataValues', array_values($this->getDataRows([], 2)));
    $this->_mapperFields = $this->getAvailableFields();
    $fieldMappings = $this->getFieldMappings();
    // Check if the import file headers match the selected import mappings, throw an error if it doesn't.
    if (empty($_POST) && count($fieldMappings) > 0 && count($this->getColumnHeaders()) !== count($fieldMappings)) {
      CRM_Core_Session::singleton()->setStatus(ts('The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.'));
    }
    if ($this->shouldSortMapperFields) {
      asort($this->_mapperFields);
    }
    parent::preProcess();
  }

  /**
   * Process the mapped fields and map it into the uploaded file
   * preview the file and extract some summary statistics
   *
   * @return void
   * @noinspection PhpDocSignatureInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function postProcess() {
    // This savedMappingID is the one selected on DataSource. It will be overwritten in saveMapping if any
    // action was taken on it.
    $this->savedMappingID = $this->getSubmittedValue('savedMapping') ?: NULL;
    $this->saveMapping();
    $this->updateUserJobMetadata('submitted_values', $this->getSubmittedValues());
    $parser = $this->getParser();
    $parser->init();
    $parser->validate();
  }

  /**
   * Add the form buttons.
   */
  protected function addFormButtons(): void {
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
   * Attempt to match header labels with our mapper fields.
   *
   * @param string $header
   * @param array $patterns
   *
   * @return string
   */
  public function defaultFromHeader($header, $patterns) {
    foreach ($patterns as $key => $re) {
      // Skip empty key/patterns
      if (!$key || !$re || strlen("$re") < 5) {
        continue;
      }

      // Scan through the headerPatterns defined in the schema for a match
      if (preg_match($re, $header)) {
        $this->_fieldUsed[$key] = TRUE;
        return $key;
      }
    }
    return '';
  }

  /**
   * Validate that sufficient fields have been supplied to match to a contact.
   *
   * @param string $contactType
   * @param array $importKeys
   *
   * @return string
   *   Message if insufficient fields are present. Empty string otherwise.
   */
  protected static function validateRequiredContactMatchFields(string $contactType, array $importKeys): string {
    [$ruleFields, $threshold] = CRM_Dedupe_BAO_DedupeRuleGroup::dedupeRuleFieldsWeight([
      'used' => 'Unsupervised',
      'contact_type' => $contactType,
    ]);
    $weightSum = 0;
    foreach ($importKeys as $key => $val) {
      if (array_key_exists($val, $ruleFields)) {
        $weightSum += $ruleFields[$val];
      }
    }
    $fieldMessage = '';
    foreach ($ruleFields as $field => $weight) {
      $fieldMessage .= ' ' . $field . '(weight ' . $weight . ')';
    }
    if ($weightSum < $threshold) {
      return $fieldMessage . ' ' . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [1 => $threshold]);
    }
    return '';
  }

  /**
   * Get the field mapped to the savable format.
   *
   * @param array $fieldMapping
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getMappedField(array $fieldMapping, int $mappingID, int $columnNumber): array {
    return $this->getParser()->getMappingFieldFromMapperInput($fieldMapping, $mappingID, $columnNumber);
  }

  /**
   * Save the mapping field.
   *
   * @param int $mappingID
   * @param int $columnNumber
   * @param bool $isUpdate
   *
   * @throws \CRM_Core_Exception
   */
  protected function saveMappingField(int $mappingID, int $columnNumber, bool $isUpdate = FALSE): void {
    if (!empty($this->userJob['metadata']['import_mappings'])) {
      // In this case Civi-Import has already saved the mapping to civicrm_user_job.metadata
      // and the code here is just keeping civicrm_mapping_field in sync.
      // Eventually we hope to phase out the use of the civicrm_mapping data &
      // just use UserJob and Import Templates (UserJob records with 'is_template' = 1
      $mappedFieldData = $this->userJob['metadata']['import_mappings'][$columnNumber];
      $mappedField = array_intersect_key($mappedFieldData, array_fill_keys(['name', 'column_number', 'entity_data'], TRUE));
      $mappedField['mapping_id'] = $mappingID;
    }
    else {
      $fieldMapping = (array) $this->getSubmittedValue('mapper')[$columnNumber];
      $mappedField = $this->getMappedField($fieldMapping, $mappingID, $columnNumber);
    }
    if (empty($mappedField['name'])) {
      $mappedField['name'] = 'do_not_import';
    }
    $existing = MappingField::get(FALSE)
      ->addWhere('column_number', '=', $columnNumber)
      ->addWhere('mapping_id', '=', $mappingID)->execute()->first();
    if (empty($existing['id'])) {
      MappingField::create(FALSE)
        ->setValues($mappedField)->execute();
    }
    else {
      MappingField::update(FALSE)
        ->setValues($mappedField)
        ->addWhere('id', '=', $existing['id'])
        ->execute();
    }
  }

  /**
   * Save the Field Mapping.
   *
   * @throws \CRM_Core_Exception
   */
  protected function saveMapping(): void {
    //Updating Mapping Records
    if ($this->getSubmittedValue('updateMapping')) {
      $savedMappingID = (int) $this->getSubmittedValue('mappingId');
      foreach (array_keys($this->getColumnHeaders()) as $i) {
        $this->saveMappingField($savedMappingID, $i, TRUE);
      }
      $this->setSavedMappingID($savedMappingID);
      $this->updateUserJobMetadata('Template', ['mapping_id' => (int) $this->getSubmittedValue('mappingId')]);
    }
    //Saving Mapping Details and Records
    if ($this->getSubmittedValue('saveMapping')) {
      $savedMappingID = Mapping::create(FALSE)->setValues([
        'name' => $this->getSubmittedValue('saveMappingName'),
        'description' => $this->getSubmittedValue('saveMappingDesc'),
        'mapping_type_id:name' => $this->getMappingTypeName(),
      ])->execute()->first()['id'];
      $this->setSavedMappingID($savedMappingID);
      $this->updateUserJobMetadata('Template', ['mapping_id' => $savedMappingID]);
      foreach (array_keys($this->getColumnHeaders()) as $i) {
        $this->saveMappingField($savedMappingID, $i, FALSE);
      }
      $this->set('savedMapping', $savedMappingID);
    }
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getFieldMappings(): array {
    $savedMappingID = $this->getSubmittedValue('savedMapping');
    if ($savedMappingID) {
      $fieldMappings = MappingField::get(FALSE)
        ->addWhere('mapping_id', '=', $savedMappingID)
        ->execute()
        ->indexBy('column_number');

      return (array) $fieldMappings;
    }
    return [];
  }

  /**
   * Add the mapper hierarchical select field to the form.
   *
   * @return array
   */
  protected function addMapper(): array {
    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);
    $hasHeaders = $this->getSubmittedValue('skipColumnHeader');
    $headerPatterns = $this->getHeaderPatterns();
    $fieldMappings = $this->getFieldMappings();
    /* Initialize all field usages to false */

    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }
    $sel1 = $this->_mapperFields;

    $formName = 'document.forms.' . $this->_name;

    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      if ($this->getSubmittedValue('savedMapping')) {
        $fieldMapping = $fieldMappings[$i] ?? NULL;
        if (isset($fieldMappings[$i])) {
          if ($fieldMapping['name'] !== ts('do_not_import')) {
            $defaults["mapper[$i]"] = [$fieldMapping['name']];
          }
          else {
            $defaults["mapper[$i]"] = [];
          }
        }
        else {
          if ($hasHeaders) {
            $defaults["mapper[$i]"] = [$this->defaultFromHeader($columnHeader, $headerPatterns)];
          }
        }
        //end of load mapping
      }
      else {
        if ($hasHeaders) {
          // Infer the default from the skipped headers if we have them
          $defaults["mapper[$i]"] = [
            $this->defaultFromHeader($columnHeader,
              $headerPatterns
            ),
            //                     $defaultLocationType->id
            0,
          ];
        }
      }
      $sel = &$this->addElement('select', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), $sel1);

    }
    $this->setDefaults($defaults);
    return [$sel, $headerPatterns];
  }

  /**
   * Add the saved mapping fields to the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function addSavedMappingFields(): void {
    $savedMappingID = $this->getSavedMappingID();
    //to save the current mappings
    if (!$savedMappingID) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    else {
      $this->add('hidden', 'mappingId', $savedMappingID);

      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    $this->assign('savedMappingName', $this->getMappingName());
    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL);
    $this->addFormRule(['CRM_Import_Form_MapField', 'mappingRule']);
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
  public static function mappingRule($fields) {
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
    // This is horrible & should be removed once gone from tpl
    if (!empty($errors['saveMappingName'])) {
      $_flag = 1;
      $assignError = new CRM_Core_Page();
      $assignError->assign('mappingDetailsError', $_flag);
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * This transforms the lists of fields for each contact type & component
   * into a single unified list suitable for select2.
   *
   * @return array
   */
  public function getFieldOptions(): array {
    $fields = $this->getFields();
    $entity = $this->getBaseEntity();
    $categories = $this->getImportEntities();
    $highlightedFields = $this->getHighlightedFields();
    foreach ($fields as $fieldName => $field) {
      if ($fieldName === '') {
        // @todo stop setting 'do not import' in the first place.
        continue;
      }
      if ($field['name'] === 'id' && $entity === $field['entity'] && !$this->isUpdateExisting()) {
        continue;
      }
      $childField = [
        'text' => $field['label'] ?? ($field['html']['label'] ?? $field['title']),
        'id' => $fieldName,
        'has_location' => !empty($field['hasLocationType']),
        'default_value' => $field['default_value'] ?? '',
        'contact_type' => $field['contact_type'] ?? NULL,
        'match_rule' => $field['match_rule'] ?? NULL,
      ];
      if (in_array($fieldName, $highlightedFields, TRUE)) {
        $childField['text'] .= '*';
      }
      $category = ($childField['has_location'] || $field['name'] === 'contact_id') ? 'Contact' : $field['entity_instance'] ?? ($field['entity'] ?? $entity);
      if (empty($categories[$category])) {
        $category = $entity;
      }
      $categories[$category]['children'][$fieldName] = $childField;
    }

    foreach ($categories as $index => $category) {
      if (empty($category['children'])) {
        unset($categories[$index]);
      }
      else {
        $categories[$index]['children'] = array_values($category['children']);
      }
    }
    return array_values($categories);
  }

  /**
   * Get the 'best' mapping default from the column headers.
   *
   * @param string $columnHeader
   *
   * @return string
   */
  protected function guessMappingBasedOnColumns(string $columnHeader): string {
    $headerPatterns = $this->getHeaderPatterns();
    // do array search first to see if has mapped key
    $columnKey = array_search($columnHeader, $this->getAvailableFields(), TRUE);
    if ($columnKey && empty($this->_fieldUsed[$columnKey])) {
      $this->_fieldUsed[$columnKey] = TRUE;
      return $columnKey;
    }
    // Infer the default from the column names if we have them
    return $this->defaultFromHeader($columnHeader, $headerPatterns);
  }

  /**
   * Get default values for the mapping.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDefaults(): array {
    $defaults = $mappingFailures = [];
    $headerPatterns = $this->getHeaderPatterns();
    $fieldMappings = $this->getFieldMappings();
    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      if ($this->getSubmittedValue('savedMapping')) {
        $fieldMapping = $fieldMappings[$i] ?? NULL;
        if (isset($fieldMappings[$i])) {
          if (($fieldMapping['name'] === 'do_not_import')) {
            $defaults["mapper[$i]"] = NULL;
          }
          elseif (array_key_exists($fieldMapping['name'], $this->getAvailableFields())) {
            $defaults["mapper[$i]"] = $fieldMapping['name'];
          }
          else {
            // The field from the saved mapping does not map to an available field.
            // This could be because of an old, not-upgraded mapping or
            // something we have failed to anticipate.
            // In this case we should let the user know, but not
            // set the default to the invalid field.
            // See https://lab.civicrm.org/dev/core/-/issues/4781
            // Note that we have made attempts (e.g 5.51) to upgrade mappings and
            // there is code to remove a mapping if a custom field is deleted
            // (but perhaps not disabled or acl-restricted) but we should also
            // handle it here rather than rely on our other efforts.
            $mappingFailures[] = $columnHeader;
            $defaults["mapper[$i]"] = NULL;
          }
        }
      }
      if (!isset($defaults["mapper[$i]"]) && $this->getSubmittedValue('skipColumnHeader')) {
        $defaults["mapper[$i]"] = $this->defaultFromHeader($columnHeader, $headerPatterns);
      }
    }
    if (!$this->isSubmitted() && $mappingFailures) {
      CRM_Core_Session::setStatus(ts('Unable to load saved mapping. Please ensure all fields are correctly mapped'));
    }
    return $defaults;
  }

  /**
   * Validate the the mapped fields contain enough to meet the dedupe rule lookup requirements.
   *
   * @internal this function may change without notice.
   *
   * @param array $rule
   * @param array $mapper Mapper array as submitted
   * @param array $contactIdentifierFields Array of fields which in themselves uniquely identify a contact.
   *   This array will likely have an import specific prefix.
   *
   * @return string|null
   *   Error string if insufficient.
   */
  protected function validateDedupeFieldsSufficientInMapping(array $rule, array $mapper, array $contactIdentifierFields): ?string {
    $threshold = $rule['threshold'];
    $ruleFields = $rule['fields'];
    $weightSum = 0;
    foreach ($mapper as $mapping) {
      // Because api v4 style fields have a . and QuickForm multiselect js does
      // not cope with a . the quick form layer will use a double underscore
      // as a stand in (the angular layer will not)
      $fieldName = str_replace('__', '.', $mapping[0]);
      if (str_contains($fieldName, '.')) {
        // If the field name contains a . - eg. address_primary.street_address
        // we just want the part after the .
        $fieldName = substr($fieldName, strpos($fieldName, '.') + 1);
      }
      if (in_array($fieldName, $contactIdentifierFields)) {
        // It is enough to have external identifier or contact ID mapped..
        $weightSum = $threshold;
        break;
      }
      if (array_key_exists($fieldName, $ruleFields)) {
        $weightSum += $ruleFields[$fieldName];
      }
    }
    if ($weightSum < $threshold) {
      return $rule['rule_message'];
    }
    return NULL;
  }

  /**
   * @param array $rule
   * @param array $mapper
   * @param array $contactIdentifierFields
   *
   * @return array
   */
  protected function validateContactFields(array $rule, array $mapper, array $contactIdentifierFields): array {
    $mapperError = [];
    if (!$this->isUpdateExisting()) {
      $missingDedupeFields = $this->validateDedupeFieldsSufficientInMapping($rule, $mapper, $contactIdentifierFields);
      if ($missingDedupeFields) {
        $mapperError[] = $missingDedupeFields;
      }
    }
    return $mapperError;
  }

}
