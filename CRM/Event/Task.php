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
 * used by the search forms
 *
 */
class CRM_Event_Task extends CRM_Core_Task {

  /**
   * Event tasks
   */
  const
    CANCEL_REGISTRATION = 301,
    PARTICIPANT_STATUS = 302;

  /**
   * @var string
   */
  public static $objectType = 'event';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array
   *   The set of tasks for a group of contacts
   *    [ 'title' => The Task title,
   *      'class' => The Task Form class name,
   *      'result => Boolean.  FIXME: Not sure what this is for
   *    ]
   */
  public static function tasks() {
    if (!self::$_tasks) {
      self::$_tasks = [
        self::TASK_DELETE => [
          'title' => ts('Delete participants from event'),
          'class' => 'CRM_Event_Form_Task_Delete',
          'result' => FALSE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Event_Form_Task_Print',
          'result' => FALSE,
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export participants'),
          'class' => [
            'CRM_Event_Export_Form_Select',
            'CRM_Event_Export_Form_Map',
          ],
          'result' => FALSE,
        ],
        self::BATCH_UPDATE => [
          'title' => ts('Update multiple participants'),
          'class' => [
            'CRM_Event_Form_Task_PickProfile',
            'CRM_Event_Form_Task_Batch',
          ],
          'result' => TRUE,
        ],
        self::CANCEL_REGISTRATION => [
          'title' => ts('Cancel registration'),
          'class' => 'CRM_Event_Form_Task_Cancel',
          'result' => FALSE,
        ],
        self::TASK_EMAIL => [
          'title' => ts('Email - send now (to %1 or less)', [
            1 => Civi::settings()
              ->get('simple_mail_limit'),
          ]),
          'class' => 'CRM_Event_Form_Task_Email',
          'result' => TRUE,
        ],
        self::SAVE_SEARCH => [
          'title' => ts('Group - create smart group'),
          'class' => 'CRM_Event_Form_Task_SaveSearch',
          'result' => TRUE,
        ],
        self::SAVE_SEARCH_UPDATE => [
          'title' => ts('Group - update smart group'),
          'class' => 'CRM_Event_Form_Task_SaveSearch_Update',
          'result' => TRUE,
        ],
        self::PARTICIPANT_STATUS => [
          'title' => ts('Participant status - change'),
          'class' => 'CRM_Event_Form_Task_ParticipantStatus',
          'result' => TRUE,
        ],
        self::LABEL_CONTACTS => [
          'title' => ts('Name badges - print'),
          'class' => 'CRM_Event_Form_Task_Badge',
          'result' => FALSE,
        ],
        self::PDF_LETTER => [
          'title' => ts('PDF letter - print for participants'),
          'class' => 'CRM_Event_Form_Task_PDF',
          'result' => TRUE,
        ],
        self::GROUP_ADD => [
          'title' => ts('Group - add contacts'),
          'class' => 'CRM_Event_Form_Task_AddToGroup',
          'result' => FALSE,
        ],
      ];

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviEvent')) {
        unset(self::$_tasks[self::TASK_DELETE]);
      }
      //CRM-12920 - check for edit permission
      if (!CRM_Core_Permission::check('edit event participants')) {
        unset(self::$_tasks[self::BATCH_UPDATE], self::$_tasks[self::CANCEL_REGISTRATION], self::$_tasks[self::PARTICIPANT_STATUS]);
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
      || CRM_Core_Permission::check('edit event participants')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = [
        self::TASK_EXPORT => self::tasks()[self::TASK_EXPORT]['title'],
        self::TASK_EMAIL => self::tasks()[self::TASK_EMAIL]['title'],
      ];

      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviEvent')) {
        $tasks[self::TASK_DELETE] = self::tasks()[self::TASK_DELETE]['title'];
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on participants
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of participants
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || empty(self::$_tasks[$value])) {
      // make the print task by default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
