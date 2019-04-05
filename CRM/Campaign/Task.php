<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * class to represent the actions that can be performed on a group of voters.
 *
 * Used by the search forms.
 */
class CRM_Campaign_Task extends CRM_Core_Task {

  const
    // Campaign tasks
    INTERVIEW = 601,
    RESERVE = 602,
    RELEASE = 603;

  static $objectType = 'campaign';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a voter / group of voters
   *
   * @return array
   *   the set of tasks for a group of voters.
   */
  public static function tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = [
        self::INTERVIEW => [
          'title' => ts('Record Respondents Interview'),
          'class' => [
            'CRM_Campaign_Form_Task_Interview',
            'CRM_Campaign_Form_Task_Release',
          ],
          'result' => FALSE,
        ],
        self::RESERVE => [
          'title' => ts('Reserve Respondents'),
          'class' => [
            'CRM_Campaign_Form_Task_Reserve',
            'CRM_Campaign_Form_Task_Interview',
            'CRM_Campaign_Form_Task_Release',
          ],
          'result' => FALSE,
        ],
        self::RELEASE => [
          'title' => ts('Release Respondents'),
          'class' => 'CRM_Campaign_Form_Task_Release',
          'result' => FALSE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print Respondents'),
          'class' => 'CRM_Campaign_Form_Task_Print',
          'result' => FALSE,
        ],
      ];

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
    $tasks = self::taskTitles();

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on voters.
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of voters.
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // Set the interview task as default
      $value = self::INTERVIEW;
    }

    return [
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    ];
  }

}
