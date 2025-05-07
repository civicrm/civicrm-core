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
 */

/**
 * Class CRM_Utils_Cache_FastArrayDecorator
 *
 * Like CRM_Utils_Cache_ArrayDecorator, this creates a two-tier cache.
 * But it's... faster. The speed improvements are achieved by sacrificing
 * compliance with PSR-16. Specific trade-offs:
 *
 * 1. TTL values are not tracked locally. Any data cached locally will stay
 *    active until the instance is destroyed (i.e. until the request ends).
 *    You won't notice this is you have short-lived requests and long-lived caches.
 * 2. If you store an *object* in the local cache, the same object instance
 *    will be used through the end of the request. If you modify a property
 *    of the object, the change will endure within the current pageview but
 *    will not pass-through to the persistent cache.
 *
 * But... it is twice as fast (on high-volume reads).
 *
 * Ex: $cache = new CRM_Utils_Cache_FastArrayDecorator(new CRM_Utils_Cache_Redis(...));
 *
 * @see CRM_Utils_Cache_ArrayDecorator
 */
class CRM_Utils_Cache_FastArrayDecorator implements CRM_Utils_Cache_Interface {

  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;

  /**
   * @var int
   *   Default time-to-live (seconds) for cache items that don't have a TTL.
   */
  protected $defaultTimeout;

  /**
   * @var CRM_Utils_Cache_Interface
   */
  private $delegate;

  /**
   * @var array
   *   Array(string $cacheKey => mixed $cacheValue).
   */
  private $values = [];

  /**
   * CRM_Utils_Cache_FastArrayDecorator constructor.
   * @param \CRM_Utils_Cache_Interface $delegate
   * @param int $defaultTimeout
   *   Default number of seconds each cache-item should endure.
   */
  public function __construct(\CRM_Utils_Cache_Interface $delegate, $defaultTimeout = 3600) {
    $this->defaultTimeout = $defaultTimeout;
    $this->delegate = $delegate;
  }

  public function set($key, $value, $ttl = NULL) {
    if (is_int($ttl) && $ttl <= 0) {
      return $this->delete($key);
    }

    if ($this->delegate->set($key, $value, $ttl)) {
      $this->values[$key] = $value;
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (array_key_exists($key, $this->values)) {
      return $this->values[$key];
    }

    $nack = CRM_Utils_Cache::nack();
    $value = $this->delegate->get($key, $nack);
    if ($value === $nack) {
      return $default;
    }

    $this->values[$key] = $value;
    return $value;
  }

  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    unset($this->values[$key]);
    return $this->delegate->delete($key);
  }

  public function flush() {
    return $this->clear();
  }

  public function clear() {
    $this->values = [];
    return $this->delegate->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    return $this->delegate->garbageCollection();
  }

  public function has($key) {
    CRM_Utils_Cache::assertValidKey($key);
    if (array_key_exists($key, $this->values)) {
      return TRUE;
    }
    // Doing a get here populates `$this->values`. If the calling
    // code does a `has()` followed by a `get` we want only one
    // look-up so do that lookup on the first request.
    // (The only real reason to do `has` & then `get` is it is
    // less ambiguous for false & empty values)
    // `$this->delegate->has($key)` here then an extra
    // lookup will be needed if we do `has` followed by `get`.
    // Reducing `get` calls to the underlying cache has significant
    // speed improvement (see https://github.com/civicrm/civicrm-core/pull/24156)
    $this->get($key);
    return array_key_exists($key, $this->values);
  }

}
