<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * @copyright David Strauss <david@fourkitchens.com> (c) 2007
 * $Id$
 *
 * This file has its origins in Donald Lobo's conversation with David
 * Strauss over IRC and the CRM_Core_DAO::transaction() function.
 *
 * David went on and abstracted this into a class which can be used in PHP 5
 * (since destructors are called automagically at the end of the script).
 * Lobo modified the code and used CiviCRM coding standards. David's
 * PressFlow Transaction module is available at
 * http://drupal.org/project/pressflow_transaction
 */
class CRM_Core_Transaction {

  /**
   * These constants represent phases at which callbacks can be invoked
   */
  CONST PHASE_PRE_COMMIT = 1;
  CONST PHASE_POST_COMMIT = 2;
  CONST PHASE_PRE_ROLLBACK = 4;
  CONST PHASE_POST_ROLLBACK = 8;

  /**
   * Keep track of the number of opens and close
   *
   * @var int
   */
  private static $_count = 0;

  /**
   * Keep track if we need to commit or rollback
   *
   * @var boolean
   */
  private static $_doCommit = TRUE;

  /**
   * hold a dao singleton for query operations
   *
   * @var object
   */
  private static $_dao = NULL;

  /**
   * Array of callbacks to invoke when the transaction commits or rolls back.
   * Array keys are phase constants.
   * Array values are arrays of callbacks.
   */
  private static $_callbacks = NULL;

  /**
   * Whether commit() has been called on this instance
   * of CRM_Core_Transaction
   */
  private $_pseudoCommitted = FALSE;
  function __construct() {
    if (!self::$_dao) {
      self::$_dao = new CRM_Core_DAO();
    }

    if (self::$_count == 0) {
      self::$_dao->query('BEGIN');
      self::$_callbacks = array(
        self::PHASE_PRE_COMMIT => array(),
        self::PHASE_POST_COMMIT => array(),
        self::PHASE_PRE_ROLLBACK => array(),
        self::PHASE_POST_ROLLBACK => array(),
      );
    }

    self::$_count++;
  }

  function __destruct() {
    $this->commit();
  }

  function commit() {
    if (self::$_count > 0 && !$this->_pseudoCommitted) {
      $this->_pseudoCommitted = TRUE;
      self::$_count--;

      if (self::$_count == 0) {

        // It's possible that, say, a POST_COMMIT callback creates another
        // transaction. That transaction will need its own list of callbacks.
        $oldCallbacks = self::$_callbacks;
        self::$_callbacks = NULL;

        if (self::$_doCommit) {
          self::invokeCallbacks(self::PHASE_PRE_COMMIT, $oldCallbacks);
          self::$_dao->query('COMMIT');
          self::invokeCallbacks(self::PHASE_POST_COMMIT, $oldCallbacks);
        }
        else {
          self::invokeCallbacks(self::PHASE_PRE_ROLLBACK, $oldCallbacks);
          self::$_dao->query('ROLLBACK');
          self::invokeCallbacks(self::PHASE_POST_ROLLBACK, $oldCallbacks);
        }
        // this transaction is complete, so reset doCommit flag
        self::$_doCommit = TRUE;
      }
    }
  }

  static public function rollbackIfFalse($flag) {
    if ($flag === FALSE) {
      self::$_doCommit = FALSE;
    }
  }

  public function rollback() {
    self::$_doCommit = FALSE;
  }

  /**
   * Force an immediate rollback, regardless of how many any
   * CRM_Core_Transaction objects are waiting for
   * pseudo-commits.
   *
   * Only rollback if the transaction API has been called.
   *
   * This is only appropriate when it is _certain_ that the
   * callstack will not wind-down normally -- e.g. before
   * a call to exit().
   */
  static public function forceRollbackIfEnabled() {
    if (self::$_count > 0) {
      $oldCallbacks = self::$_callbacks;
      self::$_callbacks = NULL;
      self::invokeCallbacks(self::PHASE_PRE_ROLLBACK, $oldCallbacks);
      self::$_dao->query('ROLLBACK');
      self::invokeCallbacks(self::PHASE_POST_ROLLBACK, $oldCallbacks);
      self::$_count = 0;
      self::$_doCommit = TRUE;
    }
  }

  static public function willCommit() {
    return self::$_doCommit;
  }

  /**
   * Determine whether there is a pending transaction
   */
  static public function isActive() {
    return (self::$_count > 0);
  }

  /**
   * Add a transaction callback
   *
   * Pre-condition: isActive()
   *
   * @param $phase A constant; one of: self::PHASE_{PRE,POST}_{COMMIT,ROLLBACK}
   * @param $callback A PHP callback
   * @param mixed $params Optional values to pass to callback.
   *          See php manual call_user_func_array for details.
   */
  static public function addCallback($phase, $callback, $params = null) {
    self::$_callbacks[$phase][] = array(
      'callback' => $callback,
      'parameters' => (is_array($params) ? $params : array($params))
    );
  }

  static protected function invokeCallbacks($phase, $callbacks) {
    if (is_array($callbacks[$phase])) {
      foreach ($callbacks[$phase] as $cb) {
        call_user_func_array($cb['callback'], $cb['parameters']);
      }
    }
  }
}

