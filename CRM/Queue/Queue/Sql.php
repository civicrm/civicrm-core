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
class CRM_Queue_Queue_Sql extends CRM_Queue_Queue {

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
   * Get the next item.
   *
   * The SELECT then guarded UPDATE prevents two processes from claiming the same
   * item without requiring table-level locking. The subquery picks the head of
   * the queue regardless of if it has been claimed; the outer WHERE returns it
   * only if it's available, so we don't fall through to the next item
   * (see testBasicLinearPolling).
   * Retry rather than directly returning NULL so this doesn't falsely indicate
   * that the queue is empty.
   *
   * TODO: On MariaDB 10.6+ / MySQL 8+ we could use SELECT ... FOR UPDATE SKIP LOCKED
   * instead, so a claimer never picks the same candidate that another just selected
   * (avoiding the retry).
   *
   * @param int|null $lease_time
   *   Hold a lease on the claimed item for $X seconds.
   *   If NULL, inherit a queue default (`$queueSpec['lease_time']`) or system default (`DEFAULT_LEASE_TIME`).
   * @return object
   *   With key 'data' that matches the inputted data.
   */
  public function claimItem($lease_time = NULL) {
    $lease_time = $lease_time ?: $this->getSpec('lease_time') ?: static::DEFAULT_LEASE_TIME;

    $maxAttempts = 5;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
      $dao = CRM_Core_DAO::executeQuery('
          SELECT first_in_queue.* FROM (
            SELECT id, queue_name, submit_time, release_time, run_count, data
            FROM civicrm_queue_item
            WHERE queue_name = %1
            ORDER BY weight, id
            LIMIT 1
          ) first_in_queue
          WHERE release_time IS NULL OR UNIX_TIMESTAMP(release_time) < %2
        ', [
          1 => [$this->getName(), 'String'],
          2 => [CRM_Utils_Time::time(), 'Integer'],
        ], TRUE, 'CRM_Queue_DAO_QueueItem');

      if (!$dao->fetch()) {
        return NULL;
      }

      if ($this->attemptClaim($dao, $lease_time)) {
        $dao->run_count++;
        $dao->data = unserialize($dao->data);
        return $dao;
      }
      // If we can't claim the selected item, retry
    }

    \Civi::log('queue')->warning('Failed to claim item from queue "{queue}" after {attempts} attempts due to contention.', [
      'queue' => $this->getName(),
      'attempts' => $maxAttempts,
    ]);
    return NULL;
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
      CRM_Core_DAO::executeQuery("UPDATE civicrm_queue_item SET release_time = from_unixtime(unix_timestamp() + %1) WHERE id = %2", [
        '1' => [CRM_Utils_Time::delta() + $lease_time, 'Integer'],
        '2' => [$dao->id, 'Integer'],
      ]);
      $dao->data = unserialize($dao->data);
      return $dao;
    }
  }

}
