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
class CRM_Queue_QueueTest extends CiviUnitTestCase {

  /* ----------------------- Queue providers ----------------------- */

  /* Define a list of queue providers which should be tested */

  /**
   * Return a list of persistent and transient queue providers.
   */
  public function getQueueSpecs() {
    $queueSpecs = array();
    $queueSpecs[] = array(
      array(
        'type' => 'Sql',
        'name' => 'test-queue',
      ),
    );
    $queueSpecs[] = array(
      array(
        'type' => 'Memory',
        'name' => 'test-queue',
      ),
    );
    return $queueSpecs;
  }

  /**
   * Per-provider tests
   */
  public function setUp() {
    parent::setUp();
    $this->queueService = CRM_Queue_Service::singleton(TRUE);
  }

  public function tearDown() {
    CRM_Utils_Time::resetTime();

    $tablesToTruncate = array('civicrm_queue_item');
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   * Create a few queue items; alternately enqueue and dequeue various
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testBasicUsage($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->assertTrue($this->queue instanceof CRM_Queue_Queue);

    $this->queue->createItem(array(
      'test-key' => 'a',
    ));
    $this->queue->createItem(array(
      'test-key' => 'b',
    ));
    $this->queue->createItem(array(
      'test-key' => 'c',
    ));

    $this->assertEquals(3, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(2, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('b', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->queue->createItem(array(
      'test-key' => 'd',
    ));

    $this->assertEquals(2, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('c', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(1, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('d', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Claim an item from the queue and release it back for subsequent processing.
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testManualRelease($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->assertTrue($this->queue instanceof CRM_Queue_Queue);

    $this->queue->createItem(array(
      'test-key' => 'a',
    ));

    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->assertEquals(1, $this->queue->numberOfItems());
    $this->queue->releaseItem($item);

    $this->assertEquals(1, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Test that item leases expire at the expected time.
   *
   * @dataProvider getQueueSpecs
   *
   * @param $queueSpec
   */
  public function testTimeoutRelease($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->assertTrue($this->queue instanceof CRM_Queue_Queue);

    CRM_Utils_Time::setTime('2012-04-01 1:00:00');
    $this->queue->createItem(array(
      'test-key' => 'a',
    ));

    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->assertEquals(1, $this->queue->numberOfItems());
    // forget to release

    // haven't reach expiration yet
    CRM_Utils_Time::setTime('2012-04-01 1:59:00');
    $item2 = $this->queue->claimItem();
    $this->assertEquals(FALSE, $item2);

    // pass expiration mark
    CRM_Utils_Time::setTime('2012-04-01 2:00:01');
    $item3 = $this->queue->claimItem();
    $this->assertEquals('a', $item3->data['test-key']);
    $this->assertEquals(1, $this->queue->numberOfItems());
    $this->queue->deleteItem($item3);

    $this->assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Test that item leases can be ignored.
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testStealItem($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->assertTrue($this->queue instanceof CRM_Queue_Queue);

    CRM_Utils_Time::setTime('2012-04-01 1:00:00');
    $this->queue->createItem(array(
      'test-key' => 'a',
    ));

    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->assertEquals(1, $this->queue->numberOfItems());
    // forget to release

    // haven't reached expiration yet, so claimItem fails
    CRM_Utils_Time::setTime('2012-04-01 1:59:00');
    $item2 = $this->queue->claimItem();
    $this->assertEquals(FALSE, $item2);

    // but stealItem works
    $item3 = $this->queue->stealItem();
    $this->assertEquals('a', $item3->data['test-key']);
    $this->assertEquals(1, $this->queue->numberOfItems());
    $this->queue->deleteItem($item3);

    $this->assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Test that queue content is reset when reset=>TRUE
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testCreateResetTrue($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->queue->createItem(array(
      'test-key' => 'a',
    ));
    $this->queue->createItem(array(
      'test-key' => 'b',
    ));
    $this->assertEquals(2, $this->queue->numberOfItems());
    unset($this->queue);

    $queue2 = $this->queueService->create(
      $queueSpec + array('reset' => TRUE)
    );
    $this->assertEquals(0, $queue2->numberOfItems());
  }

  /**
   * Test that queue content is not reset when reset is omitted.
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testCreateResetFalse($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->queue->createItem(array(
      'test-key' => 'a',
    ));
    $this->queue->createItem(array(
      'test-key' => 'b',
    ));
    $this->assertEquals(2, $this->queue->numberOfItems());
    unset($this->queue);

    $queue2 = $this->queueService->create($queueSpec);
    $this->assertEquals(2, $queue2->numberOfItems());

    $item = $queue2->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $queue2->releaseItem($item);
  }

  /**
   * Test that queue content is not reset when using load()
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testLoad($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->queue->createItem(array(
      'test-key' => 'a',
    ));
    $this->queue->createItem(array(
      'test-key' => 'b',
    ));
    $this->assertEquals(2, $this->queue->numberOfItems());
    unset($this->queue);

    $queue2 = $this->queueService->create($queueSpec);
    $this->assertEquals(2, $queue2->numberOfItems());

    $item = $queue2->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $queue2->releaseItem($item);
  }

}
