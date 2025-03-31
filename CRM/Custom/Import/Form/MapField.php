<?php

/**
 * Class CRM_Custom_Import_Form_MapField
 */
class CRM_Custom_Import_Form_MapField extends CRM_CiviImport_Form_MapField {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'custom_field_import';
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();
    $this->addFormRule([__CLASS__, 'formRule'], $this);
    $this->addMapper();
    $this->addFormButtons();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $files
   * @param \CRM_Custom_Import_Form_MapField $self
   *
   * @return array|bool
   *   list of errors to be posted back to the form
   * @throws \CRM_Core_Exception
   */
  public static function formRule(array $fields, array $files, CRM_Custom_Import_Form_MapField $self) {
    $errors = $self->validateRequiredContactFields();
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Has the user chosen to update existing records.
   * @return bool
   */
  protected function isUpdateExisting(): bool {
    return TRUE;
  }

  /**
   * @return \CRM_Custom_Import_Parser_Api()
   */
  protected function getParser():CRM_Custom_Import_Parser_Api {
    if (!$this->parser) {
      $this->parser = new CRM_Custom_Import_Parser_Api();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

  /**
   * Get the type of used for civicrm_mapping.mapping_type_id.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Multi value custom data';
  }

}
