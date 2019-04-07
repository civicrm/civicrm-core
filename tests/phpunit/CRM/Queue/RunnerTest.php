<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Ensure that various queue implementations comply with the interface
 * @group headless
 */
class CRM_Queue_RunnerTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->queueService = CRM_Queue_Service::singleton(TRUE);
    $this->queue = $this->queueService->create(array(
      'type' => 'Sql',
      'name' => 'test-queue',
    ));
    self::$_recordedValues = array();
  }

  public function tearDown() {
    unset($this->queue);
    unset($this->queueService);

    CRM_Utils_Time::resetTime();

    $tablesToTruncate = array('civicrm_queue_item');
    $this->quickCleanup($tablesToTruncate);
  }

  public function testRunAllNormal() {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('a'),
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('b'),
      'Add "b"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('c'),
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner(array(
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ));
    $this->assertEquals(self::$_recordedValues, array());
    $this->assertEquals(3, $this->queue->numberOfItems());
    $result = $runner->runAll();
    $this->assertEquals(TRUE, $result);
    $this->assertEquals(self::$_recordedValues, array('a', 'b', 'c'));
    $this->assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Run a series of tasks.
   *
   * One of the tasks will insert more TODOs at the start of the list.
   */
  public function testRunAll_AddMore() {
    // Prepare a list of tasks with an error in the middle.
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('a'),
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_enqueueNumbers'),
      array(1, 3),
      'Add more'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('b'),
      'Add "b"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner(array(
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ));
    $this->assertEquals(self::$_recordedValues, array());
    $this->assertEquals(3, $this->queue->numberOfItems());
    $result = $runner->runAll();
    $this->assertEquals(TRUE, $result);
    $this->assertEquals(self::$_recordedValues, array('a', 1, 2, 3, 'b'));
    $this->assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Run a series of tasks; when one throws an
   * exception, ignore it and continue
   */
  public function testRunAll_Continue_Exception() {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('a'),
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_throwException'),
      array('b'),
      'Throw exception'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('c'),
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner(array(
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_CONTINUE,
    ));
    $this->assertEquals(self::$_recordedValues, array());
    $this->assertEquals(3, $this->queue->numberOfItems());
    $result = $runner->runAll();
    // FIXME useless return
    $this->assertEquals(TRUE, $result);
    $this->assertEquals(self::$_recordedValues, array('a', 'c'));
    $this->assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Run a series of tasks; when one throws an exception,
   * abort processing and return it to the queue.
   */
  public function testRunAll_Abort_Exception() {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('a'),
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_throwException'),
      array('b'),
      'Throw exception'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('c'),
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner(array(
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ));
    $this->assertEquals(self::$_recordedValues, array());
    $this->assertEquals(3, $this->queue->numberOfItems());
    $result = $runner->runAll();
    $this->assertEquals(1, $result['is_error']);
    // nothing from 'c'
    $this->assertEquals(self::$_recordedValues, array('a'));
    // 'b' and 'c' remain
    $this->assertEquals(2, $this->queue->numberOfItems());
  }

  /**
   * Run a series of tasks; when one returns false,
   * abort processing and return it to the queue.
   */
  public function testRunAll_Abort_False() {
    // prepare a list of tasks with an error in the middle
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('a'),
      'Add "a"'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_returnFalse'),
      array(),
      'Return false'
    ));
    $this->queue->createItem(new CRM_Queue_Task(
      array('CRM_Queue_RunnerTest', '_recordValue'),
      array('c'),
      'Add "c"'
    ));

    // run the list of tasks
    $runner = new CRM_Queue_Runner(array(
      'queue' => $this->queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ));
    $this->assertEquals(self::$_recordedValues, array());
    $this->assertEquals(3, $this->queue->numberOfItems());
    $result = $runner->runAll();
    $this->assertEquals(1, $result['is_error']);
    // nothing from 'c'
    $this->assertEquals(self::$_recordedValues, array('a'));
    // 'b' and 'c' remain
    $this->assertEquals(2, $this->queue->numberOfItems());
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
        array('CRM_Queue_RunnerTest', '_recordValue'),
        array($i),
        sprintf('Add number "%d"', $i)
      ), array(
        'weight' => -1,
      ));
    }
    return TRUE;
  }

}
