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
   * @param \CRM_Queue_Queue $queue
   */
  abstract protected function runItem($item, $queue): void;

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
    $todos = array_keys($items);

    while (count($todos)) {
      $itemPos = array_shift($todos);
      $outcomes[$itemPos] = $this->run($queue, $items[$itemPos]);
      if ($outcomes[$itemPos] === 'abort') {
        break;
      }
    }

    // If we aborted without finishing some things, then we prefer to return them gracefully.
    if (count($todos)) {
      $this->relinquishItems($queue, CRM_Utils_Array::subset($items, $todos));
      foreach ($todos as $itemPos) {
        $outcomes[$itemPos] = 'relinquish';
      }
    }
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
    /** @var string $outcome One of 'ok', 'retry', 'delete', 'abort' */

    if (!$this->validateItem($item)) {
      // Invalid item. Do not collect $200. Do not pass go. Go directly to fail.
      \Civi::log()->debug("Skipping invalid item: " . $this->getItemTitle($item));
      $outcome = $queue->getSpec('error');
      $exception = new \Exception('Cannot run. Received invalid queue item.');
    }
    elseif (is_numeric($queue->getSpec('retry_limit')) && $item->run_count > 1 + $queue->getSpec('retry_limit')) {
      \Civi::log()->debug("Skipping exhausted task: " . $this->getItemTitle($item));
      $outcome = $queue->getSpec('error');
      $exception = new \CRM_Core_Exception(sprintf('Skipping exhausted task after %d tries: %s', $item->run_count, print_r($this->getItemDetails($item), 1)), 'queue_retry_exhausted');
    }
    else {
      \Civi::log()->debug("Running task: " . $this->getItemTitle($item));
      try {
        $this->runItem($item, $queue);
        $outcome = 'ok';
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

  /**
   * If the batch of items encounters an 'abort', then any subsequent items
   * (within the same batch) should be returned to the queue for future work.
   *
   * @param \CRM_Queue_Queue|\CRM_Queue_Queue_BatchQueueInterface $queue
   * @param array $items
   *   The items that were not attempted.
   */
  private function relinquishItems(CRM_Queue_Queue $queue, $items): void {
    $batchRelinquish = is_callable([$queue, 'relinquishItems']);

    foreach ($items as $item) {
      $logDetails = [
        'id' => $queue->getName() . '#' . $item->id,
        'task' => $this->getItemDetails($item),
        'outcome' => 'relinquish',
      ];
      \Civi::log('queue')->error('Task "{id}" was relinquished due to preceding failure.', $logDetails);

      if (!$batchRelinquish) {
        // Old/third-party driver.
        $queue->releaseItem($item);
      }
    }

    if ($batchRelinquish) {
      $queue->relinquishItems($items);
    }
  }

  protected function validateItem($item): bool {
    return TRUE;
  }

  final protected function isRetriable(\CRM_Queue_Queue $queue, $item): bool {
    return property_exists($item, 'run_count')
      && is_numeric($queue->getSpec('retry_limit'))
      && $queue->getSpec('retry_limit') + 1 > $item->run_count;
  }

}
