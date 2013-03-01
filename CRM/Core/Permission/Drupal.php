<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
class CRM_Core_Permission_Drupal extends CRM_Core_Permission_DrupalBase{

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
   * Given a roles array, check for access requirements
   *
   * @param array $array the roles to check
   *
   * @return boolean true if yes, else false
   * @access public
   */
  function checkGroupRole($array) {
    if (function_exists('user_load') && isset($array)) {
      $user = user_load( $GLOBALS['user']->uid);
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
   * Remove all vestiges of permissions for the given module.
   */
  function uninstallPermissions($module) {
    db_delete('role_permission')
      ->condition('permission', "$module|%", 'LIKE')
      ->condition('module', 'civicrm')
      ->execute();
  }

  /**
   * Ensure that all cached permissions associated with the given module are
   * actually defined by that module. This is useful during module upgrade
   * when the newer module version has removed permission that were defined
   * in the older version.
   */
  function upgradePermissions($module) {
    $config = CRM_Core_Config::singleton();
    // Get all permissions defined by the module.
    $module_permissions = $config->userPermissionClass->getModulePermissions($module);
    // Construct a delete query to remove permissions for this module.
    $query = db_delete('role_permission')
      ->condition('permission', "$module|%", 'LIKE')
      ->condition('module', 'civicrm');
    // Only if the module defines any permissions, exempt those from the delete
    // process. This approach allows us to delete all permissions for the module
    // even if the hook_civicrm_permisssion() implementation has been removed.
    if (!empty($module_permissions)) {
      $query->condition('permission', array_keys($module_permissions), 'NOT IN');
    }
    $query->execute();
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * of the given module. Permission keys are prepended with the module name
   * to facilitate cleanup of permissions later, and may be hashed to provide
   * a unique value that fits storage limitations within Drupal 7.
   * 
   * @return Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   */
  static function getModulePermissions($module) {
    $return_permissions = array();
    $fn_name = "{$module}_civicrm_permission";
    if (function_exists($fn_name)) {
      $module_permissions = array();
      $fn_name($module_permissions);
      foreach ($module_permissions as $key => $label) {
        // Prepend the module name to the key.
        $new_key = "$module|$key";
        // Limit key length to maintain compatilibility with Drupal, which
        // accepts permission keys no longer than 128 characters.
        if (strlen($new_key) > 128) {
          $new_key = "$module|". md5($key);
        }
        $return_permissions[$new_key] = $label;
      }
    }
    return $return_permissions;
  }
}

