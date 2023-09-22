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
 * Class to represent the actions that can be performed on a group of contacts used by the search forms.
 */
class CRM_Activity_Task extends CRM_Core_Task {

  /**
   * @var string
   */
  public static $objectType = 'activity';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts.
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = [
        self::TASK_DELETE => [
          'title' => ts('Delete activities'),
          'class' => 'CRM_Activity_Form_Task_Delete',
          'result' => FALSE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Activity_Form_Task_Print',
          'result' => FALSE,
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export activities'),
          'class' => [
            'CRM_Activity_Export_Form_Select',
            'CRM_Activity_Export_Form_Map',
          ],
          'result' => FALSE,
        ],
        self::BATCH_UPDATE => [
          'title' => ts('Update multiple activities'),
          'class' => [
            'CRM_Activity_Form_Task_PickProfile',
            'CRM_Activity_Form_Task_Batch',
          ],
          'result' => FALSE,
        ],
        self::TASK_EMAIL => [
          'title' => ts('Email - send now (to %1 or less)', [
            1 => Civi::settings()
              ->get('simple_mail_limit'),
          ]),
          'class' => [
            'CRM_Activity_Form_Task_PickOption',
            'CRM_Activity_Form_Task_Email',
          ],
          'result' => FALSE,
        ],
        self::PDF_LETTER => [
          'title' => ts('Print/merge Document'),
          'class' => 'CRM_Activity_Form_Task_PDF',
          'result' => FALSE,
        ],
        self::TASK_SMS => [
          'title' => ts('SMS - send reply'),
          'class' => 'CRM_Activity_Form_Task_SMS',
          'result' => FALSE,
        ],
        self::TAG_ADD => [
          'title' => ts('Tag - add to activities'),
          'class' => 'CRM_Activity_Form_Task_AddToTag',
          'result' => FALSE,
        ],
        self::TAG_REMOVE => [
          'title' => ts('Tag - remove from activities'),
          'class' => 'CRM_Activity_Form_Task_RemoveFromTag',
          'result' => FALSE,
        ],
      ];

      if (CRM_Core_Component::isEnabled('CiviCase')) {
        if (CRM_Core_Permission::check('access all cases and activities') ||
          CRM_Core_Permission::check('access my cases and activities')
        ) {
          self::$_tasks[self::TASK_SMS] = [
            'title' => ts('File on case'),
            'class' => 'CRM_Activity_Form_Task_FileOnCase',
            'result' => FALSE,
          ];
        }
      }

      // CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete activities')) {
        unset(self::$_tasks[self::TASK_DELETE]);
      }

      parent::tasks();
    }

    return self::$_tasks;
  }

  /**
   * Show tasks selectively based on the permission level of the user.
   *
   * @param int $permission
   * @param array $params
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function permissionedTaskTitles($permission, $params = []) {
    if ($permission == CRM_Core_Permission::EDIT) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = [
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
      ];

      //CRM-4418,
      if (CRM_Core_Permission::check('delete activities')) {
        $tasks[self::TASK_DELETE] = self::$_tasks[self::TASK_DELETE]['title'];
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform on activity.
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of activity
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || empty(self::$_tasks[$value])) {
      // make the print task by default
      $value = self::TASK_PRINT;
    }
    if (isset(self::$_tasks[$value])) {
      return [(array) self::$_tasks[$value]['class'], self::$_tasks[$value]['result']];
    }
    return [[], NULL];
  }

}
