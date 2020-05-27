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
 * A queue is an object (usually backed by some persistent data store)
 * which stores a list of tasks or messages for use by other processes.
 *
 * This would ideally be an interface, but it's handy to specify the
 * "function __construct()" and the "$name" handling
 *
 * Note: This interface closely parallels the DrupalQueueInterface.
 */
abstract class CRM_Queue_Queue {

  /**
   * @var string
   */
  private $_name;

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
    $this->_name = $queueSpec['name'];
  }

  /**
   * Determine the string name of this queue.
   *
   * @return string
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * Perform any registation or resource-allocation for a new queue
   */
  abstract public function createQueue();

  /**
   * Perform any loading or pre-fetch for an existing queue.
   */
  abstract public function loadQueue();

  /**
   * Release any resources claimed by the queue (memory, DB rows, etc)
   */
  abstract public function deleteQueue();

  /**
   * Check if the queue exists.
   *
   * @return bool
   */
  abstract public function existsQueue();

  /**
   * Add a new item to the queue.
   *
   * @param mixed $data
   *   Serializable PHP object or array.
   * @param array $options
   *   Queue-dependent options; for example, if this is a
   *   priority-queue, then $options might specify the item's priority.
   */
  abstract public function createItem($data, $options = []);

  /**
   * Determine number of items remaining in the queue.
   *
   * @return int
   */
  abstract public function numberOfItems();

  /**
   * Get the next item.
   *
   * @param int $lease_time
   *   Seconds.
   *
   * @return object
   *   with key 'data' that matches the inputted data
   */
  abstract public function claimItem($lease_time = 3600);

  /**
   * Get the next item, even if there's an active lease
   *
   * @param int $lease_time
   *   Seconds.
   *
   * @return object
   *   with key 'data' that matches the inputted data
   */
  abstract public function stealItem($lease_time = 3600);

  /**
   * Remove an item from the queue.
   *
   * @param object $item
   *   The item returned by claimItem.
   */
  abstract public function deleteItem($item);

  /**
   * Return an item that could not be processed.
   *
   * @param object $item
   *   The item returned by claimItem.
   */
  abstract public function releaseItem($item);

}
