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
 * Redefine the refresh action.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_QuickForm_Action_Refresh extends CRM_Core_QuickForm_Action {

  /**
   * Class constructor.
   *
   * @param object $stateMachine
   *   Reference to state machine object.
   *
   * @return \CRM_Core_QuickForm_Action_Refresh
   */
  public function __construct(&$stateMachine) {
    parent::__construct($stateMachine);
  }

  /**
   * Processes the request.
   *
   * @param CRM_Core_Form $page
   *   The current form-page.
   * @param string $actionName
   *   Current action name, as one Action object can serve multiple actions.
   *
   * @return void
   */
  public function perform(&$page, $actionName) {
    // save the form values and validation status to the session
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

    return $page->handle('jump');
  }

}
