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
   * Default is Two days: 2*24*60*60
   *
   * @var int, number of second
   */
  const DEFAULT_SESSION_TTL = 172800;

  /**
   * Cleanup ACL and System Level caches
   *
   * @deprecated
   *  Deprecated Jun 2025 in favor of Civi::rebuild().
   *     Reassess after Jun 2026.
   *     For an extension bridging before+after, suggest guard like:
   *       if (version_compare(CRM_Utils_System::version(), 'X.Y.Z', '>=')) Civi::rebuild(...)->execute()
   *       else CRM_Core_BAO_Cache::resetCaches();
   *     Choose an 'X.Y.Z' after determining that your preferred rebuild-target(s) are specifically available in X.Y.Z.
   */
  public static function resetCaches() {
    Civi::rebuild(['system' => TRUE])->execute();
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
    \Civi::dispatcher()->dispatch('civi.session.storeObjects', \Civi\Core\Event\GenericHookEvent::create([
      'names' => &$names,
    ]));
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
   * @param array $names
   */
  public static function restoreSessionFromCache($names) {
    \Civi::dispatcher()->dispatch('civi.session.restoreObjects', \Civi\Core\Event\GenericHookEvent::create([
      'names' => &$names,
    ]));
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
      $transactionPages = [
        'CRM_Contribute_Controller_Contribution',
        'CRM_Event_Controller_Registration',
      ];
      foreach ($transactionPages as $transactionPage) {
        if (str_contains($sessionKey, $transactionPage)) {
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
   * @param bool $expired
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
      Civi::service('prevnext')->cleanup();
    }

    if ($table) {
      CRM_Core_Config::clearTempTables($timeIntervalDays . ' day');
    }

    if ($session) {
      // Session caches are just regular caches, so they expire naturally per TTL.
      $expired = TRUE;
    }

    if ($expired) {
      \Civi::cache('long')->garbageCollection();
      \Civi::cache('session')->garbageCollection();
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
    if (str_contains($string, ':') || str_contains($string, ';')) {
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
   * @param string|null $path
   *   Filter by path. If NULL, then return any paths.
   * @param int|null $componentID
   *   Filter by component. If NULL, then look for explicitly NULL records.
   * @return string
   */
  protected static function whereCache($group, $path, $componentID) {
    $clauses = [];
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
   * @deprecated
   * @see CRM_Utils_Cache::cleanKey()
   */
  public static function cleanKey($key) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Utils_Cache::cleanKey');
    return CRM_Utils_Cache::cleanKey($key);
  }

}
