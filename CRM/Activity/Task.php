<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class to represent the actions that can be performed on a group of contacts used by the search forms.
 */
class CRM_Activity_Task extends CRM_Core_Task {

  static $objectType = 'activity';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts.
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = array(
        self::TASK_DELETE => array(
          'title' => ts('Delete activities'),
          'class' => 'CRM_Activity_Form_Task_Delete',
          'result' => FALSE,
        ),
        self::TASK_PRINT => array(
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Activity_Form_Task_Print',
          'result' => FALSE,
        ),
        self::TASK_EXPORT => array(
          'title' => ts('Export activities'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
        self::BATCH_UPDATE => array(
          'title' => ts('Update multiple activities'),
          'class' => array(
            'CRM_Activity_Form_Task_PickProfile',
            'CRM_Activity_Form_Task_Batch',
          ),
          'result' => FALSE,
        ),
        self::TASK_EMAIL => array(
          'title' => ts('Email - send now (to %1 or less)', array(
            1 => Civi::settings()
              ->get('simple_mail_limit'),
          )),
          'class' => array(
            'CRM_Activity_Form_Task_PickOption',
            'CRM_Activity_Form_Task_Email',
          ),
          'result' => FALSE,
        ),
        self::TASK_SMS => array(
          'title' => ts('SMS - send reply'),
          'class' => 'CRM_Activity_Form_Task_SMS',
          'result' => FALSE,
        ),
        self::TAG_ADD => array(
          'title' => ts('Tag - add to activities'),
          'class' => 'CRM_Activity_Form_Task_AddToTag',
          'result' => FALSE,
        ),
        self::TAG_REMOVE => array(
          'title' => ts('Tag - remove from activities'),
          'class' => 'CRM_Activity_Form_Task_RemoveFromTag',
          'result' => FALSE,
        ),
      );

      $config = CRM_Core_Config::singleton();
      if (in_array('CiviCase', $config->enableComponents)) {
        if (CRM_Core_Permission::check('access all cases and activities') ||
          CRM_Core_Permission::check('access my cases and activities')
        ) {
          self::$_tasks[self::TASK_SMS] = array(
            'title' => ts('File on case'),
            'class' => 'CRM_Activity_Form_Task_FileOnCase',
            'result' => FALSE,
          );
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
  public static function permissionedTaskTitles($permission, $params = array()) {
    if ($permission == CRM_Core_Permission::EDIT) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = array(
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
      );

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
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the print task by default
      $value = self::TASK_PRINT;
    }

    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }

}
