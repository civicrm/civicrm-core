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

  use \Civi\Test\QueueTestTrait;

  /**
   * @var CRM_Queue_Service
   */
  private $queueService;

  /**
   * @var CRM_Queue_Queue
   */
  private $queue;

  /* ----------------------- Queue providers ----------------------- */

  /* Define a list of queue providers which should be tested */

  /**
   * Return a list of persistent and transient queue providers.
   */
  public function getQueueSpecs() {
    $queueSpecs = [];
    $queueSpecs['Sql'] = [
      [
        'type' => 'Sql',
        'name' => 'test-queue-sql',
      ],
    ];
    $queueSpecs['Memory'] = [
      [
        'type' => 'Memory',
        'name' => 'test-queue-mem',
      ],
    ];
    $queueSpecs['SqlParallel'] = [
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
   * If the queue has an automatic background runner (`runner`), then it
   * must also have an `error` policy.
   */
  public function testRunnerRequiresErrorPolicy(): void {
    try {
      $q1 = Civi::queue('test/incomplete/1', [
        'type' => 'Sql',
        'runner' => 'task',
      ]);
      $this->fail('Should fail without error policy');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertMatchesRegularExpression('/Invalid error mode/', $e->getMessage());
    }

    $q2 = Civi::queue('test/complete/2', [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
    ]);
    $this->assertTrue($q2 instanceof CRM_Queue_Queue_Sql);
  }

  public function testStatuses(): void {
    $q1 = Civi::queue('test/valid/default', [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'delete',
    ]);
    $this->assertTrue($q1 instanceof CRM_Queue_Queue_Sql);
    $this->assertDBQuery('active', "SELECT status FROM civicrm_queue WHERE name = 'test/valid/default'");

    foreach (['draft', 'active', 'completed', 'aborted'] as $n => $exampleStatus) {
      $q1 = Civi::queue("test/valid/$n", [
        'type' => 'Sql',
        'runner' => 'task',
        'error' => 'delete',
        'status' => $exampleStatus,
      ]);
      $this->assertTrue($q1 instanceof CRM_Queue_Queue_Sql);
      $this->assertDBQuery($exampleStatus, "SELECT status FROM civicrm_queue WHERE name = 'test/valid/$n'");
    }
  }

  public function testTemplating(): void {
    \Civi\Api4\Queue::create()->setValues([
      'is_template' => TRUE,
      'name' => 'test/template',
      'type' => 'SqlParallel',
      'runner' => 'task',
      'error' => 'delete',
    ])->execute();
    $this->assertDBQuery(1, "SELECT is_template FROM civicrm_queue WHERE name = 'test/template'");

    $qActive = Civi::queue('test/my-active', [
      'template' => 'test/template',
    ]);
    $this->assertEquals('test/my-active', $qActive->getName());
    $this->assertEquals('SqlParallel', $qActive->getSpec('type'));
    $this->assertEquals('task', $qActive->getSpec('runner'));
    $this->assertEquals('delete', $qActive->getSpec('error'));
    $this->assertDBQuery('active', "SELECT status FROM civicrm_queue WHERE name = 'test/my-active'");
    $this->assertDBQuery(0, "SELECT is_template FROM civicrm_queue WHERE name = 'test/my-active'");

    $qDraft = Civi::queue('test/my-draft', [
      'template' => 'test/template',
      'status' => 'draft',
    ]);
    $this->assertEquals('test/my-draft', $qDraft->getName());
    $this->assertEquals('SqlParallel', $qDraft->getSpec('type'));
    $this->assertEquals('task', $qDraft->getSpec('runner'));
    $this->assertEquals('delete', $qDraft->getSpec('error'));
    $this->assertDBQuery('draft', "SELECT status FROM civicrm_queue WHERE name = 'test/my-draft'");
    $this->assertDBQuery(0, "SELECT is_template FROM civicrm_queue WHERE name = 'test/my-active'");
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

    $this->assertQueueStats(3, 3, 0, $this->queue);

    $item = $this->queue->claimItem();
    $this->assertQueueStats(3, 2, 1, $this->queue);
    $this->assertEquals('a', $item->data['test-key']);
    $this->assertEquals(1, $item->run_count);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(2, 2, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertQueueStats(2, 1, 1, $this->queue);
    $this->assertEquals('b', $item->data['test-key']);
    $this->assertEquals(1, $item->run_count);
    $this->queue->deleteItem($item);
    $this->assertQueueStats(1, 1, 0, $this->queue);

    $this->queue->createItem([
      'test-key' => 'd',
    ]);

    $this->assertQueueStats(2, 2, 0, $this->queue);

    $item = $this->queue->claimItem();
    $this->assertQueueStats(2, 1, 1, $this->queue);
    $this->assertEquals('c', $item->data['test-key']);
    $this->assertEquals(1, $item->run_count);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(1, 1, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('d', $item->data['test-key']);
    $this->assertEquals(1, $item->run_count);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(0, 0, 0, $this->queue);
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
    $this->assertEquals(1, $item->run_count);
    $this->assertQueueStats(1, 0, 1, $this->queue);
    $this->queue->releaseItem($item);

    $this->assertQueueStats(1, 1, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->assertEquals(2, $item->run_count);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(0, 0, 0, $this->queue);
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
    $this->assertQueueStats(1, 1, 0, $this->queue);

    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->assertEquals(1, $item->run_count);
    $this->assertQueueStats(1, 0, 1, $this->queue);
    // forget to release

    // haven't reach expiration yet
    CRM_Utils_Time::setTime('2012-04-01 1:59:00');
    $item2 = $this->queue->claimItem();
    $this->assertEquals(FALSE, $item2);

    // pass expiration mark
    CRM_Utils_Time::setTime('2012-04-01 2:00:03');
    $item3 = $this->queue->claimItem();
    $this->assertEquals('a', $item3->data['test-key']);
    $this->assertEquals(2, $item3->run_count);
    $this->assertQueueStats(1, 0, 1, $this->queue);
    $this->queue->deleteItem($item3);

    $this->assertQueueStats(0, 0, 0, $this->queue);
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
    $this->assertEquals(1, $item->run_count);
    $this->assertQueueStats(1, 0, 1, $this->queue);
    // forget to release

    // haven't reached expiration yet, so claimItem fails
    CRM_Utils_Time::setTime('2012-04-01 1:59:00');
    $item2 = $this->queue->claimItem();
    $this->assertEquals(FALSE, $item2);

    // but stealItem works
    $item3 = $this->queue->stealItem();
    $this->assertEquals('a', $item3->data['test-key']);
    $this->assertEquals(2, $item3->run_count);
    $this->assertQueueStats(1, 0, 1, $this->queue);
    $this->queue->deleteItem($item3);

    $this->assertQueueStats(0, 0, 0, $this->queue);
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

  public function testFacadeAutoCreate(): void {
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
      $this->assertMatchesRegularExpression(';Missing field "type";', $e->getMessage());
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
    $this->assertQueueStats(2, 2, 0, $this->queue);
    unset($this->queue);

    $queue2 = $this->queueService->create(
      $queueSpec + ['reset' => TRUE]
    );
    $this->assertQueueStats(0, 0, 0, $queue2);
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
    $this->assertQueueStats(2, 2, 0, $this->queue);
    unset($this->queue);

    $queue2 = $this->queueService->create($queueSpec);
    $this->assertQueueStats(2, 2, 0, $queue2);

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
    $this->assertQueueStats(2, 2, 0, $this->queue);
    unset($this->queue);

    $queue2 = $this->queueService->create($queueSpec);
    $this->assertQueueStats(2, 2, 0, $queue2);

    $item = $queue2->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $queue2->releaseItem($item);
  }

  /**
   * Grab items from a queue in batches.
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testBatchClaim($queueSpec) {
    $this->queue = $this->queueService->create($queueSpec);
    $this->assertTrue($this->queue instanceof CRM_Queue_Queue);
    if (!($this->queue instanceof CRM_Queue_Queue_BatchQueueInterface)) {
      $this->markTestSkipped("Queue class does not support batch interface: " . get_class($this->queue));
    }

    for ($i = 0; $i < 9; $i++) {
      $this->queue->createItem('x' . $i);
    }
    $this->assertQueueStats(9, 9, 0, $this->queue);

    // We expect this driver to be fully compliant with batching.
    $claimsA = $this->queue->claimItems(3);
    $claimsB = $this->queue->claimItems(3);
    $this->assertQueueStats(9, 3, 6, $this->queue);

    $this->assertEquals(['x0', 'x1', 'x2'], CRM_Utils_Array::collect('data', $claimsA));
    $this->assertEquals(['x3', 'x4', 'x5'], CRM_Utils_Array::collect('data', $claimsB));

    $this->queue->deleteItems([$claimsA[0], $claimsA[1]]); /* x0, x1 */
    $this->queue->releaseItems([$claimsA[2]]); /* x2: will retry with next claimItems() */
    $this->queue->deleteItems([$claimsB[0], $claimsB[1]]); /* x3, x4 */
    /* claimsB[2]: x5: Oops, we're gonna take some time to finish this one. */
    $this->assertQueueStats(5, 4, 1, $this->queue);

    $claimsC = $this->queue->claimItems(3);
    $this->assertEquals(['x2', 'x6', 'x7'], CRM_Utils_Array::collect('data', $claimsC));
    $this->queue->deleteItem($claimsC[0]); /* x2 */
    $this->queue->releaseItem($claimsC[1]); /* x6: will retry with next claimItems() */
    $this->queue->deleteItem($claimsC[2]); /* x7 */
    $this->assertQueueStats(3, 2, 1, $this->queue);

    $claimsD = $this->queue->claimItems(3);
    $this->assertEquals(['x6', 'x8'], CRM_Utils_Array::collect('data', $claimsD));
    $this->queue->deleteItem($claimsD[0]); /* x6 */
    $this->queue->deleteItem($claimsD[1]); /* x8 */
    $this->assertQueueStats(1, 0, 1, $this->queue);

    // claimsB took a while to wrap-up. But it finally did!
    $this->queue->deleteItem($claimsB[2]); /* x5 */
    $this->assertQueueStats(0, 0, 0, $this->queue);
  }

  public function testSetStatus(): void {
    $fired = ['status-changes' => []];
    \Civi::dispatcher()->addListener('hook_civicrm_queueStatus', function ($e) use (&$fired) {
      $fired[$e->queue->getName()][] = $e->status;
    });

    $q = Civi::queue('status-changes', [
      'type' => 'Sql',
    ]);
    $this->assertEquals([], $fired['status-changes']);

    $q->setStatus('draft');
    $this->assertEquals(['draft'], $fired['status-changes']);

    $q->setStatus('draft');
    $q->setStatus('draft');
    $q->setStatus('draft');
    $this->assertEquals(['draft'], $fired['status-changes']);

    $q->setStatus('active');
    $this->assertEquals(['draft', 'active'], $fired['status-changes']);

    $q->setStatus('active');
    $q->setStatus('active');
    $q->setStatus('active');
    $this->assertEquals(['draft', 'active'], $fired['status-changes']);

    $q->setStatus('completed');
    $this->assertEquals(['draft', 'active', 'completed'], $fired['status-changes']);
  }

}
