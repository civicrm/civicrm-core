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
class CRM_Contact_BAO_Contact_Location {

  /**
   * Get the display name, primary email, location type and location id of a contact.
   *
   * @param int $id
   *   Id of the contact.
   *
   * @param bool $isPrimary
   * @param int $locationTypeID
   *
   * @return array
   *   Array of display_name, email, location type and location id if found, or (null,null,null, null)
   */
  public static function getEmailDetails($id, $isPrimary = TRUE, $locationTypeID = NULL) {
    $params = [
      'contact_id' => $id,
      'return' => ['display_name', 'email.email'],
      'api.Email.get' => [
        'location_type_id' => $locationTypeID,
        'sequential' => 0,
        'return' => ['email', 'location_type_id', 'id'],
      ],
    ];
    if ($isPrimary) {
      $params['api.Email.get']['is_primary'] = 1;
    }

    $contacts = civicrm_api3('Contact', 'get', $params);
    if ($contacts['count'] > 0) {
      $contact = reset($contacts['values']);
      if ($contact['api.Email.get']['count'] > 0) {
        $email = reset($contact['api.Email.get']['values']);
      }
    }
    $returnParams = [
      (isset($contact['display_name'])) ? $contact['display_name'] : NULL,
      (isset($email['email'])) ? $email['email'] : NULL,
      (isset($email['location_type_id'])) ? $email['location_type_id'] : NULL,
      (isset($email['id'])) ? $email['id'] : NULL,
    ];

    return $returnParams;
  }

  /**
   * Get the information to map a contact.
   *
   * @param array $ids
   *   The list of ids for which we want map info.
   * $param  int    $locationTypeID
   *
   * @param int $locationTypeID
   * @param bool $imageUrlOnly
   *
   * @return null|string
   *   display name of the contact if found
   */
  public static function &getMapInfo($ids, $locationTypeID = NULL, $imageUrlOnly = FALSE) {
    $idString = ' ( ' . implode(',', $ids) . ' ) ';
    $sql = "
   SELECT civicrm_contact.id as contact_id,
          civicrm_contact.contact_type as contact_type,
          civicrm_contact.contact_sub_type as contact_sub_type,
          civicrm_contact.display_name as display_name,
          civicrm_address.street_address as street_address,
          civicrm_address.supplemental_address_1 as supplemental_address_1,
          civicrm_address.supplemental_address_2 as supplemental_address_2,
          civicrm_address.supplemental_address_3 as supplemental_address_3,
          civicrm_address.city as city,
          civicrm_address.postal_code as postal_code,
          civicrm_address.postal_code_suffix as postal_code_suffix,
          civicrm_address.geo_code_1 as latitude,
          civicrm_address.geo_code_2 as longitude,
          civicrm_state_province.abbreviation as state,
          civicrm_country.name as country,
          civicrm_location_type.name as location_type
     FROM civicrm_contact
LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id
LEFT JOIN civicrm_state_province ON civicrm_address.state_province_id = civicrm_state_province.id
LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
LEFT JOIN civicrm_location_type ON civicrm_location_type.id = civicrm_address.location_type_id
WHERE civicrm_address.geo_code_1 IS NOT NULL
AND civicrm_address.geo_code_2 IS NOT NULL
AND civicrm_contact.id IN $idString ";

    $params = [];
    if (!$locationTypeID) {
      $sql .= ' AND civicrm_address.is_primary = 1';
    }
    else {
      $sql .= ' AND civicrm_address.location_type_id = %1';
      $params[1] = [$locationTypeID, 'Integer'];
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $locations = [];
    $config = CRM_Core_Config::singleton();

    while ($dao->fetch()) {
      $location = [];
      $location['contactID'] = $dao->contact_id;
      $location['displayName'] = addslashes($dao->display_name);
      $location['city'] = $dao->city;
      $location['state'] = $dao->state;
      $location['postal_code'] = $dao->postal_code;
      $location['lat'] = $dao->latitude;
      $location['lng'] = $dao->longitude;
      $location['marker_class'] = $dao->contact_type;
      $address = '';

      CRM_Utils_String::append($address, '<br />',
        [
          $dao->street_address,
          $dao->supplemental_address_1,
          $dao->supplemental_address_2,
          $dao->supplemental_address_3,
          $dao->city,
        ]
      );
      CRM_Utils_String::append($address, ', ',
        [$dao->state, $dao->postal_code]
      );
      CRM_Utils_String::append($address, '<br /> ',
        [$dao->country]
      );
      // OpenLayers throws an error if they get an unexpected line break in the
      // address.
      $location['address'] = addslashes(str_replace(["\n", "\r"], ' ', $address));
      $location['displayAddress'] = str_replace('<br />', ', ', addslashes($address));
      $location['url'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $dao->contact_id);
      $location['location_type'] = $dao->location_type;
      $location['image'] = CRM_Contact_BAO_Contact_Utils::getImage($dao->contact_sub_type ?? $dao->contact_type, $imageUrlOnly, $dao->contact_id
      );
      $locations[] = $location;
    }
    return $locations;
  }

}
