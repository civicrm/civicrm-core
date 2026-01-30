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
class CRM_Utils_Cache_APCucache implements CRM_Utils_Cache_Interface {

  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;
  // TODO Native implementation
  use CRM_Utils_Cache_NaiveHasTrait;

  const DEFAULT_TIMEOUT = 3600;
  const DEFAULT_PREFIX = '';

  /**
   * The default timeout to use.
   *
   * @var int
   */
  protected $_timeout = self::DEFAULT_TIMEOUT;

  /**
   * The prefix prepended to cache keys.
   *
   * If we are using the same instance for multiple CiviCRM installs,
   * we must have a unique prefix for each install to prevent
   * the keys from clobbering each other.
   *
   * @var string
   */
  protected $_prefix = self::DEFAULT_PREFIX;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_APCucache
   */
  public function __construct(&$config) {
    if (isset($config['timeout'])) {
      $this->_timeout = intval($config['timeout']);
    }
    if (isset($config['prefix'])) {
      $this->_prefix = $config['prefix'];
    }
  }

  /**
   * @param $key
   * @param $value
   * @param null|int|\DateInterval $ttl
   *
   * @return bool
   */
  public function set($key, $value, $ttl = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (is_int($ttl) && $ttl <= 0) {
      return $this->delete($key);
    }

    $ttl = CRM_Utils_Date::convertCacheTtl($ttl, $this->_timeout);
    $expires = time() + $ttl;
    if (!apcu_store($this->_prefix . $key, ['e' => $expires, 'v' => $value], $ttl)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    $result = apcu_fetch($this->_prefix . $key, $success);
    if ($success && isset($result['e']) && $result['e'] > time()) {
      return $this->reobjectify($result['v']);
    }
    return $default;
  }

  /**
   * @param $key
   *
   * @return bool|string[]
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    apcu_delete($this->_prefix . $key);
    return TRUE;
  }

  public function flush() {
    $allinfo = apcu_cache_info();
    $keys = $allinfo['cache_list'];
    // Our keys follows this pattern: ([A-Za-z0-9_]+)?CRM_[A-Za-z0-9_]+
    $prefix = $this->_prefix;
    // Get prefix length
    $lp = strlen($prefix);

    foreach ($keys as $key) {
      $name = $key['info'];
      if ($prefix == substr($name, 0, $lp)) {
        // Ours?
        apcu_delete($name);
      }
    }
    return TRUE;
  }

  public function clear() {
    return $this->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    return FALSE;
  }

  private function reobjectify($value) {
    return is_object($value) ? unserialize(serialize($value)) : $value;
  }

}
