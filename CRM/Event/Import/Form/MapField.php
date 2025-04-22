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
class CRM_Event_Import_Form_MapField extends CRM_CiviImport_Form_MapField {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'participant_import';
  }

  /**
   * Should contact fields be filtered which determining fields to show.
   *
   * This applies to Participant import as we put all contact fields in the metadata
   * but only present those used for a match in QuickForm - the civiimport extension has
   * more functionality to update and create.
   *
   * @return bool
   */
  protected function isFilterContactFields() : bool {
    return TRUE;
  }

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess(): void {
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
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addSavedMappingFields();
    $this->addFormRule(['CRM_Event_Import_Form_MapField', 'formRule'], $this);
    $this->addMapper();
    $this->addFormButtons();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param array $files
   * @param self $self
   *
   * @return array|true
   *   list of errors to be posted back to the form
   * @throws \CRM_Core_Exception
   */
  public static function formRule($fields, $files, $self) {
    $mappedFields = $self->getMappedFields($fields['mapper']);
    if (!in_array('Participant.id', $mappedFields)) {
      $requiredError = $self->validateRequiredContactFields();
      if (!in_array('Participant.event_id', $mappedFields)) {
        $requiredError[] = ts('Missing required field: %1', [1 => 'Event']) . '<br />';
      }
    }

    return empty($requiredError) ? TRUE : ['_qf_default' => implode('<br', $requiredError)];
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
