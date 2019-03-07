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
 * BAO object for civicrm_cache table.
 *
 * This is a database cache and is persisted across sessions. Typically we use
 * this to store meta data (like profile fields, custom fields etc).
 *
 * The group_name column is used for grouping together all cache elements that logically belong to the same set.
 * Thus all session cache entries are grouped under 'CiviCRM Session'. This allows us to delete all entries of
 * a specific group if needed.
 *
 * The path column allows us to differentiate between items in that group. Thus for the session cache, the path is
 * the unique form name for each form (per user)
 */
class CRM_Core_BAO_Cache extends CRM_Core_DAO_Cache {

  /**
   * When store session/form state, how long should the data be retained?
   *
   * @var int, number of second
   */
  const DEFAULT_SESSION_TTL = 172800; // Two days: 2*24*60*60

  /**
   * @var array ($cacheKey => $cacheValue)
   */
  static $_cache = NULL;

  /**
   * Retrieve an item from the DB cache.
   *
   * @param string $group
   *   (required) The group name of the item.
   * @param string $path
   *   (required) The path under which this item is stored.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   *
   * @return object
   *   The data if present in cache, else null
   * @deprecated
   */
  public static function &getItem($group, $path, $componentID = NULL) {
    if (($adapter = CRM_Utils_Constant::value('CIVICRM_BAO_CACHE_ADAPTER')) !== NULL) {
      $value = $adapter::getItem($group, $path, $componentID);
      return $value;
    }

    if (self::$_cache === NULL) {
      self::$_cache = array();
    }

    $argString = "CRM_CT_{$group}_{$path}_{$componentID}";
    if (!array_key_exists($argString, self::$_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      $cleanKey = self::cleanKey($argString);
      self::$_cache[$argString] = $cache->get($cleanKey);
      if (self::$_cache[$argString] === NULL) {
        $table = self::getTableName();
        $where = self::whereCache($group, $path, $componentID);
        $rawData = CRM_Core_DAO::singleValueQuery("SELECT data FROM $table WHERE $where");
        $data = $rawData ? self::decode($rawData) : NULL;

        self::$_cache[$argString] = $data;
        if ($data !== NULL) {
          // Do not cache 'null' as that is most likely a cache miss & we shouldn't then cache it.
          $cache->set($cleanKey, self::$_cache[$argString]);
        }
      }
    }
    return self::$_cache[$argString];
  }

  /**
   * Retrieve all items in a group.
   *
   * @param string $group
   *   (required) The group name of the item.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   *
   * @return object
   *   The data if present in cache, else null
   * @deprecated
   */
  public static function &getItems($group, $componentID = NULL) {
    if (($adapter = CRM_Utils_Constant::value('CIVICRM_BAO_CACHE_ADAPTER')) !== NULL) {
      return $adapter::getItems($group, $componentID);
    }

    if (self::$_cache === NULL) {
      self::$_cache = array();
    }

    $argString = "CRM_CT_CI_{$group}_{$componentID}";
    if (!array_key_exists($argString, self::$_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      $cleanKey = self::cleanKey($argString);
      self::$_cache[$argString] = $cache->get($cleanKey);
      if (!self::$_cache[$argString]) {
        $table = self::getTableName();
        $where = self::whereCache($group, NULL, $componentID);
        $dao = CRM_Core_DAO::executeQuery("SELECT path, data FROM $table WHERE $where");

        $result = array();
        while ($dao->fetch()) {
          $result[$dao->path] = self::decode($dao->data);
        }

        self::$_cache[$argString] = $result;
        $cache->set($cleanKey, self::$_cache[$argString]);
      }
    }

    return self::$_cache[$argString];
  }

  /**
   * Store an item in the DB cache.
   *
   * @param object $data
   *   (required) A reference to the data that will be serialized and stored.
   * @param string $group
   *   (required) The group name of the item.
   * @param string $path
   *   (required) The path under which this item is stored.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   * @deprecated
   */
  public static function setItem(&$data, $group, $path, $componentID = NULL) {
    if (($adapter = CRM_Utils_Constant::value('CIVICRM_BAO_CACHE_ADAPTER')) !== NULL) {
      return $adapter::setItem($data, $group, $path, $componentID);
    }

    if (self::$_cache === NULL) {
      self::$_cache = array();
    }

    // get a lock so that multiple ajax requests on the same page
    // dont trample on each other
    // CRM-11234
    $lock = Civi::lockManager()->acquire("cache.{$group}_{$path}._{$componentID}");
    if (!$lock->isAcquired()) {
      CRM_Core_Error::fatal();
    }

    $table = self::getTableName();
    $where = self::whereCache($group, $path, $componentID);
    $dataExists = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM $table WHERE {$where}");
    $now = date('Y-m-d H:i:s'); // FIXME - Use SQL NOW() or CRM_Utils_Time?
    $dataSerialized = self::encode($data);

    // This table has a wonky index, so we cannot use REPLACE or
    // "INSERT ... ON DUPE". Instead, use SELECT+(INSERT|UPDATE).
    if ($dataExists) {
      $sql = "UPDATE $table SET data = %1, created_date = %2 WHERE {$where}";
      $args = array(
        1 => array($dataSerialized, 'String'),
        2 => array($now, 'String'),
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $args, TRUE, NULL, FALSE, FALSE);
    }
    else {
      $insert = CRM_Utils_SQL_Insert::into($table)
        ->row(array(
          'group_name' => $group,
          'path' => $path,
          'component_id' => $componentID,
          'data' => $dataSerialized,
          'created_date' => $now,
        ));
      $dao = CRM_Core_DAO::executeQuery($insert->toSQL(), array(), TRUE, NULL, FALSE, FALSE);
    }

    $lock->release();

    // cache coherency - refresh or remove dependent caches

    $argString = "CRM_CT_{$group}_{$path}_{$componentID}";
    $cache = CRM_Utils_Cache::singleton();
    $data = self::decode($dataSerialized);
    self::$_cache[$argString] = $data;
    $cache->set(self::cleanKey($argString), $data);

    $argString = "CRM_CT_CI_{$group}_{$componentID}";
    unset(self::$_cache[$argString]);
    $cache->delete(self::cleanKey($argString));
  }

  /**
   * Delete all the cache elements that belong to a group OR delete the entire cache if group is not specified.
   *
   * @param string $group
   *   The group name of the entries to be deleted.
   * @param string $path
   *   Path of the item that needs to be deleted.
   * @param bool $clearAll clear all caches
   * @deprecated
   */
  public static function deleteGroup($group = NULL, $path = NULL, $clearAll = TRUE) {
    if (($adapter = CRM_Utils_Constant::value('CIVICRM_BAO_CACHE_ADAPTER')) !== NULL) {
      return $adapter::deleteGroup($group, $path);
    }
    else {
      $table = self::getTableName();
      $where = self::whereCache($group, $path, NULL);
      CRM_Core_DAO::executeQuery("DELETE FROM $table WHERE $where");
    }

    if ($clearAll) {
      // also reset ACL Cache
      CRM_ACL_BAO_Cache::resetCache();

      // also reset memory cache if any
      CRM_Utils_System::flushCache();
    }
  }

  /**
   * The next two functions are internal functions used to store and retrieve session from
   * the database cache. This keeps the session to a limited size and allows us to
   * create separate session scopes for each form in a tab
   */

  /**
   * This function takes entries from the session array and stores it in the cache.
   *
   * It also deletes the entries from the $_SESSION object (for a smaller session size)
   *
   * @param array $names
   *   Array of session values that should be persisted.
   *                     This is either a form name + qfKey or just a form name
   *                     (in the case of profile)
   * @param bool $resetSession
   *   Should session state be reset on completion of DB store?.
   */
  public static function storeSessionToCache($names, $resetSession = TRUE) {
    foreach ($names as $key => $sessionName) {
      if (is_array($sessionName)) {
        $value = NULL;
        if (!empty($_SESSION[$sessionName[0]][$sessionName[1]])) {
          $value = $_SESSION[$sessionName[0]][$sessionName[1]];
        }
        $key = "{$sessionName[0]}_{$sessionName[1]}";
        Civi::cache('session')->set($key, $value, self::pickSessionTtl($key));
        if ($resetSession) {
          $_SESSION[$sessionName[0]][$sessionName[1]] = NULL;
          unset($_SESSION[$sessionName[0]][$sessionName[1]]);
        }
      }
      else {
        $value = NULL;
        if (!empty($_SESSION[$sessionName])) {
          $value = $_SESSION[$sessionName];
        }
        Civi::cache('session')->set($sessionName, $value, self::pickSessionTtl($sessionName));
        if ($resetSession) {
          $_SESSION[$sessionName] = NULL;
          unset($_SESSION[$sessionName]);
        }
      }
    }

    self::cleanup();
  }

  /* Retrieve the session values from the cache and populate the $_SESSION array
   *
   * @param array $names
   *   Array of session values that should be persisted.
   *                     This is either a form name + qfKey or just a form name
   *                     (in the case of profile)
   */

  /**
   * Restore session from cache.
   *
   * @param string $names
   */
  public static function restoreSessionFromCache($names) {
    foreach ($names as $key => $sessionName) {
      if (is_array($sessionName)) {
        $value = Civi::cache('session')->get("{$sessionName[0]}_{$sessionName[1]}");
        if ($value) {
          $_SESSION[$sessionName[0]][$sessionName[1]] = $value;
        }
      }
      else {
        $value = Civi::cache('session')->get($sessionName);
        if ($value) {
          $_SESSION[$sessionName] = $value;
        }
      }
    }
  }

  /**
   * Determine how long session-state should be retained.
   *
   * @param string $sessionKey
   *   Ex: '_CRM_Admin_Form_Preferences_Display_f1a5f232e3d850a29a7a4d4079d7c37b_4654_container'
   *   Ex: 'CiviCRM_CRM_Admin_Form_Preferences_Display_f1a5f232e3d850a29a7a4d4079d7c37b_4654'
   * @return int
   *   Number of seconds.
   */
  protected static function pickSessionTtl($sessionKey) {
    $secureSessionTimeoutMinutes = (int) Civi::settings()->get('secure_cache_timeout_minutes');
    if ($secureSessionTimeoutMinutes) {
      $transactionPages = array(
        'CRM_Contribute_Controller_Contribution',
        'CRM_Event_Controller_Registration',
      );
      foreach ($transactionPages as $transactionPage) {
        if (strpos($sessionKey, $transactionPage) !== FALSE) {
          return $secureSessionTimeoutMinutes * 60;
        }
      }
    }

    return self::DEFAULT_SESSION_TTL;
  }

  /**
   * Do periodic cleanup of the CiviCRM session table.
   *
   * Also delete all session cache entries which are a couple of days old.
   * This keeps the session cache to a manageable size
   * Delete Contribution page session caches more energetically.
   *
   * @param bool $session
   * @param bool $table
   * @param bool $prevNext
   */
  public static function cleanup($session = FALSE, $table = FALSE, $prevNext = FALSE, $expired = FALSE) {
    // clean up the session cache every $cacheCleanUpNumber probabilistically
    $cleanUpNumber = 757;

    // clean up all sessions older than $cacheTimeIntervalDays days
    $timeIntervalDays = 2;

    if (mt_rand(1, 100000) % $cleanUpNumber == 0) {
      $expired = $session = $table = $prevNext = TRUE;
    }

    if (!$session && !$table && !$prevNext && !$expired) {
      return;
    }

    if ($prevNext) {
      // delete all PrevNext caches
      CRM_Core_BAO_PrevNextCache::cleanupCache();
    }

    if ($table) {
      CRM_Core_Config::clearTempTables($timeIntervalDays . ' day');
    }

    if ($session) {
      // Session caches are just regular caches, so they expire naturally per TTL.
      $expired = TRUE;
    }

    if ($expired) {
      $sql = "DELETE FROM civicrm_cache WHERE expired_date < %1";
      $params = [
        1 => [date(CRM_Utils_Cache_SqlGroup::TS_FMT, CRM_Utils_Time::getTimeRaw()), 'String'],
      ];
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }

  /**
   * (Quasi-private) Encode an object/array/string/int as a string.
   *
   * @param $mixed
   * @return string
   */
  public static function encode($mixed) {
    return base64_encode(serialize($mixed));
  }

  /**
   * (Quasi-private) Decode an object/array/string/int from a string.
   *
   * @param $string
   * @return mixed
   */
  public static function decode($string) {
    // Upgrade support -- old records (serialize) always have this punctuation,
    // and new records (base64) never do.
    if (strpos($string, ':') !== FALSE || strpos($string, ';') !== FALSE) {
      return unserialize($string);
    }
    else {
      return unserialize(base64_decode($string));
    }
  }

  /**
   * Compose a SQL WHERE clause for the cache.
   *
   * Note: We need to use the cache during bootstrap, so we don't have
   * full access to DAO services.
   *
   * @param string $group
   * @param string|NULL $path
   *   Filter by path. If NULL, then return any paths.
   * @param int|NULL $componentID
   *   Filter by component. If NULL, then look for explicitly NULL records.
   * @return string
   */
  protected static function whereCache($group, $path, $componentID) {
    $clauses = array();
    $clauses[] = ('group_name = "' . CRM_Core_DAO::escapeString($group) . '"');
    if ($path) {
      $clauses[] = ('path = "' . CRM_Core_DAO::escapeString($path) . '"');
    }
    if ($componentID && is_numeric($componentID)) {
      $clauses[] = ('component_id = ' . (int) $componentID);
    }
    return $clauses ? implode(' AND ', $clauses) : '(1)';
  }

  /**
   * Normalize a cache key.
   *
   * This bridges an impedance mismatch between our traditional caching
   * and PSR-16 -- PSR-16 accepts a narrower range of cache keys.
   *
   * @param string $key
   *   Ex: 'ab/cd:ef'
   * @return string
   *   Ex: '_abcd1234abcd1234' or 'ab_xx/cd_xxef'.
   *   A similar key, but suitable for use with PSR-16-compliant cache providers.
   */
  public static function cleanKey($key) {
    if (!is_string($key) && !is_int($key)) {
      throw new \RuntimeException("Malformed cache key");
    }

    $maxLen = 64;
    $escape = '-';

    if (strlen($key) >= $maxLen) {
      return $escape . md5($key);
    }

    $r = preg_replace_callback(';[^A-Za-z0-9_\.];', function($m) use ($escape) {
      return $escape . dechex(ord($m[0]));
    }, $key);

    return strlen($r) >= $maxLen ? $escape . md5($key) : $r;
  }

}
