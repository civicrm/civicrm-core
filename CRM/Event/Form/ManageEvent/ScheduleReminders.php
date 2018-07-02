<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
    $setTab = CRM_Utils_Request::retrieve('setTab', 'Int', $this, FALSE, 0);

    $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => ($this->_isTemplate ? CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID : CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID),
    )));
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
          array('id' => $format['id']),
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
