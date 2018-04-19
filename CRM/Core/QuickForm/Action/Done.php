<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * Redefine the back action.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Core_QuickForm_Action_Done extends CRM_Core_QuickForm_Action {

  /**
   * Class constructor.
   *
   * @param object $stateMachine
   *   Reference to state machine object.
   *
   * @return \CRM_Core_QuickForm_Action_Done
   */
  public function __construct(&$stateMachine) {
    parent::__construct($stateMachine);
  }

  /**
   * Processes the request.
   * this is basically a self submit, so validate the page
   * and if success, call post process
   * when done processing pop to user context
   *
   * @param CRM_Core_Form $page
   *   The current form-page.
   * @param string $actionName
   *   Current action name, as one Action object can serve multiple actions.
   *
   * @return object|void
   */
  public function perform(&$page, $actionName) {
    $page->isFormBuilt() or $page->buildForm();

    $pageName = $page->getAttribute('name');
    $data = &$page->controller->container();
    $data['values'][$pageName] = $page->exportValues();
    $data['valid'][$pageName] = $page->validate();

    // Modal form and page is invalid: don't go further
    if ($page->controller->isModal() && !$data['valid'][$pageName]) {
      return $page->handle('display');
    }

    // the page is valid, process it before we jump to the next state
    $page->mainProcess();

    // ok so we are done now, pop stack and jump back to where we came from
    // we do not reset the context because u can achieve that affect using next
    // use Done when u want to pop back to the same context without a reset
    $this->popUserContext();
  }

}
