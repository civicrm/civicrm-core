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

    $mapping = CRM_Core_BAO_ActionSchedule::getMapping($this->_isTemplate ? CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID : CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID);
    $reminderList = CRM_Core_BAO_ActionSchedule::getList($mapping, $this->_id);
    $scheduleReminder = new CRM_Admin_Page_ScheduleReminders();
    // Add action links to each of the reminders
    foreach ($reminderList as & $format) {
      $action = CRM_Core_Action::UPDATE + CRM_Core_Action::DELETE;
      if ($format['is_active']) {
        $action += CRM_Core_Action::DISABLE;
      }
      else {
        $action += CRM_Core_Action::ENABLE;
      }
      $links = $scheduleReminder->links();
      $links[CRM_Core_Action::DELETE]['qs'] .= "&mapping_id={$mapping->getId()}&entity_value={$this->_id}";
      $links[CRM_Core_Action::UPDATE]['qs'] .= "&mapping_id={$mapping->getId()}&entity_value={$this->_id}";
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

    $this->assign('rows', $reminderList);
    $this->assign('addNewLink', $scheduleReminder->getLinkPath('add') . "&mapping_id={$mapping->getId()}&entity_value={$this->_id}");

    // Update tab "disabled" css class
    $this->ajaxResponse['tabValid'] = is_array($reminderList) && (count($reminderList) > 0);
    $this->setPageTitle(ts('Scheduled Reminder'));
  }

  /**
   * @return string
   */
  public function getTemplateFileName() {
    $setTab = CRM_Utils_Request::retrieve('setTab', 'Int', NULL, FALSE, 0);
    return $setTab ? 'CRM/Event/Form/ManageEvent/Tab.tpl' : 'CRM/Admin/Page/ScheduleReminders.tpl';
  }

}
