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
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms.
 */
class CRM_Pledge_Task extends CRM_Core_Task {

  static $objectType = 'pledge';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function tasks() {
    if (!self::$_tasks) {
      self::$_tasks = array(
        self::TASK_DELETE => array(
          'title' => ts('Delete pledges'),
          'class' => 'CRM_Pledge_Form_Task_Delete',
          'result' => FALSE,
        ),
        self::TASK_PRINT => array(
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Pledge_Form_Task_Print',
          'result' => FALSE,
        ),
        self::TASK_EXPORT => array(
          'title' => ts('Export pledges'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
      );

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
  public static function permissionedTaskTitles($permission, $params = array()) {
    if (($permission == CRM_Core_Permission::EDIT)
      || CRM_Core_Permission::check('edit pledges')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = array(
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
      );
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

    if (!CRM_Utils_Array::value($value, self::$_tasks)) {
      // make it the print task by default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
