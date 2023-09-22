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
 * A queue implementation which stores items in the CiviCRM SQL database
 */
class CRM_Queue_Queue_Memory extends CRM_Queue_Queue {

  /**
   * @var array
   *   array(queueItemId => queueItemData)
   */
  public $items;

  /**
   * @var array
   *   array(queueItemId => releaseTime), expressed in seconds since epoch.
   */
  public $releaseTimes;

  /**
   * Number of times each queue item has been attempted.
   *
   * @var array
   *   array(queueItemId => int $count),
   */
  protected $runCounts;

  public $nextQueueItemId = 1;

  /**
   * Create a reference to queue. After constructing the queue, one should
   * usually call createQueue (if it's a new queue) or loadQueue (if it's
   * known to be an existing queue).
   *
   * @param array $queueSpec
   *   Array with keys:
   *   - type: string, required, e.g. "interactive", "immediate", "stomp",
   *     "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - reset: bool, optional; if a queue is found, then it should be
   *     flushed; default to TRUE
   *   - (additional keys depending on the queue provider).
   */
  public function __construct($queueSpec) {
    parent::__construct($queueSpec);
  }

  /**
   * Perform any registation or resource-allocation for a new queue
   */
  public function createQueue() {
    $this->items = [];
    $this->releaseTimes = [];
    $this->runCounts = [];
  }

  /**
   * Perform any loading or pre-fetch for an existing queue.
   */
  public function loadQueue() {
    // $this->createQueue();
    throw new Exception('Unsupported: CRM_Queue_Queue_Memory::loadQueue');
  }

  /**
   * Release any resources claimed by the queue (memory, DB rows, etc)
   */
  public function deleteQueue() {
    $this->items = NULL;
    $this->releaseTimes = NULL;
    $this->runCounts = NULL;
  }

  /**
   * Check if the queue exists.
   *
   * @return bool
   */
  public function existsQueue() {
    return is_array($this->items);
  }

  /**
   * Add a new item to the queue.
   *
   * @param mixed $data
   *   Serializable PHP object or array.
   * @param array $options
   *   Queue-dependent options; for example, if this is a
   *   priority-queue, then $options might specify the item's priority.
   *   Ex: ['release_time' => strtotime('+3 hours')]
   */
  public function createItem($data, $options = []) {
    $id = $this->nextQueueItemId++;
    // force copy, no unintendedsharing effects from pointers
    $this->items[$id] = serialize($data);
    $this->runCounts[$id] = 0;
    if (isset($options['release_time'])) {
      $this->releaseTimes[$id] = $options['release_time'];
    }
  }

  /**
   * @param string $name
   * @return int|float|null
   * @see \CRM_Queue_Queue::getStatistic()
   */
  public function getStatistic(string $name) {
    $ready = function(): int {
      $now = CRM_Utils_Time::time();
      $ready = 0;
      foreach ($this->items as $id => $item) {
        if (empty($this->releaseTimes[$id]) || $this->releaseTimes[$id] <= $now) {
          $ready++;
        }
      }
      return $ready;
    };

    switch ($name) {
      case 'ready':
        return $ready();

      case 'blocked':
        return count($this->items) - $ready();

      case 'total':
        return count($this->items);

      default:
        return NULL;
    }
  }

  /**
   * Get and remove the next item.
   *
   * @param int|null $leaseTime
   *   Hold a lease on the claimed item for $X seconds.
   *   If NULL, inherit a queue default (`$queueSpec['lease_time']`) or system default (`DEFAULT_LEASE_TIME`).
   * @return object
   *   Includes key 'data' that matches the inputted data.
   */
  public function claimItem($leaseTime = NULL) {
    $leaseTime = $leaseTime ?: $this->getSpec('lease_time') ?: static::DEFAULT_LEASE_TIME;

    // foreach hits the items in order -- but we short-circuit after the first
    foreach ($this->items as $id => $data) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      if (empty($this->releaseTimes[$id]) || $this->releaseTimes[$id] < $nowEpoch) {
        $this->releaseTimes[$id] = $nowEpoch + $leaseTime;
        $this->runCounts[$id]++;

        $item = new stdClass();
        $item->id = $id;
        $item->data = unserialize($data);
        $item->run_count = $this->runCounts[$id];
        return $item;
      }
      else {
        // item in queue is reserved
        return FALSE;
      }
    }
    // nothing in queue
    return FALSE;
  }

  /**
   * Get the next item.
   *
   * @param int|null $leaseTime
   *   Hold a lease on the claimed item for $X seconds.
   *   If NULL, inherit a queue default (`$queueSpec['lease_time']`) or system default (`DEFAULT_LEASE_TIME`).
   * @return object
   *   With key 'data' that matches the inputted data.
   */
  public function stealItem($leaseTime = NULL) {
    $leaseTime = $leaseTime ?: $this->getSpec('lease_time') ?: static::DEFAULT_LEASE_TIME;

    // foreach hits the items in order -- but we short-circuit after the first
    foreach ($this->items as $id => $data) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      $this->releaseTimes[$id] = $nowEpoch + $leaseTime;
      $this->runCounts[$id]++;

      $item = new stdClass();
      $item->id = $id;
      $item->data = unserialize($data);
      $item->run_count = $this->runCounts[$id];
      return $item;
    }
    // nothing in queue
    return FALSE;
  }

  /**
   * Remove an item from the queue.
   *
   * @param object $item
   *   The item returned by claimItem.
   */
  public function deleteItem($item) {
    unset($this->items[$item->id]);
    unset($this->releaseTimes[$item->id]);
    unset($this->runCounts[$item->id]);
  }

  /**
   * Get the full data for an item.
   *
   * This is a passive peek - it does not claim/steal/release anything.
   *
   * @param int|string $id
   *   The unique ID of the task within the queue.
   * @return CRM_Queue_DAO_QueueItem|object|null $dao
   */
  public function fetchItem($id) {
    return $this->items[$id] ?? NULL;
  }

  /**
   * Return an item that could not be processed.
   *
   * @param CRM_Core_DAO $item
   *   The item returned by claimItem.
   */
  public function releaseItem($item) {
    if (empty($this->queueSpec['retry_interval'])) {
      unset($this->releaseTimes[$item->id]);
    }
    else {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      $this->releaseTimes[$item->id] = $nowEpoch + $this->queueSpec['retry_interval'];
    }
  }

}
