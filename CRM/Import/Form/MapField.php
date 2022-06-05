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
   * Attempt to match header labels with our mapper fields.
   *
   * @param string $header
   * @param array $patterns
   *
   * @return string
   */
  public function defaultFromHeader($header, &$patterns) {
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function saveMappingField(int $mappingID, int $columnNumber, bool $isUpdate = FALSE): void {
    $fieldMapping = (array) $this->getSubmittedValue('mapper')[$columnNumber];
    $mappedField = $this->getMappedField($fieldMapping, $mappingID, $columnNumber);
    if ($isUpdate) {
      Civi\Api4\MappingField::update(FALSE)
        ->setValues($mappedField)
        ->addWhere('column_number', '=', $columnNumber)
        ->addWhere('mapping_id', '=', $mappingID)
        ->execute();
    }
    else {
      Civi\Api4\MappingField::create(FALSE)
        ->setValues($mappedField)->execute();
    }
  }

  /**
   * Save the Field Mapping.
   *
   * @param string $mappingType
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function saveMapping(string $mappingType): void {
    //Updating Mapping Records
    if ($this->getSubmittedValue('updateMapping')) {
      foreach (array_keys($this->getColumnHeaders()) as $i) {
        $this->saveMappingField($this->getSubmittedValue('mappingId'), $i, TRUE);
      }
    }
    //Saving Mapping Details and Records
    if ($this->getSubmittedValue('saveMapping')) {
      $mappingParams = [
        'name' => $this->getSubmittedValue('saveMappingName'),
        'description' => $this->getSubmittedValue('saveMappingDesc'),
        'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', $mappingType),
      ];
      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

      foreach (array_keys($this->getColumnHeaders()) as $i) {
        $this->saveMappingField($saveMapping->id, $i, FALSE);
      }
      $this->set('savedMapping', $saveMapping->id);
    }
  }

}
