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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Event_Info extends CRM_Core_Component_Info {

  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'event';

  /**
   * @inheritDoc
   * @return array
   */
  public function getInfo() {
    return [
      'name' => 'CiviEvent',
      'translatedName' => ts('CiviEvent'),
      'title' => ts('CiviCRM Event Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getPermissions(): array {
    $permissions = [
      'access CiviEvent' => [
        'label' => ts('access CiviEvent'),
        'description' => ts('Create events, view all events, and view participant records (for visible contacts)'),
      ],
      'edit event participants' => [
        'label' => ts('edit event participants'),
        'description' => ts('Record and update backend event registrations'),
      ],
      'edit all events' => [
        'label' => ts('edit all events'),
        'description' => ts('Edit events even without specific ACL granted'),
      ],
      'register for events' => [
        'label' => ts('register for events'),
        'description' => ts('Register for events online'),
      ],
      'view event info' => [
        'label' => ts('view event info'),
        'description' => ts('View online event information pages'),
      ],
      'view event participants' => [
        'label' => ts('view event participants'),
      ],
      'delete in CiviEvent' => [
        'label' => ts('delete in CiviEvent'),
        'description' => ts('Delete participants and events that you can edit'),
      ],
      'manage event profiles' => [
        'label' => ts('manage event profiles'),
        'description' => ts('Allow users to create, edit and copy event-related profile forms used for online event registration.'),
      ],
    ];
    return $permissions;
  }

  /**
   * @return array
   */
  public function getAnonymousPermissionWarnings() {
    return [
      'access CiviEvent',
    ];
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function getUserDashboardElement() {
    return [
      'name' => ts('Events'),
      'title' => ts('Your Event(s)'),
      'perm' => ['register for events'],
      'weight' => 20,
    ];
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerTab() {
    return [
      'title' => ts('Events'),
      'id' => 'participant',
      'url' => 'participant',
      'weight' => 40,
    ];
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-calendar';
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return [
      'title' => ts('Events'),
      'weight' => 40,
    ];
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function getActivityTypes() {
    $types = [];
    $types['Event'] = [
      'title' => ts('Event'),
      'callback' => 'CRM_Event_Page_EventInfo::run()',
    ];
    return $types;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   * @param $newCredit
   */
  public function creatNewShortcut(&$shortCuts, $newCredit) {
    if (CRM_Core_Permission::check('access CiviEvent') &&
      CRM_Core_Permission::check('edit event participants')
    ) {
      $shortCut[] = [
        'path' => 'civicrm/participant/add',
        'query' => "reset=1&action=add&context=standalone",
        'ref' => 'new-participant',
        'title' => ts('Event Registration'),
      ];
      if ($newCredit) {
        $title = ts('Event Registration') . '<br />&nbsp;&nbsp;(' . ts('credit card') . ')';
        $shortCut[0]['shortCuts'][] = [
          'path' => 'civicrm/participant/add',
          'query' => "reset=1&action=add&context=standalone&mode=live",
          'ref' => 'new-participant-cc',
          'title' => $title,
        ];
      }
      $shortCuts = array_merge($shortCuts, $shortCut);
    }
  }

}
