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
class CRM_Grant_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'grant';

  // docs inherited from interface
  /**
   * @return array
   */
  public function getInfo() {
    return array(
      'name' => 'CiviGrant',
      'translatedName' => ts('CiviGrant'),
      'title' => 'CiviCRM Grant Management Engine',
      'path' => 'CRM_Grant_',
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
      'access CiviGrant',
      'edit grants',
      'delete in CiviGrant',
    );
  }

  // docs inherited from interface
  /**
   * @return null
   */
  public function getUserDashboardElement() {
    // no dashboard element for this component
    return NULL;
  }

  // docs inherited from interface
  /**
   * @return null
   */
  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerTab() {
    return array('title' => ts('Grants'),
      'url' => 'grant',
      'weight' => 50,
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Grants'),
      'weight' => 50,
    );
  }

  // docs inherited from interface
  /**
   * @return null
   */
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  /**
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('access CiviGrant') &&
      CRM_Core_Permission::check('edit grants')
    ) {
      $shortCuts = array_merge($shortCuts, array(
        array('path' => 'civicrm/grant/add',
            'query' => "reset=1&action=add&context=standalone",
            'ref' => 'new-grant',
            'title' => ts('Grant'),
          )));
    }
  }
}

