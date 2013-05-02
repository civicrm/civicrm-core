<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Contact_Form_Task_Email extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
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
   * all the existing templates in the system
   *
   * @var array
   */
  public $_templates = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */ function preProcess() {
    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'Positive', $this, FALSE);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);
    if ($cid) {
      CRM_Contact_Page_View::setTitle($cid);
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
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
    $this->assign('emailTask', TRUE);

    CRM_Contact_Form_Task_EmailCommon::buildQuickForm($this);
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    CRM_Contact_Form_Task_EmailCommon::postProcess($this);
  }
}

