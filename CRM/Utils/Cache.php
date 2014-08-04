<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Cache is an empty base object, we'll modify the scheme when we have different caching schemes
 *
 */
class CRM_Utils_Cache {
  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param array $config an array of configuration params
   *
   * @return \CRM_Utils_Cache
   */
  function __construct(&$config) {
    CRM_Core_Error::fatal(ts('this is just an interface and should not be called directly'));
  }

  /**
   * singleton function used to manage this object
   *
   * @return object
   * @static
   *
   */
  static function &singleton() {
    if (self::$_singleton === NULL) {
      $className = 'ArrayCache';   // default to ArrayCache for now

      // Maintain backward compatibility for now.
      // Setting CIVICRM_USE_MEMCACHE or CIVICRM_USE_ARRAYCACHE will
      // override the CIVICRM_DB_CACHE_CLASS setting.
      // Going forward, CIVICRM_USE_xxxCACHE should be deprecated.
      if (defined('CIVICRM_USE_MEMCACHE') && CIVICRM_USE_MEMCACHE) {
        $className = 'Memcache';
      }
      else if (defined('CIVICRM_USE_ARRAYCACHE') && CIVICRM_USE_ARRAYCACHE) {
        $className = 'ArrayCache';
      }
      else if (defined('CIVICRM_DB_CACHE_CLASS') && CIVICRM_DB_CACHE_CLASS) {
        $className = CIVICRM_DB_CACHE_CLASS;
      }

      // a generic method for utilizing any of the available db caches.
      $dbCacheClass = 'CRM_Utils_Cache_' . $className;
      require_once(str_replace('_', DIRECTORY_SEPARATOR, $dbCacheClass) . '.php');
      $settings = self::getCacheSettings($className);
      self::$_singleton = new $dbCacheClass($settings);
    }
    return self::$_singleton;
  }

  /**
   * Get cache relevant settings
   *
   * @param $cachePlugin
   *
   * @return array
   *   associative array of settings for the cache
   * @static
   */
  static function getCacheSettings($cachePlugin) {
    switch ($cachePlugin) {
      case 'ArrayCache':
      case 'NoCache':
        $defaults = array();
        break;

      case 'Memcache':
      case 'Memcached':
        $defaults = array(
          'host' => 'localhost',
          'port' => 11211,
          'timeout' => 3600,
          'prefix' => '',
        );

        // Use old constants if needed to ensure backward compatability
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
}
