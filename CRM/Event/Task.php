<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms
 *
 */
class CRM_Event_Task {
  // Value for SAVE_SEARCH is set to 13 in accordance with CRM_Contact_Task::SAVE_SEARCH
  const DELETE_EVENTS = 1, PRINT_EVENTS = 2, EXPORT_EVENTS = 3, BATCH_EVENTS = 4, CANCEL_REGISTRATION = 5, EMAIL_CONTACTS = 6,
    // Value for LABEL_CONTACTS is set to 16 in accordance with CRM_Contact_Task::LABEL_CONTACTS
    SAVE_SEARCH = 13, SAVE_SEARCH_UPDATE = 14, PARTICIPANT_STATUS = 15,
    LABEL_CONTACTS = 16, GROUP_CONTACTS = 20;

  /**
   * The task array
   *
   * @var array
   */
  static $_tasks = NULL;

  /**
   * The optional task array
   *
   * @var array
   */
  static $_optionalTasks = NULL;

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function &tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = array(
        1 => array(
          'title' => ts('Delete participants from event'),
          'class' => 'CRM_Event_Form_Task_Delete',
          'result' => FALSE,
        ),
        2 => array(
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Event_Form_Task_Print',
          'result' => FALSE,
        ),
        3 => array(
          'title' => ts('Export participants'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
        4 => array(
          'title' => ts('Update multiple participants'),
          'class' => array(
            'CRM_Event_Form_Task_PickProfile',
            'CRM_Event_Form_Task_Batch',
          ),
          'result' => TRUE,
        ),
        5 => array(
          'title' => ts('Cancel registration'),
          'class' => 'CRM_Event_Form_Task_Cancel',
          'result' => FALSE,
        ),
        6 => array(
          'title' => ts('Email - send now'),
          'class' => 'CRM_Event_Form_Task_Email',
          'result' => TRUE,
        ),
        13 => array(
          'title' => ts('Group - create smart group'),
          'class' => 'CRM_Event_Form_Task_SaveSearch',
          'result' => TRUE,
        ),
        14 => array(
          'title' => ts('Group - update smart group'),
          'class' => 'CRM_Event_Form_Task_SaveSearch_Update',
          'result' => TRUE,
        ),
        15 => array(
          'title' => ts('Participant status - change (emails sent)'),
          'class' => 'CRM_Event_Form_Task_ParticipantStatus',
          'result' => TRUE,
        ),
        16 => array(
          'title' => ts('Name badges - print'),
          'class' => 'CRM_Event_Form_Task_Badge',
          'result' => FALSE,
        ),
        17 => array(
          'title' => ts('PDF letter - print for participants'),
          'class' => 'CRM_Event_Form_Task_PDF',
          'result' => TRUE,
        ),
        20 => array(
          'title' => ts('Group - add contacts'),
          'class' => 'CRM_Event_Form_Task_AddToGroup',
          'result' => FALSE,
        ),
      );

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviEvent')) {
        unset(self::$_tasks[1]);
      }
      //CRM-12920 - check for edit permission
      if (!CRM_Core_Permission::check('edit event participants')) {
        unset(self::$_tasks[4], self::$_tasks[5], self::$_tasks[15]);
      }
    }

    CRM_Utils_Hook::searchTasks('event', self::$_tasks);

    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * for participants
   *
   * @return array
   *   the set of task titles
   */
  public static function &taskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_tasks as $id => $value) {
      // skip Update Smart Group task
      if ($id != self::SAVE_SEARCH_UPDATE) {
        $titles[$id] = $value['title'];
      }
    }
    return $titles;
  }

  /**
   * These tasks get added based on the context the user is in.
   *
   * @return array
   *   the set of optional tasks for a group of contacts
   */
  public static function &optionalTaskTitle() {
    $tasks = array(
      14 => self::$_tasks[14]['title'],
    );
    return $tasks;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function &permissionedTaskTitles($permission) {
    $tasks = array();
    if (($permission == CRM_Core_Permission::EDIT)
      || CRM_Core_Permission::check('edit event participants')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = array(
        3 => self::$_tasks[3]['title'],
        6 => self::$_tasks[6]['title'],
      );

      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviEvent')) {
        $tasks[1] = self::$_tasks[1]['title'];
      }
    }
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
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the print task by default
      $value = 2;
    }
    asort(self::$_tasks);
    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }

}
