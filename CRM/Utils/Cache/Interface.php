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
 * CRM_Utils_Cache_Interface is a long-standing interface used within CiviCRM
 * for interacting with a cache service. In style and substance, it is extremely
 * similar to PHP-FIG's SimpleCache interface (PSR-16). Consequently, beginning
 * with CiviCRM v5.4, this extends \Psr\SimpleCache\CacheInterface.
 *
 * @see https://www.php-fig.org/psr/psr-16/
 */
interface CRM_Utils_Cache_Interface extends \Psr\SimpleCache\CacheInterface {

  /**
   * Set the value in the cache.
   *
   * @param string $key
   * @param mixed $value
   * @param null|int|\DateInterval $ttl
   * @return bool
   */
  public function set($key, $value, $ttl = NULL);

  /**
   * Get a value from the cache.
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   *   The previously set value value, or $default (NULL).
   */
  public function get($key, $default = NULL);

  /**
   * Delete a value from the cache.
   *
   * @param string $key
   * @return bool
   */
  public function delete($key);

  /**
   * Delete all values from the cache.
   *
   * NOTE: flush() and clear() should be aliases. flush() is specified by
   * Civi's traditional interface, and clear() is specified by PSR-16.
   *
   * @return bool
   * @see clear
   * @deprecated
   */
  public function flush();

  /**
   * Delete all values from the cache.
   *
   * NOTE: flush() and clear() should be aliases. flush() is specified by
   * Civi's traditional interface, and clear() is specified by PSR-16.
   *
   * @return bool
   * @see flush
   */
  public function clear();

  /**
   * Determines whether an item is present in the cache.
   *
   * NOTE: It is recommended that has() is only to be used for cache warming type purposes
   * and not to be used within your live applications operations for get/set, as this method
   * is subject to a race condition where your has() will return true and immediately after,
   * another script can remove it making the state of your app out of date.
   *
   * @param string $key The cache item key.
   *
   * @return bool
   */
  public function has($key);

  /**
   * Performs garbage collection on a cache. Removing expired items.
   *
   * @return bool
   */
  public function garbageCollection();

}
