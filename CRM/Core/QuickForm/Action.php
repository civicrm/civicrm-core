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
 * This is the base Action class for all actions which we redefine. This is
 * integrated with the StateMachine, Controller and State objects
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
require_once 'HTML/QuickForm/Action.php';

/**
 * Class CRM_Core_QuickForm_Action
 */
class CRM_Core_QuickForm_Action extends HTML_QuickForm_Action {

  /**
   * Reference to the state machine i belong to.
   * @var object
   */
  protected $_stateMachine;

  /**
   * Constructor.
   *
   * @param object $stateMachine
   *   Reference to state machine object.
   *
   * @return \CRM_Core_QuickForm_Action
   */
  public function __construct(&$stateMachine) {
    $this->_stateMachine = &$stateMachine;
  }

  /**
   * Returns the user to the top of the user context stack.
   */
  public function popUserContext() {
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();

    // check if destination is set, if so goto destination
    $destination = $this->_stateMachine->getDestination();
    if ($destination) {
      $destination = urldecode($destination);
    }
    else {
      $destination = $session->popUserContext();

      if (empty($destination)) {
        $destination = $config->userFrameworkBaseURL;
      }
    }

    //CRM-5839 -do not redirect control.
    if (!$this->_stateMachine->getSkipRedirection()) {
      CRM_Utils_System::redirect($destination);
    }
  }

}
