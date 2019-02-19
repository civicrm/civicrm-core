<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
   */
  public function __construct(&$config) {
    CRM_Core_Error::fatal(ts('this is just an interface and should not be called directly'));
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
   * @param $cachePlugin
   *
   * @return array
   *   associative array of settings for the cache
   */
  public static function getCacheSettings($cachePlugin) {
    switch ($cachePlugin) {
      case 'ArrayCache':
      case 'NoCache':
        $defaults = array();
        break;

      case 'Redis':
      case 'Memcache':
      case 'Memcached':
        $defaults = array(
          'host' => 'localhost',
          'port' => 11211,
          'timeout' => 3600,
          'prefix' => '',
        );

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
        $defaults = array();
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
   * @return CRM_Utils_Cache_Interface
   * @throws CRM_Core_Exception
   * @see Civi::cache()
   */
  public static function create($params = array()) {
    $types = (array) $params['type'];

    if (!empty($params['name'])) {
      $params['name'] = CRM_Core_BAO_Cache::cleanKey($params['name']);
    }

    foreach ($types as $type) {
      switch ($type) {
        case '*memory*':
          if (defined('CIVICRM_DB_CACHE_CLASS') && in_array(CIVICRM_DB_CACHE_CLASS, array('Memcache', 'Memcached', 'Redis'))) {
            $dbCacheClass = 'CRM_Utils_Cache_' . CIVICRM_DB_CACHE_CLASS;
            $settings = self::getCacheSettings(CIVICRM_DB_CACHE_CLASS);
            $settings['prefix'] = CRM_Utils_Array::value('prefix', $settings, '') . self::DELIMITER . $params['name'] . self::DELIMITER;
            return new $dbCacheClass($settings);
          }
          break;

        case 'SqlGroup':
          if (defined('CIVICRM_DSN') && CIVICRM_DSN) {
            return new CRM_Utils_Cache_SqlGroup(array(
              'group' => $params['name'],
              'prefetch' => CRM_Utils_Array::value('prefetch', $params, FALSE),
            ));
          }
          break;

        case 'Arraycache':
        case 'ArrayCache':
          return new CRM_Utils_Cache_ArrayCache(array());

      }
    }

    throw new CRM_Core_Exception("Failed to instantiate cache. No supported cache type found. " . print_r($params, 1));
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
    $className = 'ArrayCache';   // default to ArrayCache for now

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

}
