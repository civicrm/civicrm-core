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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Page for displaying list of Reminders.
 */
class CRM_Admin_Page_ScheduleReminders extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

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
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/scheduleReminders',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Schedule Reminders'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Label Format'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Label Format'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/scheduleReminders',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Schedule Reminders'),
        ),
      );
    }

    return self::$_links;
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
   */
  public function browse($action = NULL) {
    //CRM-16777: Do not permit access to user, for page 'Administer->Communication->Schedule Reminder',
    //when do not have 'administer CiviCRM' permission.
    if (!CRM_Core_Permission::check('administer CiviCRM')) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    // Get list of configured reminders
    $reminderList = CRM_Core_BAO_ActionSchedule::getList();

    if (is_array($reminderList)) {
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
          array('id' => $format['id']),
          ts('more'),
          FALSE,
          'actionSchedule.manage.action',
          'ActionSchedule',
          $format['id']
        );
      }
    }

    $this->assign('rows', $reminderList);
  }

}
