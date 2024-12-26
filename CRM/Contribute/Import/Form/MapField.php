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
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'contribution_import';
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
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();

    $this->addFormRule([
      'CRM_Contribute_Import_Form_MapField',
      'formRule',
    ], $this);

    $selectColumn1 = $this->getAvailableFields();

    $selectColumn2 = [];
    $softCreditTypes = CRM_Core_OptionGroup::values('soft_credit_type');
    foreach (array_keys($selectColumn1) as $fieldName) {
      if (strpos($fieldName, 'soft_credit__contact__') === 0) {
        $selectColumn2[$fieldName] = $softCreditTypes;
      }
    }

    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $sel->setOptions([$selectColumn1, $selectColumn2]);
    }
    $defaults = $this->getDefaults();
    $this->setDefaults($defaults);

    $js = "<script type='text/javascript'>\n";
    foreach ($defaults as $index => $default) {
      //  e.g swapOptions(document.forms.MapField, 'mapper[0]', 0, 3, 'hs_mapper_0_');
      // where 0 is the highest populated field number in the array and 3 is the maximum.
      $js .= "swapOptions(document.forms.MapField, '$index', " . (array_key_last(array_filter($default)) ?: 0) . ", 2, 'hs_mapper_0_');\n";
    }
    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    $this->addFormButtons();
  }

  /**
   * Get the fields available for import selection.
   *
   * @return array
   *   e.g ['first_name' => 'First Name', 'last_name' => 'Last Name'....
   */
  protected function getAvailableFields(): array {
    $return = [];
    foreach ($this->getFields() as $name => $field) {
      if ($name === 'id' && $this->isSkipExisting()) {
        // Duplicates are being skipped so id matching is not available.
        continue;
      }
      if ($this->isUpdateExisting() && in_array($name, ['contribution_contact_id', 'email', 'first_name', 'last_name', 'external_identifier', 'email_primary.email'], TRUE)) {
        continue;
      }
      if ($this->isUpdateExisting() && in_array($name, ['contribution_id', 'invoice_id', 'trxn_id'], TRUE)) {
        $field['title'] .= (' ' . ts('(match to contribution record)'));
      }
      // Swap out dots for double underscores so as not to break the quick form js.
      // We swap this back on postProcess.
      // Arg - we need to swap out _. first as it seems some groups end in a trailing underscore,
      // which is indistinguishable to convert back - ie ___ could be _. or ._.
      // https://lab.civicrm.org/dev/core/-/issues/4317#note_91322
      $name = str_replace('_.', '~~', $name);
      $name = str_replace('.', '__', $name);
      if (($field['entity'] ?? '') === 'Contact' && $this->isFilterContactFields() && empty($field['match_rule'])) {
        // Filter out metadata that is intended for create & update - this is not available in the quick-form
        // but is now loaded in the Parser for the LexIM variant.
        continue;
      }
      $return[$name] = $field['html']['label'] ?? $field['title'];
    }
    return $return;
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
      $rule = $parser->getDedupeRule($self->getContactType(), $self->getUserJob()['metadata']['entity_configuration']['Contact']['dedupe_rule'] ?? NULL);
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
        $fieldMapping = $fieldMappings[$i] ?? [];
        $this->addMappingToDefaults($defaults, $fieldMapping, $i);
      }
      elseif ($this->getSubmittedValue('skipColumnHeader')) {
        $defaults["mapper[$i]"][0] = $this->guessMappingBasedOnColumns($columnHeader);
      }
    }
    $userDefinedMappings = array_diff_key($this->getFieldMappings(), $this->getColumnHeaders());
    foreach ($userDefinedMappings as $index => $mapping) {
      $this->addMappingToDefaults($defaults, $mapping, $index);
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
      // Because api v4 style fields have a . and QuickForm multiselect js does
      // not cope with a . the quick form layer will use a double underscore
      // as a stand in (the angular layer will not)
      $fieldName = str_replace('__', '.', $mapping[0]);
      if (str_contains($fieldName, '.')) {
        // If the field name contains a . - eg. address_primary.street_address
        // we just want the part after the .
        $fieldName = substr($fieldName, strpos($fieldName, '.') + 1);
      }
      if ($fieldName === 'external_identifier' || $fieldName === 'contribution_contact_id' || $fieldName === 'contact__id') {
        // It is enough to have external identifier mapped.
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
   * Add the saved mapping to the defaults.
   *
   * @param array $defaults
   * @param array $fieldMapping
   * @param int $rowNumber
   *
   * @return void
   */
  public function addMappingToDefaults(array &$defaults, array $fieldMapping, int $rowNumber): void {
    if ($fieldMapping) {
      if ($fieldMapping['name'] !== ts('do_not_import')) {
        // $mapping contact_type is not really a contact type - the 'about this entity' data has been mangled
        // into that field - see https://lab.civicrm.org/dev/core/-/issues/654
        $softCreditTypeID = '';
        $entityData = json_decode($fieldMapping['contact_type'] ?? '', TRUE);
        if (!empty($entityData)) {
          $softCreditTypeID = (int) $entityData['soft_credit']['soft_credit_type_id'];
        }
        $fieldName = $this->isQuickFormMode ? str_replace('.', '__', $fieldMapping['name']) : $fieldMapping['name'];
        $defaults["mapper[$rowNumber]"] = [$fieldName, $softCreditTypeID];
      }
    }
  }

  /**
   * @return string[]
   */
  protected function getHighlightedFields(): array {
    $highlightedFields = ['financial_type_id', 'total_amount'];
    //CRM-2219 removing other required fields since for updating only
    //invoice id or trxn id or contribution id is required.
    if ($this->isUpdateExisting()) {
      //modify field title only for update mode. CRM-3245
      foreach (['contribution_id', 'invoice_id', 'trxn_id'] as $key) {
        $highlightedFields[] = $key;
      }
    }
    elseif ($this->isSkipExisting()) {
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
    return $highlightedFields;
  }

}
