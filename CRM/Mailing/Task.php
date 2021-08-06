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
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms.
 *
 */
class CRM_Mailing_Task extends CRM_Core_Task {

  public static $objectType = 'mailing';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts.
   *
   * @return array
   *   the set of tasks for a group of contacts.
   */
  public static function tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = [
        self::TASK_PRINT => [
          'title' => ts('Print Mailing Recipients'),
          'class' => 'CRM_Mailing_Form_Task_Print',
          'result' => FALSE,
        ],
      ];

      parent::tasks();
    }

    return self::$_tasks;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user.
   *
   * @param int $permission
   * @param array $params
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function permissionedTaskTitles($permission, $params = []) {
    $tasks = [];

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform.
   * on mailing recipients.
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of mailing recipients
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || empty(self::$_tasks[$value])) {
      // make the print task by default
      $value = self::TASK_PRINT;
    }

    if (isset(self::$_tasks[$value])) {
      return [[self::$_tasks[$value]['class']], self::$_tasks[$value]['result']];
    }
    return [[], NULL];
  }

}
