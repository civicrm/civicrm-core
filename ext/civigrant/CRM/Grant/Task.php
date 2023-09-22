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
 * used by the search forms
 *
 */
class CRM_Grant_Task extends CRM_Core_Task {

  /**
   * Grant Tasks
   */
  const UPDATE_GRANTS = 701;

  /**
   * @var string
   */
  public static $objectType = 'grant';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = [
        self::TASK_DELETE => [
          'title' => ts('Delete grants'),
          'class' => 'CRM_Grant_Form_Task_Delete',
          'result' => FALSE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Grant_Form_Task_Print',
          'result' => FALSE,
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export grants'),
          'class' => [
            'CRM_Grant_Export_Form_Select',
            'CRM_Grant_Export_Form_Map',
          ],
          'result' => FALSE,
        ],
        self::UPDATE_GRANTS => [
          'title' => ts('Update grants'),
          'class' => 'CRM_Grant_Form_Task_Update',
          'result' => FALSE,
        ],
      ];

      if (!CRM_Core_Permission::check('delete in CiviGrant')) {
        unset(self::$_tasks[self::TASK_DELETE]);
      }

      parent::tasks();
    }

    return self::$_tasks;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   * @param array $params
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function permissionedTaskTitles($permission, $params = []) {
    if (($permission == CRM_Core_Permission::EDIT)
      || CRM_Core_Permission::check('edit grants')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = [
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
      ];
      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviGrant')) {
        $tasks[self::TASK_DELETE] = self::$_tasks[self::TASK_DELETE]['title'];
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function getTask($value) {
    self::tasks();

    if (empty(self::$_tasks[$value])) {
      // make it the print task by default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
