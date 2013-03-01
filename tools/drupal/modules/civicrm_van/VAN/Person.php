<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */
class VAN_Person {
  static
  function &getPersonDetails($vanID) {
    $params = array('PersonID' => $vanID,
      'PersonIDType' => 'VANID',
      'options' => array('ReturnSections' => 'Address,Phone,District'),
    );

    require_once 'van/Auth.php';
    $details = van_Auth::invoke('GetPerson', $params);

    return self::formatPersonDetails($details->GetPersonResult);
  }

  static
  function &formatPersonDetails(&$person) {
    $map = array('FirstName' => 'first_name',
      'LastName' => 'last_name',
      'Sex' => 'Gender',
      'Email' => 'email',
      'Party' => 'custom_party',
      'VANID' => 'custom_van_id',
      'DateOfBirth' => 'birth_date',
    );
    $values = self::mapInfo($map, $person);

    $map = array('StreetAddress' => 'street_address',
      'City' => 'city',
      'State' => 'state_province',
      'Zip5' => 'postal_code',
      'Zip4' => 'postal_code_suffix',
      'AddressType' => 'location_type',
      'IsPreferred' => 'is_primary',
    );
    self::extractInfo('Addresses', 'Address', $person, $values, $map, 'address');

    $map = array('Number' => 'phone_number',
      'PhoneType' => 'phone_type',
      'IsPreferred' => 'is_primary',
    );
    self::extractInfo('Phones', 'Phone', $person, $values, $map, 'phone');

    /***
     $map = array( 'Email'      => 'email',
     'IsPreferred' => 'is_primary' );
     self::extractInfo( 'Emails', 'Email', $person, $values, $map, 'email' );
     ***/

    self::extractDistricts($person, $values);

    return $values;
  }

  function mapInfo(&$map, &$object) {
    $value = array();
    foreach ($map as $k => $v) {
      if (isset($object->$k)) {
        $value[$map[$k]] = $object->$k;
      }
    }
    return $value;
  }

  function extractInfo($primary, $secondary, &$object, &$values, &$map, $key) {
    if (isset($object->$primary) &&
      isset($object->$primary->$secondary)
    ) {
      $values[$key] = array();
      $actual = $object->$primary->$secondary;
      if (is_array($actual)) {
        foreach ($actual as $a) {
          self::extractInfoSingle($map, $a, $values[$key]);
        }
      }
      else {
        self::extractInfoSingle($map, $actual, $values[$key]);
      }
      if (empty($values[$key])) {
        unset($values[$key]);
      }
    }
  }

  function extractInfoSingle(&$map, &$object, &$values) {
    $value = self::mapInfo($map, $object);
    if (!empty($value)) {
      $values[] = $value;
    }
  }

  function extractDistricts(&$person, &$values) {
    $values['custom'] = array('van_id' => $values['custom_van_id'],
      'party' => $values['custom_party'],
    );
    unset($values['custom_van_id']);
    unset($values['custom_party']);

    if (isset($person->Districts) &&
      isset($person->Districts->District)
    ) {
      foreach ($person->Districts->District as $district) {
        $values['custom'][preg_replace('/\s+|\W+/', '_',
          strtolower($district->DistrictType)
        )] = $district->Name;
      }
    }
  }
}

