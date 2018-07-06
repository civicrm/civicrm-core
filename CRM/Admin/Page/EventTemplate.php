<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                 |
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
 * Page for displaying list of event templates.
 */
class CRM_Admin_Page_EventTemplate extends CRM_Core_Page_Basic {

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
    return 'CRM_Event_BAO_Event';
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
          'url' => 'civicrm/event/manage/settings',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Event Template'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/event/manage',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Event Template'),
        ),
      );
    }

    return self::$_links;
  }

  /**
   * Browse all event templates.
   */
  public function browse() {
    //get all event templates.
    $allEventTemplates = array();

    $eventTemplate = new CRM_Event_DAO_Event();

    $eventTypes = CRM_Event_PseudoConstant::eventType();
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantListings = CRM_Event_PseudoConstant::participantListing();

    //find all event templates.
    $eventTemplate->is_template = TRUE;
    $eventTemplate->find();
    while ($eventTemplate->fetch()) {
      CRM_Core_DAO::storeValues($eventTemplate, $allEventTemplates[$eventTemplate->id]);

      //get listing types.
      if ($eventTemplate->participant_listing_id) {
        $allEventTemplates[$eventTemplate->id]['participant_listing'] = $participantListings[$eventTemplate->participant_listing_id];
      }

      //get participant role
      if ($eventTemplate->default_role_id) {
        $allEventTemplates[$eventTemplate->id]['participant_role'] = $participantRoles[$eventTemplate->default_role_id];
      }

      //get event type.
      if (isset($eventTypes[$eventTemplate->event_type_id])) {
        $allEventTemplates[$eventTemplate->id]['event_type'] = $eventTypes[$eventTemplate->event_type_id];
      }

      //form all action links
      $action = array_sum(array_keys($this->links()));

      //add action links.
      $allEventTemplates[$eventTemplate->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $eventTemplate->id),
        ts('more'),
        FALSE,
        'eventTemplate.manage.action',
        'Event',
        $eventTemplate->id
      );
    }
    $this->assign('rows', $allEventTemplates);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
      'reset=1&action=browse'
    ));
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_EventTemplate';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Event Templates';
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
    return 'civicrm/admin/eventTemplate';
  }

}
