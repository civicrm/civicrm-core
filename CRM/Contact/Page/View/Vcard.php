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

require_once 'Contact/Vcard/Build.php';

/**
 * vCard export class.
 */
class CRM_Contact_Page_View_Vcard extends CRM_Contact_Page_View {

  /**
   * Heart of the vCard data assignment process.
   *
   * The runner gets all the metadata for the contact and calls the writeVcard method to output the vCard
   * to the user.
   */
  public function run() {
    $this->preProcess();

    $params = [];
    $defaults = [];
    $ids = [];

    $params['id'] = $params['contact_id'] = $this->_contactId;
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, $ids);

    // now that we have the contact's data - let's build the vCard
    // TODO: non-US-ASCII support (requires changes to the Contact_Vcard_Build class)
    $vcardNames = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', ['labelColumn' => 'vcard_name']);
    $vcard = new Contact_Vcard_Build('2.1');

    if ($defaults['contact_type'] == 'Individual') {
      $vcard->setName(CRM_Utils_Array::value('last_name', $defaults),
        CRM_Utils_Array::value('first_name', $defaults),
        CRM_Utils_Array::value('middle_name', $defaults),
        CRM_Utils_Array::value('prefix', $defaults),
        CRM_Utils_Array::value('suffix', $defaults)
      );
      $organizationName = CRM_Utils_Array::value('organization_name', $defaults);
      if ($organizationName !== NULL) {
        $vcard->addOrganization($organizationName);
      }
    }
    elseif ($defaults['contact_type'] == 'Organization') {
      $vcard->setName($defaults['organization_name'], '', '', '', '');
    }
    elseif ($defaults['contact_type'] == 'Household') {
      $vcard->setName($defaults['household_name'], '', '', '', '');
    }
    $vcard->setFormattedName($defaults['display_name']);
    $vcard->setSortString($defaults['sort_name']);

    if (!empty($defaults['nick_name'])) {
      $vcard->addNickname($defaults['nick_name']);
    }

    if (!empty($defaults['job_title'])) {
      $vcard->setTitle($defaults['job_title']);
    }

    if (!empty($defaults['birth_date'])) {
      $vcard->setBirthday(CRM_Utils_Array::value('birth_date', $defaults));
    }

    if (!empty($defaults['home_URL'])) {
      $vcard->setURL($defaults['home_URL']);
    }

    // TODO: $vcard->setGeo($lat, $lon);
    if (!empty($defaults['address'])) {
      $stateProvices = CRM_Core_PseudoConstant::stateProvince();
      $countries = CRM_Core_PseudoConstant::country();
      foreach ($defaults['address'] as $location) {
        // we don't keep PO boxes in separate fields
        $pob = '';
        $extend = CRM_Utils_Array::value('supplemental_address_1', $location);
        if (!empty($location['supplemental_address_2'])) {
          $extend .= ', ' . $location['supplemental_address_2'];
        }
        if (!empty($location['supplemental_address_3'])) {
          $extend .= ', ' . $location['supplemental_address_3'];
        }
        $street = CRM_Utils_Array::value('street_address', $location);
        $locality = CRM_Utils_Array::value('city', $location);
        $region = NULL;
        if (!empty($location['state_province_id'])) {
          $region = $stateProvices[CRM_Utils_Array::value('state_province_id', $location)];
        }
        $country = NULL;
        if (!empty($location['country_id'])) {
          $country = $countries[CRM_Utils_Array::value('country_id', $location)];
        }

        $postcode = CRM_Utils_Array::value('postal_code', $location);
        if (!empty($location['postal_code_suffix'])) {
          $postcode .= '-' . $location['postal_code_suffix'];
        }

        $vcard->addAddress($pob, $extend, $street, $locality, $region, $postcode, $country);
        $vcardName = $vcardNames[$location['location_type_id']];
        if ($vcardName) {
          $vcard->addParam('TYPE', $vcardName);
        }
        if (!empty($location['is_primary'])) {
          $vcard->addParam('TYPE', 'PREF');
        }
      }
    }
    if (!empty($defaults['phone'])) {
      foreach ($defaults['phone'] as $phone) {
        $vcard->addTelephone($phone['phone']);
        $vcardName = $vcardNames[$phone['location_type_id']];
        if ($vcardName) {
          $vcard->addParam('TYPE', $vcardName);
        }
        if ($phone['is_primary']) {
          $vcard->addParam('TYPE', 'PREF');
        }
      }
    }

    if (!empty($defaults['email'])) {
      foreach ($defaults['email'] as $email) {
        $vcard->addEmail($email['email']);
        $vcardName = $vcardNames[$email['location_type_id']];
        if ($vcardName) {
          $vcard->addParam('TYPE', $vcardName);
        }
        if ($email['is_primary']) {
          $vcard->addParam('TYPE', 'PREF');
        }
      }
    }

    // all that's left is sending the vCard to the browser
    $filename = CRM_Utils_String::munge($defaults['display_name']);
    $vcard->send($filename . '.vcf', 'attachment', 'utf-8');
    CRM_Utils_System::civiExit();
  }

}
