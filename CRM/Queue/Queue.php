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

  const DEFAULT_LEASE_TIME = 3600;

  /**
   * @var string
   */
  private $_name;

  /**
   * @var array{name: string, type: string, runner: string, batch_limit: int, lease_time: ?int, retry_limit: int, retry_interval: ?int}
   * @see \CRM_Queue_Service::create()
   */
  protected $queueSpec;

  /**
   * Create a reference to queue. After constructing the queue, one should
   * usually call createQueue (if it's a new queue) or loadQueue (if it's
   * known to be an existing queue).
   *
   * @param array{name: string, type: string, runner: string, batch_limit: int, lease_time: ?int, retry_limit: int, retry_interval: ?int} $queueSpec
   *   Ex: ['name' => 'my-import', 'type' => 'SqlParallel']
   *   The full definition of queueSpec is defined in CRM_Queue_Service.
   * @see \CRM_Queue_Service::create()
   */
  public function __construct($queueSpec) {
    $this->_name = $queueSpec['name'];
    $this->queueSpec = $queueSpec;
    unset($this->queueSpec['status']);
    // Status may be meaningfully + independently toggled (eg when using type=SqlParallel,error=abort).
    // Retaining a copy of 'status' in here would be misleading.
  }

  /**
   * Determine whether this queue is currently active.
   *
   * @return bool
   *   TRUE if runners should continue claiming new tasks from this queue
   * @throws \CRM_Core_Exception
   */
  public function isActive(): bool {
    return ($this->getStatus() === 'active');
  }

  /**
   * @return string|null
   * @throws \CRM_Core_Exception
   * @see \CRM_Queue_BAO_Queue::getStatuses()
   */
  public function getStatus() {
    // Queues work with concurrent processes. We want to make sure status info is up-to-date (never cached).
    $status = CRM_Core_DAO::getFieldValue('CRM_Queue_DAO_Queue', $this->_name, 'status', 'name', TRUE);
    if ($status === 'active') {
      $suspend = CRM_Core_DAO::singleValueQuery('SELECT value FROM civicrm_setting WHERE name = "queue_paused" AND domain_id = %1', [
        1 => [CRM_Core_BAO_Domain::getDomain()->id, 'Positive'],
      ]);
      if (!empty(CRM_Utils_String::unserialize($suspend))) {
        $status = 'paused';
      }
    }
    CRM_Utils_Hook::queueActive($status, $this->getName(), $this->queueSpec);
    // Note in future we might want to consider whether an upgrade is in progress.
    // Should we set the setting at that point?
    return $status;
  }

  /**
   * Change the status of the queue.
   *
   * @param string $status
   *   Ex: 'active', 'draft', 'aborted'
   */
  public function setStatus(string $status): void {
    $result = CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET status = %1 WHERE name = %2', [
      1 => [$status, 'String'],
      2 => [$this->getName(), 'String'],
    ]);
    // If multiple workers try to setStatus('completed') at roughly the same time, only one will fire an event.
    if ($result->affectedRows() > 0) {
      CRM_Utils_Hook::queueStatus($this, $status);
    }
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
   * Get a property from the queueSpec.
   *
   * @param string $field
   * @return mixed|null
   */
  public function getSpec(string $field) {
    return $this->queueSpec[$field] ?? NULL;
  }

  /**
   * Perform any registration or resource-allocation for a new queue
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
   * Delete all items in the queue.
   */
  public function resetQueue(): void {
    $this->deleteQueue();
    $this->createQueue();
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
  abstract public function createItem($data, $options = []);

  /**
   * Determine number of items remaining in the queue.
   *
   * @return int
   * @deprecated
   *   Use `getStatistic(string $name)` instead.
   *   The definition of `numberOfItems()` has become conflicted among different subclasses.
   */
  public function numberOfItems() {
    // This is the statistic traditionally reported by core queue implementations.
    // However, it may not be as useful, and subclasses may have different interpretations.
    return $this->getStatistic('total');
  }

  /**
   * Get summary information about items in the queue.
   *
   * @param string $name
   *   The desired statistic. Ex:
   *   - 'ready': The number of items ready for execution (not currently claimed, not scheduled for future).
   *   - 'blocked': The number of items that may be runnable in the future, but cannot be run right now.
   *   - 'total': The total number of items known to the queue, regardless of whether their current status.
   * @return int|float|null
   *   The value of the statistic, or NULL if the queue backend does not unsupport this statistic.
   */
  abstract public function getStatistic(string $name);

  /**
   * Get the next item.
   *
   * @param int|null $lease_time
   *   Hold a lease on the claimed item for $X seconds.
   *   If NULL, inherit a default.
   * @return object
   *   with key 'data' that matches the inputted data
   */
  abstract public function claimItem($lease_time = NULL);

  /**
   * Get the next item, even if there's an active lease
   *
   * @param int $lease_time
   *   Seconds.
   *
   * @return object
   *   with key 'data' that matches the inputted data
   */
  abstract public function stealItem($lease_time = NULL);

  /**
   * Remove an item from the queue.
   *
   * @param object $item
   *   The item returned by claimItem.
   */
  abstract public function deleteItem($item);

  /**
   * Get the full data for an item.
   *
   * This is a passive peek - it does not claim/steal/release anything.
   *
   * @param int|string $id
   *   The unique ID of the task within the queue.
   * @return CRM_Queue_DAO_QueueItem|object|null $dao
   */
  abstract public function fetchItem($id);

  /**
   * Return an item that could not be processed.
   *
   * @param object $item
   *   The item returned by claimItem.
   */
  abstract public function releaseItem($item);

}
