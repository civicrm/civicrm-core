<?php

class CRM_CiviImport_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName(): string {
    return 'CRM/Import/Preview.tpl';
  }

  public function preProcess(): void {
    parent::preProcess();
    $this->assign('isOpenResultsInNewTab', TRUE);
    $this->assign('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/search', '', TRUE, '/display/Import_' . $this->getUserJobID() . '/Import_' . $this->getUserJobID() . '?_status=ERROR', FALSE));
    $this->assign('allRowsUrl', CRM_Utils_System::url('civicrm/search', '', TRUE, '/display/Import_' . $this->getUserJobID() . '/Import_' . $this->getUserJobID(), FALSE));
    $this->assign('importedRowsUrl', CRM_Utils_System::url('civicrm/search', '', TRUE, '/display/Import_' . $this->getUserJobID() . '/Import_' . $this->getUserJobID() . '?_status=IMPORTED', FALSE));
  }

}
