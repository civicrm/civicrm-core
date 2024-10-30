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
class CRM_Legacycustomsearches_StateMachine_Search extends CRM_Core_StateMachine {

  /**
   * The task that the wizard is currently processing
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
    $this->_pages['CRM_Contact_Form_Search_Custom'] = NULL;
    list($task, $result) = $this->taskName($controller, 'Custom');

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
      $this->_pages['CRM_Contact_Form_Task_Result'] = NULL;
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
    if ((int) $value === CRM_Core_Task::TASK_EXPORT) {
      return ['CRM_Contact_Form_Search_Action_Export', FALSE];
    }
    $componentMode = $this->_controller->get('component_mode');
    $modeValue = CRM_Contact_Form_Search::getModeValue($componentMode);
    $taskClassName = $modeValue['taskClassName'];
    return $taskClassName::getTask($value);
  }

  /**
   * Return the form name of the task.
   *
   * @return string
   */
  public function getTaskFormName() {
    if (is_array($this->_task)) {
      // return first page
      return CRM_Utils_String::getClassName($this->_task[0]);
    }
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
