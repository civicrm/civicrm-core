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
namespace Civi\Core\Lock;

/**
 * Lock interface.
 */
interface LockInterface {

  /**
   * Acquire lock.
   *
   * @param int|null $timeout
   *   The number of seconds to wait to get the lock.
   *   For a default value, use NULL.
   * @return bool
   */
  public function acquire($timeout = NULL);

  /**
   * @return bool|null|string
   *   Trueish/falsish.
   */
  public function release();

  /**
   * @return bool|null|string
   *   Trueish/falsish.
   * @deprecated
   *   Not supported by some locking strategies. If you need to poll, better
   *   to use acquire(0).
   */
  public function isFree();

  /**
   * @return bool
   */
  public function isAcquired();

}
