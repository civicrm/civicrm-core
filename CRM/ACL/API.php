<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_ACL_API {

  /**
   * The various type of permissions
   *
   * @var int
   */
  CONST EDIT = 1;
  CONST VIEW = 2;
  CONST DELETE = 3;
  CONST CREATE = 4;
  CONST SEARCH = 5;
  CONST ALL = 6;

  /**
   * given a permission string, check for access requirements
   *
   * @param string $str       the permission to check
   * @param int    $contactID the contactID for whom the check is made
   *
   * @return boolean true if yes, else false
   * @static
   * @access public
   */
  static function check($str, $contactID = NULL) {
    if ($contactID == NULL) {
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
    }

    if (!$contactID) {
      // anonymous user
      $contactID = 0;
    }

    return CRM_ACL_BAO_ACL::check($str, $contactID);
  }

  /**
   * Get the permissioned where clause for the user
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   * @param int    $contactID the contactID for whom the check is made
   * @param bool   $onlyDeleted  whether to include only deleted contacts
   * @param bool   $skipDeleteClause don't add delete clause if this is true,
   *               this means it is handled by generating query
   *
   * @return string the group where clause for this user
   * @access public
   */
  public static function whereClause($type,
    &$tables,
    &$whereTables,
    $contactID        = NULL,
    $onlyDeleted      = FALSE,
    $skipDeleteClause = FALSE
  ) {
    // the default value which is valid for rhe final AND
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
      ($type == self::VIEW &&
        CRM_Core_Permission::check('view all contacts')
      )
    ) {
      return $skipDeleteClause ? ' ( 1 ) ' : $deleteClause;
    }

    if ($contactID == NULL) {
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
    }

    if (!$contactID) {
      // anonymous user
      $contactID = 0;
    }

    return implode(' AND ',
      array(
        CRM_ACL_BAO_ACL::whereClause($type,
          $tables,
          $whereTables,
          $contactID
        ),
        $deleteClause,
      )
    );
  }

  /**
   * get all the groups the user has access to for the given operation
   *
   * @param int $type the type of permission needed
   * @param int    $contactID the contactID for whom the check is made
   *
   * @return array the ids of the groups for which the user has permissions
   * @access public
   */
  public static function group(
    $type,
    $contactID      = NULL,
    $tableName      = 'civicrm_saved_search',
    $allGroups      = NULL,
    $includedGroups = NULL
  ) {
    if ($contactID == NULL) {
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
    }

    if (!$contactID) {
      // anonymous user
      $contactID = 0;
    }

    return CRM_ACL_BAO_ACL::group($type, $contactID, $tableName, $allGroups, $includedGroups);
  }

  /**
   * check if the user has access to this group for operation $type
   *
   * @param int $type the type of permission needed
   * @param int    $contactID the contactID for whom the check is made
   *
   * @return array the ids of the groups for which the user has permissions
   * @access public
   */
  public static function groupPermission(
    $type,
    $groupID,
    $contactID      = NULL,
    $tableName      = 'civicrm_saved_search',
    $allGroups      = NULL,
    $includedGroups = NULL,
    $flush = FALSE
  ) {

    static $cache = array();
    //@todo this is pretty hacky!!!
    //adding a way for unit tests to flush the cache
    if ($flush) {
      $cache = array();
      return;
    }
    if (!$contactID) {
      $session = CRM_Core_Session::singleton();
      $contactID = NULL;
      if ($session->get('userID')) {
        $contactID = $session->get('userID');
      }
    }

    $key = "{$tableName}_{$type}_{$contactID}";
    if (array_key_exists($key, $cache)) {
      $groups = &$cache[$key];
    }
    else {
      $groups = self::group($type, $contactID, $tableName, $allGroups, $includedGroups);
      $cache[$key] = $groups;
    }

    return in_array($groupID, $groups) ? TRUE : FALSE;
  }
}

