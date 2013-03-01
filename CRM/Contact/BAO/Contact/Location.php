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
class CRM_Contact_BAO_Contact_Location {

  /**
   * function to get the display name, primary email, location type and location id of a contact
   *
   * @param  int    $id id of the contact
   *
   * @return array  of display_name, email, location type and location id if found, or (null,null,null, null)
   * @static
   * @access public
   */
  static function getEmailDetails($id, $isPrimary = TRUE, $locationTypeID = NULL) {
    $primaryClause = NULL;
    if ($isPrimary) {
      $primaryClause = " AND civicrm_email.is_primary = 1";
    }


    $locationClause = NULL;
    if ($locationTypeID) {
      $locationClause = " AND civicrm_email.location_type_id = $locationTypeID";
    }

    $sql = "
SELECT    civicrm_contact.display_name,
          civicrm_email.email,
          civicrm_email.location_type_id,
          civicrm_email.id
FROM      civicrm_contact
LEFT JOIN civicrm_email ON ( civicrm_contact.id = civicrm_email.contact_id {$primaryClause} {$locationClause} )
WHERE     civicrm_contact.id = %1";

    $params = array(1 => array($id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->fetch()) {
      return array($dao->display_name, $dao->email, $dao->location_type_id, $dao->id);
    }
    return array(NULL, NULL, NULL, NULL);
  }

  /**
   * function to get the sms number and display name of a contact
   *
   * @param  int    $id id of the contact
   *
   * @return array    tuple of display_name and sms if found, or (null,null)
   * @static
   * @access public
   */
  static function getPhoneDetails($id, $type = NULL) {
    if (!$id) {
      return array(NULL, NULL);
    }

    $cond = NULL;
    if ($type) {
      $cond = " AND civicrm_phone.phone_type_id = '$type'";
    }


    $sql = "
   SELECT civicrm_contact.display_name, civicrm_phone.phone, civicrm_contact.do_not_sms
     FROM civicrm_contact
LEFT JOIN civicrm_phone ON ( civicrm_phone.contact_id = civicrm_contact.id )
    WHERE civicrm_phone.is_primary = 1
          $cond
      AND civicrm_contact.id = %1";

    $params = array(1 => array($id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->fetch()) {
      return array($dao->display_name, $dao->phone, $dao->do_not_sms);
    }
    return array(NULL, NULL, NULL);
  }

  /**
   * function to get the information to map a contact
   *
   * @param  array  $ids    the list of ids for which we want map info
   * $param  int    $locationTypeID
   *
   * @return null|string     display name of the contact if found
   * @static
   * @access public
   */
  static function &getMapInfo($ids, $locationTypeID = NULL, $imageUrlOnly = FALSE) {
    $idString = ' ( ' . implode(',', $ids) . ' ) ';
    $sql = "
   SELECT civicrm_contact.id as contact_id,
          civicrm_contact.contact_type as contact_type,
          civicrm_contact.contact_sub_type as contact_sub_type,
          civicrm_contact.display_name as display_name,
          civicrm_address.street_address as street_address,
          civicrm_address.supplemental_address_1 as supplemental_address_1,
          civicrm_address.supplemental_address_2 as supplemental_address_2,
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

    $params = array();
    if (!$locationTypeID) {
      $sql .= " AND civicrm_address.is_primary = 1";
    }
    else {
      $sql .= " AND civicrm_address.location_type_id = %1";
      $params[1] = array($locationTypeID, 'Integer');
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $locations = array();
    $config = CRM_Core_Config::singleton();

    while ($dao->fetch()) {
      $location = array();
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
        array(
          $dao->street_address,
          $dao->supplemental_address_1,
          $dao->supplemental_address_2,
          $dao->city,
        )
      );
      CRM_Utils_String::append($address, ', ',
        array($dao->state, $dao->postal_code)
      );
      CRM_Utils_String::append($address, '<br /> ',
        array($dao->country)
      );
      $location['address'] = addslashes($address);
      $location['displayAddress'] = str_replace('<br />', ', ', $address);
      $location['url'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $dao->contact_id);
      $location['location_type'] = $dao->location_type;
      $location['image'] = CRM_Contact_BAO_Contact_Utils::getImage(isset($dao->contact_sub_type) ?
        $dao->contact_sub_type : $dao->contact_type, $imageUrlOnly, $dao->contact_id
      );
      $locations[] = $location;
    }
    return $locations;
  }
}

