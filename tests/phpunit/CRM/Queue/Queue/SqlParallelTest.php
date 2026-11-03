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
 * @group headless
 * @group queue
 */
class CRM_Queue_Queue_SqlParallelTest extends CiviUnitTestCase {

  use \Civi\Test\QueueTestTrait;

  public function tearDown(): void {
    CRM_Utils_Time::resetTime();

    $tablesToTruncate = ['civicrm_queue_item'];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  /**
   * claimItems() claims each candidate via attemptClaim()'s guarded UPDATE.
   * Simulate a race by selecting a batch of 3 then having another process
   * claim the middle item in between the initial SELECT and attemptClaim()
   * being called for it.
   */
  public function testClaimItemsExcludesCandidateLostToRace() {
    $setupQueue = new CRM_Queue_Queue_SqlParallel(['type' => 'SqlParallel', 'name' => 'test-queue']);
    $setupQueue->createItem(['test-key' => 'a']);
    $setupQueue->createItem(['test-key' => 'b']);
    $setupQueue->createItem(['test-key' => 'c']);

    $ids = array_column(CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_queue_item WHERE queue_name = %1 ORDER BY weight, id', [
      1 => ['test-queue', 'String'],
    ])->fetchAll(), 'id');
    $this->assertCount(3, $ids);

    $queue = $this->makeQueueThatRacesCandidate($ids[1]);

    $result = $queue->claimItems(3);

    $this->assertEquals([$ids[0], $ids[2]], array_column($result, 'id'), 'The raced item should be excluded; the others should keep their original order.');
  }

  /**
   * @param int $raceItemId
   * @return CRM_Queue_Queue_SqlParallel
   *   A queue that simulates another process claiming item $raceItemId in
   *   between claimItems()'s SELECT and attemptClaim() being called for it.
   */
  private function makeQueueThatRacesCandidate(int $raceItemId) {
    $queue = new class(['type' => 'SqlParallel', 'name' => 'test-queue']) extends CRM_Queue_Queue_SqlParallel {
      public $raceItemId;

      protected function attemptClaim($dao, int $lease_time): bool {
        if ((int) $dao->id === $this->raceItemId) {
          CRM_Core_DAO::executeQuery('UPDATE civicrm_queue_item SET release_time = from_unixtime(unix_timestamp() + 60), run_count = run_count + 1 WHERE id = %1', [
            1 => [$this->raceItemId, 'Integer'],
          ]);
        }
        return parent::attemptClaim($dao, $lease_time);
      }

    };
    $queue->raceItemId = $raceItemId;
    return $queue;
  }

  /**
   * If every candidate in a batch loses its race, claimItems() must retry
   * rather than reporting an empty result.
   *
   * Simulate a candidate that gets claimed-then-released by another process
   * a few times in a row before finally being claimable.
   */
  public function testClaimItemsRetriesWhenAllCandidatesLostRace() {
    $queue = $this->makeQueueThatLosesRaceNTimes(3);
    $queue->createItem(['test-key' => 'a']);
    $queue->createItem(['test-key' => 'b']);

    $result = $queue->claimItems(1);

    $this->assertCount(1, $result);
    $this->assertEquals('a', $result[0]->data['test-key']);
    $this->assertEquals(4, $queue->attemptCount, 'Should retry until it wins: 3 losses + 1 success.');
  }

  /**
   * @param int $racesRemaining
   * @return CRM_Queue_Queue_SqlParallel
   *   A queue whose candidate loses its race the first $racesRemaining
   *   times attemptClaim() is called for it, then succeeds in claiming.
   */
  private function makeQueueThatLosesRaceNTimes(int $racesRemaining) {
    $queue = new class(['type' => 'SqlParallel', 'name' => 'test-queue']) extends CRM_Queue_Queue_SqlParallel {
      use CRM_Queue_Queue_RacesCandidateTrait;
    };
    $queue->racesRemaining = $racesRemaining;
    return $queue;
  }

}
