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
   * Check if required fields are present.
   *
   * @param CRM_Contribute_Import_Form_MapField $self
   * @param string $contactORContributionId
   * @param array $importKeys
   * @param array $errors
   * @param int $weightSum
   * @param int $threshold
   * @param string $fieldMessage
   *
   * @return array
   */
  protected static function checkRequiredFields($self, string $contactORContributionId, array $importKeys, array $errors, int $weightSum, $threshold, string $fieldMessage): array {
    // FIXME: should use the schema titles, not redeclare them
    $requiredFields = [
      $contactORContributionId == 'contribution_id' ? 'contribution_id' : 'contribution_contact_id' => $contactORContributionId == 'contribution_id' ? ts('Contribution ID') : ts('Contact ID'),
      'total_amount' => ts('Total Amount'),
      'financial_type_id' => ts('Financial Type'),
    ];

    foreach ($requiredFields as $field => $title) {
      if (!in_array($field, $importKeys)) {
        if (empty($errors['_qf_default'])) {
          $errors['_qf_default'] = '';
        }
        if ($field == $contactORContributionId) {
          if (!($weightSum >= $threshold || in_array('external_identifier', $importKeys)) &&
            !$self->isUpdateExisting()
          ) {
            $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [1 => $threshold]) . '<br />';
          }
          elseif ($self->isUpdateExisting() &&
            !(in_array('invoice_id', $importKeys) || in_array('trxn_id', $importKeys) ||
              in_array('contribution_id', $importKeys)
            )
          ) {
            $errors['_qf_default'] .= ts('Invoice ID or Transaction ID or Contribution ID are required to match to the existing contribution records in Update mode.') . '<br />';
          }
        }
        else {
          $errors['_qf_default'] .= ts('Missing required field: %1', [1 => $title]) . '<br />';
        }
      }
    }
    return $errors;
  }

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
    $errors = [];
    $fieldMessage = NULL;
    $contactORContributionId = $self->isUpdateExisting() ? 'contribution_id' : 'contribution_contact_id';
    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }

      $params = [
        'used' => 'Unsupervised',
        'contact_type' => $self->getContactType(),
      ];
      [$ruleFields, $threshold] = CRM_Dedupe_BAO_DedupeRuleGroup::dedupeRuleFieldsWeight($params);
      $weightSum = 0;
      foreach ($importKeys as $key => $val) {
        if (array_key_exists($val, $ruleFields)) {
          $weightSum += $ruleFields[$val];
        }
        if ($val == "soft_credit") {
          $mapperKey = CRM_Utils_Array::key('soft_credit', $importKeys);
          if (empty($fields['mapper'][$mapperKey][1])) {
            if (empty($errors['_qf_default'])) {
              $errors['_qf_default'] = '';
            }
            $errors['_qf_default'] .= ts('Missing required fields: Soft Credit') . '<br />';
          }
        }
      }
      foreach ($ruleFields as $field => $weight) {
        $fieldMessage .= ' ' . $field . '(weight ' . $weight . ')';
      }
      $errors = self::checkRequiredFields($self, $contactORContributionId, $importKeys, $errors, $weightSum, $threshold, $fieldMessage);

      //at least one field should be mapped during update.
      if ($self->isUpdateExisting()) {
        $atleastOne = FALSE;
        foreach ($self->_mapperFields as $key => $field) {
          if (in_array($key, $importKeys) &&
            !in_array($key, [
              'doNotImport',
              'contribution_id',
              'invoice_id',
              'trxn_id',
            ])
          ) {
            $atleastOne = TRUE;
            break;
          }
        }
        if (!$atleastOne) {
          $errors['_qf_default'] .= ts('At least one contribution field needs to be mapped for update during update mode.') . '<br />';
        }
      }
    }

    if (!empty($errors)) {
      if (!empty($errors['_qf_default'])) {
        CRM_Core_Session::setStatus($errors['_qf_default'], ts("Error"), "error");
        return $errors;
      }
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

}
