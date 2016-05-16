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
 * */
class CRM_Auction_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'auction';

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
    return array('name' => 'CiviAuction',
      'translatedName' => ts('CiviAuction'),
      'title' => ts('CiviCRM Auctions'),
      'search' => 0,
      'showActivitiesInCore' => 0,
    );
  }


  // docs inherited from interface
  /**
   * @return array
   */
  public function getPermissions() {
    return array('access CiviAuction',
      'add auction items',
      'approve auction items',
      'bid on auction items',
      'delete in CiviAuction',
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
  public function getUserDashboardElement() {
    return array('name' => ts('Auctions'),
      'title' => ts('Your Winning Auction Item(s)'),
      'perm' => array('bid on auction items'),
      'weight' => 20,
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
    return array('title' => ts('Auctions'),
      'id' => 'auction',
      'url' => 'auction',
      'weight' => 40,
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
    return array('title' => ts('Auctions'),
      'weight' => 40,
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
    $types = array();
    return $types;
  }

  // add shortcut to Create New
  /**
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {}
}

