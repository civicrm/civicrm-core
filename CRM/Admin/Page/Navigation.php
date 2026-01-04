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
 * Page for editing the navigation menu.
 */
class CRM_Admin_Page_Navigation extends CRM_Core_Page {

  /**
   * Browse all menus.
   */
  public function run() {
    // assign home id to the template
    $homeMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Home', 'id', 'name');
    $this->assign('homeMenuId', $homeMenuId);

    // Add jstree support
    Civi::resources()->addScriptFile('civicrm', 'bower_components/jstree/dist/jstree.min.js', 0, 'html-header');
    Civi::resources()->addStyleFile('civicrm', 'bower_components/jstree/dist/themes/default/style.min.css');

    // Add our styles
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');
    return parent::run();
  }

}
