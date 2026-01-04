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
 * Dummy page for details of demographics.
 */
class CRM_Contact_Page_Inline_Demographics extends CRM_Core_Page {

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

    if (!empty($defaults['gender_id'])) {
      $gender = CRM_Contact_DAO_Contact::buildOptions('gender_id');
      $defaults['gender_display'] = $gender[$defaults['gender_id']];
    }
    $this->assignFieldMetadataToTemplate('Contact');

    $this->assign('contactId', $contactId);
    $this->assign($defaults);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
