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
 * This class provides the functionality to create PDF/Word letters for activities.
 */
class CRM_Activity_Form_Task_PDF extends CRM_Activity_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    CRM_Activity_Form_Task_PDFLetterCommon::preProcess($this);
  }

  /**
   * Set defaults for the pdf.
   *
   * @return array
   */
  public function setDefaultValues() {
    return CRM_Activity_Form_Task_PDFLetterCommon::setDefaultValues();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Activity_Form_Task_PDFLetterCommon::buildQuickForm($this);
    // Remove types other than pdf as they are not working (have never worked) and don't want fix
    // for them to block pdf.
    // @todo debug & fix....
    $this->add('select', 'document_type', ts('Document Type'), ['pdf' => ts('Portable Document Format (.pdf)')]);

  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Activity_Form_Task_PDFLetterCommon::postProcess($this);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    return CRM_Activity_Form_Task_PDFLetterCommon::listTokens();
  }

}
