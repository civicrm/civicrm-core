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
 * Dummy page for details for IM.
 */
class CRM_Contact_Page_Inline_IM extends CRM_Core_Page {
  use CRM_Custom_Page_CustomDataTrait;

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run(): void {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    $locationTypes = CRM_Core_BAO_Address::buildOptions('location_type_id');
    $IMProviders = CRM_Core_DAO_IM::buildOptions('provider_id');

    $entityBlock = ['contact_id' => $contactId];
    $ims = CRM_Core_BAO_IM::getValues($entityBlock);
    if (!empty($ims)) {
      foreach ($ims as $key => & $value) {
        $value['location_type'] = $locationTypes[$value['location_type_id']];
        $value['provider'] = $IMProviders[$value['provider_id']];
        $value['custom'] = $this->getCustomDataFieldsForEntityDisplay('IM', $value['id']);
      }
    }

    $this->assign('contactId', $contactId);
    $this->assign('im', $ims);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
