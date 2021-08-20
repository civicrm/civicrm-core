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
 *  Access Control List
 */
class CRM_ACL_BAO_ACL extends CRM_ACL_DAO_ACL implements \Civi\Test\HookInterface {

  /**
   * Available operations for  pseudoconstant.
   *
   * @return array
   */
  public static function operation(): array {
    return [
      'View' => ts('View'),
      'Edit' => ts('Edit'),
      'Create' => ts('Create'),
      'Delete' => ts('Delete'),
      'Search' => ts('Search'),
      'All' => ts('All'),
    ];
  }

  /**
   * Get all of the ACLs for a contact through ACL groups owned by Contact.
   * groups.
   *
   * @param int $contact_id
   *   ID of a contact to search for.
   *
   * @return array
   *   Array of assoc. arrays of ACL rules
   * @throws \CRM_Core_Exception
   */
  protected static function getGroupACLRoles(int $contact_id) {

    $query = "   SELECT          acl.*
                        FROM            civicrm_acl acl
                        INNER JOIN      civicrm_option_group og
                                ON      og.name = 'acl_role'
                        INNER JOIN      civicrm_option_value ov
                                ON      acl.entity_table   = 'civicrm_acl_role'
                                AND     ov.option_group_id  = og.id
                                AND     acl.entity_id      = ov.value
                                AND     ov.is_active        = 1
                        INNER JOIN      civicrm_acl_entity_role acl_entity_role
                                ON      acl_entity_role.acl_role_id = acl.entity_id
                                AND     acl_entity_role.is_active    = 1
                        INNER JOIN  civicrm_group_contact group_contact
                                ON      acl_entity_role.entity_id      = group_contact.group_id
                                AND     acl_entity_role.entity_table   = 'civicrm_group'
                        WHERE       acl.entity_table       = 'civicrm_acl_role'
                            AND     acl.is_active          = 1
                            AND     group_contact.contact_id         = $contact_id
                            AND     group_contact.status             = 'Added'";

    $results = [];

    $rule = CRM_Core_DAO::executeQuery($query);

    while ($rule->fetch()) {
      $results[$rule->id] = $rule->toArray();
    }

    return $results;
  }

  /**
   * Get all ACLs owned by a given contact, including domain and group-level.
   *
   * @param int|null $contact_id
   *   The contact ID.
   *
   * @return array
   *   Assoc array of ACL rules
   *
   * @throws \CRM_Core_Exception
   */
  public static function getAllByContact(int $contact_id): array {
    $result = [];

    /* First, the contact-specific ACLs, including ACL Roles */
    // 0 would be the anonymous contact.
    if ($contact_id > 0) {
      $query = " SELECT acl.*
      FROM civicrm_acl acl
      WHERE   acl.entity_table   = 'civicrm_contact'
      AND acl.entity_id      = $contact_id";

      $rule = CRM_Core_DAO::executeQuery($query);

      while ($rule->fetch()) {
        $result[$rule->id] = $rule->toArray();
      }
      $query = "
SELECT      acl.*
  FROM      civicrm_acl acl
 INNER JOIN  civicrm_group_contact group_contact
        ON  acl.entity_id      = group_contact.group_id
     WHERE  acl.entity_table   = 'civicrm_group'
       AND  group_contact.contact_id     = $contact_id
       AND  group_contact.status         = 'Added'";

      $rule = CRM_Core_DAO::executeQuery($query);

      while ($rule->fetch()) {
        $result[$rule->id] = $rule->toArray();
      }
      $result += self::getGroupACLRoles($contact_id);
    }
    // also get all acls for "Any Role" case
    // and authenticated User Role if present
    $roles = '0';
    $session = CRM_Core_Session::singleton();
    if ($session->get('ufID') > 0) {
      $roles .= ',2';
    }

    $query = "
SELECT acl.*
  FROM civicrm_acl acl
 WHERE acl.entity_id      IN ( $roles )
   AND acl.entity_table   = 'civicrm_acl_role'
";

    $rule = CRM_Core_DAO::executeQuery($query);
    while ($rule->fetch()) {
      $result[$rule->id] = $rule->toArray();
    }
    return $result;
  }

  /**
   * @param array $params
   *
   * @return CRM_ACL_DAO_ACL
   */
  public static function create($params) {
    $dao = new CRM_ACL_DAO_ACL();
    $dao->copyValues($params);
    $dao->save();
    return $dao;
  }

