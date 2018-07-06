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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */
class CRM_Event_Info extends CRM_Core_Component_Info {

  /**
   * @inheritDoc
   */
  protected $keyword = 'event';

  /**
   * @inheritDoc
   * @return array
   */
  public function getInfo() {
    return array(
      'name' => 'CiviEvent',
      'translatedName' => ts('CiviEvent'),
      'title' => ts('CiviCRM Event Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    );
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
    $permissions = array(
      'access CiviEvent' => array(
        ts('access CiviEvent'),
        ts('Create events, view all events, and view participant records (for visible contacts)'),
      ),
      'edit event participants' => array(
        ts('edit event participants'),
        ts('Record and update backend event registrations'),
      ),
      'edit all events' => array(
        ts('edit all events'),
        ts('Edit events even without specific ACL granted'),
      ),
      'register for events' => array(
        ts('register for events'),
        ts('Register for events online'),
      ),
      'view event info' => array(
        ts('view event info'),
        ts('View online event information pages'),
      ),
      'view event participants' => array(
        ts('view event participants'),
      ),
      'delete in CiviEvent' => array(
        ts('delete in CiviEvent'),
        ts('Delete participants and events that you can edit'),
      ),
      'manage event profiles' => array(
        ts('manage event profiles'),
        ts('Allow users to create, edit and copy event-related profile forms used for online event registration.'),
      ),
    );

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
    return array(
      'access CiviEvent',
    );
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function getUserDashboardElement() {
    return array(
      'name' => ts('Events'),
      'title' => ts('Your Event(s)'),
      'perm' => array('register for events'),
      'weight' => 20,
    );
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerTab() {
    return array(
      'title' => ts('Events'),
      'id' => 'participant',
      'url' => 'participant',
      'weight' => 40,
    );
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return array(
      'title' => ts('Events'),
      'weight' => 40,
    );
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function getActivityTypes() {
    $types = array();
    $types['Event'] = array(
      'title' => ts('Event'),
      'callback' => 'CRM_Event_Page_EventInfo::run()',
    );
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
      $shortCut[] = array(
        'path' => 'civicrm/participant/add',
        'query' => "reset=1&action=add&context=standalone",
        'ref' => 'new-participant',
        'title' => ts('Event Registration'),
      );
      if ($newCredit) {
        $title = ts('Event Registration') . '<br />&nbsp;&nbsp;(' . ts('credit card') . ')';
        $shortCut[0]['shortCuts'][] = array(
          'path' => 'civicrm/participant/add',
          'query' => "reset=1&action=add&context=standalone&mode=live",
          'ref' => 'new-participant-cc',
          'title' => $title,
        );
      }
      $shortCuts = array_merge($shortCuts, $shortCut);
    }
  }

}
