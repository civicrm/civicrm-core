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
class CRM_Contribute_Import_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    $highlightedFields = ['financial_type_id', 'total_amount'];
    //CRM-2219 removing other required fields since for updation only
    //invoice id or trxn id or contribution id is required.
    if ($this->isUpdateExisting()) {
      $remove = [
        'contribution_contact_id',
        'email',
        'first_name',
        'last_name',
        'external_identifier',
      ];
      foreach ($remove as $value) {
        unset($this->_mapperFields[$value]);
      }

      //modify field title only for update mode. CRM-3245
      foreach ([
        'contribution_id',
        'invoice_id',
        'trxn_id',
      ] as $key) {
        $this->_mapperFields[$key] .= ' (match to contribution record)';
        $highlightedFields[] = $key;
      }
    }
    elseif ($this->isSkipExisting()) {
      unset($this->_mapperFields['contribution_id']);
      $highlightedFieldsArray = [
        'contribution_contact_id',
        'email',
        'first_name',
        'last_name',
        'external_identifier',
      ];
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }

    // modify field title for contribution status
    $this->_mapperFields['contribution_status_id'] = ts('Contribution Status');

    $this->assign('highlightedFields', $highlightedFields);
  }

  /**
   * Should contact fields be filtered which determining fields to show.
   *
   * This applies to Contribution import as we put all contact fields in the metadata
   * but only present those used for a match - but will permit create via LeXIM.
   *
   * @return bool
   */
  protected function isFilterContactFields() : bool {
    return TRUE;
  }

  /**
   * Build the form object.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    $this->addSavedMappingFields();

    $this->addFormRule([
      'CRM_Contribute_Import_Form_MapField',
      'formRule',
    ], $this);

    $sel1 = $this->_mapperFields;

    if (!$this->isUpdateExisting()) {
      unset($sel1['id']);
      unset($sel1['contribution_id']);
    }

    $softCreditFields['contact_id'] = ts('Contact ID');
    $softCreditFields['external_identifier'] = ts('External ID');
    $softCreditFields['email'] = ts('Email');

    $sel2['soft_credit'] = $softCreditFields;
    $sel3['soft_credit']['contact_id'] = $sel3['soft_credit']['external_identifier'] = $sel3['soft_credit']['email'] = CRM_Core_OptionGroup::values('soft_credit_type');

    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $sel->setOptions([$sel1, $sel2, $sel3]);
    }
    $defaults = $this->getDefaults();
    $this->setDefaults($defaults);

    $js = "<script type='text/javascript'>\n";
    foreach ($defaults as $index => $default) {
      //  e.g swapOptions(document.forms.MapField, 'mapper[0]', 0, 3, 'hs_mapper_0_');
      // where 0 is the highest populated field number in the array and 3 is the maximum.
      $js .= "swapOptions(document.forms.MapField, '$index', " . (array_key_last(array_filter($default)) ?: 0) . ", 3, 'hs_mapper_0_');\n";
    }
    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    $this->addFormButtons();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param self $self
   *
   * @return array|true
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $mapperError = [];
    try {
      $parser = $self->getParser();
      $rule = $parser->getDedupeRule($self->getContactType());
      if (!$self->isUpdateExisting()) {
        $missingDedupeFields = $self->validateDedupeFieldsSufficientInMapping($rule, $fields['mapper']);
        if ($missingDedupeFields) {
          $mapperError[] = $missingDedupeFields;
        }
      }
      $parser->validateMapping($fields['mapper']);
    }
    catch (CRM_Core_Exception $e) {
      $mapperError[] = $e->getMessage();
    }
    if (!empty($mapperError)) {
      return ['_qf_default' => implode('<br/>', $mapperError)];
    }
    return TRUE;
  }

  /**
   * Get the mapping name per the civicrm_mapping_field.type_id option group.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Contribution';
  }

  /**
   * @return \CRM_Contribute_Import_Parser_Contribution
   */
  protected function getParser(): CRM_Contribute_Import_Parser_Contribution {
    if (!$this->parser) {
      $this->parser = new CRM_Contribute_Import_Parser_Contribution();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

  /**
   * Get default values for the mapping.
   *
   * This looks up any saved mapping or derives them from the headers if possible.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDefaults(): array {
    $defaults = [];
    $fieldMappings = $this->getFieldMappings();
    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $defaults["mapper[$i]"] = [];
      if ($this->getSubmittedValue('savedMapping')) {
        $fieldMapping = $fieldMappings[$i] ?? NULL;
        if ($fieldMapping) {
          if ($fieldMapping['name'] !== ts('do_not_import')) {
            // $mapping contact_type is not really a contact type - the data has been mangled
            // into that field - see https://lab.civicrm.org/dev/core/-/issues/654
            // Since the soft credit type id is not stored we can't load it here.
            $defaults["mapper[$i]"] = [$fieldMapping['name'], $fieldMapping['contact_type'] ?? '', ''];
          }
        }
      }
      elseif ($this->getSubmittedValue('skipColumnHeader')) {
        $defaults["mapper[$i]"][0] = $this->guessMappingBasedOnColumns($columnHeader);
      }
    }

    return $defaults;
  }

  /**
   * Validate the the mapped fields contain enough to meet the dedupe rule lookup requirements.
   *
   * @param array $rule
   * @param array $mapper
   *
   * @return string|false
   *   Error string if insufficient.
   */
  protected function validateDedupeFieldsSufficientInMapping(array $rule, array $mapper): ?string {
    $threshold = $rule['threshold'];
    $ruleFields = $rule['fields'];
    $weightSum = 0;
    foreach ($mapper as $mapping) {
      if ($mapping[0] === 'external_identifier') {
        // It is enough to have external identifier mapped.
        $weightSum = $threshold;
        break;
      }
      if (array_key_exists($mapping[0], $ruleFields)) {
        $weightSum += $ruleFields[$mapping[0]];
      }
    }
    if ($weightSum < $threshold) {
      return $rule['rule_message'];
    }
    return NULL;
  }

}
