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
 * Page for displaying list of Mail account settings
 */
class CRM_Admin_Page_MailSettings extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_MailSettings';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/mailSettings',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Mail Settings'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/mailSettings',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Mail Settings'),
        ),
      );
    }

    return self::$_links;
  }

  /**
   * Browse all mail settings.
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    //get all mail settings.
    $allMailSettings = array();
    $mailSetting = new CRM_Core_DAO_MailSettings();

    $allProtocols = CRM_Core_PseudoConstant::mailProtocol();

    //multi-domain support for mail settings. CRM-5244
    $mailSetting->domain_id = CRM_Core_Config::domainID();

    //find all mail settings.
    $mailSetting->find();
    while ($mailSetting->fetch()) {
      //replace protocol value with name
      $mailSetting->protocol = CRM_Utils_Array::value($mailSetting->protocol, $allProtocols);
      CRM_Core_DAO::storeValues($mailSetting, $allMailSettings[$mailSetting->id]);

      //form all action links
      $action = array_sum(array_keys($this->links()));

      // disallow the DELETE action for the default set of settings
      if ($mailSetting->is_default) {
        $action &= ~CRM_Core_Action::DELETE;
      }

      //add action links.
      $allMailSettings[$mailSetting->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $mailSetting->id)
      );
    }

    $this->assign('rows', $allMailSettings);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_MailSettings';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Mail Settings';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/mailSettings';
  }
}

