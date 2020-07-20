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
class CRM_Report_OutputHandler_Print extends OutputHandlerBase implements OutputHandlerInterface {

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
    return ($form->getOutputMode() === 'print');
  }

  /**
   * Return the download filename. This should be the "clean" name, not
   * a munged temporary filename.
   *
   * For 'print' there is no attachment.
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
    return $this->getOutputString();
  }

  /**
   * Return the report contents as a string.
   *
   * @return string
   */
  public function getOutputString():string {
    return $this->getForm()->compileContent();
  }

  /**
   * Set headers as appropriate and send the output to the browser.
   * Here the headers are already text/html.
   */
  public function download() {
    echo $this->getOutputString();
  }

  /**
   * Override so links displayed in the browser are relative.
   *
   * @return bool
   */
  public function isAbsoluteUrl():bool {
    return FALSE;
  }

}
