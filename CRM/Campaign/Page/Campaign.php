<?php
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying Campaigns
 */
class CRM_Campaign_Page_Campaign extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  private static $_actionLinks;

  /**
   * Get the action links for this page.
   *
   * @return array $_actionLinks
   *
   */
  function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      $deleteExtra = ts('Are you sure you want to delete this Campaign?');
      self::$_actionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/campaign/add',
          'qs' => 'reset=1&action=update&id=%%id%%',
          'title' => ts('Update Campaign'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'title' => ts('Disable Campaign'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Campaign_BAO_Campaign' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'title' => ts('Enable Campaign'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Campaign_BAO_Campaign' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/campaign/add',
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete Campaign'),
        ),
      );
    }
    return self::$_actionLinks;
  }

  function browse() {

    $campaigns = CRM_Campaign_BAO_Campaign::getCampaignSummary();

    if (!empty($campaigns)) {
      $campaignType = CRM_Core_PseudoConstant::campaignType();
      $campaignStatus = CRM_Core_PseudoConstant::campaignStatus();

      foreach ($campaigns as $cmpid => $campaign) {

        $campaigns[$cmpid]['campaign_id'] = $campaign['id'];
        $campaigns[$cmpid]['title'] = $campaign['title'];
        $campaigns[$cmpid]['name'] = $campaign['name'];
        $campaigns[$cmpid]['description'] = $campaign['description'];
        $campaigns[$cmpid]['campaign_type_id'] = $campaignType[$campaign['campaign_type_id']];
        $campaigns[$cmpid]['status_id'] = $campaignStatus[$campaign['status_id']];

        $action = array_sum(array_keys($this->actionLinks()));
        if ($campaign['is_active']) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
        $campaigns[$cmpid]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
          array('id' => $campaign['id'])
        );
      }
    }

    $this->assign('campaigns', $campaigns);
    $this->assign('addCampaignUrl', CRM_Utils_System::url('civicrm/campaign/add', 'reset=1&action=add'));
  }

  function run() {
    if (!CRM_Core_Permission::check('administer CiviCampaign')) {
      CRM_Utils_System::permissionDenied();
    }

    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 0
    );
    $this->assign('action', $action);
    $this->browse();

    return parent::run();
  }
}

