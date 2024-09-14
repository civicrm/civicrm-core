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

/**
 * Track a list of known queues.
 */
class CRM_Queue_BAO_Queue extends CRM_Queue_DAO_Queue implements \Civi\Core\HookInterface {

  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    if (!\CRM_Core_Permission::check('administer queues')) {
      $cid = (int) CRM_Core_Session::getLoggedInContactID();
      $clauses['id'] = "IN (SELECT queue_id FROM `civicrm_user_job` WHERE created_id = $cid)";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

  /**
   * Get a list of valid statuses.
   *
   * The status determines whether automatic background-execution may proceed.
   *
   * @return string[]
   */
  public static function getStatuses($context = NULL) {
    return [
      'active' => ts('Active'),
      // ^^ The queue is active. It will execute tasks at the nearest convenience.
      'completed' => ts('Complete'),
      // ^^ The queue will no longer execute tasks - because no new tasks are expected. Everything is complete.
      'draft' => ts('Draft'),
      // ^^ The queue is not ready to execute tasks - because we are still curating a list of tasks.
      'aborted' => ts('Aborted'),
      // ^^ The queue will no longer execute tasks - because it encountered an unhandled error.
    ];
  }

  /**
   * Get a list of valid error modes.
   *
   * This error-mode determines what to do if (1) a task encounters an unhandled
   * exception, and (2) there are no hooks, and (3) there are no retries.
   *
   * Support for specific error-modes may depend on the `runner`.
   *
   * @return string[]
   */
  public static function getErrorModes($context = NULL) {
    return [
      'delete' => ts('Delete failed tasks'),
      // ^^ Give up on the task. Carry-on with other tasks.
      // This is more suitable if the queue is a service that lives forever and handles new/independent tasks as-they-come.
      'abort' => ts('Abort the queue-runner'),
      // ^^ Set the queue status to 'aborted'.
      // This is more suitable if the queue is a closed batch of interdependent tasks.
      // For linear queues (`Sql`), this will stop any new task-runs. For parallel queues (`SqlParallel`),
      // it will also stop new task-runs, but on-going tasks must wind-down on their own.
    ];
  }

  /**
   * Get a list of valid queue types.
   *
   * @return string[]
   */
  public static function getTypes($context = NULL) {
    return [
      'Memory' => ts('Memory (Linear)'),
      'Sql' => ts('SQL (Linear)'),
      'SqlParallel' => ts('SQL (Parallel)'),
    ];
  }

  /**
   * Queues which contain `CRM_Queue_Task` records should use the `task` runner to evaluate them.
   *
   * @code
   * $q = Civi::queue('do-stuff', ['type' => 'Sql', 'runner' => 'task']);
   * $q->createItem(new CRM_Queue_Task('my_callback_func', [1,2,3]));
   * @endCode
   *
   * @param \CRM_Queue_Queue $queue
   * @param array $items
   * @param array $outcomes
   * @throws \CRM_Core_Exception
   * @see CRM_Utils_Hook::queueRun()
   */
  public static function hook_civicrm_queueRun_task(CRM_Queue_Queue $queue, array $items, array &$outcomes) {
    foreach ($items as $itemPos => $item) {
      $outcomes[$itemPos] = (new \CRM_Queue_TaskRunner())->run($queue, $item);
    }
  }

}
