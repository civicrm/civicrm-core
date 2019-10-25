<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
