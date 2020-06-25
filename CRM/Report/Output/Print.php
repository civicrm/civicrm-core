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
 * Print Report Output Handler
 */
class CRM_Report_Output_Print implements CRM_Report_Output_Interface {

  /**
   * Reference of the report instance.
   * @var \CRM_Report_Form
   */
  protected $report;

  /**
   * @inheritDoc
   */
  public function __construct(&$report) {
    $report->printOnly = TRUE;
    $report->absoluteUrl = TRUE;
    $report->setAddPaging(FALSE);

    $this->report = $report;
  }

  /**
   * @inheritDoc
   */
  public function supportsSqlDeveloperTab() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function generateOutput(&$rows = NULL) {
    $content = $this->report->compileContent();
    $id = $this->report->getID();
    $url = CRM_Utils_System::url("civicrm/report/instance/{$id}", "reset=1", TRUE);
  }

}
