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
 * Page for displaying list of Reminders.
 */
class CRM_Admin_Page_ScheduleReminders extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_ActionSchedule';
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_ScheduleReminders';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'ScheduleReminders';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/scheduleReminders';
  }

  /**
   * Browse all Scheduled Reminders settings.
   *
   * @param null $action
   *
   * @throws \CRM_Core_Exception
   */
  public function browse($action = NULL) {
    // Get list of configured reminders
    $reminderList = CRM_Core_BAO_ActionSchedule::getList();

    // Add action links to each of the reminders
    foreach ($reminderList as & $format) {
      $action = array_sum(array_keys($this->links()));
      if ($format['is_active']) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }
      $format['action'] = CRM_Core_Action::formLink(
        self::links(),
        $action,
        ['id' => $format['id']],
        ts('more'),
        FALSE,
        'actionSchedule.manage.action',
        'ActionSchedule',
        $format['id']
      );
      $format = array_merge(['class' => ''], $format);
    }

    $this->assign('rows', $reminderList);
    $this->assign('addNewLink', $this->getLinkPath('add'));
  }

}
