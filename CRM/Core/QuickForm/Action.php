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
 * This is the base Action class for all actions which we redefine. This is
 * integrated with the StateMachine, Controller and State objects
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
