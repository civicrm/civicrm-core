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

    self::$_cache[$id] = CRM_ACL_BAO_ACL::getAllByContact((int) $id);
    self::store($id, self::$_cache[$id]);
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
      $dao = new CRM_ACL_BAO_Cache();
      if ($id) {
        $dao->contact_id = $id;
      }
      $dao->acl_id = $aclID;

      $cache[$aclID] = 1;

      $dao->save();
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
    // CRM_Core_DAO::singleValueQuery("TRUNCATE TABLE civicrm_acl_contact_cache"); // No, force-commits transaction
    // CRM_Core_DAO::singleValueQuery("DELETE FROM civicrm_acl_contact_cache"); // Transaction-safe
    if (CRM_Core_Transaction::isActive()) {
      CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, function () {
        CRM_Core_DAO::singleValueQuery('TRUNCATE TABLE civicrm_acl_contact_cache');
      });
    }
    else {
      CRM_Core_DAO::singleValueQuery("TRUNCATE TABLE civicrm_acl_contact_cache");
    }
  }

}
