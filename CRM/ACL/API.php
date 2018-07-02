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
   * @param int $contactID
   *   The contactID for whom the check is made.
   *
   * @return bool
   *   true if yes, else false
   */
  public static function check($str, $contactID = NULL) {
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
   * @param int $contactID
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
      if (CRM_Core_Permission::check('access deleted contacts') and $onlyDeleted) {
        $deleteClause = '(contact_a.is_deleted)';
      }
      else {
        // CRM-6181
        $deleteClause = '(contact_a.is_deleted = 0)';
      }
    }

    // first see if the contact has edit / view all contacts
    if (CRM_Core_Permission::check('edit all contacts') ||
      ($type == self::VIEW && CRM_Core_Permission::check('view all contacts'))
    ) {
      return $deleteClause;
    }

    if (!$contactID) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
    }
    $contactID = (int) $contactID;

    $where = implode(' AND ',
      array(
        CRM_ACL_BAO_ACL::whereClause($type,
          $tables,
          $whereTables,
          $contactID
        ),
        $deleteClause,
      )
    );

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
   * @param int $contactID
   *   The contactID for whom the check is made.
   *
   * @param string $tableName
   * @param null $allGroups
   * @param null $includedGroups
   *
   * @return array
   *   the ids of the groups for which the user has permissions
   */
  public static function group(
    $type,
    $contactID = NULL,
    $tableName = 'civicrm_saved_search',
    $allGroups = NULL,
    $includedGroups = NULL
  ) {
    if ($contactID == NULL) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
    }

    if (!$contactID) {
      // anonymous user
      $contactID = 0;
    }

    return CRM_ACL_BAO_ACL::group($type, $contactID, $tableName, $allGroups, $includedGroups);
  }

  /**
   * Check if the user has access to this group for operation $type
   *
   * @param int $type
   *   The type of permission needed.
   * @param int $groupID
   * @param int $contactID
   *   The contactID for whom the check is made.
   * @param string $tableName
   * @param null $allGroups
   * @param null $includedGroups
   *
   * @return bool
   */
  public static function groupPermission(
    $type,
    $groupID,
    $contactID = NULL,
    $tableName = 'civicrm_saved_search',
    $allGroups = NULL,
    $includedGroups = NULL
  ) {

    if (!isset(Civi::$statics[__CLASS__]) || !isset(Civi::$statics[__CLASS__]['group_permission'])) {
      Civi::$statics[__CLASS__]['group_permission'] = array();
    }

    if (!$contactID) {
      $contactID = CRM_Core_Session::singleton()->getLoggedInContactID();
    }

    $key = "{$tableName}_{$type}_{$contactID}";
    if (!array_key_exists($key, Civi::$statics[__CLASS__]['group_permission'])) {
      Civi::$statics[__CLASS__]['group_permission'][$key] = self::group($type, $contactID, $tableName, $allGroups, $includedGroups);
    }

    return in_array($groupID, Civi::$statics[__CLASS__]['group_permission'][$key]);
  }

}
