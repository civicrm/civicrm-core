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
class CRM_Case_StateMachine_Search extends CRM_Core_StateMachine {

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

    $this->_pages['CRM_Case_Form_Search'] = NULL;
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

    if ($result) {
      $this->_pages['CRM_Case_Form_Task_Result'] = NULL;
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
  public function taskName($controller, $formName = 'Search') {
    // total hack, check POST vars and then session to determine stuff
    $value = $_POST['task'] ?? NULL;
    if (!isset($value)) {
      $value = $this->_controller->get('task');
    }
    $this->_controller->set('task', $value);

    return CRM_Case_Task::getTask($value);
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
   * Since this is a state machine for search and we want to come back to the same state
   * we dont want to issue a reset of the state session when we are done processing a task
   */
  public function shouldReset() {
    return FALSE;
  }

}
