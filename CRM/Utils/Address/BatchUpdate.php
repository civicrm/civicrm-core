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
 * A PHP cron script to format all the addresses in the database. Currently
 * it only does geocoding if the geocode values are not set. At a later
 * stage we will also handle USPS address cleanup and other formatting
 * issues
 *
 */
class CRM_Utils_Address_BatchUpdate {

  var $start = NULL;
  var $end = NULL;
  var $geocoding = 1;
  var $parse = 1;
  var $throttle = 0;

  var $returnMessages = array();
  var $returnError = 0;

  public function __construct($params) {

    foreach ($params as $name => $value) {
      $this->$name = $value;
    }

    // fixme: more params verification
  }

  public function run() {

    $config = &CRM_Core_Config::singleton();

    // do check for geocoding.
    $processGeocode = FALSE;
    if (empty($config->geocodeMethod)) {
      if ($this->geocoding == 'true') {
        $this->returnMessages[] = ts('Error: You need to set a mapping provider under Global Settings');
        $this->returnError = 1;
        $this->returnResult();
      }
    }
    else {
      $processGeocode = TRUE;
      // user might want to over-ride.
      if ($this->geocoding == 'false') {
        $processGeocode = FALSE;
      }
    }

    // do check for parse street address.
    $parseAddress = FALSE;
    $parseAddress = CRM_Utils_Array::value('street_address_parsing',
      CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options'
      ),
      FALSE
    );
    $parseStreetAddress = FALSE;
    if (!$parseAddress) {
      if ($this->parse == 'true') {
        $this->returnMessages[] = ts('Error: You need to enable Street Address Parsing under Global Settings >> Address Settings.');
        $this->returnError = 1;
        return $this->returnResult();
      }
    }
    else {
      $parseStreetAddress = TRUE;
      // user might want to over-ride.
      if ($this->parse == 'false') {
        $parseStreetAddress = FALSE;
      }
    }

    // don't process.
    if (!$parseStreetAddress && !$processGeocode) {
      $this->returnMessages[] = ts('Error: Both Geocode mapping as well as Street Address Parsing are disabled. You must configure one or both options to use this script.');
      $this->returnError = 1;
      return $this->returnResult();
    }

    // do check for parse street address.
    return $this->processContacts($config, $processGeocode, $parseStreetAddress);
  }

  function processContacts(&$config, $processGeocode, $parseStreetAddress) {
    // build where clause.
    $clause = array('( c.id = a.contact_id )');
    if ($this->start) {
      $clause[] = "( c.id >= $this->start )";
    }

    if ($this->end) {
      $clause[] = "( c.id <= $this->end )";
    }

    if ($processGeocode) {
      $clause[] = '( a.geo_code_1 is null OR a.geo_code_1 = 0 )';
      $clause[] = '( a.geo_code_2 is null OR a.geo_code_2 = 0 )';
      $clause[] = '( a.country_id is not null )';
    }

    $whereClause = implode(' AND ', $clause);

    $query = "
    SELECT     c.id,
               a.id as address_id,
               a.street_address,
               a.city,
               a.postal_code,
               s.name as state,
               o.name as country
    FROM       civicrm_contact  c
    INNER JOIN civicrm_address        a ON a.contact_id = c.id
    LEFT  JOIN civicrm_country        o ON a.country_id = o.id
    LEFT  JOIN civicrm_state_province s ON a.state_province_id = s.id
    WHERE      {$whereClause}
      ORDER BY a.id
    ";

    $totalGeocoded = $totalAddresses = $totalAddressParsed = 0;

    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    if ($processGeocode) {
      require_once (str_replace('_', DIRECTORY_SEPARATOR, $config->geocodeMethod) . '.php');
    }


    $unparseableContactAddress = array();
    while ($dao->fetch()) {
      $totalAddresses++;
      $params = array(
        'street_address' => $dao->street_address,
        'postal_code' => $dao->postal_code,
        'city' => $dao->city,
        'state_province' => $dao->state,
        'country' => $dao->country,
      );

      $addressParams = array();

      // process geocode.
      if ($processGeocode) {
        // loop through the address removing more information
        // so we can get some geocode for a partial address
        // i.e. city -> state -> country

        $maxTries = 5;
        do {
          if ($this->throttle) {
            usleep(5000000);
          }

          eval($config->geocodeMethod . '::format( $params, true );');
          array_shift($params);
          $maxTries--;
        } while ((!isset($params['geo_code_1'])) &&
          ($maxTries > 1)
        );

        if (isset($params['geo_code_1']) &&
          $params['geo_code_1'] != 'null'
        ) {
          $totalGeocoded++;
          $addressParams['geo_code_1'] = $params['geo_code_1'];
          $addressParams['geo_code_2'] = $params['geo_code_2'];
        }
      }

      // parse street address
      if ($parseStreetAddress) {
        $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($dao->street_address);
        $success = TRUE;
        // consider address is automatically parseable,
        // when we should found street_number and street_name
        if (!CRM_Utils_Array::value('street_name', $parsedFields) ||
          !CRM_Utils_Array::value('street_number', $parsedFields)
        ) {
          $success = FALSE;
        }

        // do check for all elements.
        if ($success) {
          $totalAddressParsed++;
        }
        elseif ($dao->street_address) {
          //build contact edit url,
          //so that user can manually fill the street address fields if the street address is not parsed, CRM-5886
          $url = CRM_Utils_System::url('civicrm/contact/add', "reset=1&action=update&cid={$dao->id}");
          $unparseableContactAddress[] = " Contact ID: " . $dao->id . " <a href =\"$url\"> " . $dao->street_address . " </a> ";
          // reset element values.
          $parsedFields = array_fill_keys(array_keys($parsedFields), '');
        }
        $addressParams = array_merge($addressParams, $parsedFields);
      }

      // finally update address object.
      if (!empty($addressParams)) {
        $address = new CRM_Core_DAO_Address();
        $address->id = $dao->address_id;
        $address->copyValues($addressParams);
        $address->save();
        $address->free();
      }
    }

    $this->returnMessages[] = ts("Addresses Evaluated: %1", array(
      1 => $totalAddresses)) . "\n";
    if ($processGeocode) {
      $this->returnMessages[] = ts("Addresses Geocoded: %1", array(
        1 => $totalGeocoded)) . "\n";
    }
    if ($parseStreetAddress) {
      $this->returnMessages[] = ts("Street Addresses Parsed: %1", array(
        1 => $totalAddressParsed)) . "\n";
      if ($unparseableContactAddress) {
        $this->returnMessages[] = "<br />\n" . ts("Following is the list of contacts whose address is not parsed:") . "<br />\n";
        foreach ($unparseableContactAddress as $contactLink) {
          $this->returnMessages[] = $contactLink . "<br />\n";
        }
      }
    }

    return $this->returnResult();
  }

  function returnResult() {
    $result             = array();
    $result['is_error'] = $this->returnError;
    $result['messages'] = implode("", $this->returnMessages);
    return $result;
  }
}

