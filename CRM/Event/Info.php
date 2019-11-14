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
 * $Id$
 *
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
   * @param bool $getAllUnconditionally
   * @param bool $descriptions
   *   Whether to return permission descriptions
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE, $descriptions = FALSE) {
    $permissions = [
      'access CiviEvent' => [
        ts('access CiviEvent'),
        ts('Create events, view all events, and view participant records (for visible contacts)'),
      ],
      'edit event participants' => [
        ts('edit event participants'),
        ts('Record and update backend event registrations'),
      ],
      'edit all events' => [
        ts('edit all events'),
        ts('Edit events even without specific ACL granted'),
      ],
      'register for events' => [
        ts('register for events'),
        ts('Register for events online'),
      ],
      'view event info' => [
        ts('view event info'),
        ts('View online event information pages'),
      ],
      'view event participants' => [
        ts('view event participants'),
      ],
      'delete in CiviEvent' => [
        ts('delete in CiviEvent'),
        ts('Delete participants and events that you can edit'),
      ],
      'manage event profiles' => [
        ts('manage event profiles'),
        ts('Allow users to create, edit and copy event-related profile forms used for online event registration.'),
      ],
    ];

    if (!$descriptions) {
      foreach ($permissions as $name => $attr) {
        $permissions[$name] = array_shift($attr);
      }
    }

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
