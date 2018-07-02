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
namespace Civi\Core\Lock;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class NullLock implements LockInterface {

  private $hasLock = FALSE;

  /**
   * Create lock.
   *
   * @param string $name
   *
   * @return static
   */
  public static function create($name) {
    return new static();
  }

  /**
   * Acquire lock.
   *
   * @param int|NULL $timeout
   *   The number of seconds to wait to get the lock.
   *   For a default value, use NULL.
   *
   * @return bool
   */
  public function acquire($timeout = NULL) {
    $this->hasLock = TRUE;
    return TRUE;
  }

  /**
   * Release lock.
   *
   * @return bool|null|string
   *   Trueish/falsish.
   */
  public function release() {
    $this->hasLock = FALSE;
    return TRUE;
  }

  /**
   * @return bool|null|string
   *   Trueish/falsish.
   * @deprecated
   *   Not supported by some locking strategies. If you need to poll, better
   *   to use acquire(0).
   */
  public function isFree() {
    return !$this->hasLock;
  }

  /**
   * @return bool
   */
  public function isAcquired() {
    return $this->hasLock;
  }

}
