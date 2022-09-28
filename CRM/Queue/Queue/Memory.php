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
   */
  public function createItem($data, $options = []) {
    $id = $this->nextQueueItemId++;
    // force copy, no unintendedsharing effects from pointers
    $this->items[$id] = serialize($data);
  }

  /**
   * Determine number of items remaining in the queue.
   *
   * @return int
   */
  public function numberOfItems() {
    return count($this->items);
  }

  /**
   * Get and remove the next item.
   *
   * @param int $leaseTime
   *   Seconds.
   *
   * @return object
   *   Includes key 'data' that matches the inputted data.
   */
  public function claimItem($leaseTime = 3600) {
    // foreach hits the items in order -- but we short-circuit after the first
    foreach ($this->items as $id => $data) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      if (empty($this->releaseTimes[$id]) || $this->releaseTimes[$id] < $nowEpoch) {
        $this->releaseTimes[$id] = $nowEpoch + $leaseTime;

        $item = new stdClass();
        $item->id = $id;
        $item->data = unserialize($data);
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
   * @param int $leaseTime
   *   Seconds.
   *
   * @return object
   *   With key 'data' that matches the inputted data.
   */
  public function stealItem($leaseTime = 3600) {
    // foreach hits the items in order -- but we short-circuit after the first
    foreach ($this->items as $id => $data) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      $this->releaseTimes[$id] = $nowEpoch + $leaseTime;

      $item = new stdClass();
      $item->id = $id;
      $item->data = unserialize($data);
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
  }

  /**
   * Return an item that could not be processed.
   *
   * @param CRM_Core_DAO $item
   *   The item returned by claimItem.
   */
  public function releaseItem($item) {
    unset($this->releaseTimes[$item->id]);
  }

}
