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
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms
 *
 */
class CRM_Mailing_Task {
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
   * on a contact / group of contacts
   *
   * @return array the set of tasks for a group of contacts
   * @static
   * @access public
   */
  static function &tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = array(
        1 => array(
          'title' => ts('Print Mailing Recipients'),
          'class' => 'CRM_Mailing_Form_Task_Print',
          'result' => FALSE,
        ),
      );

      CRM_Utils_Hook::searchTasks('mailing', self::$_tasks);
      asort(self::$_tasks);
    }

    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * on mailing recipients
   *
   * @return array the set of task titles
   * @static
   * @access public
   */
  static function &taskTitles() {
    return array();
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
    $task = array();
    return $task;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on mailing recipients
   *
   * @param int $value
   *
   * @return array the set of tasks for a group of mailing recipients
   * @static
   * @access public
   */
  static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the print task by default
      $value = 1;
    }
    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }
}
