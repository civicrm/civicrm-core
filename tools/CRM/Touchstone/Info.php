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



require_once 'CRM/Core/Component/Info.php';

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
class CRM_Touchstone_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'touchstone';

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
  public function getInfo() {
    return array('name' => 'CiviTouchstone',
      'translatedName' => ts('CiviTouchstone'),
      'title' => ts('CiviCRM Touchstone Component'),
      'search' => 1,
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function getPermissions() {
    return array('access CiviTouchstone');
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
  public function getUserDashboardElement() {
    return array('name' => ts('Touchstone'),
      'title' => ts('Your Touchstone'),
      'perm' => array('access CiviTouchstone'),
      'weight' => 85,
    );
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
  public function registerTab() {
    return array('title' => ts('Touchstone'),
      'url' => 'touchstone',
      'weight' => 25,
    );
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
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Touchstone'),
      'weight' => 25,
    );
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
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  /**
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {}
}

