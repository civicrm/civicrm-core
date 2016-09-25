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
namespace Civi\Core\Lock;

use Civi\Core\Resolver;

/**
 * Class LockManager
 * @package Civi\Core\Lock
 *
 * The lock-manager allows one to define the lock policy -- i.e. given a
 * specific lock, how does one acquire the lock?
 */
class LockManager {

  private $rules = array();

  /**
   * @param string $name
   *   Symbolic name for the lock. Names generally look like
   *   "worker.mailing.EmailProcessor" ("{category}.{component}.{AdhocName}").
   *
   *   Categories: worker|data|cache|...
   *   Component: core|mailing|member|contribute|...
   * @return LockInterface
   * @throws \CRM_Core_Exception
   */
  public function create($name) {
    $factory = $this->getFactory($name);
    if ($factory) {
      /** @var LockInterface $lock */
      $lock = call_user_func_array($factory, array($name));
      return $lock;
    }
    else {
      throw new \CRM_Core_Exception("Lock \"$name\" does not match any rules. Use register() to add more rules.");
    }
  }

  /**
   * Create and attempt to acquire a lock.
   *
   * Note: Be sure to check $lock->isAcquired() to determine whether
   * acquisition was successful.
   *
   * @param string $name
   *   Symbolic name for the lock. Names generally look like
   *   "worker.mailing.EmailProcessor" ("{category}.{component}.{AdhocName}").
   *
   *   Categories: worker|data|cache|...
   *   Component: core|mailing|member|contribute|...
   * @param int|NULL $timeout
   *   The number of seconds to wait to get the lock.
   *   For a default value, use NULL.
   * @return LockInterface
   * @throws \CRM_Core_Exception
   */
  public function acquire($name, $timeout = NULL) {
    $lock = $this->create($name);
    $lock->acquire($timeout);
    return $lock;
  }

  /**
   * @param string $name
   *   Symbolic name for the lock.
   * @return callable|NULL
   */
  public function getFactory($name) {
    foreach ($this->rules as $rule) {
      if (preg_match($rule['pattern'], $name)) {
        return Resolver::singleton()->get($rule['factory']);
      }
    }
    return NULL;
  }

  /**
   * Register the lock-factory to use for specific lock-names.
   *
   * @param string $pattern
   *   A regex to match against the lock name.
   * @param string|array $factory
   *   A callback. The callback should accept a $name parameter.
   *   Callbacks will be located using the resolver.
   * @return LockManager
   * @see Resolver
   */
  public function register($pattern, $factory) {
    $this->rules[] = array(
      'pattern' => $pattern,
      'factory' => $factory,
    );
    return $this;
  }

}
