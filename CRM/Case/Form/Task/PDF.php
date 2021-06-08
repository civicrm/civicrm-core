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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality to create PDF letter for a group of contacts.
 */
class CRM_Case_Form_Task_PDF extends CRM_Case_Form_Task {
  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->skipOnHold = $this->skipDeceased = FALSE;
    parent::preProcess();
    CRM_Case_Form_Task_PDFLetterCommon::preProcess($this);
  }

  /**
   * Set defaults for the pdf.
   *
   * @return array
   */
  public function setDefaultValues() {
    return CRM_Case_Form_Task_PDFLetterCommon::setDefaultValues();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Case_Form_Task_PDFLetterCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Case_Form_Task_PDFLetterCommon::postProcess($this);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    return CRM_Case_Form_Task_PDFLetterCommon::listTokens();
  }

}
