<?php

class CRM_CiviImport_Form_MapField extends CRM_Import_Form_MapField {

  public function preProcess(): void {
    parent::preProcess();
    // Add import-ui app
    Civi::service('angularjs.loader')->addModules('crmCiviimport');
    $this->assignCiviimportVariables();

    $templateJob = $this->getTemplateJob();
    if ($templateJob) {
      Civi::resources()->addVars('crmImportUi', ['savedMapping' => ['name' => substr($templateJob['name'], 7)]]);
    }
  }

  /**
   * Save the Field Mapping.
   *
   * @throws \CRM_Core_Exception
   */
  protected function saveMapping(): void {
    if ($this->getSubmittedValue('saveMapping')) {
      $this->createTemplateJob();
    }
    if ($this->getSubmittedValue('updateMapping')) {
      $this->updateUserJobMetadata('Template', ['is_use_template' => TRUE]);
    }
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName(): string {
    return 'CRM/Import/MapField.tpl';
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function getFieldMappings(): array {
    return $this->getUserJob()['metadata']['import_mappings'] ?? [];
  }

  /**
   * Get default values for the mapping.
   *
   * This looks up any saved mapping or derives them from the headers if possible.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDefaults(): array {
    $defaults = [];
    $fieldMappings = $this->getFieldMappings();
    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $defaults["mapper[$i]"] = [];
      if ($fieldMappings) {
        $fieldMapping = $fieldMappings[$i] ?? [];
        if (!empty($fieldMapping['name']) && $fieldMapping['name'] !== ts('do_not_import')) {
          $this->addMappingToDefaults($defaults, $fieldMapping, $i);
        }
      }
    }
    if (empty($defaults) && $this->getSubmittedValue('skipColumnHeader')) {
      foreach ($this->getColumnHeaders() as $i => $columnHeader) {
        $defaults["mapper[$i]"][0] = $this->guessMappingBasedOnColumns($columnHeader);
      }
    }

    return $defaults;
  }

  /**
   * Add the saved mapping to the defaults.
   *
   * @param array $defaults
   * @param array $fieldMapping
   * @param int $rowNumber
   *
   * @return void
   */
  public function addMappingToDefaults(array &$defaults, array $fieldMapping, int $rowNumber): void {
    $fieldName = $fieldMapping['name'];
    $defaults["mapper[$rowNumber]"] = [$fieldName];
  }

  /**
   * Add the saved mapping fields to the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function addSavedMappingFields(): void {
    $savedMappingID = $this->getSavedMappingID();
    //to save the current mappings
    if (!$this->getTemplateID()) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    else {
      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL);
    $this->addFormRule(['CRM_Import_Form_MapField', 'mappingRule']);
  }

}
