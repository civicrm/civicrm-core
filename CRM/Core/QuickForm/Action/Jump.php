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
 * Redefine the jump action.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    // prevent URLs that end in ? from causing redirects
    $action = rtrim($action, '?');
    // FIXME: this should be passed through CRM_Utils_System::url()
    $url = $action . (FALSE === strpos($action, '?') ? '?' : '&') . $current->getButtonName('display') . '=true' . '&qfKey=' . $page->get('qfKey');

    CRM_Utils_System::redirect($url);
  }

}
