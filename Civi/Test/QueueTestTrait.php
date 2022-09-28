<?php

namespace Civi\Test;

/**
 * Helper functions for testing queues.
 */
trait QueueTestTrait {

  protected function assertQueueStats(int $total, int $ready, int $blocked, \CRM_Queue_Queue $queue) {
    $format = 'total=%d ready=%d blocked=%d';
    $expect = [$total, $ready, $blocked];
    $actual = [$queue->getStatistic('total'), $queue->getStatistic('ready'), $queue->getStatistic('blocked')];
    $this->assertEquals(sprintf($format, ...$expect), sprintf($format, ...$actual));

    // Deprecated - but checking for continuity.
    $this->assertEquals($total, $queue->numberOfItems());
  }

}
