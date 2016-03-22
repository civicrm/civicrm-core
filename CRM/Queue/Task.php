<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
   * @var mixed, serializable
   */
  public $callback;

  /**
   * @var array, serializable
   */
  public $arguments;

  /**
   * @var string, NULL-able
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
