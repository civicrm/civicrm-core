<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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

    $groups = [
      'Customize Data and Screens' => ts('Customize Data and Screens'),
      'Communications' => ts('Communications'),
      'Localization' => ts('Localization'),
      'Users and Permissions' => ts('Users and Permissions'),
      'System Settings' => ts('System Settings'),
    ];

    foreach (CRM_Core_Component::getEnabledComponents() as $comp) {
      $groups[$comp->info['name']] = $comp->info['translatedName'];
    }

    $values = CRM_Core_Menu::getAdminLinks();

    foreach ($groups as $group => $title) {
      $groupId = str_replace(' ', '_', $group);
      $adminPanel[$groupId] = array_merge($values[$group] ?? [], ['title' => $title]);
    }

    CRM_Utils_Hook::alterAdminPanel($adminPanel);
    foreach ($adminPanel as $groupId => $group) {
      if (count($group) == 1) {
        // Presumably the only thing is the title; remove the section.
        // This is done here to give the hook a chance to edit the section.
        unset($adminPanel[$groupId]);
      }
    }
    $this->assign('adminPanel', $adminPanel);
    return parent::run();
  }

}
