<?php

use Civi\Api4\Mapping;

class CRM_CiviImport_Form_MapField extends CRM_Import_Form_MapField {

  public function preProcess(): void {
    parent::preProcess();
    // Add import-ui app
    Civi::service('angularjs.loader')->addModules('crmCiviimport');
    $this->assignCiviimportVariables();

    // @todo - remove the mapping part - once we have removed from js - tey should all have userJobs templates now.
    $savedMappingID = (int) $this->getSavedMappingID();
    $savedMapping = [];
    if ($savedMappingID) {
      $savedMapping = Mapping::get()->addWhere('id', '=', $savedMappingID)->addSelect('id', 'name', 'description')->execute()->first();
    }
    Civi::resources()->addVars('crmImportUi', ['savedMapping' => $savedMapping]);
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName(): string {
    return 'CRM/Import/MapField.tpl';
  }

}
