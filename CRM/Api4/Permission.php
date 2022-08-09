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
 * Class to handle the permission on the api4 AJAX route
 */
class CRM_Api4_Permission {

  public static function check() {
    $urlPath = explode('/', CRM_Utils_System::currentPath());
    $defaultPermissions = [
      ['access CiviCRM', 'access AJAX API'],
    ];
    // Two call formats. Which one was used? Note: CRM_Api4_Permission::check() and CRM_Api4_Page_AJAX::run() should have matching conditionals.
    if (!empty($urlPath[3])) {
      // Received single-call format
      $entity = $urlPath[3];
      $action = $urlPath[4];
      $permissions = $defaultPermissions;
      CRM_Utils_Hook::alterApiRoutePermissions($permissions, $entity, $action);
      return CRM_Core_Permission::check($permissions);
    }
    else {
      // Received multi-call format
      $calls = CRM_Utils_Request::retrieve('calls', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');
      $calls = json_decode($calls, TRUE);
      foreach ($calls as $call) {
        $permissions = $defaultPermissions;
        CRM_Utils_Hook::alterApiRoutePermissions($permissions, $call[0], $call[1]);
        if (!CRM_Core_Permission::check($permissions)) {
          return FALSE;
        }
      }
      return TRUE;
    }
  }

}
