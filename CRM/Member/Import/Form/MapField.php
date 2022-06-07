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
 * This class gets the name of the file to upload
 */
class CRM_Member_Import_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->buildSavedMappingFields($this->getSubmittedValue('savedMapping'));
    $this->addFormRule(array('CRM_Member_Import_Form_MapField', 'formRule'), $this);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $columnHeaders = $this->getColumnHeaders();
    $hasHeaders = $this->getSubmittedValue('skipColumnHeader');
    $headerPatterns = $this->getHeaderPatterns();
    $dataPatterns = $this->getDataPatterns();
    // For most fields using the html label is a good thing
    // but for contact ID we really want to specify ID.
    $this->_mapperFields['membership_contact_id'] = ts('Contact ID');
    $sel1 = $this->_mapperFields;
    if (!$this->getSubmittedValue('onDuplicate')) {
      // If not updating then do not allow membership id.
      unset($sel1['membership_id']);
    }

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;

    $fieldMappings = $this->getFieldMappings();

    foreach ($columnHeaders as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', array(1 => $i)), NULL);
      $jsSet = FALSE;
      if ($this->getSubmittedValue('savedMapping')) {
        $fieldMapping = $fieldMappings[$i] ?? NULL;
        if (isset($fieldMappings[$i])) {
          if ($fieldMapping['name'] != ts('do_not_import')) {
            //When locationType is not set
            $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";

            //When phoneType is not set
            $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";

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
            $defaults["mapper[$i]"] = array($this->defaultFromHeader($columnHeader, $headerPatterns));
          }
          else {
            $defaults["mapper[$i]"] = array($this->defaultFromData($dataPatterns, $i));
          }
        }
        //end of load mapping
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";
        if ($this->getSubmittedValue('skipColumnHeader')) {
          // Infer the default from the skipped headers if we have them
          $defaults["mapper[$i]"] = array(
            $this->defaultFromHeader($columnHeader,
              $headerPatterns
            ),
            //                     $defaultLocationType->id
            0,
          );
        }
        else {
          // Otherwise guess the default from the form of the data
          $defaults["mapper[$i]"] = array(
            $this->defaultFromData($dataPatterns, $i),
            //                     $defaultLocationType->id
            0,
          );
        }
      }
      $sel->setOptions([$sel1]);
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
   * @return array|bool
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];

    $importKeys = [];
    foreach ($fields['mapper'] as $mapperPart) {
      $importKeys[] = $mapperPart[0];
    }
    // FIXME: should use the schema titles, not redeclare them
    $requiredFields = array(
      'membership_contact_id' => ts('Contact ID'),
      'membership_type_id' => ts('Membership Type'),
      'membership_start_date' => ts('Membership Start Date'),
    );
    $params = array(
      'used' => 'Unsupervised',
      'contact_type' => $self->getContactType(),
    );
    [$ruleFields, $threshold] = CRM_Dedupe_BAO_DedupeRuleGroup::dedupeRuleFieldsWeight($params);
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

    foreach ($requiredFields as $field => $title) {
      if (!in_array($field, $importKeys)) {
        if ($field === 'membership_contact_id') {
          if ((($weightSum >= $threshold || in_array('external_identifier', $importKeys)) &&
              $self->getSubmittedValue('onDuplicate') != CRM_Import_Parser::DUPLICATE_UPDATE
            ) ||
            in_array('membership_id', $importKeys)
          ) {
            continue;
          }
          if (!isset($errors['_qf_default'])) {
            $errors['_qf_default'] = '';
          }
          $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', array(
            1 => $threshold,
          )) . ' ' . ts('(OR Membership ID if update mode.)') . '<br />';
        }
        else {
          if (!isset($errors['_qf_default'])) {
            $errors['_qf_default'] = '';
          }
          $errors['_qf_default'] .= ts('Missing required field: %1', array(
            1 => $title,
          )) . '<br />';
        }
      }
    }

    if (!empty($fields['saveMapping'])) {
      $nameField = $fields['saveMappingName'] ?? NULL;
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Membership'))) {
          $errors['saveMappingName'] = ts('Duplicate Import Membership Mapping Name');
        }
      }
    }

    if (!empty($errors)) {
      if (!empty($errors['saveMappingName'])) {
        $_flag = 1;
        $assignError = new CRM_Core_Page();
        $assignError->assign('mappingDetailsError', $_flag);
      }
      return $errors;
    }

    return TRUE;
  }

  /**
   * Get the mapping name per the civicrm_mapping_field.type_id option group.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Membership';
  }

  /**
   * @return \CRM_Member_Import_Parser_Membership
   */
  protected function getParser(): CRM_Member_Import_Parser_Membership {
    if (!$this->parser) {
      $this->parser = new CRM_Member_Import_Parser_Membership();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

  /**
   * Get the fields to be highlighted in the UI.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getHighlightedFields(): array {
    $highlightedFields = [];
    //CRM-2219 removing other required fields since for update only
    //membership id is required.
    if ($this->getSubmittedValue('onDuplicate') == CRM_Import_Parser::DUPLICATE_UPDATE) {
      $remove = [
        'membership_contact_id',
        'email',
        'first_name',
        'last_name',
        'external_identifier',
      ];
      foreach ($remove as $value) {
        unset($this->_mapperFields[$value]);
      }
      $highlightedFieldsArray = [
        'membership_id',
        'membership_start_date',
        'membership_type_id',
      ];
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }
    elseif ($this->getSubmittedValue('onDuplicate') == CRM_Import_Parser::DUPLICATE_SKIP) {
      unset($this->_mapperFields['membership_id']);
      $highlightedFieldsArray = [
        'membership_contact_id',
        'email',
        'external_identifier',
        'membership_start_date',
        'membership_type_id',
      ];
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }
    return $highlightedFields;
  }

}
