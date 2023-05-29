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
class CRM_Activity_StateMachine_Search extends CRM_Core_StateMachine {

  /**
   * The task that the wizard is currently processing.
   *
   * @var string
   */
  protected $_task;

  /**
   * Class constructor.
   *
   * @param object $controller
   * @param \const|int $action
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = [];

    $this->_pages['CRM_Activity_Form_Search'] = NULL;
    [$task] = $this->taskName($controller, 'Search');
    $this->_task = $task;
    foreach ($task as $t) {
      $this->_pages[$t] = NULL;
    }

    $this->addSequentialPages($this->_pages);
  }

  /**
   * Determine the form name based on the action. This allows us
   * to avoid using  conditional state machine, much more efficient
   * and simpler
   *
   * @param CRM_Core_Controller $controller
   *   The controller object.
   *
   * @param string $formName
   *
   * @return array
   *   the name of the form that will handle the task
   */
  protected function taskName($controller, $formName = 'Search') {
    // total hack, check POST vars and then session to determine stuff
    $value = $_POST['task'] ?? NULL;
    if (!isset($value)) {
      $value = $this->_controller->get('task');
    }
    $this->_controller->set('task', $value);
    return CRM_Activity_Task::getTask($value);
  }

  /**
   * Return the form name of the task.
   *
   * @return string
   */
  public function getTaskFormName() {
    return CRM_Utils_String::getClassName($this->_task);
  }

  /**
   * Should the controller reset the session.
   * In some cases, specifically search we want to remember
   * state across various actions and want to go back to the
   * beginning from the final state, but retain the same session
   * values
   *
   * @return bool
   */
  public function shouldReset() {
    return FALSE;
  }

}
