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
 * `CRM_Queue_TaskRunner`  a list tasks from a queue. It is designed to supported background
 * tasks which run automatically.
 *
 * This runner is not appropriate for all queues or workloads, so you might choose or create
 * a different runner. For example, `CRM_Queue_Runner` is geared toward background task lists.
 *
 * @see CRM_Queue_Runner
 */
class CRM_Queue_TaskRunner {

  /**
   * @param \CRM_Queue_Queue $queue
   * @param $item
   * @return string
   *   One of the following:
   *    - 'ok': Task executed normally. Removed from queue.
   *    - 'retry': Task encountered an error. Will try again later.
   *    - 'delete': Task encountered an error. Will not try again later. Removed from queue.
   *    - 'abort': Task encountered an error. Will not try again later. Stopped the queue.
   * @throws \CRM_Core_Exception
   */
  public function run(CRM_Queue_Queue $queue, $item): string {
    $this->assertType($item->data, ['CRM_Queue_Task'], 'Cannot run. Invalid task given.');

    /** @var \CRM_Queue_Task $task */
    $task = $item->data;

    /** @var string $outcome One of 'ok', 'retry', 'delete', 'abort' */

    if (is_numeric($queue->getSpec('retry_limit')) && $item->run_count > 1 + $queue->getSpec('retry_limit')) {
      \Civi::log()->debug('Skipping exhausted task: ' . $task->title);
      $outcome = $queue->getSpec('error');
      $exception = new \CRM_Core_Exception(sprintf('Skipping exhausted task after %d tries: %s', $item->run_count, print_r($task, 1)), 'queue_retry_exhausted');
    }
    else {
      \Civi::log()->debug('Running task: ' . $task->title);
      try {
        $runResult = $task->run($this->createContext($queue));
        $outcome = $runResult ? 'ok' : $queue->getSpec('error');
        $exception = ($outcome === 'ok') ? NULL : new \CRM_Core_Exception('Queue task returned false', 'queue_false');
      }
      catch (\Exception $e) {
        $outcome = $queue->getSpec('error');
        $exception = $e;
      }

      if (in_array($outcome, ['delete', 'abort']) && $this->isRetriable($queue, $item)) {
        $outcome = 'retry';
      }
    }

    if ($outcome !== 'ok') {
      \CRM_Utils_Hook::queueTaskError($queue, $item, $outcome, $exception);
    }

    if ($outcome === 'ok') {
      $queue->deleteItem($item);
      return $outcome;
    }

    $logDetails = [
      'id' => $queue->getName() . '#' . $item->id,
      'task' => CRM_Utils_Array::subset((array) $task, ['title', 'callback', 'arguments']),
      'outcome' => $outcome,
      'message' => $exception ? $exception->getMessage() : NULL,
      'exception' => $exception,
    ];

    switch ($outcome) {
      case 'retry':
        \Civi::log('queue')->error('Task "{id}" failed and should be retried. Task specific error: {message}', $logDetails);
        $queue->releaseItem($item);
        break;

      case 'delete':
        \Civi::log('queue')->error('Task "{id}" failed and will be deleted. Task specific error: {message}', $logDetails);
        $queue->deleteItem($item);
        break;

      case 'abort':
        \Civi::log('queue')->error('Task "{id}" failed. Queue processing aborted. Task specific error: {message}', $logDetails);
        $queue->setStatus('aborted');
        $queue->releaseItem($item); /* Sysadmin might inspect, fix, and then resume. Item should be accessible. */
        break;

      default:
        \Civi::log('queue')->critical('Unrecognized outcome for task "{id}": {outcome}', $logDetails);
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
    return property_exists($item, 'run_count')
      && is_numeric($queue->getSpec('retry_limit'))
      && $queue->getSpec('retry_limit') + 1 > $item->run_count;
  }

}
