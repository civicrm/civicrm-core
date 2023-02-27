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
class CRM_Queue_Queue_SqlParallel extends CRM_Queue_Queue implements CRM_Queue_Queue_BatchQueueInterface {

  use CRM_Queue_Queue_SqlTrait;

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
   * @inheritDoc
   */
  public function claimItem($lease_time = NULL) {
    $items = $this->claimItems(1, $lease_time);
    return $items[0] ?? NULL;
  }

  /**
   * @inheritDoc
   */
  public function claimItems(int $limit, ?int $lease_time = NULL): array {
    $lease_time = $lease_time ?: $this->getSpec('lease_time') ?: static::DEFAULT_LEASE_TIME;
    $limit = $this->getSpec('batch_limit') ? min($limit, $this->getSpec('batch_limit')) : $limit;

    $dao = CRM_Core_DAO::executeQuery('LOCK TABLES civicrm_queue_item WRITE;');
    $sql = "SELECT id, queue_name, submit_time, release_time, run_count, data
        FROM civicrm_queue_item
        WHERE queue_name = %1
              AND (release_time IS NULL OR UNIX_TIMESTAMP(release_time) < %2)
        ORDER BY weight ASC, id ASC
        LIMIT %3
      ";
    $params = [
      1 => [$this->getName(), 'String'],
      2 => [CRM_Utils_Time::time(), 'Integer'],
      3 => [$limit, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $params, TRUE, 'CRM_Queue_DAO_QueueItem');
    if (is_a($dao, 'DB_Error')) {
      // FIXME - Adding code to allow tests to pass
      CRM_Core_Error::fatal();
    }

    $result = [];
    while ($dao->fetch()) {
      $result[] = (object) [
        'id' => $dao->id,
        'data' => unserialize($dao->data),
        'queue_name' => $dao->queue_name,
        'run_count' => 1 + (int) $dao->run_count,
      ];
    }
    if ($result) {
      $sql = CRM_Utils_SQL::interpolate('UPDATE civicrm_queue_item SET release_time = FROM_UNIXTIME(UNIX_TIMESTAMP() + #release), run_count = 1+run_count WHERE id IN (#ids)', [
        'release' => CRM_Utils_Time::delta() + $lease_time,
        'ids' => CRM_Utils_Array::collect('id', $result),
      ]);
      CRM_Core_DAO::executeQuery($sql);
    }

    $dao = CRM_Core_DAO::executeQuery('UNLOCK TABLES;');

    return $result;
  }

  /**
   * Get the next item, even if there's an active lease
   *
   * @param int|null $lease_time
   *   Hold a lease on the claimed item for $X seconds.
   *   If NULL, inherit a queue default (`$queueSpec['lease_time']`) or system default (`DEFAULT_LEASE_TIME`).
   * @return object
   *   With key 'data' that matches the inputted data.
   */
  public function stealItem($lease_time = NULL) {
    $lease_time = $lease_time ?: $this->getSpec('lease_time') ?: static::DEFAULT_LEASE_TIME;

    $sql = "
      SELECT id, queue_name, submit_time, release_time, run_count, data
      FROM civicrm_queue_item
      WHERE queue_name = %1
      ORDER BY weight ASC, id ASC
      LIMIT 1
    ";
    $params = [
      1 => [$this->getName(), 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $params, TRUE, 'CRM_Queue_DAO_QueueItem');
    if ($dao->fetch()) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      $dao->run_count++;
      CRM_Core_DAO::executeQuery("UPDATE civicrm_queue_item SET release_time = from_unixtime(unix_timestamp() + %1), run_count = %3 WHERE id = %2", [
        '1' => [CRM_Utils_Time::delta() + $lease_time, 'Integer'],
        '2' => [$dao->id, 'Integer'],
        '3' => [$dao->run_count, 'Integer'],
      ]);
      $dao->data = unserialize($dao->data);
      return $dao;
    }
  }

}
