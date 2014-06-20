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
class CRM_Report_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'report';

  // docs inherited from interface
  /**
   * Provides base information about the component.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array collection of required component settings
   * @access public
   *
   */
  /**
   * @return array
   */
  public function getInfo() {
    return array(
      'name' => 'CiviReport',
      'translatedName' => ts('CiviReport'),
      'title' => 'CiviCRM Report Engine',
      'search' => 0,
      'showActivitiesInCore' => 1,
    );
  }


  // docs inherited from interface
  /**
   * Provides permissions that are used by component.
   * Needs to be implemented in component's information
   * class.
   *
   * NOTE: if using conditionally permission return,
   * implementation of $getAllUnconditionally is required.
   *
   * @param bool $getAllUnconditionally
   *
   * @return array|null collection of permissions, null if none
   * @access public
   */
  /**
   * @param bool $getAllUnconditionally
   *
   * @return array|null
   */
  public function getPermissions($getAllUnconditionally = FALSE) {
    return array('access CiviReport', 'access Report Criteria', 'administer reserved reports', 'administer Reports');
  }


  // docs inherited from interface
  /**
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null collection of required dashboard settings,
   *                    null if no element offered
   * @access public
   *
   */
  /**
   * @return array|null
   */
  public function getUserDashboardElement() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * Provides component's user dashboard page object.
   *
   * @return mixed component's User Dashboard applet object
   * @access public
   *
   */
  /**
   * @return mixed
   */
  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  // docs inherited from interface
  /**
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null collection of required dashboard settings,
   *                    null if no element offered
   * @access public
   *
   */
  /**
   * @return array|null
   */
  public function registerTab() {
    // this component doesn't use contact record tabs
    return NULL;
  }

  // docs inherited from interface
  /**
   * Provides information about advanced search pane
   * offered by this component.
   *
   * @return array|null collection of required pane settings,
   *                    null if no element offered
   * @access public
   *
   */
  /**
   * @return array|null
   */
  public function registerAdvancedSearchPane() {
    // this component doesn't use advanced search
    return NULL;
  }

  // docs inherited from interface
  /**
   * Provides potential activity types that this
   * component might want to register in activity history.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array|null collection of activity types
   * @access public
   *
   */
  /**
   * @return array|null
   */
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  /**
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {}
}

