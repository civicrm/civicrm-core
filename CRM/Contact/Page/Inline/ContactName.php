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
 * Page to display contact name on top of the summary.
 */
class CRM_Contact_Page_Inline_ContactName extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    $isDeleted = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'is_deleted');

    $this->assign('contactId', $contactId);

    $title = CRM_Contact_Page_View::setTitle($contactId, $isDeleted);
    $this->assign('title', $title);

    // Check if this is default domain contact CRM-10482
    $this->assign('domainContact', CRM_Contact_BAO_Contact::checkDomainContact($contactId));

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
