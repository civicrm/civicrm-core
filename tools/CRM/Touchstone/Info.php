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
  public function getInfo() {
    return array('name' => 'CiviTouchstone',
      'translatedName' => ts('CiviTouchstone'),
      'title' => ts('CiviCRM Touchstone Component'),
      'search' => 1,
    );
  }

  // docs inherited from interface
  public function getPermissions() {
    return array('access CiviTouchstone');
  }

  // docs inherited from interface
  public function getUserDashboardElement() {
    return array('name' => ts('Touchstone'),
      'title' => ts('Your Touchstone'),
      'perm' => array('access CiviTouchstone'),
      'weight' => 85,
    );
  }

  // docs inherited from interface
  public function registerTab() {
    return array('title' => ts('Touchstone'),
      'url' => 'touchstone',
      'weight' => 25,
    );
  }

  // docs inherited from interface
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Touchstone'),
      'weight' => 25,
    );
  }

  // docs inherited from interface
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  public function creatNewShortcut(&$shortCuts) {}
}

