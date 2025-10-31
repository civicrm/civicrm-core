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
 * A PHP cron script to format all the addresses in the database. Currently
 * it only does geocoding if the geocode values are not set. At a later
 * stage we will also handle USPS address cleanup and other formatting
 * issues
 */
class CRM_Utils_Address_BatchUpdate {

  public $start = NULL;
  public $end = NULL;
  public $geocoding = 1;
  public $parse = 1;
  public $throttle = 0;

  public $returnMessages = [];
  public $returnError = 0;

  /**
   * Class constructor.
   *
   * @param array $params
   */
  public function __construct($params) {

    foreach ($params as $name => $value) {
      $this->$name = $value;
    }

    // fixme: more params verification
  }

  /**
   * Run batch update.
   *
   * @return array
   */
  public function run() {

    // do check for geocoding.
    $processGeocode = FALSE;
    if (!CRM_Utils_GeocodeProvider::getUsableClassName()) {
      if (CRM_Utils_String::strtobool($this->geocoding) === TRUE) {
        $this->returnMessages[] = ts('Error: You need to set a mapping provider under Administer > System Settings > Mapping and Geocoding');
        $this->returnError = 1;
        return $this->returnResult();
      }
    }
    else {
      $processGeocode = TRUE;
      // user might want to over-ride.
      if (CRM_Utils_String::strtobool($this->geocoding) === FALSE) {
        $processGeocode = FALSE;
      }
    }

    // do check for parse street address.
    $parseAddress = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options')['street_address_parsing'] ?? FALSE;
    $parseStreetAddress = FALSE;
    if (!$parseAddress) {
      if (CRM_Utils_String::strtobool($this->parse) === TRUE) {
        $this->returnMessages[] = ts('Error: You need to enable Street Address Parsing under Administer > Localization > Address Settings.');
        $this->returnError = 1;
        return $this->returnResult();
      }
    }
    else {
      $parseStreetAddress = TRUE;
      // user might want to over-ride.
      if (CRM_Utils_String::strtobool($this->parse) === FALSE) {
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
    return $this->processContacts($processGeocode, $parseStreetAddress);
  }

  /**
   * Process contacts.
   *
   * @param bool $processGeocode
   * @param bool $parseStreetAddress
   *
   * @return array
   * @throws Exception
   */
  public function processContacts($processGeocode, $parseStreetAddress) {
    // build where clause.
    $clause = [];
    $params = [];
    if ($this->start) {
      $clause[] = "( c.id >= %1 )";
      $params[1] = [$this->start, 'Integer'];
    }

    if ($this->end) {
      $clause[] = "( c.id <= %2 )";
      $params[2] = [$this->end, 'Integer'];
    }

    if ($processGeocode) {
      $clause[] = '( a.geo_code_1 is null OR a.geo_code_1 = 0 )';
      $clause[] = '( a.geo_code_2 is null OR a.geo_code_2 = 0 )';
      // the scheduled job is ignoring trying to geocode addresses where manual_geocode is 1
      $clause[] = '( a.manual_geo_code = 0 )';
      $clause[] = '( a.country_id is not null )';
    }

    $whereClause = '';
    if (!empty($clause)) {
      $whereClause = 'WHERE ' . implode(' AND ', $clause);
    }

    $query = "
      SELECT c.id,
        a.id as address_id,
        a.street_address,
        a.city,
        a.postal_code,
        a.country_id,
        s.name as state,
        o.name as country
      FROM civicrm_address a
      LEFT JOIN civicrm_contact c
        ON a.contact_id = c.id
      LEFT JOIN civicrm_country o
        ON a.country_id = o.id
      LEFT JOIN civicrm_state_province s
        ON a.state_province_id = s.id
      {$whereClause}
      ORDER BY a.id
    ";

    $totalGeocoded = $totalAddresses = $totalAddressParsed = 0;

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $unparseableContactAddress = [];
    while ($dao->fetch()) {
      $totalAddresses++;
      $params = [
        'street_address' => $dao->street_address,
        'postal_code' => $dao->postal_code,
        'city' => $dao->city,
        'state_province' => $dao->state,
        'country' => $dao->country,
        'country_id' => $dao->country_id,
      ];

      $addressParams = [];

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

          CRM_Core_BAO_Address::addGeocoderData($params);

          // see if we got a geocode error, in this case we'll trigger a fatal
          // CRM-13760
          if (isset($params['geo_code_error']) && $params['geo_code_error'] == 'OVER_QUERY_LIMIT') {
            throw new CRM_Core_Exception('Aborting batch geocoding. Hit the over query limit on geocoder.');
          }

          array_shift($params);
          $maxTries--;
        } while (
          (!isset($params['geo_code_1']) || $params['geo_code_1'] == 'null') &&
          ($maxTries > 1)
        );

        if (isset($params['geo_code_1']) && $params['geo_code_1'] != 'null') {
          $totalGeocoded++;
          $addressParams = $params;
        }
        else {
          // If an address has failed in the geocoding scheduled job i.e. no lat/long is fetched, we will update the manual_geocode field to 1.
          $addressParams['manual_geo_code'] = TRUE;
          $addressParams['geo_code_1'] = $addressParams['geo_code_2'] = NULL;
        }
      }

      // parse street address
      if ($parseStreetAddress) {
        $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($dao->street_address);
        $success = TRUE;
        // consider address is automatically parseable,
        // when we should found street_number and street_name
        if (empty($parsedFields['street_name']) || empty($parsedFields['street_number'])) {
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
        \Civi\Api4\Address::update(FALSE)
          ->setValues($addressParams)
          ->addWhere('id', '=', $dao->address_id)
          ->execute();
      }
    }

    $this->returnMessages[] = ts("Addresses Evaluated: %1", [
      1 => $totalAddresses,
    ]) . "\n";
    if ($processGeocode) {
      $this->returnMessages[] = ts("Addresses Geocoded: %1", [
        1 => $totalGeocoded,
      ]) . "\n";
    }
    if ($parseStreetAddress) {
      $this->returnMessages[] = ts("Street Addresses Parsed: %1", [
        1 => $totalAddressParsed,
      ]) . "\n";
      if ($unparseableContactAddress) {
        $this->returnMessages[] = "<br />\n" . ts("Following is the list of contacts whose address is not parsed:") . "<br />\n";
        foreach ($unparseableContactAddress as $contactLink) {
          $this->returnMessages[] = $contactLink . "<br />\n";
        }
      }
    }

    return $this->returnResult();
  }

  /**
   * Return result.
   *
   * @return array
   */
  public function returnResult() {
    $result = [];
    $result['is_error'] = $this->returnError;
    $result['messages'] = '';
    // Pad message size to allow for prefix added by CRM_Core_JobManager.
    $messageSize = 255;
    // Ensure that each message can fit in the civicrm_job_log.data column.
    foreach ($this->returnMessages as $message) {
      $messageSize += strlen($message);
      if ($messageSize > CRM_Utils_Type::BLOB_SIZE) {
        $result['messages'] .= '...';
        break;
      }
      $result['messages'] .= $message;
    }
    return $result;
  }

}
