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
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Contact_Form_Task_Email extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode".
   *
   * Single mode means sending email to one specific contact.
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var bool
   */
  public $_noEmails = FALSE;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  /**
   * Store "to" contact details.
   * @var array
   */
  public $_toContactDetails = [];

  /**
   * Store all selected contact id's, that includes to, cc and bcc contacts
   * @var array
   */
  public $_allContactIds = [];

  /**
   * Store only "to" contact ids.
   * @var array
   */
  public $_toContactIds = [];

  /**
   * Store only "cc" contact ids.
   * @var array
   */
  public $_ccContactIds = [];

  /**
   * Store only "bcc" contact ids.
   * @var array
   */
  public $_bccContactIds = [];

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'String', $this, FALSE);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $cid = CRM_Utils_Request::retrieve('cid', 'String', $this, FALSE);

    // Allow request to specify email id rather than contact id
    $toEmailId = CRM_Utils_Request::retrieve('email_id', 'String', $this);
    if ($toEmailId) {
      $toEmail = civicrm_api('email', 'getsingle', ['version' => 3, 'id' => $toEmailId]);
      if (!empty($toEmail['email']) && !empty($toEmail['contact_id'])) {
        $this->_toEmail = $toEmail;
      }
      if (!$cid) {
        $cid = $toEmail['contact_id'];
        $this->set('cid', $cid);
      }
    }

    if ($cid) {
      $cid = explode(',', $cid);
      $displayName = [];

      foreach ($cid as $val) {
        $displayName[] = CRM_Contact_BAO_Contact::displayName($val);
      }

      CRM_Utils_System::setTitle(implode(',', $displayName) . ' - ' . ts('Email'));
    }
    else {
      CRM_Utils_System::setTitle(ts('New Email'));
    }
    CRM_Contact_Form_Task_EmailCommon::preProcessFromAddress($this);

    if (!$cid && $this->_context != 'standalone') {
      parent::preProcess();
    }

    $this->assign('single', $this->_single);
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->assign('isAdmin', 1);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
    $this->assign('emailTask', TRUE);

    CRM_Contact_Form_Task_EmailCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contact_Form_Task_EmailCommon::postProcess($this);
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
