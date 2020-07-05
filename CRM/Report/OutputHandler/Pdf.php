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

/**
 * PDF Report Output Handler
 */
class CRM_Report_OutputHandler_Pdf extends CRM_Report_OutputHandler_Base implements OutputHandlerInterface {

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
    return $this->getForm()->getFormValues()['report_header'] . '<p>' . ts('Report URL') .
      ": {$url}</p>" . '<p>' .
      ts('The report is attached as a PDF file.') . '</p>' .
      $this->getForm()->getFormValues()['report_footer'];
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
