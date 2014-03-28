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
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
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
class CRM_Activity_Task {
  CONST
    DELETE_ACTIVITIES = 1,
    PRINT_ACTIVITIES = 2,
    EXPORT_ACTIVITIES = 3,
    BATCH_ACTIVITIES = 4,
    EMAIL_CONTACTS = 5,
    EMAIL_SMS = 6;

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
          'title' => ts('Delete Activities'),
          'class' => 'CRM_Activity_Form_Task_Delete',
          'result' => FALSE,
        ),
        2 => array(
          'title' => ts('Print Selected Rows'),
          'class' => 'CRM_Activity_Form_Task_Print',
          'result' => FALSE,
        ),
        3 => array(
          'title' => ts('Export Activities'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
        4 => array(
          'title' => ts('Batch Update Activities Via Profile'),
          'class' => array(
            'CRM_Activity_Form_Task_PickProfile',
            'CRM_Activity_Form_Task_Batch',
          ),
          'result' => FALSE,
        ),
        5 => array(
          'title' => ts('Send Email to Contacts'),
          'class' => array(
            'CRM_Activity_Form_Task_PickOption',
            'CRM_Activity_Form_Task_Email',
          ),
          'result' => FALSE,
        ),
        6 => array(
          'title' => ts('Send Reply SMS To Contacts'),
          'class' => 'CRM_Activity_Form_Task_SMS',
          'result' => FALSE,
        ),
        7 => array(
          'title' => ts('Tag Activities (assign tags)'),
          'class' => 'CRM_Activity_Form_Task_AddToTag',
          'result' => FALSE,
        ),
        8 => array(
          'title' => ts('Untag Activities (remove tags)'),
          'class' => 'CRM_Activity_Form_Task_RemoveFromTag',
          'result' => FALSE,
        ),
      );

      $config = CRM_Core_Config::singleton();
      if (in_array('CiviCase', $config->enableComponents)) {
        if ( CRM_Core_Permission::check('access all cases and activities') ||
          CRM_Core_Permission::check('access my cases and activities') ) {
          self::$_tasks[6] = array(
            'title' => ts('File on Case'),
            'class' => 'CRM_Activity_Form_Task_FileOnCase',
            'result' => FALSE,
          );
        }
      }

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete activities')) {
        unset(self::$_tasks[1]);
      }
    }
    CRM_Utils_Hook::searchTasks('activity', self::$_tasks);
    asort(self::$_tasks);
    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * on activity
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
    $tasks = array();
    if ($permission == CRM_Core_Permission::EDIT) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = array(
        3 => self::$_tasks[3]['title'],
      );

      //CRM-4418,
      if (CRM_Core_Permission::check('delete activities')) {
        $tasks[1] = self::$_tasks[1]['title'];
      }
    }
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on activity
   *
   * @param int $value
   *
   * @return array the set of tasks for a group of activity
   * @static
   * @access public
   */
  static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the print task by default
      $value = 2;
    }
    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }
}

