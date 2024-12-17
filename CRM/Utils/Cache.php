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
 * Cache is an empty base object, we'll modify the scheme when we have different caching schemes
 */
class CRM_Utils_Cache {

  const DELIMITER = '/';

  /**
   * (Quasi-Private) Treat this as private. It is marked public to facilitate testing.
   *
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  public static $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache
   * @throws \CRM_Core_Exception
   */
  public function __construct(&$config) {
    throw new CRM_Core_Exception(ts('this is just an interface and should not be called directly'));
  }

  /**
   * Singleton function used to manage this object.
   *
   * @return CRM_Utils_Cache_Interface
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      $className = self::getCacheDriver();
      // a generic method for utilizing any of the available db caches.
      $dbCacheClass = 'CRM_Utils_Cache_' . $className;
      $settings = self::getCacheSettings($className);
      $settings['prefix'] = CRM_Utils_Array::value('prefix', $settings, '') . self::DELIMITER . 'default' . self::DELIMITER;
      self::$_singleton = new $dbCacheClass($settings);
    }
    return self::$_singleton;
  }

  /**
   * Get cache relevant settings.
   *
   * @param string $cachePlugin
   *
   * @return array
   *   associative array of settings for the cache
   */
  public static function getCacheSettings($cachePlugin) {
    switch ($cachePlugin) {
      case 'ArrayCache':
      case 'NoCache':
        $defaults = [];
        break;

      case 'Redis':
      case 'Memcache':
      case 'Memcached':
        $defaults = [
          'host' => 'localhost',
          'port' => 11211,
          'timeout' => 3600,
          'prefix' => '',
        ];

        // Use old constants if needed to ensure backward compatibility
        if (defined('CIVICRM_MEMCACHE_HOST')) {
          $defaults['host'] = CIVICRM_MEMCACHE_HOST;
        }

        if (defined('CIVICRM_MEMCACHE_PORT')) {
          $defaults['port'] = CIVICRM_MEMCACHE_PORT;
        }

        if (defined('CIVICRM_MEMCACHE_TIMEOUT')) {
          $defaults['timeout'] = CIVICRM_MEMCACHE_TIMEOUT;
        }

        if (defined('CIVICRM_MEMCACHE_PREFIX')) {
          $defaults['prefix'] = CIVICRM_MEMCACHE_PREFIX;
        }

        // Use new constants if possible
        if (defined('CIVICRM_DB_CACHE_HOST')) {
          $defaults['host'] = CIVICRM_DB_CACHE_HOST;
        }

        if (defined('CIVICRM_DB_CACHE_PORT')) {
          $defaults['port'] = CIVICRM_DB_CACHE_PORT;
        }

        if (defined('CIVICRM_DB_CACHE_TIMEOUT')) {
          $defaults['timeout'] = CIVICRM_DB_CACHE_TIMEOUT;
        }

        if (defined('CIVICRM_DB_CACHE_PREFIX')) {
          $defaults['prefix'] = CIVICRM_DB_CACHE_PREFIX;
        }

        break;

      case 'APCcache':
      case 'APCucache':
        $defaults = [];
        if (defined('CIVICRM_DB_CACHE_TIMEOUT')) {
          $defaults['timeout'] = CIVICRM_DB_CACHE_TIMEOUT;
        }
        if (defined('CIVICRM_DB_CACHE_PREFIX')) {
          $defaults['prefix'] = CIVICRM_DB_CACHE_PREFIX;
        }
        break;
    }
    return $defaults;
  }

