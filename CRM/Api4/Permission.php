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
    $config = CRM_Core_Config::singleton();
    $urlPath = explode('/', $_GET[$config->userFrameworkURLVar]);
    $permissions = [
      ['access CiviCRM', 'access AJAX API'],
    ];
    if (!empty($urlPath[3])) {
      $entity = $urlPath[3];
      $action = $urlPath[4];
      CRM_Utils_Hook::alterApiRoutePermissions($permissions, $entity, $action);
    }
    return CRM_Core_Permission::check($permissions);
  }

}
