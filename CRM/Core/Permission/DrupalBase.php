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
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Permission_DrupalBase extends CRM_Core_Permission_Base {

  /**
   * Is this user someone with access for the entire system.
   *
   * @var boolean
   */
  protected $_viewAdminUser = FALSE;
  protected $_editAdminUser = FALSE;

  /**
   * Am in in view permission or edit permission?
   * @var boolean
   */
  protected $_viewPermission = FALSE;
  protected $_editPermission = FALSE;

  /**
   * The current set of permissioned groups for the user.
   *
   * @var array
   */
  protected $_viewPermissionedGroups;
  protected $_editPermissionedGroups;

  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType
   *   Type of group(Access/Mailing).
   * @param bool $excludeHidden
   *   Exclude hidden groups.
   *
   *
   * @return array
   *   array reference of all groups.
   */
  public function group($groupType = NULL, $excludeHidden = TRUE) {
    if (!isset($this->_viewPermissionedGroups)) {
      $this->_viewPermissionedGroups = $this->_editPermissionedGroups = array();
    }

    $groupKey = $groupType ? $groupType : 'all';

    if (!isset($this->_viewPermissionedGroups[$groupKey])) {
      $this->_viewPermissionedGroups[$groupKey] = $this->_editPermissionedGroups[$groupKey] = array();

      $groups = CRM_Core_PseudoConstant::allGroup($groupType, $excludeHidden);

      if ($this->check('edit all contacts')) {
        // this is the most powerful permission, so we return
        // immediately rather than dilute it further
        $this->_editAdminUser = $this->_viewAdminUser = TRUE;
        $this->_editPermission = $this->_viewPermission = TRUE;
        $this->_editPermissionedGroups[$groupKey] = $groups;
        $this->_viewPermissionedGroups[$groupKey] = $groups;
        return $this->_viewPermissionedGroups[$groupKey];
      }
      elseif ($this->check('view all contacts')) {
        $this->_viewAdminUser = TRUE;
        $this->_viewPermission = TRUE;
        $this->_viewPermissionedGroups[$groupKey] = $groups;
      }

      $ids = CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_saved_search', $groups);
      if (!empty($ids)) {
        foreach (array_values($ids) as $id) {
          $title = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
          $this->_viewPermissionedGroups[$groupKey][$id] = $title;
          $this->_viewPermission = TRUE;
        }
      }

      $ids = CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_saved_search', $groups);
      if (!empty($ids)) {
        foreach (array_values($ids) as $id) {
          $title = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
          $this->_editPermissionedGroups[$groupKey][$id] = $title;
          $this->_viewPermissionedGroups[$groupKey][$id] = $title;
          $this->_editPermission = TRUE;
          $this->_viewPermission = TRUE;
        }
      }
    }

    return $this->_viewPermissionedGroups[$groupKey];
  }

  /**
   * Get group clause for this user. The group Clause filters the
   * list of groups that the user is permitted to see in a group listing.
   * For example it will filter both the list on the 'Manage Groups' page
   * and on the contact 'Groups' tab
   *
   * the aclGroup hook & configured ACLs contribute to this data.
   * If the contact is allowed to see all contacts the function will return  ( 1 )
   *
   * @todo the history of this function is that there was some confusion as to
   * whether it was filtering contacts or groups & some cruft may remain
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference) add the tables that are needed for the where clause.
   *
   * @return string
   *   the clause to add to the query retrieving viewable groups
   */
  public function groupClause($type, &$tables, &$whereTables) {
    if (!isset($this->_viewPermissionedGroups)) {
      $this->group();
    }

    // we basically get all the groups here
    $groupKey = 'all';
    if ($type == CRM_Core_Permission::EDIT) {
      if ($this->_editAdminUser) {
        $clause = ' ( 1 ) ';
      }
      elseif (empty($this->_editPermissionedGroups[$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = array();
        $groups = implode(', ', $this->_editPermissionedGroups[$groupKey]);
        $clauses[] = ' ( civicrm_group_contact.group_id IN ( ' . implode(', ', array_keys($this->_editPermissionedGroups[$groupKey])) . " ) AND civicrm_group_contact.status = 'Added' ) ";
        $tables['civicrm_group_contact'] = 1;
        $whereTables['civicrm_group_contact'] = 1;

        // foreach group that is potentially a saved search, add the saved search clause
        foreach (array_keys($this->_editPermissionedGroups[$groupKey]) as $id) {
          $group = new CRM_Contact_DAO_Group();
          $group->id = $id;
          if ($group->find(TRUE) && $group->saved_search_id) {
            $clause = CRM_Contact_BAO_SavedSearch::whereClause($group->saved_search_id,
              $tables,
              $whereTables
            );
            if (trim($clause)) {
              $clauses[] = $clause;
            }
          }
        }
        $clause = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }
    else {
      if ($this->_viewAdminUser) {
        $clause = ' ( 1 ) ';
      }
      elseif (empty($this->_viewPermissionedGroups[$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = array();
        $groups = implode(', ', $this->_viewPermissionedGroups[$groupKey]);
        $clauses[] = ' civicrm_group.id IN (' . implode(', ', array_keys($this->_viewPermissionedGroups[$groupKey])) . " )  ";
        $tables['civicrm_group'] = 1;
        $whereTables['civicrm_group'] = 1;
        $clause = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }

    return $clause;
  }

  /**
   * Get the current permission of this user.
   *
   * @return string
   *   the permission of the user (edit or view or null)
   */
  public function getPermission() {
    $this->group();

    if ($this->_editPermission) {
      return CRM_Core_Permission::EDIT;
    }
    elseif ($this->_viewPermission) {
      return CRM_Core_Permission::VIEW;
    }
    return NULL;
  }

  /**
   * @param $uids
   *
   * @return string
   */
  public function getContactEmails($uids) {
    if (empty($uids)) {
      return '';
    }
    $uidString = implode(',', $uids);
    $sql = "
    SELECT     e.email
    FROM       civicrm_contact c
    INNER JOIN civicrm_email e     ON ( c.id = e.contact_id AND e.is_primary = 1 )
    INNER JOIN civicrm_uf_match uf ON ( c.id = uf.contact_id )
    WHERE      c.is_deceased = 0
    AND        c.is_deleted  = 0
    AND        uf.uf_id IN ( $uidString )
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);

    $emails = array();
    while ($dao->fetch()) {
      $emails[] = $dao->email;
    }

    return implode(', ', $emails);
  }

  /**
   * Given a roles array, check for access requirements
   *
   * @param array $array
   *   The roles to check.
   *
   * @return bool
   *   true if yes, else false
   */
  public function checkGroupRole($array) {
    if (function_exists('user_load') && isset($array)) {
      $user = user_load($GLOBALS['user']->uid);
      //if giver roles found in user roles - return true
      foreach ($array as $key => $value) {
        if (in_array($value, $user->roles)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * Get all the contact emails for users that have a specific permission.
   *
   * @param string $permissionName
   *   Name of the permission we are interested in.
   *
   * @return string
   *   a comma separated list of email addresses
   */
  public function permissionEmails($permissionName) {
    static $_cache = array();

    if (isset($_cache[$permissionName])) {
      return $_cache[$permissionName];
    }

    $uids = array();
    $sql = "
      SELECT {users}.uid, {role_permission}.permission
      FROM {users}
      JOIN {users_roles}
        ON {users}.uid = {users_roles}.uid
      JOIN {role_permission}
        ON {role_permission}.rid = {users_roles}.rid
      WHERE {role_permission}.permission = '{$permissionName}'
        AND {users}.status = 1
    ";

    $result = db_query($sql);
    foreach ($result as $record) {
      $uids[] = $record->uid;
    }

    $_cache[$permissionName] = self::getContactEmails($uids);
    return $_cache[$permissionName];
  }

  /**
   * @inheritDoc
   */
  public function upgradePermissions($permissions) {
    if (empty($permissions)) {
      throw new CRM_Core_Exception("Cannot upgrade permissions: permission list missing");
    }
    $query = db_delete('role_permission')
      ->condition('module', 'civicrm')
      ->condition('permission', array_keys($permissions), 'NOT IN');
    $query->execute();
  }

}
