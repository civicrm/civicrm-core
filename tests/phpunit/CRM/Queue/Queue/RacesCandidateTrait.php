<?php

/**
 * Test instrumentation for CRM_Queue_Queue_Sql / CRM_Queue_Queue_SqlParallel.
 *
 * Mix into an anonymous subclass of either queue to simulate a candidate
 * that gets claimed-then-released by another process a number of
 * times before finally being successfully claimed.
 */
trait CRM_Queue_Queue_RacesCandidateTrait {

  /**
   * @var int
   */
  public $racesRemaining = 0;

  /**
   * @var int
   */
  public $attemptCount = 0;

  protected function attemptClaim($dao, int $lease_time): bool {
    $this->attemptCount++;
    if ($this->racesRemaining > 0) {
      $this->racesRemaining--;
      // run_count advances (so the guarded UPDATE will fail),
      // but the item is available again on the next SELECT.
      CRM_Core_DAO::executeQuery('UPDATE civicrm_queue_item SET run_count = run_count + 1 WHERE id = %1', [
        1 => [$dao->id, 'Integer'],
      ]);
    }
    return parent::attemptClaim($dao, $lease_time);
  }

}
