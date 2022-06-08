<?php

/**
 * Class CRM_Custom_Import_Form_MapField
 */
class CRM_Custom_Import_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $savedMappingID = (int) $this->getSubmittedValue('savedMapping');
    $this->buildSavedMappingFields($savedMappingID);
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
  public static function formRule($fields) {
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

    if (!empty($fields['saveMapping'])) {
      $nameField = $fields['saveMappingName'] ?? NULL;
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Multi value custom data'))) {
          $errors['saveMappingName'] = ts('Duplicate Mapping Name');
        }
      }
    }

    //display Error if loaded mapping is not selected
    if (array_key_exists('loadMapping', $fields)) {
      $getMapName = $fields['savedMapping'] ?? NULL;
      if (empty($getMapName)) {
        $errors['savedMapping'] = ts('Select saved mapping');
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
