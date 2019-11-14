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
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);
    $this->skipOnHold = $this->skipDeceased = FALSE;
    parent::preProcess();
    $this->setContactIDs();
  }

  /**
   * Set defaults for the pdf.
   *
   * @return array
   */
  public function setDefaultValues() {
    return CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contact_Form_Task_PDFLetterCommon::postProcess($this);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    foreach ($this->_entityIds as $key => $caseId) {
      $caseTypeId = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseId, 'case_type_id');
      $tokens += CRM_Core_SelectValues::caseTokens($caseTypeId);
    }
    return $tokens;
  }

}
