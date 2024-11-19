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
 * Class CRM_Utils_Cache_ArrayCache
 */
class CRM_Utils_Cache_ArrayCache implements CRM_Utils_Cache_Interface {

  use CRM_Utils_Cache_NaiveMultipleTrait;
  // TODO Native implementation
  use CRM_Utils_Cache_NaiveHasTrait;

  const DEFAULT_TIMEOUT = 3600;

  /**
   * The cache storage container, an in memory array by default
   * @var array
   */
  protected $_cache;

  protected $_expires;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_ArrayCache
   */
  public function __construct($config) {
    $this->_cache = [];
    $this->_expires = [];
  }

  /**
   * @param string $key
   * @param mixed $value
   * @param null|int|\DateInterval $ttl
   * @return bool
   * @throws \Psr\SimpleCache\InvalidArgumentException
   */
  public function set($key, $value, $ttl = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    $this->_cache[$key] = $this->reobjectify($value);
    $this->_expires[$key] = CRM_Utils_Date::convertCacheTtlToExpires($ttl, self::DEFAULT_TIMEOUT);
    return TRUE;
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   * @throws \Psr\SimpleCache\InvalidArgumentException
   */
  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (isset($this->_expires[$key]) && is_numeric($this->_expires[$key]) && $this->_expires[$key] <= time()) {
      return $default;
    }
    if (array_key_exists($key, $this->_cache)) {
      return $this->reobjectify($this->_cache[$key]);
    }
    return $default;
  }

  /**
   * @param string $key
   * @return bool
   * @throws \Psr\SimpleCache\InvalidArgumentException
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);

    unset($this->_cache[$key]);
    unset($this->_expires[$key]);
    return TRUE;
  }

  /**
   * @return true
   * @deprecated since 5.80 will be removed around 5.98
   */
  public function flush() {
    return $this->clear();
  }

  public function clear() {
    unset($this->_cache);
    unset($this->_expires);
    $this->_cache = [];
    return TRUE;
  }

  private function reobjectify($value) {
    if (is_object($value)) {
      return unserialize(serialize($value));
    }
    if (is_array($value)) {
      foreach ($value as $p) {
        if (is_object($p)) {
          return unserialize(serialize($value));
        }
      }
    }
    return $value;
  }

  /**
   * @param string $key
   * @return int|null
   */
  public function getExpires($key) {
    return $this->_expires[$key] ?? NULL;
  }

}
