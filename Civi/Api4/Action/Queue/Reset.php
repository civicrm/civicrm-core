<?php

namespace Civi\Api4\Action\Queue;

use Civi\Api4\Generic\Result;

/**
 * Drop all pending items in the queue.
 *
 * @method ?string getQueue
 * @method $this setQueue(?string $queue)
 */
class Reset extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Name of the target queue.
   *
   * @var string
   * @required
   */
  protected $queue;

  public function _run(Result $result) {
    $queue = \Civi::queue($this->queue);
    $start = $queue->getStatistic('total');
    $queue->resetQueue();
    $end = $queue->getStatistic('total');

    $result[] = [
      'items' => $start - $end,
    ];
  }

}
