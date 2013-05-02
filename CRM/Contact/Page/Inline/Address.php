<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Dummy page for details of address 
 *
 */
class CRM_Contact_Page_Inline_Address extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);
    $locBlockNo = CRM_Utils_Request::retrieve('locno', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);
    $addressId = CRM_Utils_Request::retrieve('aid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, $_REQUEST);

    $address = array();
    if ( $addressId > 0 ) {
      $locationTypes = CRM_Core_PseudoConstant::locationDisplayName();

      $entityBlock = array('id' => $addressId);
      $address = CRM_Core_BAO_Address::getValues($entityBlock, FALSE, 'id');
      if (!empty($address)) {
        foreach ($address as $key =>& $value) {
          $value['location_type'] = $locationTypes[$value['location_type_id']];
        }
      }
    }

    // we just need current address block
    $currentAddressBlock['address'][$locBlockNo] = array_pop( $address ); 
    
    if ( !empty( $currentAddressBlock['address'][$locBlockNo] ) ) {
      // get contact name of shared contact names
      $sharedAddresses = array();
      $shareAddressContactNames = CRM_Contact_BAO_Contact_Utils::getAddressShareContactNames($currentAddressBlock['address']);
      foreach ($currentAddressBlock['address'] as $key => $addressValue) {
        if (CRM_Utils_Array::value('master_id', $addressValue) &&
          !$shareAddressContactNames[$addressValue['master_id']]['is_deleted']
        ) {
          $sharedAddresses[$key]['shared_address_display'] = array(
            'address' => $addressValue['display'],
            'name' => $shareAddressContactNames[$addressValue['master_id']]['name'],
          );
        }
      }

      // add custom data of type address
      $groupTree = CRM_Core_BAO_CustomGroup::getTree( 'Address',
        $this, $currentAddressBlock['address'][$locBlockNo]['id']
      );

      // we setting the prefix to dnc_ below so that we don't overwrite smarty's grouptree var.
      $currentAddressBlock['address'][$locBlockNo]['custom'] = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $groupTree, FALSE, NULL, "dnc_");
      $this->assign("dnc_viewCustomData", NULL);
    
      $this->assign('add', $currentAddressBlock['address'][$locBlockNo]);
      $this->assign('sharedAddresses', $sharedAddresses);
    }
    $contact = new CRM_Contact_BAO_Contact( );
    $contact->id = $contactId;
    $contact->find(true);
    $privacy = array( );
    foreach ( CRM_Contact_BAO_Contact::$_commPrefs as $name ) {
      if ( isset( $contact->$name ) ) {
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

