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
 * Dummy page for details of address.
 */
class CRM_Contact_Page_Inline_Address extends CRM_Core_Page {

  use CRM_Custom_Page_CustomDataTrait;

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $locBlockNo = CRM_Utils_Request::retrieve('locno', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $addressId = CRM_Utils_Request::retrieve('aid', 'Positive');

    $address = [];
    if ($addressId > 0) {
      $locationTypes = CRM_Core_BAO_Address::buildOptions('location_type_id');

      $entityBlock = ['id' => $addressId];
      $address = CRM_Core_BAO_Address::getValues($entityBlock, FALSE, 'id');
      if (!empty($address)) {
        foreach ($address as $key => & $value) {
          $value['location_type'] = $locationTypes[$value['location_type_id']];
        }
      }
    }

    // we just need current address block
    $currentAddressBlock['address'][$locBlockNo] = array_pop($address);

    if (!empty($currentAddressBlock['address'][$locBlockNo])) {
      // get contact name of shared contact names
      $sharedAddresses = [];
      $shareAddressContactNames = CRM_Contact_BAO_Contact_Utils::getAddressShareContactNames($currentAddressBlock['address']);
      foreach ($currentAddressBlock['address'] as $key => $addressValue) {
        if (!empty($addressValue['master_id']) &&
          !$shareAddressContactNames[$addressValue['master_id']]['is_deleted']
        ) {
          $sharedAddresses[$key]['shared_address_display'] = [
            'address' => $addressValue['display'],
            'name' => $shareAddressContactNames[$addressValue['master_id']]['name'],
          ];
        }
      }
      $idValue = $currentAddressBlock['address'][$locBlockNo]['id'];
      if (!empty($currentAddressBlock['address'][$locBlockNo]['master_id'])) {
        $idValue = $currentAddressBlock['address'][$locBlockNo]['master_id'];
      }

      $currentAddressBlock['address'][$locBlockNo]['custom'] = $this->getCustomDataFieldsForEntityDisplay('Address', $idValue);

      $this->assign('add', $currentAddressBlock['address'][$locBlockNo]);
      $this->assign('sharedAddresses', $sharedAddresses);
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
    $this->assign('locationIndex', $locBlockNo);
    $this->assign('addressId', $addressId);
    $this->assign('privacy', $privacy);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
