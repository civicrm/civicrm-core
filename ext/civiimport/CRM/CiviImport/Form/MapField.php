<?php

class CRM_CiviImport_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName(): string {
    return 'CRM/Import/MapField.tpl';
  }

}
