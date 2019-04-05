<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class gets the name of the file to upload.
 */
class CRM_Contribute_Import_Form_MapField extends CRM_Import_Form_MapField {


  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_mapperFields = $this->get('fields');
    asort($this->_mapperFields);

    $this->_columnCount = $this->get('columnCount');
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $this->assign('dataValues', $this->_dataValues);

    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');
    $this->_onDuplicate = $this->get('onDuplicate', isset($onDuplicate) ? $onDuplicate : "");

    if ($skipColumnHeader) {
      $this->assign('skipColumnHeader', $skipColumnHeader);
      $this->assign('rowDisplayCount', 3);
      // If we had a column header to skip, stash it for later

      $this->_columnHeaders = $this->_dataValues[0];
    }
    else {
      $this->assign('rowDisplayCount', 2);
    }
    $highlightedFields = ['financial_type', 'total_amount'];
    //CRM-2219 removing other required fields since for updation only
    //invoice id or trxn id or contribution id is required.
    if ($this->_onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
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
    elseif ($this->_onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
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
   */
  public function buildQuickForm() {
    //to save the current mappings
    if (!$this->get('savedMapping')) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    else {
      $savedMapping = $this->get('savedMapping');

      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingRelation) = CRM_Core_BAO_Mapping::getMappingFields($savedMapping);

      $mappingName = $mappingName[1];
      $mappingContactType = $mappingContactType[1];
      $mappingLocation = CRM_Utils_Array::value('1', CRM_Utils_Array::value(1, $mappingLocation));
      $mappingPhoneType = CRM_Utils_Array::value('1', CRM_Utils_Array::value(1, $mappingPhoneType));
      $mappingRelation = CRM_Utils_Array::value('1', CRM_Utils_Array::value(1, $mappingRelation));

      //mapping is to be loaded from database

      $params = ['id' => $savedMapping];
      $temp = [];
      $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

      $this->assign('loadedMapping', $mappingDetails->name);
      $this->set('loadedMapping', $savedMapping);

      $getMappingName = new CRM_Core_DAO_Mapping();
      $getMappingName->id = $savedMapping;
      $getMappingName->mapping_type = 'Import Contributions';
      $getMappingName->find();
      while ($getMappingName->fetch()) {
        $mapperName = $getMappingName->name;
      }

      $this->assign('savedName', $mapperName);

      $this->add('hidden', 'mappingId', $savedMapping);

      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }

    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, ['onclick' => "showSaveDetails(this)"]);

    $this->addFormRule([
      'CRM_Contribute_Import_Form_MapField',
      'formRule',
    ], $this);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);
    $hasHeaders = !empty($this->_columnHeaders);
    $headerPatterns = $this->get('headerPatterns');
    $dataPatterns = $this->get('dataPatterns');
    $mapperKeysValues = $this->controller->exportValue($this->_name, 'mapper');

