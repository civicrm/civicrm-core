<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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

namespace Civi\Core\Transaction;

/**
 *
 * @package Civi
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class Manager {

  private static $singleton = NULL;

  /**
   * @var \CRM_Core_DAO
   */
  private $dao;

  /**
   * @var array<Frame> stack of SQL transactions/savepoints
   */
  private $frames = array();

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
    $this->frames = array();
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
    return isset($this->frames[0]) ? $this->frames[0] : NULL;
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
