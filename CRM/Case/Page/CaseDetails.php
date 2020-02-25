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
class CRM_Case_Page_CaseDetails extends CRM_Core_Page {

  /**
   * The main function that is called when the page loads.
   *
   * It decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $this->assign('action', $this->_action);
    $this->assign('context', $this->_context);

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    $caseId = CRM_Utils_Request::retrieve('caseId', 'Positive', $this);

    CRM_Case_Page_Tab::setContext($this);

    $this->assign('caseID', $caseId);
    $this->assign('contactID', $this->_contactId);
    $this->assign('userID', CRM_Core_Session::singleton()->get('userID'));

    return parent::run();
  }

}