    /* Initialize all field usages to false */
    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }
    $this->_location_types = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $sel1 = $this->_mapperFields;

    if (!$this->get('onDuplicate')) {
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

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $jsSet = FALSE;
      if ($this->get('savedMapping')) {
        if (isset($mappingName[$i])) {
          if ($mappingName[$i] != ts('- do not import -')) {

            $mappingHeader = array_keys($this->_mapperFields, $mappingName[$i]);
            // reusing contact_type field array for soft credit
            $softField = isset($mappingContactType[$i]) ? $mappingContactType[$i] : 0;

            if (!$softField) {
              $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
            }

            $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
            $defaults["mapper[$i]"] = [
              CRM_Utils_Array::value(0, $mappingHeader),
              ($softField) ? $softField : "",
              (isset($locationId)) ? $locationId : "",
              (isset($phoneType)) ? $phoneType : "",
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
            $defaults["mapper[$i]"] = [$this->defaultFromHeader($this->_columnHeaders[$i], $headerPatterns)];
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
          $columnKey = array_search($this->_columnHeaders[$i], $this->_mapperFields);
          if (isset($this->_fieldUsed[$columnKey])) {
            $defaults["mapper[$i]"] = $columnKey;
            $this->_fieldUsed[$key] = TRUE;
          }
          else {
            // Infer the default from the column names if we have them
            $defaults["mapper[$i]"] = [
              $this->defaultFromHeader($this->_columnHeaders[$i], $headerPatterns),
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
        if (!empty($mapperKeysValues) && $mapperKeysValues[$i][0] == 'soft_credit') {
          $js .= "cj('#mapper_" . $i . "_1').val($mapperKeysValues[$i][1]);\n";
          $js .= "cj('#mapper_" . $i . "_2').val($mapperKeysValues[$i][2]);\n";
        }
      }
      $sel->setOptions([$sel1, $sel2, $sel3, $sel4]);
    }
    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    //set warning if mismatch in more than
    if (isset($mappingName)) {
      if (($this->_columnCount != count($mappingName))) {
        $warning++;
      }
    }
    if ($warning != 0 && $this->get('savedMapping')) {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts('The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.'));
    }
    else {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(NULL);
    }

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
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    $fieldMessage = NULL;
    $contactORContributionId = $self->_onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE ? 'contribution_id' : 'contribution_contact_id';
    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }

      $contactTypeId = $self->get('contactType');
      $contactTypes = [
        CRM_Import_Parser::CONTACT_INDIVIDUAL => 'Individual',
        CRM_Import_Parser::CONTACT_HOUSEHOLD => 'Household',
        CRM_Import_Parser::CONTACT_ORGANIZATION => 'Organization',
      ];
      $params = [
        'used' => 'Unsupervised',
        'contact_type' => isset($contactTypes[$contactTypeId]) ? $contactTypes[$contactTypeId] : '',
      ];
      list($ruleFields, $threshold) = CRM_Dedupe_BAO_RuleGroup::dedupeRuleFieldsWeight($params);
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

      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = [
        $contactORContributionId == 'contribution_id' ? 'contribution_id' : 'contribution_contact_id' => $contactORContributionId == 'contribution_id' ? ts('Contribution ID') : ts('Contact ID'),
        'total_amount' => ts('Total Amount'),
        'financial_type' => ts('Financial Type'),
      ];

      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $importKeys)) {
          if (empty($errors['_qf_default'])) {
            $errors['_qf_default'] = '';
          }
          if ($field == $contactORContributionId) {
            if (!($weightSum >= $threshold || in_array('external_identifier', $importKeys)) &&
              $self->_onDuplicate != CRM_Import_Parser::DUPLICATE_UPDATE
            ) {
              $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [
                  1 => $threshold,
                ]) . '<br />';
            }
            elseif ($self->_onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE &&
              !(in_array('invoice_id', $importKeys) || in_array('trxn_id', $importKeys) ||
                in_array('contribution_id', $importKeys)
              )
            ) {
              $errors['_qf_default'] .= ts('Invoice ID or Transaction ID or Contribution ID are required to match to the existing contribution records in Update mode.') . '<br />';
            }
          }
          else {
            $errors['_qf_default'] .= ts('Missing required field: %1', [
                1 => $title,
              ]) . '<br />';
          }
        }
      }

      //at least one field should be mapped during update.
      if ($self->_onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
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
      $nameField = CRM_Utils_Array::value('saveMappingName', $fields);
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
   * Process the mapped fields and map it into the uploaded file preview the file and extract some summary statistics.
   */
  public function postProcess() {
    $params = $this->controller->exportValues('MapField');

    //reload the mapfield if load mapping is pressed
    if (!empty($params['savedMapping'])) {
      $this->set('savedMapping', $params['savedMapping']);
      $this->controller->resetPage($this->_name);
      return;
    }

    $fileName = $this->controller->exportValue('DataSource', 'uploadFile');
    $seperator = $this->controller->exportValue('DataSource', 'fieldSeparator');
    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');

    $mapper = $mapperKeys = $mapperKeysMain = $mapperSoftCredit = $softCreditFields = $mapperPhoneType = $mapperSoftCreditType = [];
    $mapperKeys = $this->controller->exportValue($this->_name, 'mapper');

    $softCreditTypes = CRM_Core_OptionGroup::values('soft_credit_type');

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $mapper[$i] = $this->_mapperFields[$mapperKeys[$i][0]];
      $mapperKeysMain[$i] = $mapperKeys[$i][0];

      if (isset($mapperKeys[$i][0]) && $mapperKeys[$i][0] == 'soft_credit') {
        $mapperSoftCredit[$i] = $mapperKeys[$i][1];
        if (strpos($mapperSoftCredit[$i], '_') !== FALSE) {
          list($first, $second) = explode('_', $mapperSoftCredit[$i]);
          $softCreditFields[$i] = ucwords($first . " " . $second);
        }
        else {
          $softCreditFields[$i] = $mapperSoftCredit[$i];
        }
        $mapperSoftCreditType[$i] = [
          'value' => isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : '',
          'label' => isset($softCreditTypes[$mapperKeys[$i][2]]) ? $softCreditTypes[$mapperKeys[$i][2]] : '',
        ];
      }
      else {
        $mapperSoftCredit[$i] = $softCreditFields[$i] = $mapperSoftCreditType[$i] = NULL;
      }
    }

    $this->set('mapper', $mapper);
    $this->set('softCreditFields', $softCreditFields);
    $this->set('mapperSoftCreditType', $mapperSoftCreditType);

    // store mapping Id to display it in the preview page
    $this->set('loadMappingId', CRM_Utils_Array::value('mappingId', $params));

    //Updating Mapping Records
    if (!empty($params['updateMapping'])) {
      $mappingFields = new CRM_Core_DAO_MappingField();
      $mappingFields->mapping_id = $params['mappingId'];
      $mappingFields->find();

      $mappingFieldsId = [];
      while ($mappingFields->fetch()) {
        if ($mappingFields->id) {
          $mappingFieldsId[$mappingFields->column_number] = $mappingFields->id;
        }
      }

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $updateMappingFields = new CRM_Core_DAO_MappingField();
        $updateMappingFields->id = $mappingFieldsId[$i];
        $updateMappingFields->mapping_id = $params['mappingId'];
        $updateMappingFields->column_number = $i;
        $updateMappingFields->name = $mapper[$i];

        //reuse contact_type field in db to store fields associated with soft credit
        $updateMappingFields->contact_type = isset($mapperSoftCredit[$i]) ? $mapperSoftCredit[$i] : NULL;
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (!empty($params['saveMapping'])) {
      $mappingParams = [
        'name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Contribution'),
      ];
      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $saveMappingFields = new CRM_Core_DAO_MappingField();
        $saveMappingFields->mapping_id = $saveMapping->id;
        $saveMappingFields->column_number = $i;
        $saveMappingFields->name = $mapper[$i];

        //reuse contact_type field in db to store fields associated with soft credit
        $saveMappingFields->contact_type = isset($mapperSoftCredit[$i]) ? $mapperSoftCredit[$i] : NULL;
        $saveMappingFields->save();
      }
      $this->set('savedMapping', $saveMappingFields->mapping_id);
    }

    $parser = new CRM_Contribute_Import_Parser_Contribution($mapperKeysMain, $mapperSoftCredit, $mapperPhoneType);
    $parser->run($fileName, $seperator, $mapper, $skipColumnHeader,
      CRM_Import_Parser::MODE_PREVIEW, $this->get('contactType')
    );

    // add all the necessary variables to the form
    $parser->set($this);
  }

}
