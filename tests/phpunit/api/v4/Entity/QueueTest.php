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
use Civi\Api4\UserJob;
use Civi\Core\Event\GenericHookEvent;
use Civi\Test\QueueTestTrait;

/**
 * @group headless
 * @group queue
 */
class QueueTest extends Api4TestBase {

  use QueueTestTrait;

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
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testBasicLinearPolling(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
      'retry_limit' => 2,
      'retry_interval' => 4,
    ]);
    $this->assertQueueStats(0, 0, 0, $queue);

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
    $this->assertQueueStats(0, 0, 0, $queue);
  }

  public function testBasicParallelPolling(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    $queue = \Civi::queue($queueName, ['type' => 'SqlParallel', 'runner' => 'task', 'error' => 'delete']);
    $this->assertQueueStats(0, 0, 0, $queue);

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

    $this->assertQueueStats(0, 0, 0, $queue);
  }

  /**
   * Create a parallel queue. Claim and execute tasks as batches.
   *
   * Batches are executed via `hook_civicrm_queueRun_{runner}`.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testBatchParallelPolling(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    \Civi::dispatcher()->addListener('hook_civicrm_queueRun_testStuff', [$this, 'onHookQueueRun']);
    $queue = \Civi::queue($queueName, [
      'type' => 'SqlParallel',
      'runner' => 'testStuff',
      'error' => 'delete',
      'batch_limit' => 3,
    ]);
    $this->assertQueueStats(0, 0, 0, $queue);

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

  public function testRunLoop() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_runloop';
    \Civi::dispatcher()->addListener('hook_civicrm_queueRun_testStuff', [$this, 'onHookQueueRun']);
    $queue = \Civi::queue($queueName, [
      'type' => 'SqlParallel',
      'runner' => 'testStuff',
      'error' => 'delete',
      'batch_limit' => 4,
    ]);
    $this->assertQueueStats(0, 0, 0, $queue);

    for ($i = 0; $i < 20; $i++) {
      \Civi::queue($queueName)->createItem(['thingy' => $i]);
    }

    // 20 items ==> 4 per batch ==> 5 batches. Let's run the first 3...
    $result = Queue::run(0)->setQueue($queueName)->setMaxRequests(3)->execute();
    $this->assertEquals([0, 1, 2, 3], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][0], 'Scope of first batch');
    $this->assertEquals([4, 5, 6, 7], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][1], 'Scope of second batch');
    $this->assertEquals([8, 9, 10, 11], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][2], 'Scope of third batch');
    $this->assertEquals(12, $result[0]['item_successes']);
    $this->assertEquals(0, $result[0]['item_errors']);
    $this->assertEquals(3, $result[0]['loop_requests']);
    $this->assertTrue(is_numeric($result[0]['loop_duration']));
    $this->assertEquals('Reached request limit (3)', $result[0]['exit_message']);
    $this->assertEquals(0, $result[0]['queue_blocked']);
    $this->assertEquals(8, $result[0]['queue_ready'], 'Due to request limit, we left some items in queue');
    $this->assertEquals(8, $result[0]['queue_total'], 'Due to request limit, we left some items in queue');

    // And run any remaining batches...
    $result = Queue::run(0)->setQueue($queueName)->setMaxRequests(10)->execute();
    $this->assertEquals([12, 13, 14, 15], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][3], 'Scope of fourth batch');
    $this->assertEquals([16, 17, 18, 19], \Civi::$statics[__CLASS__]['onHookQueueRunLog'][4], 'Scope of fifth batch');
    $this->assertEquals(8, $result[0]['item_successes']);
    $this->assertEquals(0, $result[0]['item_errors']);
    $this->assertEquals(2 + 1, $result[0]['loop_requests']);
    $this->assertTrue(is_numeric($result[0]['loop_duration']));
    $this->assertEquals('No claimable items', $result[0]['exit_message']);
    $this->assertEquals(0, $result[0]['queue_blocked'], 'Queue should be empty');
    $this->assertEquals(0, $result[0]['queue_ready'], 'Queue should be empty');
    $this->assertEquals(0, $result[0]['queue_total'], 'Queue should be empty');
  }

  public function testRunLoop_abort() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_runloopabort';
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'abort',
    ]);
    $this->assertQueueStats(0, 0, 0, $queue);

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task([__CLASS__, 'dummyTask'], ['ok'])); /*A*/
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task([__CLASS__, 'dummyTask'], ['ok'])); /*B*/
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task([__CLASS__, 'dummyTask'], ['ok'])); /*C*/
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task([__CLASS__, 'dummyTask'], ['exception'])); /*D*/
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task([__CLASS__, 'dummyTask'], ['ok']));  /*E*/

    // 20 items ==> 4 per batch ==> 5 batches. Let's run the first 3...
    $result = Queue::run(0)->setQueue($queueName)->execute();
    $this->assertEquals(3, $result[0]['item_successes'], "Executed A+B+C");
    $this->assertEquals(1, $result[0]['item_errors'], "Exception on D");
    $this->assertEquals(4, $result[0]['loop_requests'], "Attempted A+B+C+D");
    $this->assertTrue(is_numeric($result[0]['loop_duration']));
    $this->assertEquals('Queue is not active (status => aborted)', $result[0]['exit_message']);
    $this->assertEquals(0, $result[0]['queue_blocked'], 'No tasks are time-blocked (future-scheduled)');
    $this->assertEquals(2, $result[0]['queue_ready'], 'Need to try D+E');
    $this->assertEquals(2, $result[0]['queue_total'], 'Need to try D+E');
  }

  public static function dummyTask(\CRM_Queue_TaskContext $ctx, string $outcome): bool {
    if ($outcome === 'exception') {
      throw new \Exception("dummyTask simulated an exception!");
    }
    if ($outcome === 'ok') {
      return TRUE;
    }
    static::fail('dummyTask has unrecognized outcome: ' . $outcome);
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

  public function testSelect(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    $queue = \Civi::queue($queueName, ['type' => 'SqlParallel', 'runner' => 'task', 'error' => 'delete']);
    $this->assertQueueStats(0, 0, 0, $queue);

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));

    $first = Queue::claimItems()->setQueue($queueName)->setSelect(['id', 'queue'])->execute()->single();
    $this->assertTrue(is_numeric($first['id']));
    $this->assertEquals($queueName, $first['queue']);
    $this->assertFalse(isset($first['data']));
    $this->assertFalse(isset($first['run_as']));
  }

  public function testSelectRunAs(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_select';
    $queue = \Civi::queue($queueName, ['type' => 'SqlParallel', 'runner' => 'task', 'error' => 'delete']);
    $this->assertQueueStats(0, 0, 0, $queue);

    $task = new \CRM_Queue_Task([QueueTest::class, 'doSomething'], ['first']);
    $task->runAs = ['contactId' => 99, 'domainId' => 1];
    \Civi::queue($queueName)->createItem($task);

    $first = Queue::claimItems()->setQueue($queueName)->setSelect(['id', 'queue', 'run_as'])->execute()->single();
    $this->assertTrue(is_numeric($first['id']));
    $this->assertEquals($queueName, $first['queue']);
    $this->assertFalse(isset($first['data']));
    $this->assertEquals(['contactId' => 99, 'domainId' => 1], $first['run_as']);
  }

  public function testEmptyPoll(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, ['type' => 'Sql', 'runner' => 'task', 'error' => 'delete']);
    $this->assertQueueStats(0, 0, 0, $queue);

    $startResult = Queue::claimItems()->setQueue($queueName)->execute();
    $this->assertEquals(0, $startResult->count());
  }

  public function getDelayableDrivers(): array {
    return [
      'Sql' => [['type' => 'Sql', 'runner' => 'task', 'error' => 'delete']],
      'SqlParallel' => [['type' => 'SqlParallel', 'runner' => 'task', 'error' => 'delete']],
      'Memory' => [['type' => 'Memory', 'runner' => 'task', 'error' => 'delete']],
    ];
  }

  /**
   * @dataProvider getDelayableDrivers
   */
  public function testDelayedStart(array $queueSpec) {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_delayed';
    $queue = \Civi::queue($queueName, $queueSpec);
    $this->assertQueueStats(0, 0, 0, $queue);

    $releaseTime = \CRM_Utils_Time::strtotime('+3 seconds');
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['itwillstartanymomentnow']
    ), ['release_time' => $releaseTime]);
    $this->assertQueueStats(1, 0, 1, $queue);

    // Not available... yet...
    $claim1 = $queue->claimItem();
    $this->assertEquals(NULL, $claim1);

    // OK, it'll come in a few seconds...
    $claim2 = $this->waitForClaim(0.5, 6, $queueName);
    $this->assertEquals('itwillstartanymomentnow', $claim2['data']['arguments'][0]);
    $this->assertTrue(\CRM_Utils_Time::time() >= $releaseTime);
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
    $this->assertQueueStats(0, 0, 0, $queue);

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
  public function testRetryWithDelinquencyAndSuccess(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
      'retry_limit' => 2,
      'retry_interval' => 0,
      'lease_time' => 1,
    ]);
    $this->assertQueueStats(0, 0, 0, $queue);

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['playinghooky']
    ));
    $this->assertQueueStats(1, 1, 0, $queue);

    $claim1 = $this->waitForClaim(0.5, 5, $queueName);
    // Oops, don't do anything with claim #1!
    $this->assertQueueStats(1, 0, 1, $queue);
    $this->assertEquals([], \Civi::$statics[__CLASS__]['doSomethingLog']);

    $claim2 = $this->waitForClaim(0.5, 5, $queueName);
    // Oops, don't do anything with claim #2!
    $this->assertQueueStats(1, 0, 1, $queue);
    $this->assertEquals([], \Civi::$statics[__CLASS__]['doSomethingLog']);

    $claim3 = $this->waitForClaim(0.5, 5, $queueName);
    $this->assertQueueStats(1, 0, 1, $queue);
    $result = Queue::runItems(0)->setItems([$claim3])->execute()->first();
    $this->assertQueueStats(0, 0, 0, $queue);
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
    $this->assertQueueStats(0, 0, 0, $queue);

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['playinghooky']
    ));
    $this->assertQueueStats(1, 1, 0, $queue);

    $claimAndRun = function($expectOutcome, $expectEndCount) use ($queue, $queueName) {
      $claim = $this->waitForClaim(0.5, 5, $queueName);
      $this->assertQueueStats(1, 0, 1, $queue);
      $result = Queue::runItems(0)->setItems([$claim])->execute()->first();
      $this->assertEquals($expectEndCount, $queue->getStatistic('total'));
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

  /**
   * If a queue is created as part of a user-job, then it has a fixed scope-of-work. The status
   * should flip after completing its work.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testUserJobQueue_Completion(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_userjob';

    $firedQueueStatus = [];
    \Civi::dispatcher()->addListener('hook_civicrm_queueStatus', function($e) use (&$firedQueueStatus) {
      $firedQueueStatus[$e->queue->getName()] = $e->status;
    });

    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
    ]);
    $this->assertQueueStats(0, 0, 0, $queue);

    $userJob = \Civi\Api4\UserJob::create(FALSE)->setValues([
      'job_type:name' => 'contact_import',
      'status_id:name' => 'in_progress',
      'queue_id.name' => $queue->getName(),
    ])->execute()->single();

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ));

    // Verify initial status
    $this->assertQueueStats(2, 2, 0, $queue);
    $this->assertEquals(FALSE, isset($firedQueueStatus[$queueName]));
    $this->assertEquals(TRUE, $queue->isActive());
    $this->assertEquals(4, UserJob::get()->addWhere('id', '=', $userJob['id'])->execute()->first()['status_id']);

    // OK, let's run both items - and check status afterward.
    Queue::runItems(FALSE)->setQueue($queueName)->execute()->single();
    $this->assertQueueStats(1, 1, 0, $queue);
    $this->assertEquals(FALSE, isset($firedQueueStatus[$queueName]));
    $this->assertEquals(TRUE, $queue->isActive());
    $this->assertEquals(4, UserJob::get()->addWhere('id', '=', $userJob['id'])->execute()->first()['status_id']);

    Queue::runItems(FALSE)->setQueue($queueName)->execute()->single();
    $this->assertQueueStats(0, 0, 0, $queue);
    $this->assertEquals('completed', $firedQueueStatus[$queueName]);
    $this->assertEquals(FALSE, $queue->isActive());
    $this->assertEquals(1, UserJob::get()->addWhere('id', '=', $userJob['id'])->execute()->first()['status_id']);
  }

  /**
   * If a queue is created as a long-term service, then its work is never complete.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testServiceQueue_NeverComplete(): void {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_service';

    $firedQueueStatus = [];
    \Civi::dispatcher()->addListener('hook_civicrm_queueStatus', function($e) use (&$firedQueueStatus) {
      $firedQueueStatus[$e->queue->getName()] = $e->status;
    });

    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
    ]);
    $this->assertQueueStats(0, 0, 0, $queue);

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ));

    // Verify initial status
    $this->assertQueueStats(2, 2, 0, $queue);
    $this->assertEquals(FALSE, isset($firedQueueStatus[$queueName]));
    $this->assertEquals(TRUE, $queue->isActive());

    // OK, let's run both items - and check status afterward.
    Queue::runItems(FALSE)->setQueue($queueName)->execute()->single();
    $this->assertQueueStats(1, 1, 0, $queue);
    $this->assertEquals(FALSE, isset($firedQueueStatus[$queueName]));
    $this->assertEquals(TRUE, $queue->isActive());

    Queue::runItems(FALSE)->setQueue($queueName)->execute()->single();
    $this->assertQueueStats(0, 0, 0, $queue);
    $this->assertEquals(FALSE, isset($firedQueueStatus[$queueName]));
    $this->assertEquals(TRUE, $queue->isActive());
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
