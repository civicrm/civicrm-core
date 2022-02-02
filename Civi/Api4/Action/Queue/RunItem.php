<?php

namespace Civi\Api4\Action\Queue;

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
 * @method $this setItem(?array $item)
 * @method ?array getItem()
 * @method ?string setQueue
 * @method $this setQueue(?string $queue)
 */
class RunItem extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Previously claimed item - which should now be released.
   *
   * @var array|null
   *   Fields: {id: scalar, queue: string}
   */
  protected $item;

  /**
   * Name of the target queue.
   *
   * @var string|null
   */
  protected $queue;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $items = [];

    if (!empty($this->item)) {
      $this->validateItemStub();
      $queue = \Civi::queue($this->item['queue']);
      $item = $queue->fetchItem($this->item['id']);
    }
    elseif (!empty($this->queue)) {
      $queue = \Civi::queue($this->queue);
      $item = $queue->claimItem(ClaimItem::DEFAULT_LEASE_TIME);
    }
    else {
      throw new \API_Exception("runItem: Requires either 'queue' or 'item'.");
    }

    if ($item) {
      $outcome = (new \CRM_Queue_Autorunner())->run($queue, $item);
      $result[] = ['outcome' => $outcome, 'item' => $this->createItemStub($item)];
    }
  }

  private function validateItemStub(): void {
    if (empty($this->item['queue'])) {
      throw new \API_Exception("Queue item requires property 'queue'.");
    }
    if (empty($this->item['id'])) {
      throw new \API_Exception("Queue item requires property 'id'.");
    }
  }

  private function createItemStub($item): array {
    return ['id' => $item->id, 'queue' => $item->queue_name];
  }

}
