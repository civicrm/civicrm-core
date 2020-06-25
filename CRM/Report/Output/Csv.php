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
 * CSV Report Output handler
 */
class CRM_Report_Output_Csv implements CRM_Report_Output_Interface {

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
    CRM_Report_Utils_Report::export2csv($this->report, $rows);
  }

}
