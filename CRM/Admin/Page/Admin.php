<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Page for displaying Administer CiviCRM Control Panel.
 */
class CRM_Admin_Page_Admin extends CRM_Core_Page {

  /**
   * Run page.
   *
   * @return string
   */
  public function run() {
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');

    $this->assign('registerSite', htmlspecialchars('https://civicrm.org/register-your-site?src=iam&sid=' . CRM_Utils_System::getSiteID()));

    $groups = [
      'Customize Data and Screens' => ts('Customize Data and Screens'),
      'Communications' => ts('Communications'),
      'Localization' => ts('Localization'),
      'Users and Permissions' => ts('Users and Permissions'),
      'System Settings' => ts('System Settings'),
    ];

    $config = CRM_Core_Config::singleton();
    if (in_array('CiviContribute', $config->enableComponents)) {
      $groups['CiviContribute'] = ts('CiviContribute');
    }

    if (in_array('CiviMember', $config->enableComponents)) {
      $groups['CiviMember'] = ts('CiviMember');
    }

    if (in_array('CiviEvent', $config->enableComponents)) {
      $groups['CiviEvent'] = ts('CiviEvent');
    }

    if (in_array('CiviMail', $config->enableComponents)) {
      $groups['CiviMail'] = ts('CiviMail');
    }

    if (in_array('CiviCase', $config->enableComponents)) {
      $groups['CiviCase'] = ts('CiviCase');
    }

    if (in_array('CiviReport', $config->enableComponents)) {
      $groups['CiviReport'] = ts('CiviReport');
    }

    if (in_array('CiviCampaign', $config->enableComponents)) {
      $groups['CiviCampaign'] = ts('CiviCampaign');
    }

    $values = CRM_Core_Menu::getAdminLinks();

    $this->_showHide = new CRM_Core_ShowHideBlocks();
    foreach ($groups as $group => $title) {
      $groupId = str_replace(' ', '_', $group);

      $this->_showHide->addShow("id_{$groupId}_show");
      $this->_showHide->addHide("id_{$groupId}");
      $v = CRM_Core_ShowHideBlocks::links($this, $groupId, '', '', FALSE);
      if (isset($values[$group])) {
        $adminPanel[$groupId] = $values[$group];
        $adminPanel[$groupId]['show'] = $v['show'];
        $adminPanel[$groupId]['hide'] = $v['hide'];
        $adminPanel[$groupId]['title'] = $title;
      }
      else {
        $adminPanel[$groupId] = [];
        $adminPanel[$groupId]['show'] = '';
        $adminPanel[$groupId]['hide'] = '';
        $adminPanel[$groupId]['title'] = $title;
      }
    }
    $this->assign('adminPanel', $adminPanel);
    $this->_showHide->addToTemplate();
    return parent::run();
  }

}
