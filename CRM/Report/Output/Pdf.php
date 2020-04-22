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

class CRM_Report_Output_Pdf implements CRM_Report_Output_Interface {

  /**
   * Reference of the report instance.
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

    // Nb. Once upon a time we used a package called Open Flash Charts to
    // draw charts, and we had a feature whereby a browser could send the
    // server a PNG version of the chart, which could then be included in a
    // PDF by including <img> tags in the HTML for the conversion below.
    //
    // This feature stopped working when browsers stopped supporting Flash,
    // and although we have a different client-side charting library in
    // place, we decided not to reimplement the (rather convoluted)
    // browser-sending-rendered-chart-to-server process.
    //
    // If this feature is required in future we should find a better way to
    // render charts on the server side, e.g. server-created SVG.
    CRM_Utils_PDF_Utils::html2pdf($content, "CiviReport.pdf", FALSE, ['orientation' => 'landscape']);

    CRM_Utils_System::civiExit();
  }

}
