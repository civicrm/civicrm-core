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
 * class to represent the actions that can be performed on a group of voters.
 *
 * Used by the search forms.
 */
class CRM_Campaign_Task extends CRM_Core_Task {

  /**
   * Campaign tasks
   */
  const
    INTERVIEW = 601,
    RESERVE = 602,
    RELEASE = 603;

  /**
   * @var string
   */
  public static $objectType = 'campaign';

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
    if (!$value || empty(self::$_tasks[$value])) {
      // Set the interview task as default
      $value = self::INTERVIEW;
    }

    return [
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    ];
  }

}
