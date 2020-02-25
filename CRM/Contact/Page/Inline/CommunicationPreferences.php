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
 * Dummy page for details of communication preferences.
 */
class CRM_Contact_Page_Inline_CommunicationPreferences extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);

    $params = ['id' => $contactId];

    $defaults = [];
    CRM_Contact_BAO_Contact::getValues($params, $defaults);
    $defaults['privacy_values'] = CRM_Core_SelectValues::privacy();

    $communicationStyle = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'communication_style_id');
    if (!empty($communicationStyle)) {
      if (!empty($defaults['communication_style_id'])) {
        $defaults['communication_style_display'] = $communicationStyle[CRM_Utils_Array::value('communication_style_id', $defaults)];
      }
      else {
        // Make sure the field is displayed as long as it is active, even if it is unset for this contact.
        $defaults['communication_style_display'] = '';
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
