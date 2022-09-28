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
 *
 */
class CRM_Core_Permission_Base {

  /**
   * permission mapping to stub check() calls
   * @var array
   */
  public $permissions = NULL;

  /**
   * Is this user someone with access for the entire system.
   *
   * @var bool
   */
  protected $_viewAdminUser = FALSE;
  protected $_editAdminUser = FALSE;

  /**
   * Am in in view permission or edit permission?
   *
   * @var bool
   */
  protected $_viewPermission = FALSE;
  protected $_editPermission = FALSE;

  /**
   * Translate permission.
   *
   * @param string $perm
   *   Permission string e.g "administer CiviCRM", "cms:access user record", "Drupal:administer content",
   *   "Joomla:action:com_asset"
   *
   * @param string $nativePrefix
   * @param array $map
   *   Array($portableName => $nativeName).
   *
   * @return NULL|string
   *   a permission name
   */
  public function translatePermission($perm, $nativePrefix, $map) {
    [$civiPrefix, $name] = CRM_Utils_String::parsePrefix(':', $perm, NULL);
    switch ($civiPrefix) {
      case $nativePrefix:
        return $name;

      // pass through
      case 'cms':
        return CRM_Utils_Array::value($name, $map, CRM_Core_Permission::ALWAYS_DENY_PERMISSION);

      case NULL:
        return $name;

      default:
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    }
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
   * Get the permissioned where clause for the user.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the group where clause for this user
   */
  public function whereClause($type, &$tables, &$whereTables) {
    return '( 1 )';
  }

  /**
   * Get the permissioned where clause for the user when trying to see groups.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the group where clause for this user
   */
  public function getPermissionedStaticGroupClause($type, &$tables, &$whereTables) {
    $this->group();
    return $this->groupClause($type, $tables, $whereTables);
  }

  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType
   *   Type of group(Access/Mailing).
   * @param bool $excludeHidden
   *   exclude hidden groups.
   *
   *
   * @return array
   *   array reference of all groups.
   */
  public function group($groupType = NULL, $excludeHidden = TRUE) {
    $userId = CRM_Core_Session::getLoggedInContactID();
    $domainId = CRM_Core_Config::domainID();
    if (!isset(Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId])) {
      Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId] = Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId] = [];
    }

    $groupKey = $groupType ? $groupType : 'all';

    if (!isset(Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) {
      Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = [];

      $groups = CRM_Core_PseudoConstant::allGroup($groupType, $excludeHidden);

      if ($this->check('edit all contacts')) {
        // this is the most powerful permission, so we return
        // immediately rather than dilute it further
        $this->_editAdminUser = $this->_viewAdminUser = TRUE;
        $this->_editPermission = $this->_viewPermission = TRUE;
        Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = $groups;
        Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = $groups;
        return Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey];
      }
      elseif ($this->check('view all contacts')) {
        $this->_viewAdminUser = TRUE;
        $this->_viewPermission = TRUE;
        Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = $groups;
      }

      $ids = CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_saved_search', $groups);
      if (!empty($ids)) {
        foreach (array_values($ids) as $id) {
          $title = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
          Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey][$id] = $title;
          $this->_viewPermission = TRUE;
        }
      }

      $ids = CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_saved_search', $groups);
      if (!empty($ids)) {
        foreach (array_values($ids) as $id) {
          $title = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
          Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey][$id] = $title;
          Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey][$id] = $title;
          $this->_editPermission = TRUE;
          $this->_viewPermission = TRUE;
        }
      }
    }

    return Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey];
  }

  /**
   * Get group clause for this user.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the group where clause for this user
   */
  public function groupClause($type, &$tables, &$whereTables) {
    $userId = CRM_Core_Session::getLoggedInContactID();
    $domainId = CRM_Core_Config::domainID();
    if (!isset(Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId])) {
      $this->group();
    }

    // we basically get all the groups here
    $groupKey = 'all';
    if ($type == CRM_Core_Permission::EDIT) {
      if ($this->_editAdminUser) {
        $clause = ' ( 1 ) ';
      }
      elseif (empty(Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = [];
        $groups = implode(', ', Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey]);
        $clauses[] = ' ( civicrm_group_contact.group_id IN ( ' . implode(', ', array_keys(Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) . " ) AND civicrm_group_contact.status = 'Added' ) ";
        $tables['civicrm_group_contact'] = 1;
        $whereTables['civicrm_group_contact'] = 1;

        // foreach group that is potentially a saved search, add the saved search clause
        foreach (array_keys(Civi::$statics[__CLASS__]['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey]) as $id) {
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
      elseif (empty(Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = [];
        $groups = implode(', ', Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey]);
        $clauses[] = ' civicrm_group.id IN (' . implode(', ', array_keys(Civi::$statics[__CLASS__]['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) . " )  ";
        $tables['civicrm_group'] = 1;
        $whereTables['civicrm_group'] = 1;
        $clause = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }
    return $clause;
  }

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   * @param int $userId
   *
   * @return bool;
   *
   */
  public function check($str, $userId = NULL) {
    //no default behaviour
    return FALSE;
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
    return FALSE;
  }

  /**
   * Get the palette of available permissions in the CMS's user-management system.
   *
   * @return array
   *   List of permissions, keyed by symbolic name. Each item may have fields:
   *     - title: string
   *     - description: string
   *
   *   The permission-name should correspond to the Civi notation used by
   *   'CRM_Core_Permission::check()'. For CMS-specific permissions, these are
   *   translated names (eg "WordPress:list_users" or "Drupal:post comments").
   *
   *   The list should include *only* CMS permissions. Exclude Civi-native permissions.
   *
   * @see \CRM_Core_Permission_Base::translatePermission()
   */
  public function getAvailablePermissions() {
    return [];
  }

  /**
   * Get all the contact emails for users that have a specific permission.
   *
   * @param string $permissionName
   *   Name of the permission we are interested in.
   *
   * @throws CRM_Core_Exception.
   */
  public function permissionEmails($permissionName) {
    throw new CRM_Core_Exception("this function only works in Drupal 6 at the moment");
  }

  /**
   * Get all the contact emails for users that have a specific role.
   *
   * @param string $roleName
   *   Name of the role we are interested in.
   *
   * @throws CRM_Core_Exception.
   */
  public function roleEmails($roleName) {
    throw new CRM_Core_Exception("this function only works in Drupal 6 at the moment");
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
   * @param array $permissions
   *   Same format as CRM_Core_Permission::getCorePermissions().
   *
   * @throws CRM_Core_Exception
   * @see CRM_Core_Permission::getCorePermissions
   */
  public function upgradePermissions($permissions) {
    throw new CRM_Core_Exception("Unimplemented method: CRM_Core_Permission_*::upgradePermissions");
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * of the given module.
   *
   * Note: At time of writing, this is only used with native extension-modules, so
   * there's one, predictable calling convention (regardless of CMS).
   *
   * @param $module
   *
   * @return array
   *   Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   * @see CRM_Core_Permission::getCorePermissions
   */
  public function getModulePermissions($module): array {
    $return_permissions = [];
    $fn_name = "{$module}_civicrm_permission";
    if (function_exists($fn_name)) {
      $module_permissions = [];
      $fn_name($module_permissions);
      $return_permissions = $module_permissions;
    }
    return $return_permissions;
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * in all enabled CiviCRM module extensions.
   *
   * @param bool $descriptions
   *
   * @return array
   *   Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   */
  public function getAllModulePermissions($descriptions = FALSE): array {
    $permissions = [];
    CRM_Utils_Hook::permission($permissions);

    if ($descriptions) {
      foreach ($permissions as $permission => $label) {
        $permissions[$permission] = (is_array($label)) ? $label : [$label];
      }
    }
    else {
      // Passing in false here is to be deprecated.
      foreach ($permissions as $permission => $label) {
        $permissions[$permission] = (is_array($label)) ? array_shift($label) : $label;
      }
    }
    return $permissions;
  }

}
