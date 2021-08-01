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
 * Class to represent the actions that can be performed on a group of contacts.
 *
 * Used by the search forms
 */
class CRM_Case_Task extends CRM_Core_Task {

  /**
   * Case tasks
   */
  const RESTORE_CASES = 501;

  /**
   * @var string
   */
  public static $objectType = 'case';

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
          'title' => ts('Delete cases'),
          'class' => 'CRM_Case_Form_Task_Delete',
          'result' => FALSE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Case_Form_Task_Print',
          'result' => FALSE,
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export cases'),
          'class' => [
            'CRM_Case_Export_Form_Select',
            'CRM_Case_Export_Form_Map',
          ],
          'result' => FALSE,
        ],
        self::RESTORE_CASES => [
          'title' => ts('Restore cases'),
          'class' => 'CRM_Case_Form_Task_Restore',
          'result' => FALSE,
        ],
        self::PDF_LETTER => [
          'title' => ts('Print/merge document'),
          'class' => 'CRM_Case_Form_Task_PDF',
          'result' => FALSE,
        ],
        self::BATCH_UPDATE => [
          'title' => ts('Update multiple cases'),
          'class' => [
            'CRM_Case_Form_Task_PickProfile',
            'CRM_Case_Form_Task_Batch',
          ],
          'result' => FALSE,
        ],
      ];

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviCase')) {
        unset(self::$_tasks[self::TASK_DELETE]);
      }

      parent::tasks();
    }

    return self::$_tasks;
  }

  /**
   * Show tasks selectively based on the permission level.
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
      || CRM_Core_Permission::check('access all cases and activities')
      || CRM_Core_Permission::check('access my cases and activities')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = [
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
      ];
      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviCase')) {
        $tasks[self::TASK_DELETE] = self::$_tasks[self::TASK_DELETE]['title'];
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks.
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || empty(self::$_tasks[$value])) {
      // make the print task by default
      $value = self::TASK_PRINT;
    }

    return [
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    ];
  }

}
