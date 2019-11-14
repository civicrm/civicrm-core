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
 */
class CRM_Queue_QueueTest extends CiviUnitTestCase {

  /* ----------------------- Queue providers ----------------------- */

  /* Define a list of queue providers which should be tested */

  /**
   * Return a list of persistent and transient queue providers.
   */
  public function getQueueSpecs() {
    $queueSpecs = [];
    $queueSpecs[] = [
      [
        'type' => 'Sql',
        'name' => 'test-queue',
      ],
    ];
    $queueSpecs[] = [
      [
        'type' => 'Memory',
        'name' => 'test-queue',
      ],
    ];
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

    $tablesToTruncate = ['civicrm_queue_item'];
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

    $this->queue->createItem([
      'test-key' => 'a',
    ]);
    $this->queue->createItem([
      'test-key' => 'b',
    ]);
    $this->queue->createItem([
      'test-key' => 'c',
    ]);

    $this->assertEquals(3, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(2, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('b', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->queue->createItem([
      'test-key' => 'd',
    ]);

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

    $this->queue->createItem([
      'test-key' => 'a',
    ]);

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
    $this->queue->createItem([
      'test-key' => 'a',
    ]);

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
    $this->queue->createItem([
      'test-key' => 'a',
    ]);

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
    $this->queue->createItem([
      'test-key' => 'a',
    ]);
    $this->queue->createItem([
      'test-key' => 'b',
    ]);
    $this->assertEquals(2, $this->queue->numberOfItems());
    unset($this->queue);

    $queue2 = $this->queueService->create(
      $queueSpec + ['reset' => TRUE]
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
    $this->queue->createItem([
      'test-key' => 'a',
    ]);
    $this->queue->createItem([
      'test-key' => 'b',
    ]);
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
    $this->queue->createItem([
      'test-key' => 'a',
    ]);
    $this->queue->createItem([
      'test-key' => 'b',
    ]);
    $this->assertEquals(2, $this->queue->numberOfItems());
    unset($this->queue);

    $queue2 = $this->queueService->create($queueSpec);
    $this->assertEquals(2, $queue2->numberOfItems());

    $item = $queue2->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $queue2->releaseItem($item);
  }

}
