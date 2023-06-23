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
class CRM_ACL_API {

  /**
   * The various type of permissions.
   *
   * @var int
   */
  const EDIT = 1;
  const VIEW = 2;
  const DELETE = 3;
  const CREATE = 4;
  const SEARCH = 5;
  const ALL = 6;

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   * @param int|null $contactID
   *   The contactID for whom the check is made.
   *
   * @return bool
   *   true if yes, else false
   *
   * @deprecated
   */
  public static function check($str, $contactID = NULL) {
    \CRM_Core_Error::deprecatedWarning(__CLASS__ . '::' . __FUNCTION__ . ' is deprecated.');
    if ($contactID == NULL) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
    }

    if (!$contactID) {
      // anonymous user
      $contactID = 0;
    }

    return CRM_ACL_BAO_ACL::check($str, $contactID);
  }

  /**
   * Get the permissioned where clause for the user.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   * @param int|null $contactID
   *   The contactID for whom the check is made.
   * @param bool $onlyDeleted
   *   Whether to include only deleted contacts.
   * @param bool $skipDeleteClause
   *   Don't add delete clause if this is true,.
   *   this means it is handled by generating query
   * @param bool $skipOwnContactClause
   *   Do not add 'OR contact_id = $userID' to the where clause.
   *   This is a hideously inefficient query and should be avoided
   *   wherever possible.
   *
   * @return string
   *   the group where clause for this user
   */
  public static function whereClause(
    $type,
    &$tables,
    &$whereTables,
    $contactID = NULL,
    $onlyDeleted = FALSE,
    $skipDeleteClause = FALSE,
    $skipOwnContactClause = FALSE
  ) {
    // the default value which is valid for the final AND
    $deleteClause = ' ( 1 ) ';
    if (!$skipDeleteClause) {
      if (CRM_Core_Permission::check('access deleted contacts')) {
        if ($onlyDeleted) {
          $deleteClause = '(contact_a.is_deleted)';
        }
      }
      else {
        // Exclude deleted contacts due to permissions
        $deleteClause = '(contact_a.is_deleted = 0)';
      }
    }

    if (!$contactID) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
    }
    $contactID = (int) $contactID;

    // first see if the contact has edit / view all permission
    if (CRM_Core_Permission::check('edit all contacts', $contactID) ||
      ($type == self::VIEW && CRM_Core_Permission::check('view all contacts', $contactID))
    ) {
      return $deleteClause;
    }

    $whereClause = CRM_ACL_BAO_ACL::whereClause($type,
      $tables,
      $whereTables,
      $contactID
    );
    $where = implode(' AND ', [$whereClause, $deleteClause]);

    // Add permission on self if we really hate our server or have hardly any contacts.
    if (!$skipOwnContactClause && $contactID && (CRM_Core_Permission::check('edit my contact') ||
        $type == self::VIEW && CRM_Core_Permission::check('view my contact'))
    ) {
      $where = "(contact_a.id = $contactID OR ($where))";
    }
    return $where;
  }

  /**
   * Get all the groups the user has access to for the given operation.
   *
   * @param int $type
   *   The type of permission needed.
   * @param int|null $contactID
   *   The contactID for whom the check is made.
   *
   * @param string $tableName
   * @param array|null $allGroups
   * @param array $includedGroups
   *
   * @return array
   *   the ids of the groups for which the user has permissions
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
    if ($contactID == NULL) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
    }

    return CRM_ACL_BAO_ACL::group($type, (int) $contactID, $tableName, $allGroups, $includedGroups);
  }

  /**
   * Check if the user has access to this group for operation $type
   *
   * @param int $type
   *   The type of permission needed.
   * @param int $groupID
   * @param int|null $contactID
   *   The contactID for whom the check is made.
   * @param string $tableName
   * @param array|null $allGroups
   * @param array|null $includedGroups
   *
   * @return bool
   */
  public static function groupPermission(
    $type,
    $groupID,
    $contactID = NULL,
    $tableName = 'civicrm_group',
    $allGroups = NULL,
    $includedGroups = NULL
  ) {

    if (!isset(Civi::$statics[__CLASS__]) || !isset(Civi::$statics[__CLASS__]['group_permission'])) {
      Civi::$statics[__CLASS__]['group_permission'] = [];
    }

    if (!$contactID) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
    }

    $key = "{$tableName}_{$type}_{$contactID}";
    if (!array_key_exists($key, Civi::$statics[__CLASS__]['group_permission'])) {
      Civi::$statics[__CLASS__]['group_permission'][$key] = self::group($type, $contactID, $tableName, $allGroups, $includedGroups ?? []);
    }

    return in_array($groupID, Civi::$statics[__CLASS__]['group_permission'][$key]);
  }

}
