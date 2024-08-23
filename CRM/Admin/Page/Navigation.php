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
class CRM_Admin_Page_Navigation extends CRM_Core_Page_Basic {

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_Navigation';
  }

  /**
   * Get action Links.
   *
   * @return array|NULL
   *   (reference) of action links
   */
  public function &links() {
    return NULL;
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_Navigation';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'CiviCRM Navigation';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/menu';
  }

  /**
   * Browse all menus.
   */
  public function browse() {
    // assign home id to the template
    $homeMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Home', 'id', 'name');
    $this->assign('homeMenuId', $homeMenuId);

    // Add jstree support
    Civi::resources()->addScriptFile('civicrm', 'bower_components/jstree/dist/jstree.min.js', 0, 'html-header');
    Civi::resources()->addStyleFile('civicrm', 'bower_components/jstree/dist/themes/default/style.min.css');

    // Add our styles
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');
  }

}
