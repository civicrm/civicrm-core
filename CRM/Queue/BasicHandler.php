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

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * `CRM_Queue_BasicHandler` is a base-class that helps to execute queue-items.
 * It takes a batch of items and executes them 1-by-1. It enforces the
 * queue configuration options, such as `retry_limit=>5` and `error=>abort`.
 *
 * This class will have an incubation period circa Oct 2023 - Apr 2024. Unless otherwise
 * noted, it should be considered stable at that point.
 */
abstract class CRM_Queue_BasicHandler extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_queueRun_' . static::getTypeName() => 'runBatch',
    ];
  }

  abstract public static function getTypeName(): string;

  /**
   * Do a unit of work with one item from the queue.
   *
   * @param $item
   * @param $queue
   * @return mixed
   *   Boolean-ish. TRUE for success. FALSE for failure.
   *   Same as CRM_Queue_Task::run()
   */
  abstract protected function runItem($item, $queue);

  /**
   * Get a nice title for the item.
   *
   * @param \CRM_Queue_DAO_QueueItem $item
   * @return string
   */
  protected function getItemTitle($item): string {
    return ($item instanceof CRM_Queue_DAO_QueueItem)
      ? $item->queue_name . '#' . $item->id
      : $item->id;
  }

  /**
   * Get detailed info about the item. This is used for debugging.
   *
   * @param $item
   * @return array
   */
  protected function getItemDetails($item): array {
    return [];
  }

  /**
   * Run a batch of items, one-by-one.
   *
   * @param \CRM_Queue_Queue $queue
   * @param array $items
   * @param array $outcomes
   * @throws \CRM_Core_Exception
   * @see CRM_Utils_Hook::queueRun()
   */

  final public function runBatch(CRM_Queue_Queue $queue, array $items, array &$outcomes): void {
    foreach ($items as $itemPos => $item) {
      $outcomes[$itemPos] = $this->run($queue, $item);
    }
    // TODO: If an item has outcome==abort, then release the rest of the items.
  }

  /**
   * Run a specific item. Determine its status. Update the others.
   *
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
  final public function run(CRM_Queue_Queue $queue, $item): string {
    $this->assertType($item->data, ['CRM_Queue_Task'], 'Cannot run. Invalid task given.');

    /** @var string $outcome One of 'ok', 'retry', 'delete', 'abort' */

    if (is_numeric($queue->getSpec('retry_limit')) && $item->run_count > 1 + $queue->getSpec('retry_limit')) {
      \Civi::log()->debug("Skipping exhausted task: " . $this->getItemTitle($item));
      $outcome = $queue->getSpec('error');
      $exception = new \CRM_Core_Exception(sprintf('Skipping exhausted task after %d tries: %s', $item->run_count, print_r($this->getItemDetails($item), 1)), 'queue_retry_exhausted');
    }
    else {
      \Civi::log()->debug("Running task: " . $this->getItemTitle($item));
      try {
        $runResult = $this->runItem($item, $queue);
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
      'task' => $this->getItemDetails($item),
      'outcome' => $outcome,
      'message' => $exception ? $exception->getMessage() : NULL,
      'exception' => $exception,
    ];

    switch ($outcome) {
      case 'retry':
        \Civi::log('queue')->error('Task "{id}" failed and should be retried. {message}', $logDetails);
        $queue->releaseItem($item);
        break;

      case 'delete':
        \Civi::log('queue')->error('Task "{id}" failed and will be deleted. {message}', $logDetails);
        $queue->deleteItem($item);
        break;

      case 'abort':
        \Civi::log('queue')->error('Task "{id}" failed. Queue processing aborted. {message}', $logDetails);
        $queue->setStatus('aborted');
        $queue->releaseItem($item); /* Sysadmin might inspect, fix, and then resume. Item should be accessible. */
        break;

      default:
        \Civi::log('queue')->critical('Unrecognized outcome for task "{id}": {outcome}', $logDetails);
        break;
    }

    return $outcome;
  }

  final protected function assertType($object, array $types, string $message) {
    foreach ($types as $type) {
      if ($object instanceof $type) {
        return;
      }
    }
    throw new \Exception($message);
  }

  final protected function isRetriable(\CRM_Queue_Queue $queue, $item): bool {
    return property_exists($item, 'run_count')
      && is_numeric($queue->getSpec('retry_limit'))
      && $queue->getSpec('retry_limit') + 1 > $item->run_count;
  }

}
