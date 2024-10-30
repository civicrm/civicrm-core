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

use Civi\Api4\Generic\Traits\SelectParamTrait;

/**
 * Claim an item from the queue.  Returns zero or one items.
 *
 * @method ?string setQueue
 * @method $this setQueue(?string $queue)
 */
class ClaimItems extends \Civi\Api4\Generic\AbstractAction {

  use SelectParamTrait;

  /**
   * Name of the target queue.
   *
   * @var string|null
   */
  protected $queue;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $this->select = empty($this->select) ? ['id', 'data', 'queue'] : $this->select;
    $queue = $this->queue();
    if (!$queue->isActive()) {
      return;
    }

    $isBatch = $queue instanceof \CRM_Queue_Queue_BatchQueueInterface;
    $limit = $queue->getSpec('batch_limit') ?: 1;
    if ($limit > 1 && !$isBatch) {
      throw new \CRM_Core_Exception(sprintf('Queue "%s" (%s) does not support batching.', $queue->getName(), get_class($queue)));
      // Note 1: Simply looping over `claimItem()` is unlikley to help the consumer b/c
      // drivers like Sql+Memory are linear+blocking.
      // Note 2: The default is batch_limit=1. So someone has specifically chosen an invalid configuration...
    }
    $items = $isBatch ? $queue->claimItems($limit) : [$queue->claimItem()];

    foreach ($items as $item) {
      if ($item) {
        $result[] = $this->convertItemToStub($item);
      }
    }
  }

  /**
   * @param \CRM_Queue_DAO_QueueItem|\stdClass $item
   * @return array
   */
  protected function convertItemToStub(object $item): array {
    $array = [];
    foreach ($this->select as $field) {
      switch ($field) {
        case 'id':
          $array['id'] = $item->id;
          break;

        case 'data':
          $array['data'] = (array) $item->data;
          break;

        case 'run_as':
          $array['run_as'] = ($item->data instanceof \CRM_Queue_Task) ? $item->data->runAs : NULL;
          break;

        case 'queue':
          $array['queue'] = $this->queue;
          break;

      }
    }
    return $array;
  }

  protected function queue(): \CRM_Queue_Queue {
    if (empty($this->queue)) {
      throw new \CRM_Core_Exception('Missing required parameter: $queue');
    }
    return \Civi::queue($this->queue);
  }

}
