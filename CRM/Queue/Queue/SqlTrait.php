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
    return ($this->getStatistic('total') > 0);
  }

  /**
   * @param string $name
   * @return int|float|null
   * @see \CRM_Queue_Queue::getStatistic()
   */
  public function getStatistic(string $name) {
    switch ($name) {
      case 'ready':
        return (int) CRM_Core_DAO::singleValueQuery(
          'SELECT count(*) FROM civicrm_queue_item WHERE queue_name = %1 AND (release_time is null OR UNIX_TIMESTAMP(release_time) <= %2)',
          [1 => [$this->getName(), 'String'], 2 => [CRM_Utils_Time::time(), 'Int']]);

      case 'blocked':
        return (int) CRM_Core_DAO::singleValueQuery(
          'SELECT count(*) FROM civicrm_queue_item WHERE queue_name = %1 AND UNIX_TIMESTAMP(release_time) > %2',
          [1 => [$this->getName(), 'String'], 2 => [CRM_Utils_Time::time(), 'Int']]);

      case 'total':
        return (int) CRM_Core_DAO::singleValueQuery(
          'SELECT count(*) FROM civicrm_queue_item WHERE queue_name = %1',
          [1 => [$this->getName(), 'String']]);

      default:
        return NULL;
    }
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
    if (!isset($options['release_time'])) {
      $releaseTime = 'null';
    }
    elseif (is_numeric($options['release_time'])) {
      $releaseTime = sprintf('FROM_UNIXTIME(%d)', $options['release_time']);
    }
    else {
      throw new \CRM_Core_Exception("Cannot enqueue item. Malformed release_time.");
    }

    \CRM_Core_DAO::executeQuery("INSERT INTO civicrm_queue_item (queue_name, submit_time, data, weight, release_time) VALUES (%1, now(), %2, %3, {$releaseTime})", [
      1 => [$this->getName(), 'String'],
      2 => [serialize($data), 'String'],
      3 => [$options['weight'] ?? 0, 'Integer'],
    ], TRUE, NULL, FALSE, FALSE);
  }

  /**
   * Remove an item from the queue.
   *
   * @param CRM_Core_DAO|stdClass $item
   *   The item returned by claimItem.
   */
  public function deleteItem($item) {
    $this->deleteItems([$item]);
  }

  public function deleteItems($items): void {
    if (empty($items)) {
      return;
    }
    $sql = CRM_Utils_SQL::interpolate('DELETE FROM civicrm_queue_item WHERE id IN (#ids) AND queue_name = @name', [
      'ids' => CRM_Utils_Array::collect('id', $items),
      'name' => $this->getName(),
    ]);
    CRM_Core_DAO::executeQuery($sql);
    $this->freeDAOs($items);
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
    $items = $this->fetchItems([$id]);
    return $items[0] ?? NULL;
  }

  public function fetchItems(array $ids): array {
    $dao = CRM_Utils_SQL_Select::from('civicrm_queue_item')
      ->select(['id', 'data', 'run_count'])
      ->where('id IN (#ids)', ['ids' => $ids])
      ->where('queue_name = @name', ['name' => $this->getName()])
      ->execute();
    $result = [];
    while ($dao->fetch()) {
      $result[] = (object) [
        'id' => $dao->id,
        'data' => unserialize($dao->data),
        'run_count' => $dao->run_count,
        'queue_name' => $this->getName(),
      ];
    }
    return $result;
  }

  /**
   * Return an item that could not be processed.
   *
   * @param CRM_Core_DAO $item
   *   The item returned by claimItem.
   */
  public function releaseItem($item) {
    $this->releaseItems([$item]);
  }

  public function releaseItems($items): void {
    if (empty($items)) {
      return;
    }
    $sql = empty($this->queueSpec['retry_interval'])
      ? 'UPDATE civicrm_queue_item SET release_time = NULL WHERE id IN (#ids) AND queue_name = @name'
      : 'UPDATE civicrm_queue_item SET release_time = DATE_ADD(NOW(), INTERVAL #retry SECOND) WHERE id IN (#ids) AND queue_name = @name';
    CRM_Core_DAO::executeQuery(CRM_Utils_SQL::interpolate($sql, [
      'ids' => CRM_Utils_Array::collect('id', $items),
      'name' => $this->getName(),
      'retry' => $this->queueSpec['retry_interval'] ?? NULL,
    ]));
    $this->freeDAOs($items);
  }

  /**
   * An item was previously claimed. No work was even attempted.
   *
   * @param array $items
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function relinquishItems($items): void {
    $sql = CRM_Utils_SQL::interpolate('UPDATE civicrm_queue_item SET release_time = NULL, run_count = run_count - 1 WHERE id IN (#ids) AND queue_name = @name', [
      'ids' => CRM_Utils_Array::collect('id', $items),
      'name' => $this->getName(),
    ]);
    CRM_Core_DAO::executeQuery($sql);
    $this->releaseItems($items);
  }

  protected function freeDAOs($mixed) {
    $mixed = (array) $mixed;
    foreach ($mixed as $item) {
      if ($item instanceof CRM_Core_DAO) {
        $item->free();
      }
    }
  }

}
