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
 * Main page for viewing contact.
 */
class CRM_Contact_Page_View_Print extends CRM_Contact_Page_View_Summary {

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function run() {
    $this->_print = CRM_Core_Smarty::PRINT_PAGE;

    $this->preProcess();

    $this->view();

    return parent::run();
  }

  /**
   * View summary details of a contact.
   */
  public function view() {
    $params = [];
    $defaults = [];
    $ids = [];

    $params['id'] = $params['contact_id'] = $this->_contactId;
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, $ids);

    $this->assign('pageTitle', $contact->sort_name);

    return parent::view();
  }

}
