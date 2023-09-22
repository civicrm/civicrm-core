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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms.
 */
class CRM_Pledge_Task extends CRM_Core_Task {

  public static $objectType = 'pledge';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function tasks() {
    if (!self::$_tasks) {
      self::$_tasks = [
        self::TASK_DELETE => [
          'title' => ts('Delete pledges'),
          'class' => 'CRM_Pledge_Form_Task_Delete',
          'result' => FALSE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Pledge_Form_Task_Print',
          'result' => FALSE,
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export pledges'),
          'class' => [
            'CRM_Pledge_Export_Form_Select',
            'CRM_Pledge_Export_Form_Map',
          ],
          'result' => FALSE,
        ],
      ];

      // CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviPledge')) {
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
      || CRM_Core_Permission::check('edit pledges')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = [
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
      ];
      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviPledge')) {
        $tasks[self::TASK_DELETE] = self::$_tasks[self::TASK_DELETE]['title'];
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on pledges.
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of pledge holders
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
