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
 * Class to represent the actions that can be performed on a group of contacts used by the search forms.
 */
class CRM_Contact_CustomSearchTask extends CRM_Core_Task {

  /**
   * @var string
   */
  public static $objectType = 'contact';

  public static function tasks() {
    if (!self::$_tasks) {
      parent::tasks();
    }
    return self::$_tasks;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user
   * This function should be overridden by the child class which would normally call parent::corePermissionedTaskTitles
   *
   * @param int $permission
   * @param array $params
   *             "ssID: Saved Search ID": If !empty we are in saved search context
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function permissionedTaskTitles($permission, $params) {
    $tasks = self::taskTitles();
    if (!is_array($tasks)) {
      $tasks = array();
    }
    return self::corePermissionedTaskTitles($tasks, $permission, $params);
  }

}
