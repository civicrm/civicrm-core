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
   * Cache of preview data values
   *
   * @var array
   */
  protected $_dataValues;

  /**
   * Mapper fields
   *
   * @var array
   */
  protected $_mapperFields;

  /**
   * Number of columns in import file
   *
   * @var int
   */
  protected $_columnCount;

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
   */
  public function preProcess() {
    $this->assignMapFieldVariables();
    $this->_mapperFields = $this->getAvailableFields();
    asort($this->_mapperFields);
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
    $this->updateUserJobMetadata('submitted_values', $this->getSubmittedValues());
    $this->saveMapping();
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
   * Guess at the field names given the data and patterns from the schema.
   *
   * @param array $patterns
   * @param string $index
   *
   * @return string
   */
  public function defaultFromData($patterns, $index) {
    $best = '';
    $bestHits = 0;
    $n = count($this->_dataValues);

    foreach ($patterns as $key => $re) {
      // Skip empty key/patterns
      if (!$key || !$re || strlen("$re") < 5) {
        continue;
      }

      /* Take a vote over the preview data set */
      $hits = 0;
      for ($i = 0; $i < $n; $i++) {
        if (isset($this->_dataValues[$i][$index])) {
          if (preg_match($re, $this->_dataValues[$i][$index])) {
            $hits++;
          }
        }
      }
      if ($hits > $bestHits) {
        $bestHits = $hits;
        $best = $key;
      }
    }

    if ($best != '') {
      $this->_fieldUsed[$best] = TRUE;
    }
    return $best;
  }

  /**
   * Add the saved mapping fields to the form.
   *
   * @param int|null $savedMappingID
   *
   * @deprecated - working to remove this in favour of `addSavedMappingFields`
   * @throws \CRM_Core_Exception
   */
  protected function buildSavedMappingFields($savedMappingID) {
    //to save the current mappings
    if (!$savedMappingID) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    else {
      $savedMapping = $this->get('savedMapping');

      $mappingName = (string) civicrm_api3('Mapping', 'getvalue', ['id' => $savedMappingID, 'return' => 'name']);
      $this->set('loadedMapping', $savedMapping);
      $this->add('hidden', 'mappingId', $savedMappingID);

      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    $this->assign('savedMappingName', $mappingName ?? NULL);
    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, ['onclick' => "showSaveDetails(this)"]);
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
      return $fieldMessage . ' ' . ts('(Sum of all weights should be greater than or equal to threshold: %1).', array(
        1 => $threshold,
      ));
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
    $fieldMapping = (array) $this->getSubmittedValue('mapper')[$columnNumber];
    $mappedField = $this->getMappedField($fieldMapping, $mappingID, $columnNumber);
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
      foreach (array_keys($this->getColumnHeaders()) as $i) {
        $this->saveMappingField((int) $this->getSubmittedValue('mappingId'), $i, TRUE);
      }
      $this->updateUserJobMetadata('mapping', ['id' => (int) $this->getSubmittedValue('mappingId')]);
    }
    //Saving Mapping Details and Records
    if ($this->getSubmittedValue('saveMapping')) {
      $savedMappingID = Mapping::create(FALSE)->setValues([
        'name' => $this->getSubmittedValue('saveMappingName'),
        'description' => $this->getSubmittedValue('saveMappingDesc'),
        'mapping_type_id:name' => $this->getMappingTypeName(),
      ])->execute()->first()['id'];

      foreach (array_keys($this->getColumnHeaders()) as $i) {
        $this->saveMappingField($savedMappingID, $i, FALSE);
      }
      $this->set('savedMapping', $savedMappingID);
      $this->updateUserJobMetadata('mapping', ['id' => $savedMappingID]);
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

      if ((count($this->getColumnHeaders()) !== count($fieldMappings))) {
        CRM_Core_Session::singleton()->setStatus(ts('The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.'));
      }
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

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;

    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $jsSet = FALSE;
      if ($this->getSubmittedValue('savedMapping')) {
        $fieldMapping = $fieldMappings[$i] ?? NULL;
        if (isset($fieldMappings[$i])) {
          if ($fieldMapping['name'] !== ts('do_not_import')) {
            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
            $defaults["mapper[$i]"] = [$fieldMapping['name']];
            $jsSet = TRUE;
          }
          else {
            $defaults["mapper[$i]"] = [];
          }
          if (!$jsSet) {
            for ($k = 1; $k < 4; $k++) {
              $js .= "{$formName}['mapper[$i][$k]'].style.display = 'none';\n";
            }
          }
        }
        else {
          // this load section to help mapping if we ran out of saved columns when doing Load Mapping
          $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";

          if ($hasHeaders) {
            $defaults["mapper[$i]"] = [$this->defaultFromHeader($columnHeader, $headerPatterns)];
          }
        }
        //end of load mapping
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";
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
      $sel->setOptions([$sel1]);
    }
    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);
    $this->setDefaults($defaults);
    return [$sel, $headerPatterns];
  }

  /**
   * Add the saved mapping fields to the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function addSavedMappingFields(): void {
    $savedMappingID = (int) $this->getSubmittedValue('savedMapping');
    $this->buildSavedMappingFields($savedMappingID);
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
        'text' => $field['title'],
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

}