  /**
   * @param array $params
   * @param array $defaults
   */
  public static function retrieve(&$params, &$defaults) {
    CRM_Core_DAO::commonRetrieve('CRM_ACL_DAO_ACL', $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    Civi::cache('fields')->flush();
    // reset ACL and system caches.
    CRM_Core_BAO_Cache::resetCaches();

    return CRM_Core_DAO::setFieldValue('CRM_ACL_DAO_ACL', $id, 'is_active', $is_active);
  }

  /**
   * @param $str
   * @param int $contactID
   *
   * @return bool
   *
   * @deprecated
   */
  public static function check($str, $contactID) {
    \CRM_Core_Error::deprecatedWarning(__CLASS__ . '::' . __FUNCTION__ . ' is deprecated.');

    $acls = CRM_ACL_BAO_Cache::build($contactID);

    $aclKeys = array_keys($acls);
    $aclKeys = implode(',', $aclKeys);

    if (empty($aclKeys)) {
      return FALSE;
    }

    $query = "
SELECT count( a.id )
  FROM civicrm_acl_cache c, civicrm_acl a
 WHERE c.acl_id       =  a.id
   AND a.is_active    =  1
   AND a.object_table =  %1
   AND a.id           IN ( $aclKeys )
";
    $params = [1 => [$str, 'String']];

    $count = CRM_Core_DAO::singleValueQuery($query, $params);
    return (bool) $count;
  }

  /**
   * @param $type
   * @param $tables
   * @param $whereTables
   * @param int $contactID
   *
   * @return null|string
   */
  public static function whereClause($type, &$tables, &$whereTables, $contactID = NULL) {
    $acls = CRM_ACL_BAO_Cache::build($contactID);

    $whereClause = NULL;
    $clauses = [];

    if (!empty($acls)) {
      $aclKeys = array_keys($acls);
      $aclKeys = implode(',', $aclKeys);

      $query = "
SELECT   a.operation, a.object_id
  FROM   civicrm_acl_cache c, civicrm_acl a
 WHERE   c.acl_id       =  a.id
   AND   a.is_active    =  1
   AND   a.object_table = 'civicrm_saved_search'
   AND   a.id        IN ( $aclKeys )
ORDER BY a.object_id
";

      $dao = CRM_Core_DAO::executeQuery($query);

      // do an or of all the where clauses u see
      $ids = [];
      while ($dao->fetch()) {
        // make sure operation matches the type TODO
        if (self::matchType($type, $dao->operation)) {
          if (!$dao->object_id) {
            $ids = [];
            $whereClause = ' ( 1 ) ';
            break;
          }
          $ids[] = $dao->object_id;
        }
      }

      if (!empty($ids)) {
        $ids = implode(',', $ids);
        $query = "
SELECT g.*
  FROM civicrm_group g
 WHERE g.id IN ( $ids )
 AND   g.is_active = 1
";
        $dao = CRM_Core_DAO::executeQuery($query);
        $groupIDs = [];
        $groupContactCacheClause = FALSE;
        while ($dao->fetch()) {
          $groupIDs[] = $dao->id;

          if (($dao->saved_search_id || $dao->children || $dao->parents)) {
            if ($dao->cache_date == NULL) {
              CRM_Contact_BAO_GroupContactCache::load($dao);
            }
            $groupContactCacheClause = " UNION SELECT contact_id FROM civicrm_group_contact_cache WHERE group_id IN (" . implode(', ', $groupIDs) . ")";
          }

        }

        if ($groupIDs) {
          $clauses[] = "(
            `contact_a`.id IN (
               SELECT contact_id FROM civicrm_group_contact WHERE group_id IN (" . implode(', ', $groupIDs) . ") AND status = 'Added'
               $groupContactCacheClause
             )
          )";
        }
      }
    }

    if (!empty($clauses)) {
      $whereClause = ' ( ' . implode(' OR ', $clauses) . ' ) ';
    }

    // call the hook to get additional whereClauses
    CRM_Utils_Hook::aclWhereClause($type, $tables, $whereTables, $contactID, $whereClause);

    if (empty($whereClause)) {
      $whereClause = ' ( 0 ) ';
    }

    return $whereClause;
  }

