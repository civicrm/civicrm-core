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
 * CSV Report Output Handler
 */
class CRM_Report_OutputHandler_Csv extends OutputHandlerBase implements OutputHandlerInterface {

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
    return ($form->getOutputMode() === 'csv');
  }

  /**
   * Return the download filename. This should be the "clean" name, not
   * a munged temporary filename.
   *
   * @return string
   */
  public function getFileName():string {
    return 'CiviReport.csv';
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
      ts('The report is attached as a CSV file.') . '</p>' .
      $this->getForm()->getReportFooter();
  }

  /**
   * Return the report contents as a string, in this case the csv output.
   *
   * @return string
   */
  public function getOutputString():string {
    //@todo Hmm. See note in CRM_Report_Form::endPostProcess about $rows.
    $rows = $this->getForm()->getTemplate()->getTemplateVars('rows');

    // avoid pass-by-ref warning
    $form = $this->getForm();

    return CRM_Report_Utils_Report::makeCsv($form, $rows);
  }

  /**
   * Set headers as appropriate and send the output to the browser.
   */
  public function download() {
    //@todo Hmm. See note in CRM_Report_Form::endPostProcess about $rows.
    $rows = $this->getForm()->getTemplate()->getTemplateVars('rows');

    // avoid pass-by-ref warning
    $form = $this->getForm();

    CRM_Report_Utils_Report::export2csv($form, $rows);
  }

  /**
   * Mime type of the attachment.
   *
   * @return string
   */
  public function getMimeType():string {
    return 'text/csv';
  }

  /**
   * Charset of the attachment.
   *
   * @return string
   */
  public function getCharset():string {
    return 'utf-8';
  }

}