  /**
   * Create a new, named, limited-use cache.
   *
   * This is a factory function. Generally, you should use Civi::cache($name)
   * to locate managed cached instance.
   *
   * @param array $params
   *   Array with keys:
   *   - name: string, unique symbolic name.
   *     For a naming convention, use `snake_case` or `CamelCase` to maximize
   *     portability/cleanliness. Any other punctuation or whitespace
   *     should function correctly, but it can be harder to inspect/debug.
   *   - type: array|string, list of acceptable cache types, in order of preference.
   *   - prefetch: bool, whether to prefetch all data in cache (if possible).
   *   - scope: string, How broadly is this cache shared? (Available in v5.80+)
   *       - version: Cache is tied to current version of CiviCRM (RECOMMENDED)
   *       - runtime: Cache is tied to domain ID, HTTP host, version, etc
   *       - global: Cache is shared across all versions, domain IDs, HTTP hosts, etc (DEFAULT)
   *   - withArray: bool|null|'fast', whether to setup a thread-local array-cache in front of the cache driver.
   *     Note that cache-values may be passed to the underlying driver with extra metadata,
   *     so this will slightly change/enlarge the on-disk format.
   *     Support varies by driver:
   *       - For most memory backed caches, this option is meaningful.
   *       - For SqlGroup, this option is ignored. SqlGroup has equivalent behavior built-in.
   *       - For ArrayCache, this option is ignored. It's redundant.
   *      If this is a short-lived process in which TTL's don't matter, you might
   *      use 'fast' mode. It sacrifices some PSR-16 compliance and cache-coherency
   *      protections to improve performance.
   * @return CRM_Utils_Cache_Interface
   * @throws CRM_Core_Exception
   * @see Civi::cache()
   */
  public static function create($params = []) {
    $types = (array) $params['type'];

    switch ($params['scope'] ?? 'global') {
      case 'version':
        $scopeId = self::DELIMITER . static::getVersionCode();
        break;

      case 'runtime':
        $scopeId = self::DELIMITER . CRM_Core_Config_Runtime::getId();
        break;

      case 'global':
        $scopeId = '';
        break;

      default:
        throw new \LogicException("Invalid scope given to CRM_Utils_Cache::create()");
    }

    foreach ($types as $type) {
      switch ($type) {
        case '*memory*':
          if (defined('CIVICRM_DB_CACHE_CLASS') && in_array(CIVICRM_DB_CACHE_CLASS, ['Memcache', 'Memcached', 'Redis'])) {
            $shortName = self::cleanKey($params['name'], 64);
            $dbCacheClass = 'CRM_Utils_Cache_' . CIVICRM_DB_CACHE_CLASS;
            $settings = self::getCacheSettings(CIVICRM_DB_CACHE_CLASS);
            $settings['prefix'] = CRM_Utils_Array::value('prefix', $settings, '') . self::DELIMITER . $shortName . $scopeId;
            $cache = new $dbCacheClass($settings);
            if (!empty($params['withArray'])) {
              $cache = $params['withArray'] === 'fast' ? new CRM_Utils_Cache_FastArrayDecorator($cache) : new CRM_Utils_Cache_ArrayDecorator($cache);
            }
            break 2;
          }
          break;

        case 'SqlGroup':
          if (defined('CIVICRM_DSN') && CIVICRM_DSN) {
            $shortName = self::cleanKey($params['name'] . $scopeId, 32, 16, ';[^A-Za-z0-9_\. /];');
            // Name goes first because it's most interesting to skim. When value is long enough to provoke hashing, we want to retain as much ['name'] as possible.
            $cache = new CRM_Utils_Cache_SqlGroup([
              'group' => $shortName,
              'prefetch' => $params['prefetch'] ?? FALSE,
            ]);
            break 2;
          }
          break;

        case 'Arraycache':
        case 'ArrayCache':
          $cache = new CRM_Utils_Cache_ArrayCache([]);
          break 2;

      }
    }
    if (isset($cache)) {
      return new CRM_Utils_Cache_CacheWrapper($cache, $params['service'] ?? $params['name'] ?? NULL);
    }
    throw new CRM_Core_Exception("Failed to instantiate cache. No supported cache type found. " . print_r($params, 1));
  }

  private static function getVersionCode(): string {
    static $ver = NULL;
    if ($ver === NULL) {
      $ver = CRM_Utils_System::version();
      if (preg_match('/^(\d+)\.(\d+)\.(alpha|beta|)(\d+)/', $ver, $matches)) {
        $stages = ['alpha' => 'a', 'beta' => 'b', '' => '.' /* stable */];
        $ver = $matches[1] . '.' . $matches[2] . $stages[$matches[3]] . $matches[4];
      }
    }
    return $ver;
  }

