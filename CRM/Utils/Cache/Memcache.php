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
class CRM_Utils_Cache_Memcache implements CRM_Utils_Cache_Interface {

  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;

  const DEFAULT_HOST = 'localhost';
  const DEFAULT_PORT = 11211;
  const DEFAULT_TIMEOUT = 3600;
  const DEFAULT_PREFIX = '';

  /**
   * If another process clears namespace, we'll find out in ~5 sec.
   */
  const NS_LOCAL_TTL = 5;

  /**
   * The host name of the memcached server.
   *
   * @var string
   */
  protected $_host = self::DEFAULT_HOST;

  /**
   * The port on which to connect on.
   *
   * @var int
   */
  protected $_port = self::DEFAULT_PORT;

  /**
   * The default timeout to use.
   *
   * @var int
   */
  protected $_timeout = self::DEFAULT_TIMEOUT;

  /**
   * The prefix prepended to cache keys.
   *
   * If we are using the same memcache instance for multiple CiviCRM
   * installs, we must have a unique prefix for each install to prevent
   * the keys from clobbering each other.
   *
   * @var string
   */
  protected $_prefix = self::DEFAULT_PREFIX;

  /**
   * The actual memcache object.
   *
   * @var Memcache
   */
  protected $_cache;

  /**
   * @var null|array
   *
   * This is the effective prefix. It may be bumped up whenever the dataset is flushed.
   *
   * @see https://github.com/memcached/memcached/wiki/ProgrammingTricks#deleting-by-namespace
   */
  protected $_truePrefix = NULL;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_Memcache
   */
  public function __construct($config) {
    if (isset($config['host'])) {
      $this->_host = $config['host'];
    }
    if (isset($config['port'])) {
      $this->_port = $config['port'];
    }
    if (isset($config['timeout'])) {
      $this->_timeout = $config['timeout'];
    }
    if (isset($config['prefix'])) {
      $this->_prefix = $config['prefix'];
    }

    $this->_cache = new Memcache();

    if (!$this->_cache->connect($this->_host, $this->_port)) {
      // dont use fatal here since we can go in an infinite loop
      echo 'Could not connect to Memcached server';
      CRM_Utils_System::civiExit();
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
    $expires = CRM_Utils_Date::convertCacheTtlToExpires($ttl, $this->_timeout);
    return $this->_cache->set($this->getTruePrefix() . $key, serialize($value), FALSE, $expires);
  }

  /**
   * @param $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    $result = $this->_cache->get($this->getTruePrefix() . $key);
    return ($result === FALSE) ? $default : unserialize($result);
  }

  /**
   * @param string $key
   *
   * @return bool
   * @throws \Psr\SimpleCache\CacheException
   */
  public function has($key) {
    CRM_Utils_Cache::assertValidKey($key);
    $result = $this->_cache->get($this->getTruePrefix() . $key);
    return ($result !== FALSE);
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    $this->_cache->delete($this->getTruePrefix() . $key);
    return TRUE;
  }

  /**
   * @return bool
   */
  public function flush() {
    $this->_truePrefix = NULL;
    $this->_cache->delete($this->_prefix);
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

  protected function getTruePrefix() {
    if ($this->_truePrefix === NULL || $this->_truePrefix['expires'] < time()) {
      $key = $this->_prefix;
      $value = $this->_cache->get($key);
      if ($value === FALSE) {
        $value = uniqid();
        // Indefinite.
        $this->_cache->set($key, $value, FALSE, 0);
      }
      $this->_truePrefix = [
        'value' => $value,
        'expires' => time() + self::NS_LOCAL_TTL,
      ];
    }
    return $this->_prefix . $this->_truePrefix['value'] . '/';
  }

}
