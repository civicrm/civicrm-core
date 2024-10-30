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
 * Page to display contact information on top of summary.
 */
class CRM_Contact_Page_Inline_ContactInfo extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    $params = ['id' => $contactId];

    $defaults = [];
    CRM_Contact_BAO_Contact::getValues($params, $defaults);

    //get the current employer name
    if (($defaults['contact_type'] ?? NULL) == 'Individual') {
      if (!empty($defaults['employer_id']) && !empty($defaults['organization_name'])) {
        $defaults['current_employer'] = $defaults['organization_name'];
        $defaults['current_employer_id'] = $defaults['employer_id'];
      }
    }

    $this->assign('contactId', $contactId);
    $this->assign($defaults);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
