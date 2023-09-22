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
 * Ensure that the extended interface for SQL-backed queues
 * work. For example, the createItem() interface supports
 * priority-queueing.
 * @group headless
 * @group queue
 */
class CRM_Queue_Queue_SqlTest extends CiviUnitTestCase {

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
    $queueSpecs[] = [
      [
        'type' => 'Sql',
        'name' => 'test-queue',
      ],
    ];
    return $queueSpecs;
  }

  /**
   * Per-provider tests
   *
   */
  public function setUp(): void {
    parent::setUp();
    $this->queueService = CRM_Queue_Service::singleton(TRUE);
  }

  public function tearDown(): void {
    CRM_Utils_Time::resetTime();

    $tablesToTruncate = ['civicrm_queue_item'];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  /**
   * Create a few queue items; alternately enqueue and dequeue various
   *
   * @dataProvider getQueueSpecs
   * @param $queueSpec
   */
  public function testPriorities($queueSpec) {
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

    $this->assertQueueStats(3, 3, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('a', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(2, 2, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('b', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->queue->createItem(
      [
        'test-key' => 'start',
      ],
      [
        'weight' => -1,
      ]
    );
    $this->queue->createItem(
      [
        'test-key' => 'end',
      ],
      [
        'weight' => 1,
      ]
    );
    $this->queue->createItem([
      'test-key' => 'd',
    ]);

    $this->assertQueueStats(4, 4, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('start', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(3, 3, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('c', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(2, 2, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('d', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(1, 1, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertEquals('end', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(0, 0, 0, $this->queue);
  }

}
