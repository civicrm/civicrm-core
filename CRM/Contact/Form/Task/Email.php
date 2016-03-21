<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
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
   * @var boolean
   */
  public $_single = FALSE;

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var boolean
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
  public $_toContactDetails = array();

  /**
   * Store all selected contact id's, that includes to, cc and bcc contacts
   * @var array
   */
  public $_allContactIds = array();

  /**
   * Store only "to" contact ids.
   * @var array
   */
  public $_toContactIds = array();

  /**
   * Store only "cc" contact ids.
   * @var array
   */
  public $_ccContactIds = array();

  /**
   * Store only "bcc" contact ids.
   * @var array
   */
  public $_bccContactIds = array();

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'String', $this, FALSE);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    $cid = CRM_Utils_Request::retrieve('cid', 'String', $this, FALSE);

    // Allow request to specify email id rather than contact id
    $toEmailId = CRM_Utils_Request::retrieve('email_id', 'String', $this);
    if ($toEmailId) {
      $toEmail = civicrm_api('email', 'getsingle', array('version' => 3, 'id' => $toEmailId));
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
      $displayName = array();

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

    //early prevent, CRM-6209
    if (count($this->_contactIds) > CRM_Contact_Form_Task_EmailCommon::MAX_EMAILS_KILL_SWITCH) {
      CRM_Core_Error::statusBounce(ts('Please do not use this task to send a lot of emails (greater than %1). We recommend using CiviMail instead.', array(1 => CRM_Contact_Form_Task_EmailCommon::MAX_EMAILS_KILL_SWITCH)));
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
    return $tokens;
  }

}
