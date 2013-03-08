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
   * {@inheritDoc}
   */
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  function upgradePermissions($permissions) {
    if (empty($permissions)) {
      throw new CRM_Core_Exception("Cannot upgrade permissions: permission list missing");
    }
    $query = db_delete('role_permission')
      ->condition('module', 'civicrm')
      ->condition('permission', array_keys($permissions), 'NOT IN');
    $query->execute();
  }
}

