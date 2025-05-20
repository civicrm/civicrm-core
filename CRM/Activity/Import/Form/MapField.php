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
class CRM_Activity_Import_Form_MapField extends CRM_Import_Form_MapField {

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
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'activity_import';
  }

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Validate that required fields are present.
   *
   * @param array $importKeys
   *
   * return array|null
   */
  protected function validateRequiredFields(array $importKeys): ?array {
    $errors = [];
    $requiredFields = [
      'Activity.activity_date_time' => ts('Activity Date'),
      'Activity.subject' => ts('Activity Subject'),
      'Activity.activity_type_id' => ts('Activity Type ID'),
    ];

    foreach ($requiredFields as $field => $title) {
      if (!in_array($field, $importKeys, TRUE)) {
        $errors[] = ts('Missing required field: %1', [1 => $title]) . '<br />';
      }
    }
    return $errors;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();
    $this->addFormRule(['CRM_Activity_Import_Form_MapField', 'formRule'], $this);

    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $this->add('select', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), $this->getAvailableFields(), FALSE, ['class' => 'big', 'placeholder' => ts('- do not import -')]);
    }

    $this->setDefaults($this->getDefaults());

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
      if (($field['entity'] ?? '') === 'Contact' && $this->isFilterContactFields() && empty($field['match_rule'])) {
        // Filter out metadata that is intended for create & update - this is not available in the quick-form
        // but is now loaded in the Parser for the LexIM variant.
        continue;
      }
      $prefix = empty($field['entity_name']) ? '' : $field['entity_name'] . '.';
      $return[$prefix . $name] = $field['title'];
    }
    return $return;
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $files
   * @param self $self
   *
   * @return array|bool
   *   list of errors to be posted back to the form
   * @throws \CRM_Core_Exception
   */
  public static function formRule(array $fields, $files, $self): bool|array {
    $errors = [];

    if (!array_key_exists('savedMapping', $fields)) {
      if (!in_array('id', $fields['mapper'], TRUE)) {
        $importKeys = [];
        foreach ($fields['mapper'] as $field) {
          $importKeys[] = [$field];
        }
        $parser = $self->getParser();
        $rule = $parser->getDedupeRule('Individual', $self->getUserJob()['metadata']['entity_configuration']['TargetContact']['dedupe_rule'] ?? NULL);
        $missingFields = $self->validateContactFields($rule, $importKeys, ['external_identifier', 'id']);

        $missingFields += $self->validateRequiredFields($fields['mapper']);
        if ($missingFields) {
          $errors['_qf_default'] = implode(',', $missingFields);
        }
      }
    }
    return $errors ?: TRUE;
  }

  /**
   * @return CRM_Activity_Import_Parser_Activity
   */
  protected function getParser(): CRM_Activity_Import_Parser_Activity {
    if (!$this->parser) {
      $this->parser = new CRM_Activity_Import_Parser_Activity();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

  protected function getHighlightedFields(): array {
    $highlightedFields = [];
    $requiredFields = [
      'activity_date_time',
      'activity_type_id',
      'target_contact_id',
      'activity_subject',
    ];
    foreach ($requiredFields as $val) {
      $highlightedFields[] = $val;
    }
    return $highlightedFields;
  }

  public function getImportType(): string {
    return 'Import Activity';
  }

  /**
   * Get the mapping name per the civicrm_mapping_field.type_id option group.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Activity';
  }

}
