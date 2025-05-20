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
 * Class CRM_Utils_Cache_ArrayDecorator
 *
 * This creates a two-tier cache-hierarchy: a thread-local, array-based cache
 * combined with some third-party cache driver.
 *
 * Ex: $cache = new CRM_Utils_Cache_ArrayDecorator(new CRM_Utils_Cache_Redis(...));
 */
class CRM_Utils_Cache_ArrayDecorator implements CRM_Utils_Cache_Interface {

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
   * @var array
   *   Array(string $cacheKey => int $expirationTime).
   */
  private $expires = [];

  /**
   * CRM_Utils_Cache_ArrayDecorator constructor.
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

    $expiresAt = CRM_Utils_Date::convertCacheTtlToExpires($ttl, $this->defaultTimeout);
    if ($this->delegate->set($key, [$expiresAt, $value], $ttl)) {
      $this->values[$key] = $this->reobjectify($value);
      $this->expires[$key] = $expiresAt;
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (array_key_exists($key, $this->values) && $this->expires[$key] > CRM_Utils_Time::getTimeRaw()) {
      return $this->reobjectify($this->values[$key]);
    }

    $nack = CRM_Utils_Cache::nack();
    $value = $this->delegate->get($key, $nack);
    if ($value === $nack) {
      return $default;
    }

    $this->expires[$key] = $value[0];
    $this->values[$key] = $value[1];
    return $this->reobjectify($this->values[$key]);
  }

  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    unset($this->values[$key]);
    unset($this->expires[$key]);
    return $this->delegate->delete($key);
  }

  public function flush() {
    return $this->clear();
  }

  public function clear() {
    $this->values = [];
    $this->expires = [];
    return $this->delegate->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $this->expires = [];
    return TRUE;
  }

  public function has($key) {
    CRM_Utils_Cache::assertValidKey($key);
    if (array_key_exists($key, $this->values) && $this->expires[$key] > CRM_Utils_Time::time()) {
      return TRUE;
    }
    return $this->delegate->has($key);
  }

  private function reobjectify($value) {
    return is_object($value) ? unserialize(serialize($value)) : $value;
  }

}
