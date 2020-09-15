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
 * A task is an item that can be enqueued and later executed
 */
class CRM_Queue_Task {

  /**
   * Task was performed successfully.
   */
  const TASK_SUCCESS = 1;

  /**
   * Task failed and should not be retried.
   */
  const TASK_FAIL = 2;

  /**
   * @var mixed
   * serializable
   */
  public $callback;

  /**
   * @var array
   * serializable
   */
  public $arguments;

  /**
   * @var string|null
   */
  public $title;

  /**
   * @param mixed $callback
   *   Serializable, a callable PHP item; must accept at least one argument
   *   (CRM_Queue_TaskContext).
   * @param array $arguments
   *   Serializable, extra arguments to pass to the callback (in order).
   * @param string $title
   *   A printable string which describes this task.
   */
  public function __construct($callback, $arguments, $title = NULL) {
    $this->callback = $callback;
    $this->arguments = $arguments;
    $this->title = $title;
  }

  /**
   * Perform the task.
   *
   * @param array $taskCtx
   *   Array with keys:
   *   - log: object 'Log'
   *
   * @throws Exception
   * @return bool, TRUE if task completes successfully
   */
  public function run($taskCtx) {
    $args = $this->arguments;
    array_unshift($args, $taskCtx);

    if (is_callable($this->callback)) {
      $result = call_user_func_array($this->callback, $args);
      return $result;
    }
    else {
      throw new Exception('Failed to call callback: ' . print_r($this->callback));
    }
  }

}
