<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
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
  CONST DELETE_EVENTS = 1, PRINT_EVENTS = 2, EXPORT_EVENTS = 3, BATCH_EVENTS = 4, CANCEL_REGISTRATION = 5, EMAIL_CONTACTS = 6,
  // Value for LABEL_CONTACTS is set to 16 in accordance with CRM_Contact_Task::LABEL_CONTACTS
  SAVE_SEARCH = 13, SAVE_SEARCH_UPDATE = 14, PARTICIPANT_STATUS = 15,
  LABEL_CONTACTS = 16;

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
      self::$_tasks = array(1 => array('title' => ts('Delete Participants'),
          'class' => 'CRM_Event_Form_Task_Delete',
          'result' => FALSE,
        ),
        2 => array('title' => ts('Print Participants'),
          'class' => 'CRM_Event_Form_Task_Print',
          'result' => FALSE,
        ),
        3 => array('title' => ts('Export Participants'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
        4 => array('title' => ts('Batch Update Participants Via Profile'),
          'class' => array(
            'CRM_Event_Form_Task_PickProfile',
            'CRM_Event_Form_Task_Batch',
          ),
          'result' => TRUE,
        ),
        5 => array('title' => ts('Cancel Registration'),
          'class' => 'CRM_Event_Form_Task_Cancel',
          'result' => FALSE,
        ),
        6 => array('title' => ts('Send Email to Contacts'),
          'class' => 'CRM_Event_Form_Task_Email',
          'result' => TRUE,
        ),
        13 => array('title' => ts('New Smart Group'),
          'class' => 'CRM_Event_Form_Task_SaveSearch',
          'result' => TRUE,
        ),
        14 => array('title' => ts('Update Smart Group'),
          'class' => 'CRM_Event_Form_Task_SaveSearch_Update',
          'result' => TRUE,
        ),
        15 => array('title' => ts('Change Participant Status'),
          'class' => 'CRM_Event_Form_Task_ParticipantStatus',
          'result' => TRUE,
        ),
        16 => array('title' => ts('Print Event Name Badges'),
          'class' => 'CRM_Event_Form_Task_Badge',
          'result' => FALSE,
        ),
      );

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviEvent')) {
        unset(self::$_tasks[1]);
      }
    }

    CRM_Utils_Hook::searchTasks('event', self::$_tasks);
    asort(self::$_tasks);
    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * for participants
   *
   * @return array the set of task titles
   * @static
   * @access public
   */
  static function &taskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_tasks as $id => $value) {
      // skip Print Participants and Update Smart Group task
      if (!in_array($id, array(
        2, 14))) {
        $titles[$id] = $value['title'];
      }
      else {
        continue;
      }
    }
    return $titles;
  }

  /**
   * These tasks get added based on the context the user is in
   *
   * @return array the set of optional tasks for a group of contacts
   * @static
   * @access public
   */
  static function &optionalTaskTitle() {
    $tasks = array(
      14 => self::$_tasks[14]['title'],
    );
    return $tasks;
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
   * @return array the set of tasks for a group of participants
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

