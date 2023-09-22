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

use Civi\Core\Event\GenericHookEvent;

/**
 * Class CRM_Core_Permission_List
 *
 * When presenting the administrator with a list of available permissions (`Permission.get`),
 * the methods in provide the default implementations.
 *
 * These methods are not intended for public consumption or frequent execution.
 *
 * @see \Civi\Api4\Action\Permission\Get
 */
class CRM_Core_Permission_List {

  /**
   * Enumerate concrete permissions that originate in CiviCRM (core or extension).
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::permissionList
   */
  public static function findCiviPermissions(GenericHookEvent $e) {
    $activeCorePerms = \CRM_Core_Permission::basicPermissions(FALSE);
    $allCorePerms = \CRM_Core_Permission::basicPermissions(TRUE, TRUE);
    foreach ($allCorePerms as $permName => $corePerm) {
      $e->permissions[$permName] = [
        'group' => 'civicrm',
        'title' => $corePerm['label'] ?? $corePerm[0] ?? $permName,
        'description' => $corePerm['description'] ?? $corePerm[1] ?? NULL,
        'is_active' => isset($activeCorePerms[$permName]),
      ];
    }
  }

  /**
   * Enumerate permissions that originate in the CMS (core or module/plugin),
   * excluding any Civi permissions.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::permissionList
   */
  public static function findCmsPermissions(GenericHookEvent $e) {
    $config = \CRM_Core_Config::singleton();

    $ufPerms = $config->userPermissionClass->getAvailablePermissions();
    foreach ($ufPerms as $permName => $cmsPerm) {
      $e->permissions[$permName] = [
        'group' => 'cms',
        'title' => $cmsPerm['title'] ?? $permName,
        'description' => $cmsPerm['description'] ?? NULL,
      ];
    }

    // There are a handful of special permissions defined in CRM/Core/Permission/*.php
    // using the `translatePermission()` mechanism.
    $e->permissions['cms:view user account'] = [
      'group' => 'cms',
      'title' => ts('CMS') . ': ' . ts('View user accounts'),
      'description' => ts('View user accounts. (Synthetic permission - adapts to local CMS)'),
      'is_synthetic' => TRUE,
    ];
    $e->permissions['cms:administer users'] = [
      'group' => 'cms',
      'title' => ts('CMS') . ': ' . ts('Administer user accounts'),
      'description' => ts('Administer user accounts. (Synthetic permission - adapts to local CMS)'),
      'is_synthetic' => TRUE,
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::permissionList
   */
  public static function findConstPermissions(GenericHookEvent $e) {
    // There are a handful of special permissions defined in CRM/Core/Permission.
    $e->permissions[\CRM_Core_Permission::ALWAYS_DENY_PERMISSION] = [
      'group' => 'const',
      'title' => ts('Generic: Deny all users'),
      'is_synthetic' => TRUE,
    ];
    $e->permissions[\CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION] = [
      'group' => 'const',
      'title' => ts('Generic: Allow all users (including anonymous)'),
      'is_synthetic' => TRUE,
    ];
  }

}
