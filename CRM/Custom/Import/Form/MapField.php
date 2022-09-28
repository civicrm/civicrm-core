<?php

/**
 * Class CRM_Custom_Import_Form_MapField
 */
class CRM_Custom_Import_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();
    $this->addFormRule(['CRM_Custom_Import_Form_MapField', 'formRule']);
    $this->addMapper();
    $this->addFormButtons();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array|bool
   *   list of errors to be posted back to the form
   */
  public static function formRule(array $fields) {
    // todo - this could be shared with other mapFields forms.
    $errors = [];
    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }

      // check either contact id or external identifier
      if (!in_array('contact_id', $importKeys) && !in_array('external_identifier', $importKeys)) {
        if (!isset($errors['_qf_default'])) {
          $errors['_qf_default'] = '';
        }
        $errors['_qf_default'] .= ts('Missing required field: %1', [1 => ts('Contact ID or External Identifier')]);
      }
    }
    return empty($errors) ? TRUE : $errors;
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
