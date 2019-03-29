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
 * Class to represent the actions that can be performed on a group of contacts.
 *
 * Used by the search forms.
 */
class CRM_Contribute_Task extends CRM_Core_Task {

  const
    // Contribution tasks
    UPDATE_STATUS = 401,
    PDF_RECEIPT = 402,
    PDF_THANKYOU = 403,
    PDF_INVOICE = 404;

  static $objectType = 'contribution';

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = [
        self::TASK_DELETE => [
          'title' => ts('Delete contributions'),
          'class' => 'CRM_Contribute_Form_Task_Delete',
          'result' => FALSE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Contribute_Form_Task_Print',
          'result' => FALSE,
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export contributions'),
          'class' => [
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ],
          'result' => FALSE,
        ],
        self::BATCH_UPDATE => [
          'title' => ts('Update multiple contributions'),
          'class' => [
            'CRM_Contribute_Form_Task_PickProfile',
            'CRM_Contribute_Form_Task_Batch',
          ],
          'result' => TRUE,
        ],
        self::TASK_EMAIL => [
          'title' => ts('Email - send now (to %1 or less)', [
            1 => Civi::settings()
              ->get('simple_mail_limit'),
          ]),
          'class' => 'CRM_Contribute_Form_Task_Email',
          'result' => TRUE,
        ],
        self::UPDATE_STATUS => [
          'title' => ts('Update pending contribution status'),
          'class' => 'CRM_Contribute_Form_Task_Status',
          'result' => TRUE,
        ],
        self::PDF_RECEIPT => [
          'title' => ts('Receipts - print or email'),
          'class' => 'CRM_Contribute_Form_Task_PDF',
          'result' => FALSE,
        ],
        self::PDF_THANKYOU => [
          'title' => ts('Thank-you letters - print or email'),
          'class' => 'CRM_Contribute_Form_Task_PDFLetter',
          'result' => FALSE,
        ],
        self::PDF_INVOICE => [
          'title' => ts('Invoices - print or email'),
          'class' => 'CRM_Contribute_Form_Task_Invoice',
          'result' => FALSE,
        ],
      ];

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviContribute')) {
        unset(self::$_tasks[self::TASK_DELETE]);
      }
      //CRM-12920 - check for edit permission
      if (!CRM_Core_Permission::check('edit contributions')) {
        unset(self::$_tasks[self::BATCH_UPDATE], self::$_tasks[self::UPDATE_STATUS]);
      }

      // remove action "Invoices - print or email"
      $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
      $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
      if (!$invoicing) {
        unset(self::$_tasks[self::PDF_INVOICE]);
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
   *
   * @param array $params
   *              bool softCreditFiltering: derived from CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function permissionedTaskTitles($permission, $params = []) {
    if (!isset($params['softCreditFiltering'])) {
      $params['softCreditFiltering'] = FALSE;
    }
    if (($permission == CRM_Core_Permission::EDIT)
      || CRM_Core_Permission::check('edit contributions')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = [
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
        self::TASK_EMAIL => self::$_tasks[self::TASK_EMAIL]['title'],
        self::PDF_RECEIPT => self::$_tasks[self::PDF_RECEIPT]['title'],
      ];

      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviContribute')) {
        $tasks[self::TASK_DELETE] = self::$_tasks[self::TASK_DELETE]['title'];
      }
    }
    if ($params['softCreditFiltering']) {
      unset($tasks[self::BATCH_UPDATE], $tasks[self::PDF_RECEIPT]);
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
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
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
