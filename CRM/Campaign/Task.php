<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * class to represent the actions that can be performed on a
 * group of voters.
 * used by the search forms
 *
 */
class CRM_Campaign_Task {
  CONST INTERVIEW = 1, RESERVE = 2, RELEASE = 3, PRINT_VOTERS = 4;

  /**
   * the task array
   *
   * @var array
   * @static
   */
  static $_tasks = NULL;

  /**
   * the optional task array
   *
   * @var array
   * @static
   */
  static $_optionalTasks = NULL;

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a voter / group of voters
   *
   * @return array the set of tasks for a group of voters.
   * @static
   * @access public
   */
  static function &tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = array(1 => array(
        'title' => ts('Record Respondents Interview'),
          'class' => array(
            'CRM_Campaign_Form_Task_Interview',
            'CRM_Campaign_Form_Task_Release',
          ),
          'result' => FALSE,
        ),
        2 => array(
          'title' => ts('Reserve Respondents'),
          'class' => array(
            'CRM_Campaign_Form_Task_Reserve',
            'CRM_Campaign_Form_Task_Interview',
            'CRM_Campaign_Form_Task_Release',
          ),
          'result' => FALSE,
        ),
        3 => array(
          'title' => ts('Release Respondents'),
          'class' => 'CRM_Campaign_Form_Task_Release',
          'result' => FALSE,
        ),
        4 => array(
          'title' => ts('Print Respondents'),
          'class' => 'CRM_Campaign_Form_Task_Print',
          'result' => FALSE,
        ),
      );
    }

    CRM_Utils_Hook::searchTasks('campaign', self::$_tasks);

    asort(self::$_tasks);

    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * on voters.
   *
   * @return array the set of task titles
   * @static
   * @access public
   */
  static function &taskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_tasks as $id => $value) {
      $titles[$id] = $value['title'];
    }

    return $titles;
  }

  /**
   * show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   *
   * @return array set of tasks that are valid for the user
   * @access public
   */
  static function &permissionedTaskTitles($permission) {
    $tasks = self::taskTitles();

    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on voters.
   *
   * @param int $value
   *
   * @return array the set of tasks for a group of voters.
   * @static
   * @access public
   */
  static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the interview task by default
      $value = 1;
    }

    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }
}

