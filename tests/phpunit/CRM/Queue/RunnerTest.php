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
 * Ensure that various queue implementations comply with the interface
 * @group headless
 * @group queue
 */
class CRM_Queue_RunnerTest extends CiviUnitTestCase {

  use \Civi\Test\QueueTestTrait;

  /**
   * @var CRM_Queue_Service
   */
  private $queueService;

  /**
   * @var CRM_Queue_Queue
   */
  private $queue;

  public function setUp(): void {
    parent::setUp();
    $this->queueService = CRM_Queue_Service::singleton(TRUE);
    $this->queue = $this->queueService->create([
      'type' => 'Sql',
      'name' => 'test-queue',
    ]);
    self::$_recordedValues = [];
  }

  public function tearDown(): void {
    unset($this->queue);
    unset($this->queueService);

    CRM_Utils_Time::resetTime();

    $tablesToTruncate = ['civicrm_queue_item'];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  /**
   * Test that the queue is not left in a state where another run causes an exception.
   */
  public function testRunAllTwice(): void {
    $this->runAQueue();
    $this->runAQueue();
  }

  public function testRunAllNormal(): void {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['a'],
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['b'],
      'Add "b"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['c'],
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner([
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ]);
    $this->assertEquals(self::$_recordedValues, []);
    $this->assertQueueStats(3, 3, 0, $this->queue);
    $result = $runner->runAll();
    $this->assertEquals(TRUE, $result);
    $this->assertEquals(self::$_recordedValues, ['a', 'b', 'c']);
    $this->assertQueueStats(0, 0, 0, $this->queue);
  }

  /**
   * Run a series of tasks.
   *
   * One of the tasks will insert more TODOs at the start of the list.
   */
  public function testRunAll_AddMore(): void {
    // Prepare a list of tasks with an error in the middle.
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['a'],
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_enqueueNumbers'],
      [1, 3],
      'Add more'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['b'],
      'Add "b"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner([
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ]);
    $this->assertEquals(self::$_recordedValues, []);
    $this->assertQueueStats(3, 3, 0, $this->queue);
    $result = $runner->runAll();
    $this->assertEquals(TRUE, $result);
    $this->assertEquals(self::$_recordedValues, ['a', 1, 2, 3, 'b']);
    $this->assertQueueStats(0, 0, 0, $this->queue);
  }

  /**
   * Run a series of tasks; when one throws an
   * exception, ignore it and continue
   */
  public function testRunAll_Continue_Exception(): void {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['a'],
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_throwException'],
      ['b'],
      'Throw exception'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['c'],
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner([
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_CONTINUE,
    ]);
    $this->assertEquals(self::$_recordedValues, []);
    $this->assertQueueStats(3, 3, 0, $this->queue);

    $result = $runner->runAll();
    // FIXME useless return
    $this->assertEquals(TRUE, $result);
    $this->assertEquals(self::$_recordedValues, ['a', 'c']);
    $this->assertQueueStats(0, 0, 0, $this->queue);
  }

  /**
   * Run a series of tasks; when one throws an exception,
   * abort processing and return it to the queue.
   */
  public function testRunAll_Abort_Exception(): void {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['a'],
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_throwException'],
      ['b'],
      'Throw exception'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['c'],
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner([
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ]);
    $this->assertEquals(self::$_recordedValues, []);
    $this->assertQueueStats(3, 3, 0, $this->queue);

    $result = $runner->runAll();
    $this->assertEquals(1, $result['is_error']);
    // nothing from 'c'
    $this->assertEquals(self::$_recordedValues, ['a']);
    // 'b' and 'c' remain
    $this->assertQueueStats(2, 2, 0, $this->queue);
  }

  /**
   * Run a series of tasks; when one returns false,
   * abort processing and return it to the queue.
   */
  public function testRunAll_Abort_False(): void {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['a'],
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_returnFalse'],
      [],
      'Return false'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['c'],
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner([
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ]);
    $this->assertEquals(self::$_recordedValues, []);
    $this->assertQueueStats(3, 3, 0, $this->queue);
    $result = $runner->runAll();
    $this->assertEquals(1, $result['is_error']);
    // nothing from 'c'
    $this->assertEquals(self::$_recordedValues, ['a']);
    // 'b' and 'c' remain
    $this->assertQueueStats(2, 2, 0, $this->queue);
  }

  /**
   * Queue tasks
   * @var array
   */
  protected static $_recordedValues;

  /**
   * @param $taskCtx
   * @param $value
   *
   * @return bool
   */
  public static function _recordValue($taskCtx, $value) {
    self::$_recordedValues[] = $value;
    return TRUE;
  }

  /**
   * @param $taskCtx
   *
   * @return bool
   */
  public static function _returnFalse($taskCtx) {
    return FALSE;
  }

  /**
   * @param $taskCtx
   * @param $value
   *
   * @throws Exception
   */
  public static function _throwException($taskCtx, $value) {
    throw new Exception("Manufactured error: $value");
  }

  /**
   * @param $taskCtx
   * @param $low
   * @param $high
   *
   * @return bool
   */
  public static function _enqueueNumbers($taskCtx, $low, $high) {
    for ($i = $low; $i <= $high; $i++) {
      $taskCtx->queue->createItem(new CRM_Queue_Task(
        ['CRM_Queue_RunnerTest', '_recordValue'],
        [$i],
        sprintf('Add number "%d"', $i)
      ), [
        'weight' => -1,
      ]);
    }
    return TRUE;
  }

  protected function runAQueue(): void {
    $this->queueService = CRM_Queue_Service::singleton(TRUE);
    $queueName = 'seeing-double';
    $queue = \Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'retry_limit' => 3,
      'retry_interval' => 20,
      'error' => 'abort',
    ]);
    // prepare a list of tasks with an error in the middle
    $queue->createItem(new CRM_Queue_Task(
      ['CRM_Queue_RunnerTest', '_recordValue'],
      ['a'],
      'Add "a"'
    ));
    // run the list of tasks
    $runner = new CRM_Queue_Runner([
      'queue' => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ]);
    $runner->runAll();
  }

}
