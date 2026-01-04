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

namespace Civi\Api4\Action\Permission;

use Civi\Api4\Generic\BasicGetAction;

/**
 * Get a list of extant permissions.
 *
 * NOTE: This is a high-level API intended for introspective use by administrative tools.
 * It may be poorly suited to recursive usage (e.g. permissions defined dynamically
 * on top of permissions!) or during install/uninstall processes.
 *
 * The list of permissions is generated via hook, and there is a standard/default listener.
 *
 * @see CRM_Core_Permission_List
 * @see \CRM_Utils_Hook::permissionList
 */
class Get extends BasicGetAction {

  /**
   * @return array[]
   */
  protected function getRecords() {
    $cacheKey = 'list_' . $GLOBALS['tsLocale'];
    if (!isset(\Civi::$statics[__CLASS__][$cacheKey])) {
      $perms = [];
      \CRM_Utils_Hook::permissionList($perms);
      foreach ($perms as $permName => $permission) {
        $defaults = [
          'name' => $permName,
          'is_synthetic' => ($permName[0] === '@'),
        ];
        $perms[$permName] = array_merge($defaults, $permission);
      }
      \Civi::$statics[__CLASS__][$cacheKey] = $perms;
    }

    return \Civi::$statics[__CLASS__][$cacheKey];
  }

}
