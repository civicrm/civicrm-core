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
 * Base Report Output Handler
 */
class CRM_Report_OutputHandler_Base implements OutputHandlerInterface {

  /**
   * This is for convenience since otherwise several functions
   * would take it as a parameter.
   *
   * @var CRM_Report_Form
   */
  protected $form;

  /**
   * @return CRM_Report_Form
   */
  public function getForm():CRM_Report_Form {
    return $this->form;
  }

  /**
   * @param CRM_Report_Form $form
   */
  public function setForm(CRM_Report_Form $form) {
    $this->form = $form;
  }

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
    return FALSE;
  }

  /**
   * Return the download filename. This should be the "clean" name, not
   * a munged temporary filename.
   *
   * @return string
   */
  public function getFileName():string {
    return '';
  }

  /**
   * Return the html body of the email.
   *
   * @return string
   */
  public function getMailBody():string {
    return '';
  }

  /**
   * Return the report contents as a string.
   *
   * @return string
   */
  public function getOutputString():string {
    return '';
  }

  /**
   * Set headers as appropriate and send the output to the browser.
   */
  public function download() {
  }

  /**
   * Mime type of the attachment.
   *
   * @return string
   */
  public function getMimeType():string {
    return 'text/html';
  }

}
