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

use Civi\Api4\Utils\CoreUtil;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 *  Access Control List
 */
class CRM_ACL_BAO_ACL extends CRM_ACL_DAO_ACL implements \Civi\Core\HookInterface {

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
   * @param int $contact_id
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
   * @deprecated
   * @param array $params
   * @return CRM_ACL_DAO_ACL
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_ACL_DAO_ACL', $id, 'is_active', $is_active);
  }

  /**
   * @param string $str
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
   * @param int $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   *
   * @return null|string
   */
  public static function whereClause($type, &$tables, &$whereTables, $contactID = NULL) {

    $whereClause = NULL;
    $allInclude = $allExclude = FALSE;
    $clauses = [];

    $dao = self::getOrderedActiveACLs($contactID, 'civicrm_group');
    if ($dao !== NULL) {
      // do an or of all the where clauses u see
      $ids = $excludeIds = [];
      while ($dao->fetch()) {
        // make sure operation matches the type TODO
        if (self::matchType($type, $dao->operation)) {
          if (!$dao->deny) {
            if (empty($dao->object_id)) {
              $allInclude = TRUE;
            }
            else {
              $ids[] = $dao->object_id;
            }
          }
          else {
            if (empty($dao->object_id)) {
              $allExclude = TRUE;
            }
            else {
              $excludeIds[] = $dao->object_id;
            }
          }
        }
      }
      if (!empty($excludeIds) && !$allInclude) {
        $ids = array_diff($ids, $excludeIds);
      }
      elseif (!empty($excludeIds) && $allInclude) {
        $ids = [];
        $clauses[] = self::getGroupClause($excludeIds, 'NOT IN');
      }
      if (!empty($ids) && !$allInclude) {
        $clauses[] = self::getGroupClause($ids, 'IN');
      }
      elseif ($allInclude && empty($excludeIds)) {
        $clauses[] = ' ( 1 ) ';
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
   * @param array|null $allGroups
   * @param array $includedGroups
   *
   * @return array
   */
  public static function group(
    $type,
    $contactID = NULL,
    $tableName = 'civicrm_group',
    $allGroups = NULL,
    $includedGroups = []
  ) {
    if (!is_array($includedGroups)) {
      CRM_Core_Error::deprecatedWarning('pass an array for included groups');
      $includedGroups = (array) $includedGroups;
    }
    $userCacheKey = "{$contactID}_{$type}_{$tableName}_" . CRM_Core_Config::domainID() . '_' . md5(implode(',', array_merge((array) $allGroups, $includedGroups)));
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

    if (empty($ids) && !empty($includedGroups)) {
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
   * @param string $operation
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
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
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
   * @param array $allGroups
   *
   * @return array
   */
  protected static function loadPermittedIDs(int $contactID, string $tableName, int $type, $allGroups): array {
    $ids = [];
    $dao = self::getOrderedActiveACLs($contactID, $tableName);
    if ($dao !== NULL) {
      while ($dao->fetch()) {
        if ($dao->object_id) {
          if (self::matchType($type, $dao->operation)) {
            if (!$dao->deny) {
              $ids[] = $dao->object_id;
            }
            else {
              $ids = array_diff($ids, [$dao->object_id]);
            }
          }
        }
        else {
          // this user has got the permission for all objects of this type
          // check if the type matches
          if (self::matchType($type, $dao->operation)) {
            if (!$dao->deny) {
              foreach ($allGroups as $id => $dontCare) {
                $ids[] = $id;
              }
            }
            else {
              $ids = array_diff($ids, array_keys($allGroups));
            }
          }
        }
      }
    }
    return $ids;
  }

  /**
   * Execute a query to find active ACLs for a contact, ordered by priority (if supported) and object ID.
   * The query returns the 'operation', 'object_id' and 'deny' properties.
   * Returns NULL if CRM_ACL_BAO_Cache::build (effectively, CRM_ACL_BAO_ACL::getAllByContact)
   * returns no ACLs (active or not) for the contact.
   *
   * @param string $contactID
   * @param string $tableName
   * @return NULL|CRM_Core_DAO|object
   */
  private static function getOrderedActiveACLs(string $contactID, string $tableName) {
    $dao = NULL;
    $acls = CRM_ACL_BAO_Cache::build($contactID);
    if (!empty($acls)) {
      $aclKeys = array_keys($acls);
      $aclKeys = implode(',', $aclKeys);
      $orderBy = 'a.object_id';
      if (array_key_exists('priority', CRM_ACL_BAO_ACL::getSupportedFields())) {
        $orderBy = "a.priority, $orderBy";
      }
      $query = "
SELECT   a.operation, a.object_id, a.deny
  FROM   civicrm_acl_cache c, civicrm_acl a
 WHERE   c.acl_id       =  a.id
   AND   a.is_active    =  1
   AND   a.object_table = %1
   AND   a.id        IN ({$aclKeys})
ORDER BY {$orderBy}
";
      $params = [1 => [$tableName, 'String']];
      $dao = CRM_Core_DAO::executeQuery($query, $params);
    }
    return $dao;
  }

  private static function getGroupClause(array $groupIDs, string $operation): string {
    $ids = implode(',', $groupIDs);
    $query = "
SELECT g.*
  FROM civicrm_group g
 WHERE g.id IN ( $ids )
 AND   g.is_active = 1
";
    $dao = CRM_Core_DAO::executeQuery($query);
    $foundGroupIDs = [];
    $groupContactCacheClause = '';
    while ($dao->fetch()) {
      $foundGroupIDs[] = $dao->id;
      if (($dao->saved_search_id || $dao->children || $dao->parents)) {
        if ($dao->cache_date == NULL) {
          CRM_Contact_BAO_GroupContactCache::load($dao);
        }
        $groupContactCacheClause = " UNION SELECT contact_id FROM civicrm_group_contact_cache WHERE group_id IN (" . implode(', ', $foundGroupIDs) . ")";
      }
    }

    if ($foundGroupIDs) {
      return "(
        `contact_a`.id $operation (
         SELECT contact_id FROM civicrm_group_contact WHERE group_id IN (" . implode(', ', $foundGroupIDs) . ") AND status = 'Added'
           $groupContactCacheClause
         )
      )";
    }
    else {
      // Edge case avoiding SQL syntax error if no $foundGroupIDs
      return "(
        `contact_a`.id $operation (0)
      )";
    }
  }

  public static function getObjectTableOptions(): array {
    return [
      'civicrm_group' => ts('Group'),
      'civicrm_uf_group' => ts('Profile'),
      'civicrm_event' => ts('Event'),
      'civicrm_custom_group' => ts('Custom Group'),
    ];
  }

  /**
   * Provides pseudoconstant list for `object_id` field.
   *
   * @param string $fieldName
   * @param array $params
   * @return array
   */
  public static function getObjectIdOptions(string $fieldName, array $params): array {
    $values = self::fillValues($params['values'], ['object_table']);
    $tableName = $values['object_table'];
    if (!$tableName) {
      return [];
    }
    if (!isset(Civi::$statics[__FUNCTION__][$tableName])) {
      $entity = CoreUtil::getApiNameFromTableName($tableName);
      $label = CoreUtil::getInfoItem($entity, 'label_field');
      $titlePlural = CoreUtil::getInfoItem($entity, 'title_plural');
      Civi::$statics[__FUNCTION__][$tableName] = [];
      Civi::$statics[__FUNCTION__][$tableName][] = [
        'label' => ts('All %1', [1 => $titlePlural]),
        'id' => 0,
        'name' => 0,
      ];
      $options = civicrm_api4($entity, 'get', [
        'select' => [$label, 'id', 'name'],
      ]);
      foreach ($options as $option) {
        Civi::$statics[__FUNCTION__][$tableName][] = [
          'label' => $option[$label],
          'id' => $option['id'],
          'name' => $option['name'] ?? $option['id'],
        ];
      }
    }
    return Civi::$statics[__FUNCTION__][$tableName];
  }

}
