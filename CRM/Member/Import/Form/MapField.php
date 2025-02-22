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
    // Suppress non-match contact fields at the QuickForm layer as
    // their use will only be on the angular layer.
    foreach ($options as &$option) {
      if ($option['is_contact']) {
        foreach ($option['children'] as $index => $contactField) {
          if (empty($contactField['match_rule'])) {
            unset($option['children'][$index]);
          }
        }
      }
    }
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
    $importKeys = [];
    foreach ($fields['mapper'] as $field) {
      $importKeys[] = [$field];
    }
    $parser = $self->getParser();
    $rule = $parser->getDedupeRule($self->getContactType(), $self->getUserJob()['metadata']['entity_configuration']['Contact']['dedupe_rule'] ?? NULL);
    $errors = $self->validateContactFields($rule, $importKeys, ['external_identifier', 'membership_contact_id', 'contact__id']);

    if (!in_array('membership_id', $fields['mapper'])) {
      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = [
        'membership_type_id' => ts('Membership Type'),
        'membership_start_date' => ts('Membership Start Date'),
      ];
      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $fields['mapper'])) {
          if (!isset($errors['_qf_default'])) {
            $errors['_qf_default'] = '';
          }
          $errors['_qf_default'] .= ts('Missing required field: %1', [1 => $title]) . '<br />';
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
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'membership_import';
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
        'email_primary.email',
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
        'email_primary.email',
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
