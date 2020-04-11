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

namespace Civi\Core\Transaction;

/**
 *
 * @package Civi
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Manager {

  private static $singleton = NULL;

  /**
   * @var \CRM_Core_DAO
   */
  private $dao;

  /**
   * Stack of SQL transactions/savepoints.
   *
   * @var \Civi\Core\Transaction\Frame[]
   */
  private $frames = [];

  /**
   * @var int
   */
  private $savePointCount = 0;

  /**
   * @param bool $fresh
   * @return Manager
   */
  public static function singleton($fresh = FALSE) {
    if (NULL === self::$singleton || $fresh) {
      self::$singleton = new Manager(new \CRM_Core_DAO());
    }
    return self::$singleton;
  }

  /**
   * @param \CRM_Core_DAO $dao
   *   Handle for the DB connection that will execute transaction statements.
   *   (all we really care about is the query() function)
   */
  public function __construct($dao) {
    $this->dao = $dao;
  }

  /**
   * Increment the transaction count / add a new transaction level
   *
   * @param bool $nest
   *   Determines what to do if there's currently an active transaction:.
   *   - If true, then make a new nested transaction ("SAVEPOINT")
   *   - If false, then attach to the existing transaction
   */
  public function inc($nest = FALSE) {
    if (!isset($this->frames[0])) {
      $frame = $this->createBaseFrame();
      array_unshift($this->frames, $frame);
      $frame->inc();
      $frame->begin();
    }
    elseif ($nest) {
      $frame = $this->createSavePoint();
      array_unshift($this->frames, $frame);
      $frame->inc();
      $frame->begin();
    }
    else {
      $this->frames[0]->inc();
    }
  }

  /**
   * Decrement the transaction count / close out a transaction level
   *
   * @throws \CRM_Core_Exception
   */
  public function dec() {
    if (!isset($this->frames[0]) || $this->frames[0]->isEmpty()) {
      throw new \CRM_Core_Exception('Transaction integrity error: Expected to find active frame');
    }

    $this->frames[0]->dec();

    if ($this->frames[0]->isEmpty()) {
      // Callbacks may cause additional work (such as new transactions),
      // and it would be confusing if the old frame was still active.
      // De-register it before calling finish().
      $oldFrame = array_shift($this->frames);
      $oldFrame->finish();
    }
  }

  /**
   * Force an immediate rollback, regardless of how many
   * transaction or frame objects exist.
   *
   * This is only appropriate when it is _certain_ that the
   * callstack will not wind-down normally -- e.g. before
   * a call to exit().
   */
  public function forceRollback() {
    // we take the long-way-round (rolling back each frame) so that the
    // internal state of each frame is consistent with its outcome

    $oldFrames = $this->frames;
    $this->frames = [];
    foreach ($oldFrames as $oldFrame) {
      $oldFrame->forceRollback();
    }
  }

  /**
   * Get the (innermost) SQL transaction.
   *
   * @return \Civi\Core\Transaction\Frame
   */
  public function getFrame() {
    return $this->frames[0] ?? NULL;
  }

  /**
   * Get the (outermost) SQL transaction (i.e. the one
   * demarcated by BEGIN/COMMIT/ROLLBACK)
   *
   * @return \Civi\Core\Transaction\Frame
   */
  public function getBaseFrame() {
    if (empty($this->frames)) {
      return NULL;
    }
    return $this->frames[count($this->frames) - 1];
  }

  /**
   * @return \Civi\Core\Transaction\Frame
   */
  protected function createBaseFrame() {
    return new Frame($this->dao, 'BEGIN', 'COMMIT', 'ROLLBACK');
  }

  /**
   * @return \Civi\Core\Transaction\Frame
   */
  protected function createSavePoint() {
    $spId = $this->savePointCount++;
    return new Frame($this->dao, "SAVEPOINT civi_{$spId}", NULL, "ROLLBACK TO SAVEPOINT civi_{$spId}");
  }

}
