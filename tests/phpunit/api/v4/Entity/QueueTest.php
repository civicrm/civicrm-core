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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Entity;

use Civi\Api4\Queue;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class QueueTest extends UnitTestCase {

  /**
   * Setup a queue with a line of back-to-back tasks.
   *
   * The first task runs normally. The second task fails at first, but it is retried, and then
   * succeeds.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testBasicLinearPolling() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, ['type' => 'Sql']);
    $this->assertEquals(0, $queue->numberOfItems());

    $retry = ['retry_count' => 2, 'retry_interval' => 4];
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ), $retry);
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ), $retry);

    // Get item #1. Run it. Finish it.
    $first = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $this->assertCallback('doSomething', ['first'], $first);
    $this->assertEquals(0, count(Queue::claimItem()->setQueue($queueName)->execute()), 'Linear queue should not return more items while first item is pending.');
    $firstResult = Queue::runItem(0)->setItem($first)->execute()->single();
    $this->assertEquals('ok', $firstResult['outcome']);
    $this->assertEquals($first['id'], $firstResult['item']['id']);
    $this->assertEquals($first['queue'], $firstResult['item']['queue']);
    $this->assertEquals(['first_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    // Get item #2. Run it - but fail!
    $second = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $this->assertCallback('doSomething', ['second'], $second);
    \Civi::$statics[__CLASS__]['doSomethingResult'] = FALSE;
    $secondResult = Queue::runItem(0)->setItem($second)->execute()->single();
    unset(\Civi::$statics[__CLASS__]['doSomethingResult']);
    $this->assertEquals('retry', $secondResult['outcome']);
    $this->assertEquals(['first_ok', 'second_err'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    // Item #2 is delayed... it'll take a few seconds to come up...
    $waitCount = $this->waitFor(1.0, 10, function() use ($queueName, &$retrySecond): bool {
      $retrySecond = Queue::claimItem()->setQueue($queueName)->execute()->first();
      return !empty($retrySecond);
    });
    $this->assertTrue($waitCount > 0, 'Failed task should not become available immediately. It should take a few seconds.');
    $this->assertCallback('doSomething', ['second'], $retrySecond);
    $retrySecondResult = Queue::runItem(0)->setItem($retrySecond)->execute()->single();
    $this->assertEquals('ok', $retrySecondResult['outcome']);
    $this->assertEquals(['first_ok', 'second_err', 'second_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    // All done.
    $this->assertEquals(0, $queue->numberOfItems());
  }

  public function testBasicParallelPolling() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    $queue = \Civi::queue($queueName, ['type' => 'SqlParallel']);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ));

    $first = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $second = Queue::claimItem()->setQueue($queueName)->execute()->single();

    $this->assertCallback('doSomething', ['first'], $first);
    $this->assertCallback('doSomething', ['second'], $second);

    // Just for fun, let's run these tasks in opposite order.

    Queue::runItem(0)->setItem($second)->execute();
    $this->assertEquals(['second_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    Queue::runItem(0)->setItem($first)->execute();
    $this->assertEquals(['second_ok', 'first_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    $this->assertEquals(0, $queue->numberOfItems());
  }

  public function testEmptyPoll() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, ['type' => 'Sql']);
    $this->assertEquals(0, $queue->numberOfItems());

    $startResult = Queue::claimItem()->setQueue($queueName)->execute();
    $this->assertEquals(0, $startResult->count());
  }

  /**
   * Add a task which is never going to succeed. Try it multiple times (until we run out
   * of retries).
   */
  public function testRetryExhaustion() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, ['type' => 'Sql']);
    $this->assertEquals(0, $queue->numberOfItems());

    $retry = ['retry_count' => 2, 'retry_interval' => 1];
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['nogooddirtyscoundrel']
    ), $retry);

    \Civi::$statics[__CLASS__]['doSomethingResult'] = FALSE;
    $outcomes = [];
    $this->waitFor(0.5, 15, function() use ($queueName, &$outcomes) {
      $claimed = Queue::claimItem(0)->setQueue($queueName)->execute()->first();
      if (!$claimed) {
        return FALSE;
      }
      $result = Queue::runItem(0)->setItem($claimed)->execute()->first();
      $outcomes[] = $result['outcome'];
      return ($result['outcome'] !== 'retry');
    });

    $this->assertEquals(['retry', 'retry', 'fail'], $outcomes);
    $this->assertEquals(
      ['nogooddirtyscoundrel_err', 'nogooddirtyscoundrel_err', 'nogooddirtyscoundrel_err'],
      \Civi::$statics[__CLASS__]['doSomethingLog']
    );
  }

  public static function doSomething(\CRM_Queue_TaskContext $ctx, string $something) {
    $ok = \Civi::$statics[__CLASS__]['doSomethingResult'] ?? TRUE;
    \Civi::$statics[__CLASS__]['doSomethingLog'][] = $something . ($ok ? '_ok' : '_err');
    return $ok;
  }

  protected function assertCallback($expectMethod, $expectArgs, $actualTask) {
    $this->assertEquals([QueueTest::class, $expectMethod], $actualTask['data']['callback'], 'Claimed task should have expected method');
    $this->assertEquals($expectArgs, $actualTask['data']['arguments'], 'Claimed task should have expected arguments');
  }

  /**
   * Repeatedly check $condition until it returns true (or until we exhaust timeout).
   *
   * @param float $interval
   *   Seconds to wait between checks.
   * @param float $timeout
   *   Total maximum seconds to wait across all checks.
   * @param callable $condition
   *   The condition to check.
   * @return int
   *   Total number of intervals we had to wait/sleep.
   */
  protected function waitFor(float $interval, float $timeout, callable $condition): int {
    $end = microtime(TRUE) + $timeout;
    $interval *= round($interval * 1000 * 1000);
    $waitCount = 0;
    $ready = $condition();
    while (!$ready && microtime(TRUE) <= $end) {
      usleep($interval);
      $waitCount++;
      $ready = $condition();
    }
    $this->assertTrue($ready, 'Wait condition not met');
    return $waitCount;
  }

}
