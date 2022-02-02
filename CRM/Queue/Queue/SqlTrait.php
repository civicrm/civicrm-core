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
 * Trait defines methods that are commonly used to implement a SQL-backed queue.
 */
trait CRM_Queue_Queue_SqlTrait {

  /**
   * Perform any registation or resource-allocation for a new queue
   */
  public function createQueue() {
    // nothing to do -- just start CRUDing items in the appropriate table
  }

  /**
   * Perform any loading or pre-fetch for an existing queue.
   */
  public function loadQueue() {
    // nothing to do -- just start CRUDing items in the appropriate table
  }

  /**
   * Release any resources claimed by the queue (memory, DB rows, etc)
   */
  public function deleteQueue() {
    return CRM_Core_DAO::singleValueQuery("
      DELETE FROM civicrm_queue_item
      WHERE queue_name = %1
    ", [
      1 => [$this->getName(), 'String'],
    ]);
  }

  /**
   * Check if the queue exists.
   *
   * @return bool
   */
  public function existsQueue() {
    return ($this->numberOfItems() > 0);
  }

  /**
   * Determine number of items remaining in the queue.
   *
   * @return int
   */
  public function numberOfItems() {
    return CRM_Core_DAO::singleValueQuery("
      SELECT count(*)
      FROM civicrm_queue_item
      WHERE queue_name = %1
    ", [
      1 => [$this->getName(), 'String'],
    ]);
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
    $dao = new CRM_Queue_DAO_QueueItem();
    $dao->queue_name = $this->getName();
    $dao->submit_time = CRM_Utils_Time::getTime('YmdHis');
    $dao->data = serialize($data);
    $dao->weight = CRM_Utils_Array::value('weight', $options, 0);
    if (empty($options['retry_count']) xor empty($options['retry_interval'])) {
      throw new \CRM_Core_Exception("Incomplete retry policy. Must specify retry_count and retry_interval together.");
    }
    $dao->retry_count = $options['retry_count'] ?? NULL;
    $dao->retry_interval = $options['retry_interval'] ?? NULL;
    $dao->save();
  }

  /**
   * Remove an item from the queue.
   *
   * @param CRM_Core_DAO|stdClass $dao
   *   The item returned by claimItem.
   */
  public function deleteItem($dao) {
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_queue_item WHERE id = %1', [
      1 => [$dao->id, 'Positive'],
    ]);
    if ($dao instanceof CRM_Core_DAO) {
      $dao->free();
    }
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
    $dao = new CRM_Queue_DAO_QueueItem();
    $dao->id = $id;
    $dao->queue_name = $this->getName();
    if (!$dao->find(TRUE)) {
      return NULL;
    }
    $dao->data = unserialize($dao->data);
    return $dao;
  }

  /**
   * Mark an item for retry.
   *
   * @param CRM_Core_DAO|stdClass $dao
   *   The item returned by claimItem.
   */
  public function retryItem($dao) {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue_item
      SET retry_count = retry_count - 1,
          release_time = DATE_ADD(NOW(), INTERVAL retry_interval SECOND)
      WHERE id = %1',
      [
        1 => [$dao->id, 'Positive'],
      ]
    );
    if ($dao instanceof CRM_Core_DAO) {
      $dao->free();
    }
  }

  /**
   * Return an item that could not be processed.
   *
   * @param CRM_Core_DAO $dao
   *   The item returned by claimItem.
   */
  public function releaseItem($dao) {
    $sql = "UPDATE civicrm_queue_item SET release_time = NULL WHERE id = %1";
    $params = [
      1 => [$dao->id, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao instanceof CRM_Core_DAO) {
      $dao->free();
    }
  }

}