  /**
   * @param int $type
   * @param int $contactID
   * @param string $tableName
   * @param null $allGroups
   * @param null $includedGroups
   *
   * @return array
   */
  public static function group(
    $type,
    $contactID = NULL,
    $tableName = 'civicrm_saved_search',
    $allGroups = NULL,
    $includedGroups = NULL
  ) {
    $userCacheKey = "{$contactID}_{$type}_{$tableName}_" . CRM_Core_Config::domainID() . '_' . md5(implode(',', array_merge((array) $allGroups, (array) $includedGroups)));
    if (empty(Civi::$statics[__CLASS__]['permissioned_groups'])) {
      Civi::$statics[__CLASS__]['permissioned_groups'] = [];
    }
    if (!empty(Civi::$statics[__CLASS__]['permissioned_groups'][$userCacheKey])) {
      return Civi::$statics[__CLASS__]['permissioned_groups'][$userCacheKey];
    }

    if ($allGroups == NULL) {
      $allGroups = CRM_Contact_BAO_Contact::buildOptions('group_id', 'get');
    }

    $acls = CRM_ACL_BAO_Cache::build($contactID);

    $ids = [];
    if (!empty($acls)) {
      $aclKeys = array_keys($acls);
      $aclKeys = implode(',', $aclKeys);

      $cacheKey = CRM_Utils_Cache::cleanKey("$type-$tableName-$aclKeys");
      $cache = CRM_Utils_Cache::singleton();
      $ids = $cache->get($cacheKey);
      if (!is_array($ids)) {
        $ids = self::loadPermittedIDs((int) $contactID, $tableName, $type, $allGroups);
        $cache->set($cacheKey, $ids);
      }
    }

    if (empty($ids) && !empty($includedGroups) &&
      is_array($includedGroups)
    ) {
      // This is pretty alarming - we 'sometimes' include all included groups
      // seems problematic per https://lab.civicrm.org/dev/core/-/issues/1879
      $ids = $includedGroups;
    }
    if ($contactID) {
      $groupWhere = '';
      if (!empty($allGroups)) {
        $groupWhere = ' AND id IN (' . implode(',', array_keys($allGroups)) . ")";
      }
      // Contacts create hidden groups from search results. They should be able to retrieve their own.
      $ownHiddenGroupsList = CRM_Core_DAO::singleValueQuery("
        SELECT GROUP_CONCAT(id) FROM civicrm_group WHERE is_hidden =1 AND created_id = $contactID
        $groupWhere
      ");
      if ($ownHiddenGroupsList) {
        $ownHiddenGroups = explode(',', $ownHiddenGroupsList);
        $ids = array_merge((array) $ids, $ownHiddenGroups);
      }

    }

    CRM_Utils_Hook::aclGroup($type, $contactID, $tableName, $allGroups, $ids);
    Civi::$statics[__CLASS__]['permissioned_groups'][$userCacheKey] = $ids;
    return $ids;
  }

  /**
   * @param int $type
   * @param $operation
   *
   * @return bool
   */
  protected static function matchType($type, $operation) {
    $typeCheck = FALSE;
    switch ($operation) {
      case 'All':
        $typeCheck = TRUE;
        break;

      case 'View':
        if ($type == CRM_ACL_API::VIEW) {
          $typeCheck = TRUE;
        }
        break;

      case 'Edit':
        if ($type == CRM_ACL_API::VIEW || $type == CRM_ACL_API::EDIT) {
          $typeCheck = TRUE;
        }
        break;

      case 'Create':
        if ($type == CRM_ACL_API::CREATE) {
          $typeCheck = TRUE;
        }
        break;

      case 'Delete':
        if ($type == CRM_ACL_API::DELETE) {
          $typeCheck = TRUE;
        }
        break;

      case 'Search':
        if ($type == CRM_ACL_API::SEARCH) {
          $typeCheck = TRUE;
        }
        break;
    }
    return $typeCheck;
  }

  /**
   * Delete ACL records.
   *
   * @param int $aclId
   * @deprecated
   */
  public static function del($aclId) {
    self::deleteRecord(['id' => $aclId]);
  }

  /**
   * Event fired before an action is taken on an ACL record.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    // Reset cache when deleting an ACL record
    if ($event->action === 'delete') {
      CRM_ACL_BAO_Cache::resetCache();
    }
  }

  /**
   * Load permitted acl IDs.
   *
   * @param int $contactID
   * @param string $tableName
   * @param int $type
   * @param $allGroups
   *
   * @return array
   */
  protected static function loadPermittedIDs(int $contactID, string $tableName, int $type, $allGroups): array {
    $ids = [];
    $acls = CRM_ACL_BAO_Cache::build($contactID);
    $aclKeys = array_keys($acls);
    $aclKeys = implode(',', $aclKeys);
    $query = "
SELECT   a.operation, a.object_id
  FROM   civicrm_acl_cache c, civicrm_acl a
 WHERE   c.acl_id       =  a.id
   AND   a.is_active    =  1
   AND   a.object_table = %1
   AND   a.id        IN ( $aclKeys )
GROUP BY a.operation,a.object_id
ORDER BY a.object_id
";
    $params = [1 => [$tableName, 'String']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      if ($dao->object_id) {
        if (self::matchType($type, $dao->operation)) {
          $ids[] = $dao->object_id;
        }
      }
      else {
        // this user has got the permission for all objects of this type
        // check if the type matches
        if (self::matchType($type, $dao->operation)) {
          foreach ($allGroups as $id => $dontCare) {
            $ids[] = $id;
          }
        }
        break;
      }
    }
    return $ids;
  }

}
