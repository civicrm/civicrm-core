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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @copyright David Strauss <david@fourkitchens.com> (c) 2007
 *
 * (Note: This has been considerably rewritten; the interface is preserved
 * for backward compatibility.)
 *
 * Transaction management in Civi is divided among three classes:
 *  - CRM_Core_Transaction: API. This binds to __construct() + __destruct()
 *    and notifies the transaction manager when it's OK to begin/end a transaction.
 *  - Civi\Core\Transaction\Manager: Tracks pending transaction-frames
 *  - Civi\Core\Transaction\Frame: A nestable transaction (e.g. based on BEGIN/COMMIT/ROLLBACK
 *    or SAVEPOINT/ROLLBACK TO SAVEPOINT).
 *
 * Examples:
 *
 * ```
 * // Some business logic using the helper functions
 * function my_business_logic() {
 *   CRM_Core_Transaction::create()->run(function($tx) {
 *     ...do work...
 *    if ($error) throw new Exception();
 *   });
 * }
 *
 * // Some business logic which returns an error-value
 * // and explicitly manages the transaction.
 * function my_business_logic() {
 *   $tx = new CRM_Core_Transaction();
 *   ...do work...
 *   if ($error) {
 *     $tx->rollback();
 *     return error_value;
 *   }
 * }
 *
 * // Some business logic which uses exceptions
 * // and explicitly manages the transaction.
 * function my_business_logic() {
 *   $tx = new CRM_Core_Transaction();
 *   try {
 *     ...do work...
 *   } catch (Exception $ex) {
 *     $tx->rollback()->commit();
 *     throw $ex;
 *   }
 * }
 *
 * ```
 *
 * Note: As of 4.6, the transaction manager supports both reference-counting and nested
 * transactions (SAVEPOINTs). In the past, it only supported reference-counting. The two cases
 * may exhibit different systemic effects with respect to unhandled exceptions.
 */
class CRM_Core_Transaction {

  /**
   * These constants represent phases at which callbacks can be invoked.
   */
  const PHASE_PRE_COMMIT = 1;
  const PHASE_POST_COMMIT = 2;
  const PHASE_PRE_ROLLBACK = 4;
  const PHASE_POST_ROLLBACK = 8;

  /**
   * Whether commit() has been called on this instance
   * of CRM_Core_Transaction
   * @var bool
   */
  private $_pseudoCommitted = FALSE;

  /**
   * Ensure that an SQL transaction is started.
   *
   * This is a thin wrapper around __construct() which allows more fluent coding.
   *
   * @param bool $nest
   *   Determines what to do if there's currently an active transaction:.
   *   - If true, then make a new nested transaction ("SAVEPOINT")
   *   - If false, then attach to the existing transaction
   * @return \CRM_Core_Transaction
   */
  public static function create($nest = FALSE) {
    return new self($nest);
  }

  /**
   * Ensure that an SQL transaction is started.
   *
   * @param bool $nest
   *   Determines what to do if there's currently an active transaction:.
   *   - If true, then make a new nested transaction ("SAVEPOINT")
   *   - If false, then attach to the existing transaction
   */
  public function __construct($nest = FALSE) {
    \Civi\Core\Transaction\Manager::singleton()->inc($nest);
  }

  public function __destruct() {
    $this->commit();
  }

  /**
   * Immediately commit or rollback.
   *
   * (Note: Prior to 4.6, return void)
   *
   * @return \CRM_Core_Exception this
   */
  public function commit() {
    if (!$this->_pseudoCommitted) {
      $this->_pseudoCommitted = TRUE;
      \Civi\Core\Transaction\Manager::singleton()->dec();
    }
    return $this;
  }

  /**
   * @param $flag
   */
  public static function rollbackIfFalse($flag) {
    $frame = \Civi\Core\Transaction\Manager::singleton()->getFrame();
    if ($flag === FALSE && $frame !== NULL) {
      $frame->setRollbackOnly();
    }
  }

  /**
   * Mark the transaction for rollback.
   *
   * (Note: Prior to 4.6, return void)
   * @return \CRM_Core_Transaction
   */
  public function rollback() {
    $frame = \Civi\Core\Transaction\Manager::singleton()->getFrame();
    if ($frame !== NULL) {
      $frame->setRollbackOnly();
    }
    return $this;
  }

  /**
   * Execute a function ($callable) within the scope of a transaction. If
   * $callable encounters an unhandled exception, then rollback the transaction.
   *
   * After calling run(), the CRM_Core_Transaction object is "used up"; do not
   * use it again.
   *
   * @param callable $callable
   *   Should expect one parameter (CRM_Core_Transaction).
   * @return CRM_Core_Transaction
   * @throws Exception
   */
  public function run($callable) {
    try {
      $callable($this);
    }
    catch (Exception $ex) {
      $this->rollback()->commit();
      throw $ex;
    }
    $this->commit();
    return $this;
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
  public static function forceRollbackIfEnabled() {
    if (\Civi\Core\Transaction\Manager::singleton()->getFrame() !== NULL) {
      \Civi\Core\Transaction\Manager::singleton()->forceRollback();
    }
  }

  /**
   * @return bool
   */
  public static function willCommit() {
    $frame = \Civi\Core\Transaction\Manager::singleton()->getFrame();
    return ($frame === NULL) ? TRUE : !$frame->isRollbackOnly();
  }

  /**
   * Determine whether there is a pending transaction.
   */
  public static function isActive() {
    $frame = \Civi\Core\Transaction\Manager::singleton()->getFrame();
    return ($frame !== NULL);
  }

  /**
   * Add a transaction callback.
   *
   * Note: It's conceivable to add callbacks to the main/overall transaction
   * (aka $manager->getBaseFrame()) or to the innermost nested transaction
   * (aka $manager->getFrame()). addCallback() has been used in the past to
   * work-around deadlocks. This may or may not be necessary now -- but it
   * seems more consistent (for b/c purposes) to attach callbacks to the
   * main/overall transaction.
   *
   * Pre-condition: isActive()
   *
   * @param int $phase
   *   A constant; one of: self::PHASE_{PRE,POST}_{COMMIT,ROLLBACK}.
   * @param callable $callback
   *   A PHP callback.
   * @param mixed $params
   *   Optional values to pass to callback.
   *          See php manual call_user_func_array for details.
   * @param string|int|null $id
   */
  public static function addCallback($phase, $callback, $params = NULL, $id = NULL) {
    $frame = \Civi\Core\Transaction\Manager::singleton()->getBaseFrame();
    $frame->addCallback($phase, $callback, $params, $id);
  }

  /**
   * Whenever hook_civicrm_post fires, schedule an equivalent
   * call to hook_civicrm_postCommit.
   *
   * @param \Civi\Core\Event\PostEvent $e
   * @see CRM_Utils_Hook::post
   */
  public static function addPostCommit($e) {
    // Do we want to dedupe post-commit hooks for the same txn? Setting an ID
    // would allow this.
    // $id = $e->entity . chr(0) . $e->action . chr(0) . $e->id;
    $frame = \Civi\Core\Transaction\Manager::singleton()->getBaseFrame();
    if ($frame) {
      $frame->addCallback(self::PHASE_POST_COMMIT, ['CRM_Utils_Hook', 'postCommit'], [$e->action, $e->entity, $e->id, $e->object]);
    }
    else {
      \CRM_Utils_Hook::postCommit($e->action, $e->entity, $e->id, $e->object);
    }
  }

}
