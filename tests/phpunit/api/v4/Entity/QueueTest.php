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

use api\v4\Api4TestBase;
use Civi\Api4\Queue;
use Civi\Core\Event\GenericHookEvent;

/**
 * @group headless
 * @group queue
 */
class QueueTest extends Api4TestBase {

  protected function setUp(): void {
    \Civi::$statics[__CLASS__] = [
      'doSomethingResult' => TRUE,
      'doSomethingLog' => [],
      'onHookQueueRunLog' => [],
    ];
    parent::setUp();
  }

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
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
      'retry_limit' => 2,
      'retry_interval' => 4,
    ]);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ));

    // Get item #1. Run it. Finish it.
    $first = Queue::claimItems()->setQueue($queueName)->execute()->single();
    $this->assertCallback('doSomething', ['first'], $first);
    $this->assertEquals(0, count(Queue::claimItems()->setQueue($queueName)->execute()), 'Linear queue should not return more items while first item is pending.');
    $firstResult = Queue::runItems(0)->setItems([$first])->execute()->single();
    $this->assertEquals('ok', $firstResult['outcome']);
    $this->assertEquals($first['id'], $firstResult['item']['id']);
    $this->assertEquals($first['queue'], $firstResult['item']['queue']);
    $this->assertEquals(['first_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    // Get item #2. Run it - but fail!
    $second = Queue::claimItems()->setQueue($queueName)->execute()->single();
    $this->assertCallback('doSomething', ['second'], $second);
    \Civi::$statics[__CLASS__]['doSomethingResult'] = FALSE;
    $secondResult = Queue::runItems(0)->setItems([$second])->execute()->single();
    \Civi::$statics[__CLASS__]['doSomethingResult'] = TRUE;
    $this->assertEquals('retry', $secondResult['outcome']);
    $this->assertEquals(['first_ok', 'second_err'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    // Item #2 is delayed... it'll take a few seconds to come up...
    $waitCount = $this->waitFor(1.0, 10, function() use ($queueName, &$retrySecond): bool {
      $retrySecond = Queue::claimItems()->setQueue($queueName)->execute()->first();
      return !empty($retrySecond);
    });
    $this->assertTrue($waitCount > 0, 'Failed task should not become available immediately. It should take a few seconds.');
    $this->assertCallback('doSomething', ['second'], $retrySecond);
    $retrySecondResult = Queue::runItems(0)->setItems([$retrySecond])->execute()->single();
    $this->assertEquals('ok', $retrySecondResult['outcome']);
    $this->assertEquals(['first_ok', 'second_err', 'second_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    // All done.
    $this->assertEquals(0, $queue->numberOfItems());
  }

  public function testBasicParallelPolling() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    $queue = \Civi::queue($queueName, ['type' => 'SqlParallel', 'runner' => 'task', 'error' => 'delete']);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ));

    $first = Queue::claimItems()->setQueue($queueName)->execute()->single();
    $second = Queue::claimItems()->setQueue($queueName)->execute()->single();

    $this->assertCallback('doSomething', ['first'], $first);
    $this->assertCallback('doSomething', ['second'], $second);

    // Just for fun, let's run these tasks in opposite order.

    Queue::runItems(0)->setItems([$second])->execute();
    $this->assertEquals(['second_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    Queue::runItems(0)->setItems([$first])->execute();
    $this->assertEquals(['second_ok', 'first_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);

    $this->assertEquals(0, $queue->numberOfItems());
  }

  /**
   * Create a parallel queue. Claim and execute tasks as batches.
   *
   * Batches are executed via `hook_civicrm_queueRun_{runner}`.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testBatchParallelPolling() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    \Civi::dispatcher()->addListener('hook_civicrm_queueRun_testStuff', [$this, 'onHookQueueRun']);
    $queue = \Civi::queue($queueName, [
      'type' => 'SqlParallel',
      'runner' => 'testStuff',
      'error' => 'delete',
      'batch_limit' => 3,
    ]);
    $this->assertEquals(0, $queue->numberOfItems());

    for ($i = 0; $i < 7; $i++) {
      \Civi::queue($queueName)->createItem(['thingy' => $i]);
    }

    $result = Queue::runItems(0)->setQueue($queueName)->execute();
    $this->assertEquals(3, count($result));
    $this->assertEquals([0, 1, 2], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][0]);

    $result = Queue::runItems(0)->setQueue($queueName)->execute();
    $this->assertEquals(3, count($result));
    $this->assertEquals([3, 4, 5], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][1]);

    $result = Queue::runItems(0)->setQueue($queueName)->execute();
    $this->assertEquals(1, count($result));
    $this->assertEquals([6], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][2]);
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::queueRun()
   */
  public function onHookQueueRun(GenericHookEvent $e): void {
    \Civi::$statics[__CLASS__]['onHookQueueRunLog'][] = array_map(
      function($item) {
        return $item->data['thingy'];
      },
      $e->items
    );

    foreach ($e->items as $itemKey => $item) {
      $e->outcomes[$itemKey] = 'ok';
      $e->queue->deleteItem($item);
    }
  }

  public function testSelect() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    $queue = \Civi::queue($queueName, ['type' => 'SqlParallel', 'runner' => 'task', 'error' => 'delete']);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));

    $first = Queue::claimItems()->setQueue($queueName)->setSelect(['id', 'queue'])->execute()->single();
    $this->assertTrue(is_numeric($first['id']));
    $this->assertEquals($queueName, $first['queue']);
    $this->assertFalse(isset($first['data']));
  }

  public function testEmptyPoll() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, ['type' => 'Sql', 'runner' => 'task', 'error' => 'delete']);
    $this->assertEquals(0, $queue->numberOfItems());

    $startResult = Queue::claimItems()->setQueue($queueName)->execute();
    $this->assertEquals(0, $startResult->count());
  }

  public function getErrorModes(): array {
    return [
      'delete' => ['delete'],
      'abort' => ['abort'],
    ];
  }

  /**
   * Add a task which is never going to succeed. Try it multiple times (until we run out
   * of retries).
   *
   * @param string $errorMode
   *   Either 'delete' or 'abort'
   * @dataProvider getErrorModes
   */
  public function testRetryWithPoliteExhaustion(string $errorMode) {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => $errorMode,
      'retry_limit' => 2,
      'retry_interval' => 1,
    ]);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['nogooddirtyscoundrel']
    ));

    \Civi::$statics[__CLASS__]['doSomethingResult'] = FALSE;
    $outcomes = [];
    $this->waitFor(0.5, 15, function() use ($queueName, &$outcomes) {
      $claimed = Queue::claimItems(0)->setQueue($queueName)->execute()->first();
      if (!$claimed) {
        return FALSE;
      }
      $result = Queue::runItems(0)->setItems([$claimed])->execute()->first();
      $outcomes[] = $result['outcome'];
      return ($result['outcome'] !== 'retry');
    });

    $this->assertEquals(['retry', 'retry', $errorMode], $outcomes);
    $this->assertEquals(
      ['nogooddirtyscoundrel_err', 'nogooddirtyscoundrel_err', 'nogooddirtyscoundrel_err'],
      \Civi::$statics[__CLASS__]['doSomethingLog']
    );

    $expectActive = ['delete' => TRUE, 'abort' => FALSE];
    $this->assertEquals($expectActive[$errorMode], $queue->isActive());
  }

  /**
   * Add a task. The task-running agent is a bit delinquent... so it forgets the first
   * few tasks. But the third one works!
   */
  public function testRetryWithDelinquencyAndSuccess() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
      'retry_limit' => 2,
      'retry_interval' => 0,
      'lease_time' => 1,
    ]);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['playinghooky']
    ));
    $this->assertEquals(1, $queue->numberOfItems());

    $claim1 = $this->waitForClaim(0.5, 5, $queueName);
    // Oops, don't do anything with claim #1!
    $this->assertEquals(1, $queue->numberOfItems());
    $this->assertEquals([], \Civi::$statics[__CLASS__]['doSomethingLog']);

    $claim2 = $this->waitForClaim(0.5, 5, $queueName);
    // Oops, don't do anything with claim #2!
    $this->assertEquals(1, $queue->numberOfItems());
    $this->assertEquals([], \Civi::$statics[__CLASS__]['doSomethingLog']);

    $claim3 = $this->waitForClaim(0.5, 5, $queueName);
    $this->assertEquals(1, $queue->numberOfItems());
    $result = Queue::runItems(0)->setItems([$claim3])->execute()->first();
    $this->assertEquals(0, $queue->numberOfItems());
    $this->assertEquals(['playinghooky_ok'], \Civi::$statics[__CLASS__]['doSomethingLog']);
    $this->assertEquals('ok', $result['outcome']);
  }

  /**
   * Add a task which is never going to succeed. The task fails every time, and eventually
   * we either delete it or abort the queue.
   *
   * @param string $errorMode
   *   Either 'delete' or 'abort'
   * @dataProvider getErrorModes
   */
  public function testRetryWithEventualFailure(string $errorMode) {
    \Civi::$statics[__CLASS__]['doSomethingResult'] = FALSE;

    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => $errorMode,
      'retry_limit' => 2,
      'retry_interval' => 0,
      'lease_time' => 1,
    ]);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['playinghooky']
    ));
    $this->assertEquals(1, $queue->numberOfItems());

    $claimAndRun = function($expectOutcome, $expectEndCount) use ($queue, $queueName) {
      $claim = $this->waitForClaim(0.5, 5, $queueName);
      $this->assertEquals(1, $queue->numberOfItems());
      $result = Queue::runItems(0)->setItems([$claim])->execute()->first();
      $this->assertEquals($expectEndCount, $queue->numberOfItems());
      $this->assertEquals($expectOutcome, $result['outcome']);
    };

    $claimAndRun('retry', 1);
    $claimAndRun('retry', 1);
    switch ($errorMode) {
      case 'delete':
        $claimAndRun('delete', 0);
        $this->assertEquals(TRUE, $queue->isActive());
        break;

      case 'abort':
        $claimAndRun('abort', 1);
        $this->assertEquals(FALSE, $queue->isActive());
        break;
    }

    $this->assertEquals(['playinghooky_err', 'playinghooky_err', 'playinghooky_err'], \Civi::$statics[__CLASS__]['doSomethingLog']);
  }

  public static function doSomething(\CRM_Queue_TaskContext $ctx, string $something) {
    $ok = \Civi::$statics[__CLASS__]['doSomethingResult'];
    \Civi::$statics[__CLASS__]['doSomethingLog'][] = $something . ($ok ? '_ok' : '_err');
    return $ok;
  }

  protected function assertCallback($expectMethod, $expectArgs, $actualTask) {
    $this->assertEquals([QueueTest::class, $expectMethod], $actualTask['data']['callback'], 'Claimed task should have expected method');
    $this->assertEquals($expectArgs, $actualTask['data']['arguments'], 'Claimed task should have expected arguments');
  }

  protected function waitForClaim(float $interval, float $timeout, string $queueName): ?array {
    $claims = [];
    $this->waitFor($interval, $timeout, function() use ($queueName, &$claims) {
      $claimed = Queue::claimItems(0)->setQueue($queueName)->execute()->first();
      if (!$claimed) {
        return FALSE;
      }
      $claims[] = $claimed;
      return TRUE;
    });
    return $claims[0] ?? NULL;
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
