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
 * A "frame" is a layer in a series of nested transactions. Generally,
 * the outermost frame is a normal SQL transaction (BEGIN/ROLLBACK/COMMIT)
 * and any nested frames are SQL savepoints (SAVEPOINT foo/ROLLBACK TO SAVEPOINT).
 *
 * @package Civi
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Frame {

  const F_NEW = 0, F_ACTIVE = 1, F_DONE = 2, F_FORCED = 3;

  /**
   * @var \CRM_Core_DAO
   */
  private $dao;

  /**
   * The statement used to start this transaction - e.g. "BEGIN" or "SAVEPOINT foo"
   *
   * @var string|null
   */
  private $beginStmt;

  /**
   * The statement used to commit this transaction - e.g. "COMMIT"
   *
   * @var string|null
   */
  private $commitStmt;

  /**
   * The statement used to rollback this transaction - e.g. "ROLLBACK" or "ROLLBACK TO SAVEPOINT foo"
   *
   * @var string|null
   */
  private $rollbackStmt;

  /**
   * @var int
   */
  private $refCount = 0;
  private $callbacks;
  private $doCommit = TRUE;

  /**
   * @var int
   */
  private $state = self::F_NEW;

  /**
   * @param \CRM_Core_DAO $dao
   * @param string|null $beginStmt e.g. "BEGIN" or "SAVEPOINT foo"
   * @param string|null $commitStmt e.g. "COMMIT"
   * @param string|null $rollbackStmt e.g. "ROLLBACK" or "ROLLBACK TO SAVEPOINT foo"
   */
  public function __construct($dao, $beginStmt, $commitStmt, $rollbackStmt) {
    $this->dao = $dao;
    $this->beginStmt = $beginStmt;
    $this->commitStmt = $commitStmt;
    $this->rollbackStmt = $rollbackStmt;

    $this->callbacks = [
      \CRM_Core_Transaction::PHASE_PRE_COMMIT => [],
      \CRM_Core_Transaction::PHASE_POST_COMMIT => [],
      \CRM_Core_Transaction::PHASE_PRE_ROLLBACK => [],
      \CRM_Core_Transaction::PHASE_POST_ROLLBACK => [],
    ];
  }

  public function inc() {
    $this->refCount++;
  }

  public function dec() {
    $this->refCount--;
  }

  /**
   * @return bool
   */
  public function isEmpty() {
    return ($this->refCount == 0);
  }

  /**
   * @return bool
   */
  public function isRollbackOnly() {
    return !$this->doCommit;
  }

  public function setRollbackOnly() {
    $this->doCommit = FALSE;
  }

  /**
   * Begin frame processing.
   *
   * @throws \CRM_Core_Exception
   */
  public function begin() {
    if ($this->state !== self::F_NEW) {
      throw new \CRM_Core_Exception('State is not F_NEW');
    };

    $this->state = self::F_ACTIVE;
    if ($this->beginStmt) {
      $this->dao->query($this->beginStmt);
    }
  }

  /**
   * Finish frame processing.
   *
   * @param int $newState
   *
   * @throws \CRM_Core_Exception
   */
  public function finish($newState = self::F_DONE) {
    if ($this->state == self::F_FORCED) {
      return;
    }
    if ($this->state !== self::F_ACTIVE) {
      throw new \CRM_Core_Exception('State is not F_ACTIVE');
    };

    $this->state = $newState;

    if ($this->doCommit) {
      $this->invokeCallbacks(\CRM_Core_Transaction::PHASE_PRE_COMMIT);
      if ($this->commitStmt) {
        $this->dao->query($this->commitStmt);
      }
      $this->invokeCallbacks(\CRM_Core_Transaction::PHASE_POST_COMMIT);
    }
    else {
      $this->invokeCallbacks(\CRM_Core_Transaction::PHASE_PRE_ROLLBACK);
      if ($this->rollbackStmt) {
        $this->dao->query($this->rollbackStmt);
      }
      $this->invokeCallbacks(\CRM_Core_Transaction::PHASE_POST_ROLLBACK);
    }
  }

  public function forceRollback() {
    $this->setRollbackOnly();
    $this->finish(self::F_FORCED);
  }

  /**
   * Add a transaction callback.
   *
   * Pre-condition: isActive()
   *
   * @param int $phase
   *   A constant; one of: self::PHASE_{PRE,POST}_{COMMIT,ROLLBACK}.
   * @param callable $callback
   *   A PHP callback.
   * @param mixed $params Optional values to pass to callback.
   *          See php manual call_user_func_array for details.
   * @param string|int|null $id
   */
  public function addCallback($phase, $callback, $params = NULL, $id = NULL) {
    if ($id) {
      $this->callbacks[$phase][$id] = [
        'callback' => $callback,
        'parameters' => (is_array($params) ? $params : [$params]),
      ];
    }
    else {
      $this->callbacks[$phase][] = [
        'callback' => $callback,
        'parameters' => (is_array($params) ? $params : [$params]),
      ];
    }
  }

  /**
   * @param int $phase
   */
  public function invokeCallbacks($phase) {
    if (is_array($this->callbacks[$phase])) {
      foreach ($this->callbacks[$phase] as $cb) {
        call_user_func_array($cb['callback'], $cb['parameters']);
      }
    }
  }

}
