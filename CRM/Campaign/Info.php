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
class CRM_Campaign_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'campaign';

  // docs inherited from interface
  /**
   * @return array
   */
  public function getInfo() {
    return array(
      'name' => 'CiviCampaign',
      'translatedName' => ts('CiviCampaign'),
      'title' => 'CiviCRM Campaign Engine',
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
      'administer CiviCampaign',
      'manage campaign',
      'reserve campaign contacts',
      'release campaign contacts',
      'interview campaign contacts',
      'gotv campaign contacts',
      'sign CiviCRM Petition',
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

  /**
   * @return null
   */
  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  // docs inherited from interface
  /**
   * @return null
   */
  public function registerTab() {
    // this component doesn't use contact record tabs
    return NULL;
  }

  // docs inherited from interface
  /**
   * @return null
   */
  public function registerAdvancedSearchPane() {
    // this component doesn't use advanced search
    return NULL;
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
    if (CRM_Core_Permission::check('manage campaign') ||
      CRM_Core_Permission::check('administer CiviCampaign')
    ) {
      $shortCuts = array_merge($shortCuts, array(
        array('path' => 'civicrm/campaign/add',
            'query' => "reset=1&action=add",
            'ref' => 'new-campaign',
            'title' => ts('Campaign'),
          ),
          array(
            'path' => 'civicrm/survey/add',
            'query' => "reset=1&action=add",
            'ref' => 'new-survey',
            'title' => ts('Survey'),
          ),
        ));
    }
  }
}

