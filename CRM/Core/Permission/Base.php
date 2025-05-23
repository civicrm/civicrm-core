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
      case NULL:
        return $name;

      // pass through
      case 'cms':
        return $map[$name] ?? CRM_Core_Permission::ALWAYS_DENY_PERMISSION;

      default:
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    }
  }

  /**
   * Get the maximum permission of the current user with respect to _any_ contact records.
   *
   * @return int|string|null
   * @see \CRM_Core_Permission::getPermission()
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
    if (!isset(Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId])) {
      Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId] = Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId] = [];
    }

    $groupKey = $groupType ?: 'all';

    if (!isset(Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) {
      Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = [];

      $groups = CRM_Core_PseudoConstant::allGroup($groupType, $excludeHidden);

      if (CRM_Core_Permission::check('edit all contacts')) {
        // this is the most powerful permission, so we return
        // immediately rather than dilute it further
        $this->_editAdminUser = $this->_viewAdminUser = TRUE;
        $this->_editPermission = $this->_viewPermission = TRUE;
        Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = $groups;
        Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = $groups;
        return Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey];
      }
      elseif (CRM_Core_Permission::check('view all contacts')) {
        $this->_viewAdminUser = TRUE;
        $this->_viewPermission = TRUE;
        Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey] = $groups;
      }

      $ids = CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_group', $groups);
      if (!empty($ids)) {
        foreach (array_values($ids) as $id) {
          $title = $groups[$id] ?? CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
          Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey][$id] = $title;
          $this->_viewPermission = TRUE;
        }
      }

      $ids = CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_group', $groups);
      if (!empty($ids)) {
        foreach (array_values($ids) as $id) {
          $title = $groups[$id] ?? CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
          Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey][$id] = $title;
          Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey][$id] = $title;
          $this->_editPermission = TRUE;
          $this->_viewPermission = TRUE;
        }
      }
    }

    return Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey];
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
    if (!isset(Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId])) {
      $this->group();
    }

    // we basically get all the groups here
    $groupKey = 'all';
    if ($type == CRM_Core_Permission::EDIT) {
      if ($this->_editAdminUser) {
        $clause = ' ( 1 ) ';
      }
      elseif (empty(Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = [];
        $groups = implode(', ', Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey]);
        $clauses[] = ' ( civicrm_group_contact.group_id IN ( ' . implode(', ', array_keys(Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) . " ) AND civicrm_group_contact.status = 'Added' ) ";
        $tables['civicrm_group_contact'] = 1;
        $whereTables['civicrm_group_contact'] = 1;

        // foreach group that is potentially a saved search, add the saved search clause
        foreach (array_keys(Civi::$statics['CRM_ACL_API']['editPermissionedGroups_' . $domainId . '_' . $userId][$groupKey]) as $id) {
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
      elseif (empty(Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = [];
        $groups = implode(', ', Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey]);
        $clauses[] = ' civicrm_group.id IN (' . implode(', ', array_keys(Civi::$statics['CRM_ACL_API']['viewPermissionedGroups_' . $domainId . '_' . $userId][$groupKey])) . " )  ";
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
    // These "synthetic" permissions are translated to the relevant CMS permissions in CRM/Core/Permission/*.php
    // using the `translatePermission()` mechanism.
    // EXCEPT in Standalone, where they are not needed
    return [
      'cms:view user account' => [
        'title' => ts('CMS') . ': ' . ts('View user accounts'),
        'description' => ts('View user accounts. (Synthetic permission - adapts to local CMS)'),
        'is_synthetic' => TRUE,
      ],
      'cms:administer users' => [
        'title' => ts('CMS') . ': ' . ts('Administer user accounts'),
        'description' => ts('Administer user accounts. (Synthetic permission - adapts to local CMS)'),
        'is_synthetic' => TRUE,
      ],
      'cms:bypass maintenance mode' => [
        'label' => ts('CMS') . ': ' . ts('Bypass maintenance mode'),
        'description' => ts('Allow to bypass maintenance mode checks - e.g. when using AJAX API'),
        'is_synthetic' => TRUE,
      ],
    ];
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
   * hook_civicrm_permission.)
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
   * @return array
   *   Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   * @throws RuntimeException
   */
  public function getAllModulePermissions(): array {
    $permissions = [];
    CRM_Utils_Hook::permission($permissions);

    // Normalize permission array format.
    // Historically, a string was acceptable (interpreted as label), as was a non-associative array.
    // Convert them all to associative arrays.
    foreach ($permissions as $name => $defn) {
      $defn = (array) $defn;
      if (!isset($defn['label'])) {
        CRM_Core_Error::deprecatedWarning("Permission '$name' should be declared with 'label' and 'description' keys. See https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission/");
      }
      $permission = [
        'label' => $defn['label'] ?? $defn[0],
        'description' => $defn['description'] ?? $defn[1] ?? NULL,
        'disabled' => $defn['disabled'] ?? NULL,
        'implies' => $defn['implies'] ?? NULL,
        'implied_by' => $defn['implied_by'] ?? NULL,
      ];
      $permissions[$name] = array_filter($permission, fn($item) => isset($item));
    }
    return $permissions;
  }

}
