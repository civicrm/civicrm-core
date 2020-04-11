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

use Civi\Core\Resolver;

/**
 * Class LockManager
 * @package Civi\Core\Lock
 *
 * The lock-manager allows one to define the lock policy -- i.e. given a
 * specific lock, how does one acquire the lock?
 */
class LockManager {

  private $rules = [];

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
      $lock = call_user_func_array($factory, [$name]);
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
   * @param int|null $timeout
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
    $this->rules[] = [
      'pattern' => $pattern,
      'factory' => $factory,
    ];
    return $this;
  }

}
