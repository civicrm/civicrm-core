<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * A "frame" is a layer in a series of nested transactions. Generally,
 * the outermost frame is a normal SQL transaction (BEGIN/ROLLBACK/COMMIT)
 * and any nested frames are SQL savepoints (SAVEPOINT foo/ROLLBACK TO SAVEPOINT).
 *
 * @package Civi
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class Frame {

  const F_NEW = 0, F_ACTIVE = 1, F_DONE = 2, F_FORCED = 3;

  /**
   * @var \CRM_Core_DAO
   */
  private $dao;

  /**
   * @var string|null e.g. "BEGIN" or "SAVEPOINT foo"
   */
  private $beginStmt;

  /**
   * @var string|null e.g. "COMMIT"
   */
  private $commitStmt;

  /**
   * @var string|null e.g. "ROLLBACK" or "ROLLBACK TO SAVEPOINT foo"
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

    $this->callbacks = array(
      \CRM_Core_Transaction::PHASE_PRE_COMMIT => array(),
      \CRM_Core_Transaction::PHASE_POST_COMMIT => array(),
      \CRM_Core_Transaction::PHASE_PRE_ROLLBACK => array(),
      \CRM_Core_Transaction::PHASE_POST_ROLLBACK => array(),
    );
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
   * @param mixed $callback
   *   A PHP callback.
   * @param array|NULL $params Optional values to pass to callback.
   *          See php manual call_user_func_array for details.
   * @param null $id
   */
  public function addCallback($phase, $callback, $params = NULL, $id = NULL) {
    if ($id) {
      $this->callbacks[$phase][$id] = array(
        'callback' => $callback,
        'parameters' => (is_array($params) ? $params : array($params)),
      );
    }
    else {
      $this->callbacks[$phase][] = array(
        'callback' => $callback,
        'parameters' => (is_array($params) ? $params : array($params)),
      );
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
