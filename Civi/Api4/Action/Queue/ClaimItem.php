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

namespace Civi\Api4\Action\Queue;

/**
 * Claim an item from the queue.  Returns zero or one items.
 *
 * @method ?string setQueue
 * @method $this setQueue(?string $queue)
 */
class ClaimItem extends \Civi\Api4\Generic\AbstractAction {

  /**
   * If the leaseTime is left blank, use this leaseTime.
   *
   * Note: This constant may become irrelevant in the future. For example, the system could
   * have a configurable value on the queue, queue-item, or system-settings.
   */
  const DEFAULT_LEASE_TIME = 3600;

  /**
   * Name of the target queue.
   *
   * @var string|null
   */
  protected $queue;

  /**
   * Amount of time to hold a claimed item (#seconds).
   *
   * If you do not complete the item, then it may be claimed by another agent.
   *
   * @var int|null
   */
  protected $leaseTime = NULL;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $queue = $this->queue();
    $item = $queue->claimItem($this->leaseTime ?: ClaimItem::DEFAULT_LEASE_TIME);
    if ($item) {
      $result[] = [
        'id' => $item->id,
        'data' => (array) $item->data,
        'queue' => $this->queue,
      ];
    }
  }

  protected function queue(): \CRM_Queue_Queue {
    if (empty($this->queue)) {
      throw new \API_Exception('Missing required parameter: $queue');
    }
    return \Civi::queue($this->queue);
  }

}
