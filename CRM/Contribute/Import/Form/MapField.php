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
    $savedMappingID = $this->getSubmittedValue('savedMapping');

    $this->buildSavedMappingFields($savedMappingID);

    $this->addFormRule([
      'CRM_Contribute_Import_Form_MapField',
      'formRule',
    ], $this);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);
    $hasHeaders = $this->getSubmittedValue('skipColumnHeader');
    $headerPatterns = $this->getHeaderPatterns();
    $dataPatterns = $this->getDataPatterns();
    $mapperKeysValues = $this->getSubmittedValue('mapper');
    $columnHeaders = $this->getColumnHeaders();
    $fieldMappings = $this->getFieldMappings();

    /* Initialize all field usages to false */
    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }
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
    $sel4 = NULL;

    // end of soft credit section
    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;

    //used to warn for mismatch column count or mismatch mapping
    $warning = 0;

    foreach ($columnHeaders as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $jsSet = FALSE;
      if ($this->getSubmittedValue('savedMapping')) {
        // $mappingContactType is not really a contact type - the data has been mangled
        // into that field - see https://lab.civicrm.org/dev/core/-/issues/654
        [$mappingName, $mappingContactType] = CRM_Core_BAO_Mapping::getMappingFields($savedMappingID);
        $fieldMapping = $fieldMappings[$i] ?? NULL;
        $mappingContactType = $mappingContactType[1];
        if (isset($fieldMappings[$i])) {
          if ($fieldMapping['name'] !== ts('do_not_import')) {
            $softField = $mappingContactType[$i] ?? '';

            if (!$softField) {
              $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
            }

            $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
            $defaults["mapper[$i]"] = [
              $fieldMapping['name'],
              $softField,
              // Since the soft credit type id is not stored we can't load it here.
              '',
            ];
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
          $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_0_');\n";

          if ($hasHeaders) {
            $defaults["mapper[$i]"] = [$this->defaultFromHeader($columnHeader, $headerPatterns)];
          }
          else {
            $defaults["mapper[$i]"] = [$this->defaultFromData($dataPatterns, $i)];
          }
        }
        //end of load mapping
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_0_');\n";
        if ($hasHeaders) {
          // do array search first to see if has mapped key
          $columnKey = array_search($columnHeader, $this->_mapperFields);
          if (isset($this->_fieldUsed[$columnKey])) {
            $defaults["mapper[$i]"] = $columnKey;
            $this->_fieldUsed[$key] = TRUE;
          }
          else {
            // Infer the default from the column names if we have them
            $defaults["mapper[$i]"] = [
              $this->defaultFromHeader($columnHeader, $headerPatterns),
              0,
            ];
          }
        }
        else {
          // Otherwise guess the default from the form of the data
          $defaults["mapper[$i]"] = [
            $this->defaultFromData($dataPatterns, $i),
            0,
          ];
        }
        if (!empty($mapperKeysValues) && ($mapperKeysValues[$i][0] ?? NULL) === 'soft_credit') {
          $softCreditField = $mapperKeysValues[$i][1];
          $softCreditTypeID = $mapperKeysValues[$i][2];
          $js .= "cj('#mapper_" . $i . "_1').val($softCreditField);\n";
          $js .= "cj('#mapper_" . $i . "_2').val($softCreditTypeID);\n";
        }
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
   * @param $files
   * @param self $self
   *
   * @return array
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

    if (!empty($fields['saveMapping'])) {
      $nameField = $fields['saveMappingName'] ?? NULL;
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Contribution'))) {
          $errors['saveMappingName'] = ts('Duplicate Import Contribution Mapping Name');
        }
      }
    }

    if (!empty($errors)) {
      if (!empty($errors['saveMappingName'])) {
        $_flag = 1;
        $assignError = new CRM_Core_Page();
        $assignError->assign('mappingDetailsError', $_flag);
      }
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

}
