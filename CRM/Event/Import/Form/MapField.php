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
class CRM_Event_Import_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    unset($this->_mapperFields['participant_is_test']);

    if ($this->getSubmittedValue('onDuplicate') == CRM_Import_Parser::DUPLICATE_UPDATE) {
      $remove = [
        'participant_contact_id',
        'email',
        'first_name',
        'last_name',
        'external_identifier',
      ];
      foreach ($remove as $value) {
        unset($this->_mapperFields[$value]);
      }
    }
    elseif (
      $this->getSubmittedValue('onDuplicate') == CRM_Import_Parser::DUPLICATE_SKIP
      || $this->getSubmittedValue('onDuplicate') == CRM_Import_Parser::DUPLICATE_NOCHECK) {
      unset($this->_mapperFields['participant_id']);
    }
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addSavedMappingFields();
    $this->addFormRule(array('CRM_Event_Import_Form_MapField', 'formRule'), $this);
    $this->addMapper();
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
    // define so we avoid notices below
    $errors['_qf_default'] = '';

    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }
      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = array(
        'contact_id' => ts('Contact ID'),
        'event_id' => ts('Event ID'),
      );

      $contactFieldsBelowWeightMessage = self::validateRequiredContactMatchFields($self->getContactType(), $importKeys);

      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $importKeys)) {
          if ($field === 'contact_id') {
            if (!$contactFieldsBelowWeightMessage || in_array('external_identifier', $importKeys) ||
              in_array('participant_id', $importKeys)
            ) {
              continue;
            }
            if ($self->isUpdateExisting()) {
              $errors['_qf_default'] .= ts('Missing required field: Provide Participant ID') . '<br />';
            }
            else {
              $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $contactFieldsBelowWeightMessage " . ' ' . ts('Or Provide Contact ID or External ID.') . '<br />';
            }
          }
          elseif (!in_array('event_title', $importKeys)) {
            $errors['_qf_default'] .= ts('Missing required field: Provide %1 or %2',
                array(1 => $title, 2 => 'Event Title')
              ) . '<br />';
          }
        }
      }
    }

    if (empty($errors['_qf_default'])) {
      unset($errors['_qf_default']);
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * @return CRM_Event_Import_Parser_Participant
   */
  protected function getParser(): CRM_Event_Import_Parser_Participant {
    if (!$this->parser) {
      $this->parser = new CRM_Event_Import_Parser_Participant();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

  /**
   * Get the fields to highlight.
   *
   * @return array
   */
  protected function getHighlightedFields(): array {
    $highlightedFields = [];
    if ($this->isUpdateExisting()) {
      $highlightedFieldsArray = [
        'id',
        'event_id',
        'event_title',
        'status_id',
      ];
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }
    elseif ($this->getSubmittedValue('onDuplicate') == CRM_Import_Parser::DUPLICATE_SKIP ||
      $this->getSubmittedValue('onDuplicate') == CRM_Import_Parser::DUPLICATE_NOCHECK
    ) {
      // this should be retrieved from the parser.
      $highlightedFieldsArray = [
        'contact_id',
        'event_id',
        'email',
        'first_name',
        'last_name',
        'organization_name',
        'household_name',
        'external_identifier',
        'status_id',
      ];
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }
    return $highlightedFields;
  }

  /**
   * Get the mapping name per the civicrm_mapping_field.type_id option group.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Participant';
  }

}
