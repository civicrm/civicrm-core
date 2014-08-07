<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Activity_StateMachine_Search extends CRM_Core_StateMachine {

  /**
   * The task that the wizard is currently processing
   *
   * @var string
   * @protected
   */
  protected $_task;

  /**
   * class constructor
   */
  function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = array();

    $this->_pages['CRM_Activity_Form_Search'] = NULL;
    list($task, $result) = $this->taskName($controller, 'Search');
    $this->_task = $task;

    if (is_array($task)) {
      foreach ($task as $t) {
        $this->_pages[$t] = NULL;
      }
    }
    else {
      $this->_pages[$task] = NULL;
    }

    $this->addSequentialPages($this->_pages, $action);
  }

  /**
   * Determine the form name based on the action. This allows us
   * to avoid using  conditional state machine, much more efficient
   * and simpler
   *
   * @param CRM_Core_Controller $controller the controller object
   *
   * @param string $formName
   *
   * @return string the name of the form that will handle the task
   * @access protected
   */
  function taskName($controller, $formName = 'Search') {
    // total hack, check POST vars and then session to determine stuff
    $value = CRM_Utils_Array::value('task', $_POST);
    if (!isset($value)) {
      $value = $this->_controller->get('task');
    }
    $this->_controller->set('task', $value);
    return CRM_Activity_Task::getTask($value);
  }

  /**
   * return the form name of the task
   *
   * @return string
   * @access public
   */
  function getTaskFormName() {
    return CRM_Utils_String::getClassName($this->_task);
  }

  /**
   * Should the controller reset the session
   * In some cases, specifically search we want to remember
   * state across various actions and want to go back to the
   * beginning from the final state, but retain the same session
   * values
   *
   * @return boolean
   */
  /**
   * @return bool
   */
  function shouldReset() {
    return FALSE;
  }
}

