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
class CRM_Utils_Cache_Redis implements CRM_Utils_Cache_Interface {

  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;
  // TODO Native implementation
  use CRM_Utils_Cache_NaiveHasTrait;

  const DEFAULT_HOST    = 'localhost';
  const DEFAULT_PORT    = 6379;
  const DEFAULT_TIMEOUT = 3600;
  const DEFAULT_PREFIX  = '';

  /**
   * The default timeout to use
   *
   * @var int
   */
  protected $_timeout = self::DEFAULT_TIMEOUT;

  /**
   * The prefix prepended to cache keys.
   *
   * If we are using the same redis instance for multiple CiviCRM
   * installs, we must have a unique prefix for each install to prevent
   * the keys from clobbering each other.
   *
   * @var string
   */
  protected $_prefix = self::DEFAULT_PREFIX;

  /**
   * The actual redis object
   *
   * @var Redis
   */
  protected $_cache;

  /**
   * Create a connection. If a connection already exists, re-use it.
   *
   * @param array $config
   * @return Redis
   */
  public static function connect($config) {
    $host = $config['host'] ?? self::DEFAULT_HOST;
    $port = $config['port'] ?? self::DEFAULT_PORT;
    // Ugh.
    $pass = CRM_Utils_Constant::value('CIVICRM_DB_CACHE_PASSWORD');
    $id = implode(':', ['connect', $host, $port /* $pass is constant */]);
    if (!isset(Civi::$statics[__CLASS__][$id])) {
      // Ideally, we'd track the connection in the service-container, but the
      // cache connection is boot-critical.
      $redis = new Redis();
      if (!$redis->connect($host, $port)) {
        // dont use fatal here since we can go in an infinite loop
        echo 'Could not connect to redisd server';
        CRM_Utils_System::civiExit();
      }
      if ($pass) {
        $redis->auth($pass);
      }
      Civi::$statics[__CLASS__][$id] = $redis;
    }
    return Civi::$statics[__CLASS__][$id];
  }

  /**
   * Constructor
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_Redis
   */
  public function __construct($config) {
    if (isset($config['timeout'])) {
      $this->_timeout = $config['timeout'];
    }
    if (isset($config['prefix'])) {
      $this->_prefix = $config['prefix'];
    }
    if (defined('CIVICRM_DEPLOY_ID')) {
      $this->_prefix = CIVICRM_DEPLOY_ID . '_' . $this->_prefix;
    }

    $this->_cache = self::connect($config);
  }

  /**
   * @param $key
   * @param $value
   * @param null|int|\DateInterval $ttl
   *
   * @return bool
   * @throws Exception
   */
  public function set($key, $value, $ttl = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (is_int($ttl) && $ttl <= 0) {
      return $this->delete($key);
    }
    $ttl = CRM_Utils_Date::convertCacheTtl($ttl, self::DEFAULT_TIMEOUT);
    if (!$this->_cache->setex($this->_prefix . $key, $ttl, serialize($value))) {
      if (PHP_SAPI === 'cli' || (Civi\Core\Container::isContainerBooted() && CRM_Core_Permission::check('view debug output'))) {
        throw new CRM_Utils_Cache_CacheException("Redis set ($key) failed: " . $this->_cache->getLastError());
      }
      else {
        Civi::log()->error("Redis set ($key) failed: " . $this->_cache->getLastError());
        throw new CRM_Utils_Cache_CacheException("Redis set ($key) failed");
      }
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
    $result = $this->_cache->get($this->_prefix . $key);
    return ($result === FALSE) ? $default : unserialize($result);
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    $this->_cache->del($this->_prefix . $key);
    return TRUE;
  }

  /**
   * @return bool
   */
  public function flush() {
    // FIXME: Ideally, we'd map each prefix to a different 'hash' object in Redis,
    // and this would be simpler. However, that needs to go in tandem with a
    // more general rethink of cache expiration/TTL.

    $keys = $this->_cache->keys($this->_prefix . '*');
    $this->_cache->del($keys);
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

}
