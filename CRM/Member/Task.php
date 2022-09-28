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
        'permissions' => ['delete in CiviMember'],
        'result' => FALSE,
        // Hopefully transitional key - if permission to edit the contact also required.
        'requires_edit_contact_permission' => FALSE,
      ],
      self::TASK_PRINT => [
        'title' => ts('Print selected rows'),
        'class' => 'CRM_Member_Form_Task_Print',
        'result' => FALSE,
        'permissions' => [['view memberships', 'edit memberships']],
        // Transitional key. May change.
        'requires_edit_contact_permission' => FALSE,
      ],
      self::TASK_EXPORT => [
        'title' => ts('Export members'),
        'class' => [
          'CRM_Member_Export_Form_Select',
          'CRM_Member_Export_Form_Map',
        ],
        'permissions' => [['view memberships', 'edit memberships']],
        // Transitional key. May change.
        'requires_edit_contact_permission' => FALSE,
        'result' => FALSE,
      ],
      self::TASK_EMAIL => [
        'title' => ts('Email - send now (to %1 or less)', [
          1 => Civi::settings()
            ->get('simple_mail_limit'),
        ]),
        'class' => 'CRM_Member_Form_Task_Email',
        'result' => TRUE,
        'permissions' => ['edit memberships'],
        // Transitional key. May change.
        'requires_edit_contact_permission' => TRUE,
      ],
      self::BATCH_UPDATE => [
        'title' => ts('Update multiple memberships'),
        'class' => [
          'CRM_Member_Form_Task_PickProfile',
          'CRM_Member_Form_Task_Batch',
        ],
        'permissions' => ['edit memberships'],
        // Transitional key. May change.
        'requires_edit_contact_permission' => TRUE,
        'result' => TRUE,
      ],
      self::LABEL_MEMBERS => [
        'title' => ts('Mailing labels - print'),
        'class' => [
          'CRM_Member_Form_Task_Label',
        ],
        'permissions' => ['edit memberships'],
        // Transitional key. May change.
        'requires_edit_contact_permission' => TRUE,
        'result' => TRUE,
      ],
      self::PDF_LETTER => [
        'title' => ts('Print/merge document for memberships'),
        'class' => 'CRM_Member_Form_Task_PDFLetter',
        'result' => FALSE,
        'permissions' => ['edit memberships'],
        // Transitional key. May change.
        'requires_edit_contact_permission' => TRUE,
      ],
      self::SAVE_SEARCH => [
        'title' => ts('Group - create smart group'),
        'class' => 'CRM_Contact_Form_Task_SaveSearch',
        'result' => TRUE,
        'permissions' => ['edit groups'],
        // Transitional key. May change.
        'requires_edit_contact_permission' => FALSE,
      ],
      self::SAVE_SEARCH_UPDATE => [
        'title' => ts('Group - update smart group'),
        'class' => 'CRM_Contact_Form_Task_SaveSearch_Update',
        'result' => TRUE,
        'permissions' => ['edit groups'],
        // Transitional key. May change.
        'requires_edit_contact_permission' => FALSE,
      ],
    ];
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
    $tasks = self::getTitlesFilteredByPermission(self::tasks(), $permission === CRM_Core_Permission::EDIT);
    return parent::corePermissionedTaskTitles($tasks, $permission, $params);
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
    if (!$value || empty(self::tasks()[$value])) {
      // Make the print task the default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
