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
        'name' => 'test-queue-sql',
      ],
    ];
    $queueSpecs[] = [
      [
        'type' => 'Memory',
        'name' => 'test-queue-mem',
      ],
    ];
    $queueSpecs[] = [
      [
        'type' => 'SqlParallel',
        'name' => 'test-queue-sqlparallel',
      ],
    ];
    return $queueSpecs;
  }

  /**
   * Per-provider tests
   */
  public function setUp(): void {
    parent::setUp();
    $this->queueService = CRM_Queue_Service::singleton(TRUE);
  }

  public function tearDown(): void {
    CRM_Utils_Time::resetTime();

    $tablesToTruncate = ['civicrm_queue_item', 'civicrm_queue'];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  /**
   * Create a few queue items; alternately enqueue and dequeue various
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testBasicUsage($queueSpec) {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_queue');
    $this->queue = $this->queueService->create($queueSpec);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_queue');
    $this->assertTrue($this->queue instanceof CRM_Queue_Queue);
    $this->assertEquals($queueSpec['name'], $this->queue->getSpec('name'));
    $this->assertEquals($queueSpec['type'], $this->queue->getSpec('type'));

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
    CRM_Utils_Time::setTime('2012-04-01 2:00:03');
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
   * Create a persistent queue via CRM_Queue_Service. Get a queue object with Civi::queue().
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testPersistentUsage_service($queueSpec) {
    $this->assertTrue(!empty($queueSpec['name']));
    $this->assertTrue(!empty($queueSpec['type']));

    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_queue');
    $q1 = CRM_Queue_Service::singleton()->create($queueSpec + [
      'is_persistent' => TRUE,
    ]);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_queue');

    $q2 = Civi::queue($queueSpec['name']);
    $this->assertInstanceOf('CRM_Queue_Queue_' . $queueSpec['type'], $q2);
    $this->assertTrue($q1 === $q2);
  }

  /**
   * Create a persistent queue via APIv4. Get a queue object with Civi::queue().
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testPersistentUsage_api4($queueSpec) {
    $this->assertTrue(!empty($queueSpec['name']));
    $this->assertTrue(!empty($queueSpec['type']));

    \Civi\Api4\Queue::create(0)
      ->setValues($queueSpec)
      ->execute();

    $q1 = Civi::queue($queueSpec['name']);
    $this->assertInstanceOf('CRM_Queue_Queue_' . $queueSpec['type'], $q1);

    if ($queueSpec['type'] !== 'Memory') {
      CRM_Queue_Service::singleton(TRUE);
      $q2 = CRM_Queue_Service::singleton()->load([
        'name' => $queueSpec['name'],
        'is_persistent' => TRUE,
      ]);
      $this->assertInstanceOf('CRM_Queue_Queue_' . $queueSpec['type'], $q1);
    }
  }

  public function testFacadeAutoCreate() {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_queue');
    $q1 = Civi::queue('testFacadeAutoCreate_q1', [
      'type' => 'Sql',
    ]);
    $q2 = Civi::queue('testFacadeAutoCreate_q2', [
      'type' => 'SqlParallel',
    ]);
    $q1Reload = Civi::queue('testFacadeAutoCreate_q1', [
      /* q1 already exists, so it doesn't matter what type you give. */
      'type' => 'ZoombaroombaFaketypeGoombapoompa',
    ]);
    $this->assertDBQuery(2, 'SELECT count(*) FROM civicrm_queue');
    $this->assertInstanceOf('CRM_Queue_Queue_Sql', $q1);
    $this->assertInstanceOf('CRM_Queue_Queue_SqlParallel', $q2);
    $this->assertInstanceOf('CRM_Queue_Queue_Sql', $q1Reload);

    try {
      Civi::queue('testFacadeAutoCreate_q3' /* missing type */);
      $this->fail('Queue lookup should fail. There is neither pre-existing registration nor new details.');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertRegExp(';Missing field "type";', $e->getMessage());
    }
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
