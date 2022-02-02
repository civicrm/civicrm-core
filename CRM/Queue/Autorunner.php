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
 * `CRM_Queue_Autorunner`  a list tasks from a queue. It is designed to supported background
 * tasks which run automatically.
 *
 * This runner is not appropriate for all queues or workloads, so you might choose or create
 * a different runner. For example, `CRM_Queue_Runner` is geared toward background task lists.
 *
 * @see CRM_Queue_Runner
 */
class CRM_Queue_Autorunner {

  /**
   * @param \CRM_Queue_Queue $queue
   * @param $item
   * @return string
   *   One of the following:
   *    - 'ok': Task executed normally. Removed from queue.
   *    - 'retry': Task encountered an error. Will try again later.
   *    - 'fail': Task encountered an error. Will not try again later. Removed from queue.
   * @throws \API_Exception
   */
  public function run(CRM_Queue_Queue $queue, $item): string {
    $this->assertType($item->data, ['CRM_Queue_Task'], 'Cannot run. Invalid task given.');

    /** @var \CRM_Queue_Task $task */
    $task = $item->data;

    \Civi::log()->debug("Running task: " . $task->title);
    try {
      $outcome = $task->run($this->createContext($queue)) ? 'ok' : 'fail';
      $exception = ($outcome === 'ok') ? NULL : new \API_Exception('Queue task returned false');
    }
    catch (\Exception $e) {
      $outcome = 'fail';
      $exception = $e;
    }

    if ($outcome === 'fail') {
      if ($this->isRetriable($queue, $item)) {
        $outcome = 'retry';
      }
      \CRM_Utils_Hook::queueAutorunError($queue, $item, $outcome, $exception);
    }

    switch ($outcome) {
      case 'ok':
        $queue->deleteItem($item);
        break;

      case 'retry':
        $queue->retryItem($item);
        break;

      case 'fail':
        $queue->deleteItem($item);
        break;
    }

    return $outcome;
  }

  /**
   * @param \CRM_Queue_Queue $queue
   * return CRM_Queue_TaskContext;
   */
  private function createContext(\CRM_Queue_Queue $queue): \CRM_Queue_TaskContext {
    $taskCtx = new \CRM_Queue_TaskContext();
    $taskCtx->queue = $queue;
    $taskCtx->log = \CRM_Core_Error::createDebugLogger();
    return $taskCtx;
  }

  private function assertType($object, array $types, string $message) {
    foreach ($types as $type) {
      if ($object instanceof  $type) {
        return;
      }
    }
    throw new \Exception($message);
  }

  private function isRetriable(\CRM_Queue_Queue $queue, $item): bool {
    return method_exists($queue, 'retryItem')
      && property_exists($item, 'retry_count')
      && is_numeric($item->retry_count)
      && is_numeric($item->retry_interval)
      && $item->retry_count > 0;
  }

}
