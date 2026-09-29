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

  // has(), getMultiple() and deleteMultiple() are native (below). setMultiple() is
  // deliberately left naive: Redis has no multi-key set with a TTL, so a native version
  // would need a pipeline/transaction of SETEX calls, with new partial-failure semantics
  // to reconcile with set()'s throw-on-failure behaviour. Writes are also far rarer than reads.
  use CRM_Utils_Cache_NaiveMultipleTrait;

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
    $socket = $config['socket'] ?? '';
    // Ugh.
    $pass = CRM_Utils_Constant::value('CIVICRM_DB_CACHE_PASSWORD');
    if (!empty($socket)) {
      $id = implode(':', ['connect', $socket /* $pass is constant */]);
    }
    else {
      $id = implode(':', ['connect', $host, $port /* $pass is constant */]);
    }
    if (!isset(Civi::$statics[__CLASS__][$id])) {
      // Ideally, we'd track the connection in the service-container, but the
      // cache connection is boot-critical.
      $redis = new Redis();
      if (!empty($socket)) {
        if (!$redis->pconnect($socket)) {
          // Don't use fatal here since we can go in an infinite loop.
          echo 'Could not connect to Redis server using socket';
          CRM_Utils_System::civiExit();
        }
      }
      else {
        if (!$redis->connect($host, $port)) {
          // Don't use fatal here since we can go in an infinite loop.
          echo 'Could not connect to redisd server';
          CRM_Utils_System::civiExit();
        }
      }
      if ($pass) {
        $redis->auth($pass);
      }
      // Select the database if it is not the default 0.
      $base = CRM_Utils_Constant::value('CIVICRM_DB_CACHE_BASE', 0);
      if ($base) {
        $redis->select($base);
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
   * @param string $key
   *
   * @return bool
   */
  public function has($key) {
    CRM_Utils_Cache::assertValidKey($key);
    // EXISTS avoids transferring and unserializing the payload just to test presence.
    return (bool) $this->_cache->exists($this->_prefix . $key);
  }

  /**
   * @param iterable $keys
   * @param mixed $default
   *
   * @return array
   */
  public function getMultiple($keys, $default = NULL) {
    $keys = $this->prepareKeys('getMultiple', $keys);
    if (!$keys) {
      return [];
    }
    $raw = $this->_cache->mget($this->prefixKeys($keys));
    $result = [];
    foreach ($keys as $i => $key) {
      // Like get(), a FALSE reply (missing key, or a failed command) yields the default.
      $result[$key] = (!is_array($raw) || $raw[$i] === FALSE) ? $default : unserialize($raw[$i]);
    }
    return $result;
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    $this->_cache->del($this->_prefix . $key);
    return TRUE;
  }

  /**
   * @param iterable $keys
   *
   * @return bool
   */
  public function deleteMultiple($keys) {
    $keys = $this->prepareKeys('deleteMultiple', $keys);
    if ($keys) {
      $this->_cache->del($this->prefixKeys($keys));
    }
    return TRUE;
  }

  /**
   * Check that $keys is an iterable of valid keys, and return them as a list.
   *
   * @param string $func
   * @param iterable $keys
   *
   * @return string[]|int[]
   * @throws \CRM_Utils_Cache_InvalidArgumentException
   */
  private function prepareKeys($func, $keys): array {
    $this->assertIterable($func, $keys);
    // Not preserving keys: a generator may yield the same key more than once.
    $keys = is_array($keys) ? array_values($keys) : iterator_to_array($keys, FALSE);
    foreach ($keys as $key) {
      CRM_Utils_Cache::assertValidKey($key);
    }
    return $keys;
  }

  /**
   * @param string[] $keys
   *
   * @return string[]
   */
  private function prefixKeys(array $keys): array {
    return array_map(fn($key) => $this->_prefix . $key, $keys);
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
