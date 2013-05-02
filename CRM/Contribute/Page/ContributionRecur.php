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
 * Main page for viewing Recurring Contributions.
 *
 */
class CRM_Contribute_Page_ContributionRecur extends CRM_Core_Page {

  static $_links = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * View details of a recurring contribution
   *
   * @return void
   * @access public
   */ function view() {
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $this->_id;
    if ($recur->find(TRUE)) {
      $values = array();
      CRM_Core_DAO::storeValues($recur, $values);
      // if there is a payment processor ID, get the name of the payment processor
      if (CRM_Utils_Array::value('payment_processor_id', $values)) {
        $values['payment_processor'] = CRM_Core_DAO::getFieldValue(
          'CRM_Financial_DAO_PaymentProcessor',
          $values['payment_processor_id'],
          'name'
        );
      }
      // get contribution status label
      if (CRM_Utils_Array::value('contribution_status_id', $values)) {
        $values['contribution_status'] = CRM_Core_OptionGroup::getLabel('contribution_status', $values['contribution_status_id']);
      }

      $this->assign('recur', $values);
    }
  }

  function preProcess() {
    $context          = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_action    = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'view');
    $this->_id        = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    // set page title
    CRM_Contact_Page_View::setTitle($this->_contactId);

    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit contributions')) {
      // demote to view since user does not have edit contrib rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->preProcess();

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }

    return parent::run();
  }
}

