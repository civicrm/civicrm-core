<?php

namespace Civi\Api4\Action\Queue;

use Civi\Core\Event\GenericHookEvent;

/**
 * Run an enqueued item (task).
 *
 * You must either:
 *
 * - (a) Give the target queue-item specifically (`setItem()`). Useful if you called `claimItem()` separately.
 * - (b) Give the name of the queue from which to find an item (`setQueue()`).
 *
 * Note: If you use `setItem()`, the inputted will be validated (refetched) to ensure authenticity of all details.
 *
 * Returns 0 or 1 records which indicate the outcome of running the chosen task.
 *
 * ```php
 * $todo = Civi\Api4\Queue::claimItem()->setQueue($item)->setLeaseTime(600)->execute()->single();
 * $result = Civi\Api4\Queue::runItem()->setItem($todo)->execute()->single();
 * assert(in_array($result['outcome'], ['ok', 'retry', 'fail']))
 *
 * $result = Civi\Api4\Queue::runItem()->setQueue('foo')->execute()->first();
 * assert(in_array($result['outcome'], ['ok', 'retry', 'fail']))
 * ```
 *
 * Valid outcomes are:
 * - 'ok': Task executed normally. Removed from queue.
 * - 'retry': Task encountered an error. Will try again later.
 * - 'fail': Task encountered an error. Will not try again later. Removed from queue.
 *
 * @method $this setItems(?array $items)
 * @method ?array getItems()
 * @method ?string setQueue
 * @method $this setQueue(?string $queue)
 */
class RunItems extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Previously claimed item - which should now be released.
   *
   * @var array|null
   *   Fields: {id: scalar, queue: string}
   */
  protected $items;

  /**
   * Name of the target queue.
   *
   * @var string|null
   */
  protected $queue;

  public function _run(\Civi\Api4\Generic\Result $result) {
    if (!empty($this->items)) {
      $this->validateItemStubs();
      $queue = \Civi::queue($this->items[0]['queue']);
      $ids = array_column($this->items, 'id');
      if (count($ids) > 1 && !($queue instanceof \CRM_Queue_Queue_BatchQueueInterface)) {
        throw new \CRM_Core_Exception("runItems: Error: Running multiple items requires BatchQueueInterface");
      }
      if (count($ids) > 1) {
        $items = $queue->fetchItems($ids);
      }
      else {
        $items = [$queue->fetchItem($ids[0])];
      }
    }
    elseif (!empty($this->queue)) {
      $queue = \Civi::queue($this->queue);
      if (!$queue->isActive()) {
        return;
      }
      $items = $queue instanceof \CRM_Queue_Queue_BatchQueueInterface
        ? $queue->claimItems($queue->getSpec('batch_limit') ?: 1)
        : [$queue->claimItem()];
    }
    else {
      throw new \CRM_Core_Exception("runItems: Requires either 'queue' or 'item'.");
    }

    if (empty($items)) {
      return;
    }

    $outcomes = [];
    \CRM_Utils_Hook::queueRun($queue, $items, $outcomes);
    if (empty($outcomes)) {
      throw new \CRM_Core_Exception(sprintf('Failed to run queue items (name=%s, runner=%s, itemCount=%d, outcomeCount=%d)',
        $queue->getName(), $queue->getSpec('runner'), count($items), count($outcomes)));
    }
    foreach ($items as $itemPos => $item) {
      $result[] = ['outcome' => $outcomes[$itemPos], 'item' => $this->createItemStub($item)];
    }

    \Civi::dispatcher()->dispatch('civi.queue.check', GenericHookEvent::create([
      'queue' => $queue,
    ]));
  }

  private function validateItemStubs(): void {
    $queueNames = [];
    if (!isset($this->items[0])) {
      throw new \CRM_Core_Exception("Queue items must be given as numeric array.");
    }
    foreach ($this->items as $item) {
      if (empty($item['queue'])) {
        throw new \CRM_Core_Exception("Queue item requires property 'queue'.");
      }
      if (empty($item['id'])) {
        throw new \CRM_Core_Exception("Queue item requires property 'id'.");
      }
      $queueNames[$item['queue']] = 1;
    }
    if (count($queueNames) > 1) {
      throw new \CRM_Core_Exception("Queue items cannot be mixed. Found queues: " . implode(', ', array_keys($queueNames)));
    }
  }

  private function createItemStub($item): array {
    return ['id' => $item->id, 'queue' => $item->queue_name];
  }

}
