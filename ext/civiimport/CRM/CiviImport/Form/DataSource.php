<?php

use Civi\Api4\UserJob;
use CRM_Civiimport_ExtensionUtil as E;

class CRM_CiviImport_Form_DataSource extends CRM_Import_Form_DataSource {

  /**
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function addMappingSelector(): void {
    $templates = (array) UserJob::get()
      ->addWhere('is_template', '=', TRUE)
      ->addWhere('job_type', '=', $this->getUserJobType())
      ->execute();
    $mappings = [];
    foreach ($templates as $template) {
      $mappings[] = ['id' => $template['id'], 'text' => substr($template['name'], 7)];
    }
    if ($mappings) {
      $this->add('select2', 'userJobTemplate', E::ts('Saved Field Mapping'), $mappings, FALSE, ['class' => 'crm-select2', 'placeholder' => E::ts('- select -')]);
    }
  }

}
