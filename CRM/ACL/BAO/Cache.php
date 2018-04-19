<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 *  Access Control Cache.
 */
class CRM_ACL_BAO_Cache extends CRM_ACL_DAO_Cache {

  static $_cache = NULL;

  /**
   * @param int $id
   *
   * @return mixed
   */
  public static function &build($id) {
    if (!self::$_cache) {
      self::$_cache = array();
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

    self::$_cache[$id] = CRM_ACL_BAO_ACL::getAllByContact($id);
    self::store($id, self::$_cache[$id]);
    return self::$_cache[$id];
  }

  /**
   * @param int $id
   *
   * @return array
   */
  public static function retrieve($id) {
    $query = "
SELECT acl_id
  FROM civicrm_acl_cache
 WHERE contact_id = %1
";
    $params = array(1 => array($id, 'Integer'));

    if ($id == 0) {
      $query .= " OR contact_id IS NULL";
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $cache = array();
    while ($dao->fetch()) {
      $cache[$dao->acl_id] = 1;
    }
    return $cache;
  }

  /**
   * @param int $id
   * @param array $cache
   */
  public static function store($id, &$cache) {
    foreach ($cache as $aclID => $data) {
      $dao = new CRM_ACL_DAO_Cache();
      if ($id) {
        $dao->contact_id = $id;
      }
      $dao->acl_id = $aclID;

      $cache[$aclID] = 1;

      $dao->save();
    }
  }

  /**
   * @param int $id
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
    $params = array(1 => array($id, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * @param int $id
   */
  public static function updateEntry($id) {
    // rebuilds civicrm_acl_cache
    self::deleteEntry($id);
    self::build($id);

    // rebuilds civicrm_acl_contact_cache
    CRM_Contact_BAO_Contact_Permission::cache($id, CRM_Core_Permission::VIEW, TRUE);
  }

  /**
   * Deletes all the cache entries.
   */
  public static function resetCache() {
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
    $params = array(1 => array(CRM_Contact_BAO_GroupContactCache::getCacheInvalidDateTime(), 'String'));
    CRM_Core_DAO::singleValueQuery($query, $params);

    // CRM_Core_DAO::singleValueQuery("TRUNCATE TABLE civicrm_acl_contact_cache"); // No, force-commits transaction
    // CRM_Core_DAO::singleValueQuery("DELETE FROM civicrm_acl_contact_cache"); // Transaction-safe
    if (CRM_Core_Transaction::isActive()) {
      CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, function () {
        CRM_Core_DAO::singleValueQuery("TRUNCATE TABLE civicrm_acl_contact_cache");
      });
    }
    else {
      CRM_Core_DAO::singleValueQuery("TRUNCATE TABLE civicrm_acl_contact_cache");
    }
  }

}
