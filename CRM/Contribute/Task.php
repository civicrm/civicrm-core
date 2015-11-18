<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Class to represent the actions that can be performed on a group of contacts.
 *
 * Used by the search forms.
 */
class CRM_Contribute_Task {
  const DELETE_CONTRIBUTIONS = 1, PRINT_CONTRIBUTIONS = 2, EXPORT_CONTRIBUTIONS = 3, BATCH_CONTRIBUTIONS = 4, EMAIL_CONTACTS = 5, UPDATE_STATUS = 6, PDF_RECEIPT = 7;

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
  public static function tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = array(
        1 => array(
          'title' => ts('Delete contributions'),
          'class' => 'CRM_Contribute_Form_Task_Delete',
          'result' => FALSE,
        ),
        2 => array(
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Contribute_Form_Task_Print',
          'result' => FALSE,
        ),
        3 => array(
          'title' => ts('Export contributions'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
        4 => array(
          'title' => ts('Update multiple contributions'),
          'class' => array(
            'CRM_Contribute_Form_Task_PickProfile',
            'CRM_Contribute_Form_Task_Batch',
          ),
          'result' => TRUE,
        ),
        5 => array(
          'title' => ts('Email - send now'),
          'class' => 'CRM_Contribute_Form_Task_Email',
          'result' => TRUE,
        ),
        6 => array(
          'title' => ts('Update pending contribution status'),
          'class' => 'CRM_Contribute_Form_Task_Status',
          'result' => TRUE,
        ),
        7 => array(
          'title' => ts('Receipts - print or email'),
          'class' => 'CRM_Contribute_Form_Task_PDF',
          'result' => FALSE,
        ),
        8 => array(
          'title' => ts('Thank-you letters - print or email'),
          'class' => 'CRM_Contribute_Form_Task_PDFLetter',
          'result' => FALSE,
        ),
        9 => array(
          'title' => ts('Invoices - print or email'),
          'class' => 'CRM_Contribute_Form_Task_Invoice',
          'result' => FALSE,
        ),
      );

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviContribute')) {
        unset(self::$_tasks[1]);
      }
      //CRM-12920 - check for edit permission
      if (!CRM_Core_Permission::check('edit contributions')) {
        unset(self::$_tasks[4], self::$_tasks[6]);
      }

      // remove action "Invoices - print or email"
      $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
      $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
      if (!$invoicing) {
        unset(self::$_tasks[9]);
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
   * @return array
   *   the set of task titles
   */
  public static function &taskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_tasks as $id => $value) {
      $titles[$id] = $value['title'];
    }
    return $titles;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   *
   * @param bool $softCreditFiltering
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function &permissionedTaskTitles($permission, $softCreditFiltering = FALSE) {
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
    if ($softCreditFiltering) {
      unset($tasks[4], $tasks[7]);
    }
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on contributors
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of contributors
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the print task by default
      $value = 2;
    }
    // this is possible since hooks can inject a task
    // CRM-13697
    if (!isset(self::$_tasks[$value]['result'])) {
      self::$_tasks[$value]['result'] = NULL;
    }
    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }

}
