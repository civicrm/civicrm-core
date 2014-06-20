<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Event_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'event';

  // docs inherited from interface
  /**
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

  // docs inherited from interface
  /**
   * @param bool $getAllUnconditionally
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE) {
    return array(
      'access CiviEvent',
      'edit event participants',
      'edit all events',
      'register for events',
      'view event info',
      'view event participants',
      'delete in CiviEvent',
    );
  }

  /**
   * @return array
   */
  public function getAnonymousPermissionWarnings() {
    return array(
      'access CiviEvent',
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function getUserDashboardElement() {
    return array('name' => ts('Events'),
      'title' => ts('Your Event(s)'),
      'perm' => array('register for events'),
      'weight' => 20,
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerTab() {
    return array('title' => ts('Events'),
      'id' => 'participant',
      'url' => 'participant',
      'weight' => 40,
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Events'),
      'weight' => 40,
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function getActivityTypes() {
    $types = array();
    $types['Event'] = array('title' => ts('Event'),
      'callback' => 'CRM_Event_Page_EventInfo::run()',
    );
    return $types;
  }

  // add shortcut to Create New
  /**
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

