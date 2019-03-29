<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function upgradePermissions($permissions) {
  }

}
