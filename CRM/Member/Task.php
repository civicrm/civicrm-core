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
 * class to represent the actions that can be performed on a group of
 * contacts (CiviMember)
 * used by the search forms
 *
 */
class CRM_Member_Task extends CRM_Core_Task {
  /**
   * Member tasks
   */
  const LABEL_MEMBERS = 201;

  /**
   * @var string
   */
  public static $objectType = 'membership';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function tasks() {
    self::$_tasks = [
      self::TASK_DELETE => [
        'title' => ts('Delete memberships'),
        'class' => 'CRM_Member_Form_Task_Delete',
        'result' => FALSE,
      ],
      self::TASK_PRINT => [
        'title' => ts('Print selected rows'),
        'class' => 'CRM_Member_Form_Task_Print',
        'result' => FALSE,
      ],
      self::TASK_EXPORT => [
        'title' => ts('Export members'),
        'class' => [
          'CRM_Member_Export_Form_Select',
          'CRM_Member_Export_Form_Map',
        ],
        'result' => FALSE,
      ],
      self::TASK_EMAIL => [
        'title' => ts('Email - send now (to %1 or less)', [
          1 => Civi::settings()
            ->get('simple_mail_limit'),
        ]),
        'class' => 'CRM_Member_Form_Task_Email',
        'result' => TRUE,
      ],
      self::BATCH_UPDATE => [
        'title' => ts('Update multiple memberships'),
        'class' => [
          'CRM_Member_Form_Task_PickProfile',
          'CRM_Member_Form_Task_Batch',
        ],
        'result' => TRUE,
      ],
      self::LABEL_MEMBERS => [
        'title' => ts('Mailing labels - print'),
        'class' => [
          'CRM_Member_Form_Task_Label',
        ],
        'result' => TRUE,
      ],
      self::PDF_LETTER => [
        'title' => ts('Print/merge document for memberships'),
        'class' => 'CRM_Member_Form_Task_PDFLetter',
        'result' => FALSE,
      ],
      self::SAVE_SEARCH => [
        'title' => ts('Group - create smart group'),
        'class' => 'CRM_Contact_Form_Task_SaveSearch',
        'result' => TRUE,
      ],
      self::SAVE_SEARCH_UPDATE => [
        'title' => ts('Group - update smart group'),
        'class' => 'CRM_Contact_Form_Task_SaveSearch_Update',
        'result' => TRUE,
      ],
    ];

    //CRM-4418, check for delete
    if (!CRM_Core_Permission::check('delete in CiviMember')) {
      unset(self::$_tasks[self::TASK_DELETE]);
    }
    //CRM-12920 - check for edit permission
    if (!CRM_Core_Permission::check('edit memberships')) {
      unset(self::$_tasks[self::BATCH_UPDATE]);
    }

    parent::tasks();

    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * on members
   *
   * @return array
   *   the set of task titles
   */
  public static function taskTitles() {
    return parent::taskTitles();
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
      || CRM_Core_Permission::check('edit memberships')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = [
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
        self::TASK_EMAIL => self::$_tasks[self::TASK_EMAIL]['title'],
      ];
      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviMember')) {
        $tasks[self::TASK_DELETE] = self::$_tasks[self::TASK_DELETE]['title'];
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on members
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of members
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || empty(self::$_tasks[$value])) {
      // Make the print task the default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
