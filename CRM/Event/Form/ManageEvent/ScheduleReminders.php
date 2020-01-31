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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */

/**
 * This class generates form components for scheduling reminders for Event
 *
 */
class CRM_Event_Form_ManageEvent_ScheduleReminders extends CRM_Event_Form_ManageEvent {

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('reminder');
    $setTab = CRM_Utils_Request::retrieve('setTab', 'Int', $this, FALSE, 0);

    $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings([
      'id' => ($this->_isTemplate ? CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID : CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID),
    ]));
    $reminderList = CRM_Core_BAO_ActionSchedule::getList(FALSE, $mapping, $this->_id);
    if ($reminderList && is_array($reminderList)) {
      // Add action links to each of the reminders
      foreach ($reminderList as & $format) {
        $action = CRM_Core_Action::UPDATE + CRM_Core_Action::DELETE;
        if ($format['is_active']) {
          $action += CRM_Core_Action::DISABLE;
        }
        else {
          $action += CRM_Core_Action::ENABLE;
        }
        $scheduleReminder = new CRM_Admin_Page_ScheduleReminders();
        $links = $scheduleReminder->links();
        $links[CRM_Core_Action::DELETE]['qs'] .= "&context=event&compId={$this->_id}";
        $links[CRM_Core_Action::UPDATE]['qs'] .= "&context=event&compId={$this->_id}";
        $format['action'] = CRM_Core_Action::formLink(
          $links,
          $action,
          ['id' => $format['id']],
          ts('more'),
          FALSE,
          'actionSchedule.manage.action',
          'ActionSchedule',
          $this->_id
        );
      }
    }

    $this->assign('rows', $reminderList);
    $this->assign('setTab', $setTab);
    $this->assign('component', 'event');

    // Update tab "disabled" css class
    $this->ajaxResponse['tabValid'] = is_array($reminderList) && (count($reminderList) > 0);
    $this->setPageTitle(ts('Scheduled Reminder'));
  }

  /**
   * @return string
   */
  public function getTemplateFileName() {
    return 'CRM/Admin/Page/ScheduleReminders.tpl';
  }

}
