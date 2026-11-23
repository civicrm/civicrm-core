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
  public static function getQueueSpecs() {
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
    // A table lock left by a failed claim would make the cleanup below wait.
    CRM_Core_DAO::executeQuery('UNLOCK TABLES');
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
    $this->assertSame('a', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(2, 2, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertSame('b', $item->data['test-key']);
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
    $this->assertSame('start', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(3, 3, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertSame('c', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(2, 2, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertSame('d', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(1, 1, 0, $this->queue);
    $item = $this->queue->claimItem();
    $this->assertSame('end', $item->data['test-key']);
    $this->queue->deleteItem($item);

    $this->assertQueueStats(0, 0, 0, $this->queue);
  }

  /**
   * claimItem()'s guarded UPDATE (attemptClaim()) is what prevents two
   * processes from claiming the same item without table-level locking.
   * Simulate the race by claiming the item once, then try attemptClaim()
   * again using the state a racer would have read before that claim:
   * once with a stale run_count and once with a release_time that's since
   * moved into the future (e.g. releaseItem() set a later retry,
   * which doesn't touch run_count).
   */
  public function testAttemptClaimRejectsStaleClaims() {
    $queue = $this->makeInstrumentedQueue(['type' => 'Sql', 'name' => 'test-queue', 'retry_interval' => 100]);
    $queue->createItem(['test-key' => 'a']);

    $id = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_queue_item WHERE queue_name = %1', [
      1 => ['test-queue', 'String'],
    ]);
    $staleDao = (object) ['id' => $id, 'run_count' => 0];

    // Someone else claims it first.
    $item = $queue->claimItem();
    $this->assertSame($id, (int) $item->id);
    $this->assertSame(1, $item->run_count);

    // A racer holding the pre-claim run_count must be rejected.
    $won = $queue->attemptClaim($staleDao, 60);
    $this->assertFalse($won, 'A claim based on stale run_count should be rejected.');
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT run_count FROM civicrm_queue_item WHERE id = %1', [
      1 => [$id, 'Integer'],
    ]), 'The winning claim should be untouched.');

    // The claimant releases it for a later retry: run_count stays the same,
    // but release_time moves into the future.
    $queue->releaseItem($item);

    // A racer whose run_count still matches the current value must still be
    // rejected, since the item is now on a retry delay.
    $stillCurrentDao = (object) ['id' => $id, 'run_count' => $item->run_count];
    $won = $queue->attemptClaim($stillCurrentDao, 60);
    $this->assertFalse($won, 'A claim must be rejected once release_time has moved into the future, even if run_count still matches.');
  }

  /**
   * @param array $queueSpec
   * @return CRM_Queue_Queue_Sql
   *   A queue with attemptClaim() made public.
   */
  private function makeInstrumentedQueue(array $queueSpec = ['type' => 'Sql', 'name' => 'test-queue']) {
    return new class($queueSpec) extends CRM_Queue_Queue_Sql {

      public function attemptClaim($dao, int $lease_time): bool {
        return parent::attemptClaim($dao, $lease_time);
      }

    };
  }

  /**
   * If claimItem() keeps losing the race to another process, it must retry
   * with a fresh SELECT and eventually succeed.
   */
  public function testClaimItemRetriesUntilItWins() {
    $queue = $this->makeQueueThatLosesRaceNTimes(3);
    $queue->createItem(['test-key' => 'a']);

    $item = $queue->claimItem();

    $this->assertSame('a', $item->data['test-key']);
    $this->assertSame(4, $queue->attemptCount, 'Should retry until it wins: 3 losses + 1 success.');
  }

  /**
   * A poll on a leased head returns NULL at once, without claim attempts.
   */
  public function testClaimItemDoesNotRetryOnLeasedHead() {
    $queue = $this->makeQueueThatLosesRaceNTimes(0);
    $queue->createItem(['test-key' => 'a']);
    $queue->createItem(['test-key' => 'b']);
    $this->assertSame('a', $queue->claimItem()->data['test-key']);

    $this->assertNull($queue->claimItem());
    $this->assertSame(1, $queue->attemptCount, 'A leased head should not be claimed again.');
  }

  /**
   * A claim that fails must not leave a table lock on the connection: later
   * queries on other tables would fail, and other connections would wait.
   */
  public function testFailedClaimLeavesNoTableLock() {
    $queue = new CRM_Queue_Queue_Sql(['type' => 'Sql', 'name' => 'test-queue']);
    $queue->createItem(['test-key' => 'a']);
    // 'a' cannot be unserialized, so the claim fails.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue_item SET data = %1 WHERE queue_name = %2', [
      1 => ['O:7:"Closure":0:{}', 'String'],
      2 => ['test-queue', 'String'],
    ]);

    try {
      $queue->claimItem();
      $this->fail('claimItem() should pass on the error');
    }
    catch (Exception $e) {
      $this->assertStringContainsString('Closure', $e->getMessage());
    }

    $error = NULL;
    try {
      CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_contact WHERE id = 0');
    }
    catch (Exception $e) {
      $error = $e->getMessage();
    }
    $this->assertNull($error, 'The failed claim left a table lock on the connection.');
  }

  /**
   * Background workers poll claimItem() continuously, so it must not need a
   * temporary table, which MariaDB writes to disk because of the data column.
   */
  public function testClaimItemNeedsNoTemporaryTable() {
    $queue = new CRM_Queue_Queue_Sql(['type' => 'Sql', 'name' => 'test-queue']);
    $queue->createItem(['test-key' => 'a']);
    $queue->createItem(['test-key' => 'b']);

    // SHOW STATUS may count a temporary table of its own, so measure that first.
    $start = $this->countTemporaryTables();
    $overhead = $this->countTemporaryTables() - $start;
    $before = $this->countTemporaryTables();
    $item = $queue->claimItem();
    $after = $this->countTemporaryTables();

    $this->assertSame('a', $item->data['test-key']);
    $this->assertSame($overhead, $after - $before, 'claimItem() should not create a temporary table');
  }

  /**
   * @return int
   *   Number of temporary tables created so far on this database connection.
   */
  private function countTemporaryTables(): int {
    $dao = CRM_Core_DAO::executeQuery("SHOW SESSION STATUS LIKE 'Created_tmp_tables'");
    $dao->fetch();
    return (int) $dao->Value;
  }

  /**
   * @param int $racesRemaining
   * @return CRM_Queue_Queue_Sql
   *   A queue whose candidate loses its race the first $racesRemaining
   *   times attemptClaim() is called for it, then wins normally.
   */
  private function makeQueueThatLosesRaceNTimes(int $racesRemaining) {
    $queue = new class(['type' => 'Sql', 'name' => 'test-queue']) extends CRM_Queue_Queue_Sql {
      use CRM_Queue_Queue_RacesCandidateTrait;
    };
    $queue->racesRemaining = $racesRemaining;
    return $queue;
  }

}
