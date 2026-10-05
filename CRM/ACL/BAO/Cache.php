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
 *  Access Control Cache.
 */
class CRM_ACL_BAO_Cache extends CRM_ACL_DAO_ACLCache {

  public static $_cache = NULL;

  /**
   * Build an array of ACLs for a specific ACLed user
   *
   * @param int $id - contact_id of the ACLed user
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public static function &build($id) {
    if (!self::$_cache) {
      self::$_cache = [];
    }

    if (array_key_exists($id, self::$_cache)) {
      return self::$_cache[$id];
    }

    // check if this entry exists in db
    // if so retrieve and return
    self::$_cache[$id] = self::retrieve($id);
    if (self::$_cache[$id]) {
      return self::$_cache[$id];
    }

    // Use a lock to prevent users from reading this data while the table is being filled
    // See https://lab.civicrm.org/dev/core/-/work_items/2641
    $lock = Civi::lockManager()->acquire("data.core.acl.$id");
    if (!$lock->isAcquired()) {
      \Civi::log()->debug("Failed to acquire lock data.core.acl.$id. Lock will be ignored. ACL may have inconsistencies.");
      // If you're hitting this frequently, then the question is... Why? Maybe you just need to extend the
      // timeout? Or maybe there's a structural reason?
    }

    self::$_cache[$id] = self::retrieve($id);
    if (self::$_cache[$id]) {
      $lock->release();
      return self::$_cache[$id];
    }

    self::$_cache[$id] = CRM_ACL_BAO_ACL::getAllByContact((int) $id);
    self::store($id, self::$_cache[$id]);
    $lock->release();
    return self::$_cache[$id];
  }

  /**
   * @param int $id
   *
   * @return array
   */
  protected static function retrieve($id) {
    $query = "
SELECT acl_id
  FROM civicrm_acl_cache
 WHERE contact_id = %1
";
    $params = [1 => [$id, 'Integer']];

    if ($id == 0) {
      $query .= " OR contact_id IS NULL";
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $cache = [];
    while ($dao->fetch()) {
      $cache[$dao->acl_id] = 1;
    }
    return $cache;
  }

  /**
   * Store ACLs for a specific user in the `civicrm_acl_cache` table
   * @param int $id - contact_id of the ACLed user
   * @param array $cache - key civicrm_acl.id - values is the details of the ACL.
   *
   */
  protected static function store($id, &$cache) {
    foreach ($cache as $aclID => $data) {
      $cache[$aclID] = 1;

      if ($id) {
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_cache (acl_id, contact_id) VALUES (%1, %2)", [
          1  => [$aclID, 'Integer'],
          2 => [$id, 'Integer'],
        ]);
      }
      else {
        // contact_id is null if the user is not logged in. Not sure why we need to insert a record for NULL though?
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_cache (acl_id, contact_id) VALUES (%1, NULL)", [
          1  => [$aclID, 'Integer'],
        ]);
      }
    }
  }

  /**
   * Remove entries from civicrm_acl_cache for a specified ACLed user
   * @param int $id - contact_id of the ACLed user
   *
   */
  public static function deleteEntry($id) {
    if (self::$_cache &&
      array_key_exists($id, self::$_cache)
    ) {
      unset(self::$_cache[$id]);
    }

    $query = "
DELETE FROM civicrm_acl_cache
WHERE contact_id = %1
";
    $params = [1 => [$id, 'Integer']];
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Do an opportunistic cache refresh if the site is configured for these.
   *
   * Sites that use acls and do not run the acl cache clearing cron job should
   * refresh the caches on demand. The user session will be forced to wait
   * and this is a common source of deadlocks, so it is less ideal.
   */
  public static function opportunisticCacheFlush(): void {
    if (Civi::settings()->get('acl_cache_refresh_mode') === 'opportunistic') {
      self::resetCache();
    }
  }

  /**
   * Deletes all the cache entries.
   */
  public static function resetCache(): void {
    if (!CRM_Core_Config::isPermitCacheFlushMode()) {
      return;
    }
    // reset any static caching
    self::$_cache = NULL;

    $query = "
DELETE
FROM   civicrm_acl_cache
WHERE  modified_date IS NULL
   OR  (modified_date <= %1)
";
    $params = [
      1 => [
        CRM_Contact_BAO_GroupContactCache::getCacheInvalidDateTime(),
        'String',
      ],
    ];
    CRM_Core_DAO::singleValueQuery($query, $params);
    self::flushACLContactCache();
  }

  /**
   * Remove Entries from `civicrm_acl_contact_cache` for a specific ACLed user
   * @param int $userID - contact_id of the ACLed user
   *
   */
  public static function deleteContactCacheEntry($userID) {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_acl_contact_cache WHERE user_id = %1", [1 => [$userID, 'Positive']]);
  }

  /**
   * Flush the contents of the acl contact cache.
   */
  protected static function flushACLContactCache(): void {
    unset(Civi::$statics['CRM_ACL_API']);
    // Use DELETE rather than TRUNCATE: TRUNCATE is DDL, so it force-commits the
    // current transaction and bumps the table's metadata version. Concurrent
    // connections with an open SELECT on civicrm_acl_contact_cache (e.g. a
    // reminder cron or another web request) then hit MySQL error 1213
    // ("Deadlock found... try restarting transaction") or "table definition
    // has changed". DELETE is plain DML and transaction-safe.
    if (CRM_Core_Transaction::isActive()) {
      CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, function () {
        CRM_Core_DAO::singleValueQuery('DELETE FROM civicrm_acl_contact_cache');
      });
    }
    else {
      CRM_Core_DAO::singleValueQuery("DELETE FROM civicrm_acl_contact_cache");
    }
  }

}
