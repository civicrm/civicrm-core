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
 * Page for displaying list of event templates.
 */
class CRM_Admin_Page_EventTemplate extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

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
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/event/manage/settings',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Event Template'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/event/manage',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Event Template'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }

    return self::$_links;
  }

  /**
   * Browse all event templates.
   */
  public function browse() {
    //get all event templates.
    $allEventTemplates = [];

    $eventTemplate = new CRM_Event_DAO_Event();

    $eventTypes = CRM_Event_PseudoConstant::eventType();
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantListings = CRM_Event_BAO_Event::buildOptions('participant_listing_id');

    //find all event templates.
    $eventTemplate->is_template = TRUE;
    $eventTemplate->find();
    while ($eventTemplate->fetch()) {
      CRM_Core_DAO::storeValues($eventTemplate, $allEventTemplates[$eventTemplate->id]);

      //get listing types.
      $allEventTemplates[$eventTemplate->id]['participant_listing'] = ts('Disabled');
      if ($eventTemplate->participant_listing_id) {
        $allEventTemplates[$eventTemplate->id]['participant_listing'] = $participantListings[$eventTemplate->participant_listing_id];
      }

      //get participant role
      $allEventTemplates[$eventTemplate->id]['participant_role'] = '';
      if ($eventTemplate->default_role_id) {
        $allEventTemplates[$eventTemplate->id]['participant_role'] = $participantRoles[$eventTemplate->default_role_id];
      }

      //get event type.
      $allEventTemplates[$eventTemplate->id]['event_type'] = '';
      if (isset($eventTypes[$eventTemplate->event_type_id])) {
        $allEventTemplates[$eventTemplate->id]['event_type'] = $eventTypes[$eventTemplate->event_type_id];
      }

      //form all action links
      $action = array_sum(array_keys($this->links()));

      //add action links.
      $allEventTemplates[$eventTemplate->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        ['id' => $eventTemplate->id],
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
