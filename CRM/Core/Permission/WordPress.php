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
class CRM_Core_Permission_WordPress extends CRM_Core_Permission_Base {

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
      $this->_viewPermissionedGroups = $this->_editPermissionedGroups = [];
    }

    $groupKey = $groupType ? $groupType : 'all';

    if (!isset($this->_viewPermissionedGroups[$groupKey])) {
      $this->_viewPermissionedGroups[$groupKey] = $this->_editPermissionedGroups[$groupKey] = [];

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
        $clauses = [];
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
        $clauses = [];
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
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   * @param int $userId
   *
   * @return bool
   *   true if yes, else false
   */
  public function check($str, $userId = NULL) {
    // Generic cms 'administer users' role tranlates to users with the 'edit_users' capability' in WordPress
    $str = $this->translatePermission($str, 'WordPress', [
      'administer users' => 'edit_users',
    ]);
    if ($str == CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($str == CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }

    // CRM-15629
    // During some extern/* calls we don't bootstrap CMS hence
    // below constants are not set. In such cases, we don't need to
    // check permission, hence directly return TRUE
    if (!defined('ABSPATH') || !defined('WPINC')) {
      require_once 'CRM/Utils/System.php';
      CRM_Utils_System::loadBootStrap();
    }

    require_once ABSPATH . WPINC . '/pluggable.php';

    // for administrators give them all permissions
    if (!function_exists('current_user_can')) {
      return TRUE;
    }

    $user = $userId ? get_userdata($userId) : wp_get_current_user();

    if ($user->has_cap('super admin') || $user->has_cap('administrator')) {
      return TRUE;
    }

    // Make string lowercase and convert spaces into underscore
    $str = CRM_Utils_String::munge(strtolower($str));

    if ($user->exists()) {
      // Check whether the logged in user has the capabilitity
      if ($user->has_cap($str)) {
        return TRUE;
      }
    }
    else {
      //check the capabilities of Anonymous user)
      $roleObj = new WP_Roles();
      if (
        $roleObj->get_role('anonymous_user') != NULL &&
        array_key_exists($str, $roleObj->get_role('anonymous_user')->capabilities)
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getAvailablePermissions() {
    // We want to list *only* WordPress perms, so we'll *skip* Civi perms.
    $mungedCorePerms = array_map(
      function($str) {
        return CRM_Utils_String::munge(strtolower($str));
      },
      array_keys(\CRM_Core_Permission::basicPermissions(TRUE))
    );

    // WP doesn't have an API to list all capabilities. However, we can discover a
    // pretty good list by inspecting the (super)admin roles.
    $wpCaps = [];
    foreach (wp_roles()->roles as $wpRole) {
      $wpCaps = array_unique(array_merge(array_keys($wpRole['capabilities']), $wpCaps));
    }

    $permissions = [];
    foreach ($wpCaps as $wpCap) {
      if (!in_array($wpCap, $mungedCorePerms)) {
        $permissions["WordPress:$wpCap"] = [
          'title' => "WordPress: $wpCap",
        ];
      }
    }
    return $permissions;
  }

  /**
   * @inheritDoc
   */
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function upgradePermissions($permissions) {
  }

}
