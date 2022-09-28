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
 * Class CRM_Export_Controller_Standalone
 */
abstract class CRM_Core_Controller_Task extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {

    parent::__construct($title, $modal);
    $id = explode(',', CRM_Utils_Request::retrieve('id', 'CommaSeparatedIntegers', $this, TRUE));

    // Check permissions
    $perm = civicrm_api3($this->getEntity(), 'get', [
      'return' => 'id',
      'options' => ['limit' => 0],
      'check_permissions' => 1,
      'id' => ['IN' => $id],
    ])['values'];
    if (empty($perm)) {
      throw new CRM_Core_Exception(ts('No records available'));
    }
    $this->set('id', implode(',', array_keys($perm)));
    $pages = array_fill_keys($this->getTaskClass(), NULL);

    $this->_stateMachine = new CRM_Core_StateMachine($this);
    $this->_stateMachine->addSequentialPages($pages);
    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);
    // add all the actions
    $this->addActions();
  }

  /**
   * Get the name used to construct the class.
   *
   * @return string
   */
  abstract public function getEntity():string;

  /**
   * Get the available tasks for the entity.
   *
   * @return array
   */
  abstract public function getAvailableTasks():array;

  /**
   * Get the class for the action.
   *
   * @return array Array of the classes for the form controlle.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getTaskClass(): array {
    $task = CRM_Utils_Request::retrieve('task', 'Alphanumeric', $this, TRUE);
    foreach ($this->getAvailableTasks() as $taskAction) {
      if (($taskAction['key'] ?? '') === $task) {
        return (array) $taskAction['class'];
      }
    }
    throw new CRM_Core_Exception(ts('Invalid task'));
  }

}
