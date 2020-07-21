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

use Civi\Report\OutputHandlerInterface;
use Civi\Report\OutputHandlerBase;

/**
 * PDF Report Output Handler
 */
class CRM_Report_OutputHandler_Pdf extends OutputHandlerBase implements OutputHandlerInterface {

  /**
   * Are we a suitable output handler based on the given form?
   *
   * The class member $form isn't set yet at this point since we don't
   * even know if we're in play yet, so the form is a parameter.
   *
   * @param CRM_Report_Form $form
   *
   * @return bool
   */
  public function isOutputHandlerFor(CRM_Report_Form $form):bool {
    return ($form->getOutputMode() === 'pdf');
  }

  /**
   * Return the download filename. This should be the "clean" name, not
   * a munged temporary filename.
   *
   * @return string
   */
  public function getFileName():string {
    return 'CiviReport.pdf';
  }

  /**
   * Return the html body of the email.
   *
   * @return string
   */
  public function getMailBody():string {
    // @todo It would be nice if this was more end-user configurable, but
    // keeping it the same as it was before for now.
    $url = CRM_Utils_System::url('civicrm/report/instance/' . $this->getForm()->getID(), 'reset=1', TRUE);
    return $this->getForm()->getReportHeader() . '<p>' . ts('Report URL') .
      ": {$url}</p>" . '<p>' .
      ts('The report is attached as a PDF file.') . '</p>' .
      $this->getForm()->getReportFooter();
  }

  /**
   * Return the report contents as a string, in this case the pdf file.
   *
   * @return string
   */
  public function getOutputString():string {
    return CRM_Utils_PDF_Utils::html2pdf(
      $this->getForm()->compileContent(),
      $this->getFileName(),
      TRUE,
      ['orientation' => 'landscape']
    );
  }

  /**
   * Set headers as appropriate and send the output to the browser.
   */
  public function download() {
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

    CRM_Utils_PDF_Utils::html2pdf(
      $this->getForm()->compileContent(),
      $this->getFileName(),
      FALSE,
      ['orientation' => 'landscape']
    );
  }

  /**
   * Mime type of the attachment.
   *
   * @return string
   */
  public function getMimeType():string {
    return 'application/pdf';
  }

}
