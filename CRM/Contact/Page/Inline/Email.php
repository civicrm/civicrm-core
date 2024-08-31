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
 * Dummy page for details of Email.
 */
class CRM_Contact_Page_Inline_Email extends CRM_Core_Page {

  use CRM_Custom_Page_CustomDataTrait;

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', NULL, TRUE);

    $locationTypes = CRM_Core_BAO_Address::buildOptions('location_type_id');

    $entityBlock = ['contact_id' => $contactId];
    $emails = CRM_Core_BAO_Email::getValues($entityBlock);
    if (!empty($emails)) {
      foreach ($emails as &$value) {
        $value['location_type'] = $locationTypes[$value['location_type_id']];
        $value['custom'] = $this->getCustomDataFieldsForEntityDisplay('Email', $value['id']);
      }
    }

    $contact = new CRM_Contact_BAO_Contact();
    $contact->id = $contactId;
    $contact->find(TRUE);
    $privacy = [];
    foreach (CRM_Contact_BAO_Contact::$_commPrefs as $name) {
      if (isset($contact->$name)) {
        $privacy[$name] = $contact->$name;
      }
    }

    $this->assign('contactId', $contactId);
    $this->assign('email', $emails);
    $this->assign('privacy', $privacy);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
