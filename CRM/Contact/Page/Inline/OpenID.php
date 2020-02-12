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
 * Dummy page for details for OpenID.
 */
class CRM_Contact_Page_Inline_OpenID extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);

    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', ['labelColumn' => 'display_name']);

    $entityBlock = ['contact_id' => $contactId];
    $openids = CRM_Core_BAO_OpenID::getValues($entityBlock);
    if (!empty($openids)) {
      foreach ($openids as $key => & $value) {
        $value['location_type'] = $locationTypes[$value['location_type_id']];
      }
    }

    $this->assign('contactId', $contactId);
    $this->assign('openid', $openids);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
