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
class CRM_Contribute_Info extends CRM_Core_Component_Info {


  // docs inherited from interface
  protected $keyword = 'contribute';

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
      'name' => 'CiviContribute',
      'translatedName' => ts('CiviContribute'),
      'title' => ts('CiviCRM Contribution Engine'),
      'search' => 1,
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
    return array(
      'access CiviContribute',
      'edit contributions',
      'make online contributions',
      'delete in CiviContribute',
    );
  }

  /**
   * Provides permissions that are unwise for Anonymous Roles to have
   *
   * @return array list of permissions
   * @see CRM_Component_Info::getPermissions
   */
  /**
   * @return array
   */
  public function getAnonymousPermissionWarnings() {
    return array(
      'access CiviContribute',
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
  /**
   * @return array|null
   */
  public function getUserDashboardElement() {
    return array('name' => ts('Contributions'),
      'title' => ts('Your Contribution(s)'),
      'perm' => array('make online contributions'),
      'weight' => 10,
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
  /**
   * @return array|null
   */
  public function registerTab() {
    return array('title' => ts('Contributions'),
      'url' => 'contribution',
      'weight' => 20,
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
  /**
   * @return array|null
   */
  public function registerAdvancedSearchPane() {
    return array(
      'title' => ts('Contributions'),
      'weight' => 20,
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
  /**
   * @return array|null
   */
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  /**
   * @param $shortCuts
   * @param $newCredit
   */
  public function creatNewShortcut(&$shortCuts, $newCredit) {
    if (CRM_Core_Permission::check('access CiviContribute') &&
      CRM_Core_Permission::check('edit contributions')
    ) {
      $shortCut[] = array(
        'path' => 'civicrm/contribute/add',
        'query' => "reset=1&action=add&context=standalone",
        'ref' => 'new-contribution',
        'title' => ts('Contribution'),
      );
      if ($newCredit) {
        $title = ts('Contribution') . '<br />&nbsp;&nbsp;(' . ts('credit card') . ')';
        $shortCut[0]['shortCuts'][] = array(
          'path' => 'civicrm/contribute/add',
          'query' => "reset=1&action=add&context=standalone&mode=live",
          'ref' => 'new-contribution-cc',
          'title' => $title,
        );
      }
      $shortCuts = array_merge($shortCuts, $shortCut);
    }
  }
}