  /**
   * Normalize a cache key.
   *
   * This bridges an impedance mismatch between our traditional caching
   * and PSR-16 -- PSR-16 accepts a narrower range of cache keys.
   *
   * @param string $key
   *   Ex: 'ab/cd:ef'
   * @param int $maxLen
   *   The maximum length of the string.
   *   To be useful, this must exceed the hash-length (24 characters).
   * @param int|NULL $hashLen
   *   The maximum length of the hashed suffix.
   *   If NULL, it will be full length (typically 24 characters).
   * @param string $allowChars
   *   Regex describing the list of valid characters
   * @return string
   *   Ex: '_abcd1234abcd1234' or 'ab_xx/cd_xxef'.
   *   A similar key, but suitable for use with PSR-16-compliant cache providers.
   */
  public static function cleanKey($key, int $maxLen = 64, ?int $hashLen = NULL, string $allowChars = ';[^A-Za-z0-9_\.];') {
    if (!is_string($key) && !is_int($key)) {
      throw new \RuntimeException("Malformed cache key");
    }

    $escape = '-';
    if ($key[0] === $escape) {
      throw new \RuntimeException("Malformed cache key. Has this been double-encoded?");
    }

    $r = preg_replace_callback($allowChars, function($m) use ($escape) {
      return $escape . dechex(ord($m[0]));
    }, $key);

    $len = strlen($r);
    if ($len < $maxLen) {
      return $r;
    }

    $hash = CRM_Utils_String::base64UrlEncode(md5($key, TRUE));
    if ($hashLen !== NULL) {
      $hash = substr($hash, 0, $hashLen);
    }
    return $escape . substr($r, 0, $maxLen - strlen($hash) - 3) . $hash;
  }

  /**
   * Assert that a key is well-formed.
   *
   * @param string $key
   * @return string
   *   Same $key, if it's valid.
   * @throws \CRM_Utils_Cache_InvalidArgumentException
   */
  public static function assertValidKey($key) {
    $strict = CRM_Utils_Constant::value('CIVICRM_PSR16_STRICT', FALSE) || defined('CIVICRM_TEST');

    if (!is_string($key)) {
      throw new CRM_Utils_Cache_InvalidArgumentException("Invalid cache key: Not a string");
    }

    if ($strict && !preg_match(';^[A-Za-z0-9_\-\. ]+$;', $key)) {
      throw new CRM_Utils_Cache_InvalidArgumentException("Invalid cache key: Illegal characters");
    }

    if ($strict && strlen($key) > 255) {
      throw new CRM_Utils_Cache_InvalidArgumentException("Invalid cache key: Too long");
    }

    return $key;
  }

  /**
   * @return string
   *   Ex: 'ArrayCache', 'Memcache', 'Redis'.
   */
  public static function getCacheDriver() {
    // default to ArrayCache for now
    $className = 'ArrayCache';

    // Maintain backward compatibility for now.
    // Setting CIVICRM_USE_MEMCACHE or CIVICRM_USE_ARRAYCACHE will
    // override the CIVICRM_DB_CACHE_CLASS setting.
    // Going forward, CIVICRM_USE_xxxCACHE should be deprecated.
    if (defined('CIVICRM_USE_MEMCACHE') && CIVICRM_USE_MEMCACHE) {
      $className = 'Memcache';
      return $className;
    }
    elseif (defined('CIVICRM_USE_ARRAYCACHE') && CIVICRM_USE_ARRAYCACHE) {
      $className = 'ArrayCache';
      return $className;
    }
    elseif (defined('CIVICRM_DB_CACHE_CLASS') && CIVICRM_DB_CACHE_CLASS) {
      $className = CIVICRM_DB_CACHE_CLASS;
      return $className;
    }
    return $className;
  }

  /**
   * Generate a unique negative-acknowledgement token (NACK).
   *
   * When using PSR-16 to read a value, the `$cahce->get()` will a return a default
   * value on cache-miss, so it's hard to know if you've gotten a geniune value
   * from the cache or just a default. If you're in an edge-case where it matters
   * (and you want to do has()+get() in a single roundtrip), use the nack() as
   * the default:
   *
   *   $nack = CRM_Utils_Cache::nack();
   *   $value = $cache->get('foo', $nack);
   *   echo ($value === $nack) ? "Cache has a value, and we got it" : "Cache has no value".
   *
   * The value should be unique to avoid accidental matches.
   *
   * @return string
   *   Unique nonce value indicating a "negative acknowledgement" (failed read).
   *   If we need to accurately perform has($key)+get($key), we can
   *   use `get($key,$nack)`.
   */
  public static function nack() {
    $st =& Civi::$statics[__CLASS__];
    if (!isset($st['nack-c'])) {
      $st['nack-c'] = md5(CRM_Utils_Request::id() . CIVICRM_SITE_KEY . CIVICRM_DSN . mt_rand(0, 10000));
      $st['nack-i'] = 0;
    }
    return 'NACK:' . $st['nack-c'] . $st['nack-i']++;
  }

}
