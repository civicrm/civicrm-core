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
class CRM_Contribute_Task {
  CONST DELETE_CONTRIBUTIONS = 1, PRINT_CONTRIBUTIONS = 2, EXPORT_CONTRIBUTIONS = 3, BATCH_CONTRIBUTIONS = 4, EMAIL_CONTACTS = 5, UPDATE_STATUS = 6, PDF_RECEIPT = 7;

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
        1 => array('title' => ts('Delete Contributions'),
          'class' => 'CRM_Contribute_Form_Task_Delete',
          'result' => FALSE,
        ),
        2 => array('title' => ts('Print Contributions'),
          'class' => 'CRM_Contribute_Form_Task_Print',
          'result' => FALSE,
        ),
        3 => array('title' => ts('Export Contributions'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
        4 => array('title' => ts('Batch Update Contributions Via Profile'),
          'class' => array(
            'CRM_Contribute_Form_Task_PickProfile',
            'CRM_Contribute_Form_Task_Batch',
          ),
          'result' => TRUE,
        ),
        5 => array('title' => ts('Send Email to Contacts'),
          'class' => 'CRM_Contribute_Form_Task_Email',
          'result' => TRUE,
        ),
        6 => array('title' => ts('Update Pending Contribution Status'),
          'class' => 'CRM_Contribute_Form_Task_Status',
          'result' => TRUE,
        ),
        7 => array('title' => ts('Print or Email Contribution Receipts'),
          'class' => 'CRM_Contribute_Form_Task_PDF',
          'result' => FALSE,
        ),
        8 => array('title' => ts('Thank-you Letters for Contributions'),
          'class' => 'CRM_Contribute_Form_Task_PDFLetter',
          'result' => FALSE,
        ),
      );

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviContribute')) {
        unset(self::$_tasks[1]);
      }

      CRM_Utils_Hook::searchTasks('contribution', self::$_tasks);
      asort(self::$_tasks);
    }

    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * on contributors
   *
   * @return array the set of task titles
   * @static
   * @access public
   */
  static function &taskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_tasks as $id => $value) {
      // skip Print Contribution task
      if ($id != 2) {
        $titles[$id] = $value['title'];
      }
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
    if (($permission == CRM_Core_Permission::EDIT)
      || CRM_Core_Permission::check('edit contributions')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = array(
        3 => self::$_tasks[3]['title'],
        5 => self::$_tasks[5]['title'],
        7 => self::$_tasks[7]['title'],
      );

      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviContribute')) {
        $tasks[1] = self::$_tasks[1]['title'];
      }
    }
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on contributors
   *
   * @param int $value
   *
   * @return array the set of tasks for a group of contributors
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

