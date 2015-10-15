<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Redefine the jump action.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_QuickForm_Action_Jump extends CRM_Core_QuickForm_Action {

  /**
   * Class constructor.
   *
   * @param object $stateMachine
   *   Reference to state machine object.
   *
   * @return \CRM_Core_QuickForm_Action_Jump
   */
  public function __construct(&$stateMachine) {
    parent::__construct($stateMachine);
  }

  /**
   * Processes the request.
   *
   * @param CRM_Core_Form $page
   *   CRM_Core_Form the current form-page.
   * @param string $actionName
   *   Current action name, as one Action object can serve multiple actions.
   *
   * @return void
   */
  public function perform(&$page, $actionName) {
    // check whether the page is valid before trying to go to it
    if ($page->controller->isModal()) {
      // we check whether *all* pages up to current are valid
      // if there is an invalid page we go to it, instead of the
      // requested one
      $pageName = $page->getAttribute('id');
      if (!$page->controller->isValid($pageName)) {
        $pageName = $page->controller->findInvalid();
      }
      $current = &$page->controller->getPage($pageName);
    }
    else {
      $current = &$page;
    }
    // generate the URL for the page 'display' event and redirect to it
    $action = $current->getAttribute('action');
    $url = $action . (FALSE === strpos($action, '?') ? '?' : '&') . $current->getButtonName('display') . '=true' . '&qfKey=' . $page->get('qfKey');

    CRM_Utils_System::redirect($url);
  }

}
