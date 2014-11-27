<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Permission_WordPress extends CRM_Core_Permission_Base {
  /**
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @access public
   */
  function check($str) {
    // Generic cms 'administer users' role tranlates to 'administrator' WordPress role
    $str = $this->translatePermission($str, 'WordPress', array(
      'administer users' => 'administrator',
    ));
    if ($str == CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($str == CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }

    require_once ABSPATH . WPINC . '/pluggable.php';

    // for administrators give them all permissions
    if (!function_exists('current_user_can')) {
      return TRUE;
    }

    if (current_user_can('super admin') || current_user_can('administrator')) {
      return TRUE;
    }

    // Make string lowercase and convert spaces into underscore
    $str = CRM_Utils_String::munge(strtolower($str));

    if ( is_user_logged_in() ) {
      // Check whether the logged in user has the capabilitity
      if (current_user_can($str)) {
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
   * {@inheritDoc}
   */
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  function upgradePermissions($permissions) {
    return;
  }
}
