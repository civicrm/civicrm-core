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

/**
 *
 */
class CRM_Core_Permission_Base {

  /**
   * is this user someone with access for the entire system
   *
   * @var boolean
   */
  protected $_viewAdminUser = FALSE;
  protected $_editAdminUser = FALSE;

  /**
   * am in in view permission or edit permission?
   * @var boolean
   */
  protected $_viewPermission = FALSE;
  protected $_editPermission = FALSE;

  /**
   * the current set of permissioned groups for the user
   *
   * @var array
   */
  protected $_viewPermissionedGroups;
  protected $_editPermissionedGroups;


  /**
   * Translate permission
   *
   * @param string $name e.g. "administer CiviCRM", "cms:access user record", "Drupal:administer content", "Joomla:action:com_asset"
   * @param string $nativePrefix
   * @param array $map array($portableName => $nativeName)
   * @return NULL|string a permission name
   */
  public function translatePermission($perm, $nativePrefix, $map) {
    list ($civiPrefix, $name) = CRM_Utils_String::parsePrefix(':', $perm, NULL);
    switch ($civiPrefix) {
      case $nativePrefix:
        return $name; // pass through
      case 'cms':
        return CRM_Utils_Array::value($name, $map, CRM_Core_Permission::ALWAYS_DENY_PERMISSION);
      case NULL:
        return $name;
      default:
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    }
  }

  /**
   * Get the permissioned where clause for the user
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   *
   * @return string the group where clause for this user
   * @access public
   */
  public function whereClause($type, &$tables, &$whereTables) {
    return '( 1 )';
  }
  /**
   * Get the permissioned where clause for the user when trying to see groups
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   *
   * @return string the group where clause for this user
   * @access public
   */
  public function getPermissionedStaticGroupClause($type, &$tables, &$whereTables) {
    $this->group();
    return $this->groupClause($type, $tables, $whereTables);
  }


  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType     type of group(Access/Mailing)
   * @param boolen $excludeHidden exclude hidden groups.
   *
   * @access public
   *
   * @return array - array reference of all groups.
   *
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
   * @param int $type the type of permission needed
   * @param  array $tables (reference) add the tables that are needed for the select clause
   * @param  array $whereTables (reference) add the tables that are needed for the where clause
   *
   * @return string the clause to add to the query retrieving viewable groups
   * @access public
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
   * get the current permission of this user
   *
   * @return string the permission of the user (edit or view or null)
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

  function getContactEmails($uids) {
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
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @access public
   */

  function check($str) {
    // no default behaviour
  }

  /**
   * Given a roles array, check for access requirements
   *
   * @param array $array the roles to check
   *
   * @return boolean true if yes, else false
   * @access public
   */
  function checkGroupRole($array) {
    return FALSE;
  }

  /**
   * Get all the contact emails for users that have a specific permission
   *
   * @param string $permissionName name of the permission we are interested in
   *
   * @return string a comma separated list of email addresses
   */
  public function permissionEmails($permissionName) {
    CRM_Core_Error::fatal("this function only works in Drupal 6 at the moment");
  }

  /**
   * Get all the contact emails for users that have a specific role
   *
   * @param string $roleName name of the role we are interested in
   *
   * @return string a comma separated list of email addresses
   */
  public function roleEmails($roleName) {
    CRM_Core_Error::fatal("this function only works in Drupal 6 at the moment");
  }

  /**
   * Determine whether the permission store allows us to store
   * a list of permissions generated dynamically (eg by
   * hook_civicrm_permissions.)
   *
   * @return bool
   */
  public function isModulePermissionSupported() {
    return FALSE;
  }

  /**
   * Ensure that the CMS supports all the permissions defined by CiviCRM
   * and its extensions. If there are stale permissions, they should be
   * deleted. This is useful during module upgrade when the newer module
   * version has removed permission that were defined in the older version.
   *
   * @param array $permissions same format as CRM_Core_Permission::getCorePermissions().
   * @see CRM_Core_Permission::getCorePermissions
   */
  function upgradePermissions($permissions) {
    throw new CRM_Core_Exception("Unimplemented method: CRM_Core_Permission_*::upgradePermissions");
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * of the given module.
   *
   * Note: At time of writing, this is only used with native extension-modules, so
   * there's one, predictable calling convention (regardless of CMS).
   *
   * @return Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   * @see CRM_Core_Permission::getCorePermissions
   */
  static function getModulePermissions($module) {
    $return_permissions = array();
    $fn_name = "{$module}_civicrm_permission";
    if (function_exists($fn_name)) {
      $module_permissions = array();
      $fn_name($module_permissions);
      $return_permissions = $module_permissions;
    }
    return $return_permissions;
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * in all enabled CiviCRM module extensions.
   *
   * @return Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   */
  function getAllModulePermissions() {
    $permissions = array();
    CRM_Utils_Hook::permission($permissions);
    return $permissions;
  }
}

