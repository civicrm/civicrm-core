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
 * Dummy page for actions button.
 */
class CRM_Contact_Page_Inline_Actions extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    $this->assign('contactId', $contactId);
    $this->assign('actionsMenuList', CRM_Contact_BAO_Contact::contextMenu($contactId));
    CRM_Contact_Page_View::addUrls($this, $contactId);

    // also create the form element for the activity links box
    $controller = new CRM_Core_Controller_Simple('CRM_Activity_Form_ActivityLinks',
      ts('Activity Links'),
      NULL
    );
    $controller->setEmbedded(TRUE);
    $controller->run();

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
