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
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();
    $this->addFormRule(['CRM_Member_Import_Form_MapField', 'formRule'], $this);

    $options = $this->getFieldOptions();
    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $this->add('select2', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), $options, FALSE, ['class' => 'big', 'placeholder' => ts('- do not import -')]);
    }

    $this->setDefaults($this->getDefaults());

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
      $importKeys[] = $mapperPart;
    }
    // FIXME: should use the schema titles, not redeclare them
    $requiredFields = [
      'membership_contact_id' => ts('Contact ID'),
      'membership_type_id' => ts('Membership Type'),
      'membership_start_date' => ts('Membership Start Date'),
    ];
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
          $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [
            1 => $threshold,
          ]) . ' ' . ts('(OR Membership ID if update mode.)') . '<br />';
        }
        else {
          if (!isset($errors['_qf_default'])) {
            $errors['_qf_default'] = '';
          }
          $errors['_qf_default'] .= ts('Missing required field: %1', [
            1 => $title,
          ]) . '<br />';
        }
      }
    }
    return $errors ?: TRUE;
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
