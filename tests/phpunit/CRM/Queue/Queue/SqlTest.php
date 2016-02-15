<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Ensure that the extended interface for SQL-backed queues
 * work. For example, the createItem() interface supports
 * priority-queueing.
 * @group headless
 */
class CRM_Queue_Queue_SqlTest extends CiviUnitTestCase {

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
    return $queueSpecs;
  }

  /**
   * Per-provider tests
   *
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
  public function testPriorities($queueSpec) {
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

    $this->queue->createItem(
      array(
        'test-key' => 'start',
      ),
      array(
        'weight' => -1,
      )
    );
    $this->queue->createItem(
      array(
        'test-key' => 'end',
      ),
      array(
        'weight' => 1,
      )
    );
    $this->queue->createItem(array(
      'test-key' => 'd',
    ));

    $this->assertEquals(4, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('start', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(3, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('c', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(2, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('d', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(1, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertEquals('end', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertEquals(0, $this->queue->numberOfItems());
  }

}
