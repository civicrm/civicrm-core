<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Report_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'report';

  // docs inherited from interface
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
  public function getPermissions($getAllUnconditionally = FALSE) {
    return array('access CiviReport', 'access Report Criteria', 'administer reserved reports', 'administer Reports');
  }


  // docs inherited from interface
  public function getUserDashboardElement() {
    // no dashboard element for this component
    return NULL;
  }

  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  // docs inherited from interface
  public function registerTab() {
    // this component doesn't use contact record tabs
    return NULL;
  }

  // docs inherited from interface
  public function registerAdvancedSearchPane() {
    // this component doesn't use advanced search
    return NULL;
  }

  // docs inherited from interface
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  public function creatNewShortcut(&$shortCuts) {}
}

