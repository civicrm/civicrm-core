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

    if ($userId !== 0 && ($user->has_cap('super admin') || $user->has_cap('administrator'))) {
      return TRUE;
    }

    // Make string lowercase and convert spaces into underscore
    $str = CRM_Utils_String::munge(strtolower($str));

    if ($userId !== 0 && $user->exists()) {
      // Check whether the logged in user has the capabilitity
      if ($user->has_cap($str)) {
        return TRUE;
      }
    }
    else {
      //check the capabilities of Anonymous user)
      $roleObj = new WP_Roles();
      $anonObj = $roleObj->get_role('anonymous_user');
      if (!empty($anonObj->capabilities) && array_key_exists($str, $anonObj->capabilities)) {
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

    $permissions = parent::getAvailablePermissions();
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
