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
 * Class to represent the actions that can be performed on a group of contacts.
 *
 * Used by the search forms.
 */
class CRM_Contribute_Task extends CRM_Core_Task {

  /**
   * Contribution tasks
   */
  const
    UPDATE_STATUS = 401,
    PDF_RECEIPT = 402,
    PDF_THANKYOU = 403,
    PDF_INVOICE = 404;

  /**
   * @var string
   */
  public static $objectType = 'contribution';

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
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Contribute_Form_Task_Print',
          'result' => FALSE,
          'weight' => 10,
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export contributions'),
          'class' => [
            'CRM_Contribute_Export_Form_Select',
            'CRM_Contribute_Export_Form_Map',
          ],
          'result' => FALSE,
          'weight' => 20,
        ],
        self::BATCH_UPDATE => [
          'title' => ts('Update multiple contributions'),
          'class' => [
            'CRM_Contribute_Form_Task_PickProfile',
            'CRM_Contribute_Form_Task_Batch',
          ],
          'result' => TRUE,
          'weight' => 30,
        ],
        self::TASK_EMAIL => [
          'title' => ts('Email - send now (to %1 or less)', [
            1 => Civi::settings()
              ->get('simple_mail_limit'),
          ]),
          'class' => 'CRM_Contribute_Form_Task_Email',
          'result' => TRUE,
          'weight' => 40,
        ],
        self::UPDATE_STATUS => [
          'title' => ts('Record payments for contributions'),
          'class' => 'CRM_Contribute_Form_Task_Status',
          'result' => TRUE,
          'weight' => 50,
        ],
        self::PDF_RECEIPT => [
          'title' => ts('Receipts - print or email'),
          'class' => 'CRM_Contribute_Form_Task_PDF',
          'result' => FALSE,
          'title_single_mode' => ts('Send Receipt'),
          'name' => ts('Send Receipt'),
          'url' => 'civicrm/contribute/task?reset=1&task_item=receipt',
          'key' => 'receipt',
          'icon' => 'fa-envelope-o',
          'filters' => ['contribution_status_id' => [CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')]],
          'is_single_mode' => TRUE,
          'weight' => 60,
        ],
        self::PDF_THANKYOU => [
          'title' => ts('Thank-you letters - print or email'),
          'class' => 'CRM_Contribute_Form_Task_PDFLetter',
          'result' => FALSE,
          'url' => 'civicrm/contribute/task?reset=1&task_item=letter',
          'key' => 'letter',
          'name' => ts('Send Letter'),
          'is_single_mode' => TRUE,
          'title_single_mode' => ts('Thank-you letter - print or email'),
          'weight' => 70,
        ],
        self::PDF_INVOICE => [
          'title' => ts('Invoices - print or email'),
          'class' => 'CRM_Contribute_Form_Task_Invoice',
          'result' => FALSE,
          'weight' => 80,
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
      $invoicing = \Civi::settings()->get('invoicing');
      if (!$invoicing) {
        unset(self::$_tasks[self::PDF_INVOICE]);
      }

      parent::tasks();
    }

    return self::$_tasks;
  }

  /**
   * Get links appropriate to the context of the row.
   *
   * @param array $row
   *
   * @return array
   */
  public static function getContextualLinks($row) {
    $tasks = self::tasks();
    foreach ($tasks as $key => $task) {
      if (empty($task['is_single_mode'])) {
        unset($tasks[$key]);
        continue;
      }
      if (!empty($task['filters'])) {
        foreach ($task['filters'] as $filter => $values) {
          if (!in_array($row[$filter], $values, FALSE)) {
            unset($tasks[$key]);
            continue 2;
          }
        }
      }
      $tasks[$key]['url'] = $task['url'];
      $tasks[$key]['qs'] = ['id' => $row['contribution_id']];
      $tasks[$key]['title'] = $task['title_single_mode'] ?? $task['title'];
    }
    return $tasks;
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
      // See https://lab.civicrm.org/dev/core/-/issues/3737
      static::tasks();
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
    if (!$value || empty(self::$_tasks[$value])) {
      // make the print task by default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
