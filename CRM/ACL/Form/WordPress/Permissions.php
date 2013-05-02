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
 * This class provides the functionality to Grant access to CiviCRM components and other CiviCRM permissions.
 */
class CRM_ACL_Form_WordPress_Permissions extends CRM_Core_Form {

  /**
   * Function to build the form
   *
   * @access public
   * @return void
   */
  function buildQuickForm( ) {

    CRM_Utils_System::setTitle( 'Wordpress Access Control' );

    // Get the core permissions array
    $permissionsArray = self::getPermissionArray();

    // Get the wordpress roles, default capabilities and assign to the form
    // TODO: Create a new wordpress role (Anonymous user) and define capabilities in Wordpress Access Control
    global $wp_roles;
    if (!isset($wp_roles)) {
      $wp_roles = new WP_Roles();
    }
    foreach ( $wp_roles->role_names as $role => $name ) {
      // Dont show the permissions options for administrator, as they have all permissions
      if ($role !== 'administrator') {
        $roleObj = $wp_roles->get_role($role);
        if (!empty($roleObj->capabilities)) {
          foreach ($roleObj->capabilities as $ckey => $cname) {
            if (array_key_exists($ckey , $permissionsArray)) {
              $elementName = $role.'['.$ckey.']';
              $defaults[$elementName] = 1;
            }
          }
        }

        // Compose the checkbox array for each role, to assign to form
        $rolePerms[$role] = $permissionsArray;
        foreach ( $rolePerms[$role] as $key => $value) {
          $elementName = $role.'['.$key.']';
          $this->add('checkbox' , $elementName , $value);
        }
        $roles[$role] = $name;
      }
    }

    $this->setDefaults($defaults);

    $this->assign('rolePerms', $rolePerms);
    $this->assign('roles', $roles);

    $this->addButtons(
      array(
        array (
          'type'      => 'next',
          'name'      => ts('Save'),
          'spacing'   => '',
          'isDefault' => false   ),
      )
    );

  }

  /**
   * Function to process the form
   *
   * @access public
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $permissionsArray = self::getPermissionArray();

    // Function to get Wordpress roles
    global $wp_roles;
    if (!isset($wp_roles)) {
      $wp_roles = new WP_Roles();
    }
    foreach ( $wp_roles->role_names as $role => $name ) {
      $roleObj = $wp_roles->get_role($role);

      //Remove all civicrm capabilities for the role, as there may be some capabilities checkbox unticked
      foreach ($permissionsArray as $key => $capability){
        $roleObj->remove_cap($key);
      }

      //Add the selected wordpress capabilities for the role
      $rolePermissions = $params[$role];
      if (!empty($rolePermissions)) {
        foreach ( $rolePermissions as $key => $capability ) {
          $roleObj->add_cap($key);
        }
      }
    }

    // FIXME
    // Changed the 'access_civicrm_nav_link' capability in civicrm.php file
    // But for some reason, if i remove 'Access CiviCRM' administrator and save, it is showing
    // 'You do not have sufficient permissions to access this page'
    // which should not happen for Super Admin and Administrators, as checking permissions for Super
    // Admin and Administrators always gives TRUE
    wp_civicrm_capability();

    CRM_Core_Session::setStatus("", ts('Wordpress Access Control Updated'), "success");

    // rebuild the menus to comply with the new permisssions/capabilites
    CRM_Core_Invoke::rebuildMenuAndCaches( );

    CRM_Utils_System::redirect('admin.php?page=CiviCRM&q=civicrm/admin/access&reset=1');
    CRM_Utils_System::civiExit();
  }

  /**
   * Get the core civicrm permissions array.
   * This function should be shared from a similar one in
   * distmaker/utils/joomlaxml.php
   *
   * @access public
   * @return array   civicrm permissions
   */
  static function getPermissionArray(){
    global $civicrm_root;

    $permissions = CRM_Core_Permission::getCorePermissions();
    $crmFolderDir = $civicrm_root . DIRECTORY_SEPARATOR . 'CRM';

    $components = CRM_Core_Component::getComponentsFromFile($crmFolderDir);
    foreach ($components as $comp) {
      $perm = $comp->getPermissions();
      if ($perm) {
        $info = $comp->getInfo();
        foreach ($perm as $p) {
          $permissions[$p] = $info['translatedName'] . ': ' . $p;
        }
      }
    }

    $perms_array = array();
    foreach ($permissions as $perm => $title) {
      //order matters here, but we deal with that later
      $perms_array[CRM_Utils_String::munge(strtolower($perm))] = $title;
    }

    return $perms_array;
  }
}

