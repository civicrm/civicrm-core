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
 * This class provides the functionality to create PDF letter for a group of contacts or a single contact.
 */
class CRM_Contact_Form_Task_PDF extends CRM_Contact_Form_Task {

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  public $_activityId = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {

    $this->skipOnHold = $this->skipDeceased = FALSE;
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);

    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'CommaSeparatedIntegers', $this, FALSE);
    if (!empty($this->_caseId) && strpos($this->_caseId, ',')) {
      $this->_caseIds = explode(',', $this->_caseId);
      unset($this->_caseId);
    }

    // retrieve contact ID if this is 'single' mode
    $cid = CRM_Utils_Request::retrieve('cid', 'CommaSeparatedIntegers', $this, FALSE);

    if ($cid) {
      // this is true in non-search context / single mode
      // in search context 'id' is the default profile id for search display
      // CRM-11227
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    }

    if ($cid) {
      CRM_Contact_Form_Task_PDFLetterCommon::preProcessSingle($this, $cid);
      $this->_single = TRUE;
    }
    else {
      parent::preProcess();
    }
    $this->assign('single', $this->_single);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    if (isset($this->_activityId)) {
      $params = ['id' => $this->_activityId];
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      $defaults['html_message'] = CRM_Utils_Array::value('details', $defaults);
    }
    $defaults = $defaults + CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
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
    if (isset($this->_caseId) || isset($this->_caseIds)) {
      // For a single case, list tokens relevant for only that case type
      $caseTypeId = isset($this->_caseId) ? CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $this->_caseId, 'case_type_id') : NULL;
      $tokens += CRM_Core_SelectValues::caseTokens($caseTypeId);
    }
    return $tokens;
  }

}
