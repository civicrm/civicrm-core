<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class CRM_Utils_AutoClean
 *
 * Automatically cleanup state when the object handle is released.
 * This is useful for unordered cleanup when a function has many
 * different exit scenarios (eg multiple returns, exceptions).
 */
class CRM_Utils_AutoClean {
  protected $callback;
  protected $args;

  /**
   * Call a cleanup function when the current context shuts down.
   *
   * @code
   * function doStuff() {
   *   $ac = CRM_Utils_AutoClean::with(function(){
   *     MyCleanup::doIt();
   *   });
   *   ...
   * }
   * @endcode
   *
   * @param mixed $callback
   * @return CRM_Utils_AutoClean
   */
  public static function with($callback) {
    $ac = new CRM_Utils_AutoClean();
    $ac->args = func_get_args();
    $ac->callback = array_shift($ac->args);
    return $ac;
  }

  /**
   * Temporarily swap values using callback functions, and cleanup
   * when the current context shuts down.
   *
   * @code
   * function doStuff() {
   *   $ac = CRM_Utils_AutoClean::swap('My::get', 'My::set', 'tmpValue');
   *   ...
   * }
   * @endcode
   *
   * @param mixed $getter
   *   Function to lookup current value.
   * @param mixed $setter
   *   Function to set new value.
   * @param mixed $tmpValue
   *   The value to temporarily use.
   * @return CRM_Utils_AutoClean
   * @see \Civi\Core\Resolver
   */
  public static function swap($getter, $setter, $tmpValue) {
    $resolver = \Civi\Core\Resolver::singleton();

    $origValue = $resolver->call($getter, []);

    $ac = new CRM_Utils_AutoClean();
    $ac->callback = $setter;
    $ac->args = [$origValue];

    $resolver->call($setter, [$tmpValue]);

    return $ac;
  }

  public function __destruct() {
    \Civi\Core\Resolver::singleton()->call($this->callback, $this->args);
  }

}
