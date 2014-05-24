<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

define('THROTTLE_REQUESTS', 0);
function run() {
  session_start();

  require_once '../civicrm.config.php';
  require_once 'CRM/Core/Config.php';

  $config = CRM_Core_Config::singleton();

  require_once 'Console/Getopt.php';
  $shortOptions = "n:p:s:e:k:g:parse";
  $longOptions = array('name=', 'pass=', 'key=', 'start=', 'end=', 'geocoding=', 'parse=');

  $getopt = new Console_Getopt();
  $args = $getopt->readPHPArgv();

  array_shift($args);
  list($valid, $dontCare) = $getopt->getopt2($args, $shortOptions, $longOptions);

  $vars = array(
    'start' => 's',
    'end' => 'e',
    'name' => 'n',
    'pass' => 'p',
    'key' => 'k',
    'geocoding' => 'g',
    'parse' => 'ap',
  );

  foreach ($vars as $var => $short) {
    $$var = NULL;
    foreach ($valid as $v) {
      if ($v[0] == $short || $v[0] == "--$var") {
        $$var = $v[1];
        break;
      }
    }
    if (!$$var) {
      $$var = CRM_Utils_Array::value($var, $_REQUEST);
    }
    $_REQUEST[$var] = $$var;
  }

  // this does not return on failure
  // require_once 'CRM/Utils/System.php';
  CRM_Utils_System::authenticateScript(TRUE, $name, $pass);

  //log the execution of script
  CRM_Core_Error::debug_log_message('UpdateAddress.php');

  // do check for geocoding.
  $processGeocode = FALSE;
  if (empty($config->geocodeMethod)) {
    if ($geocoding == 'true') {
      echo ts('Error: You need to set a mapping provider under Global Settings');
      exit();
    }
  }
  else {
    $processGeocode = TRUE;
    // user might want to over-ride.
    if ($geocoding == 'false') {
      $processGeocode = FALSE;
    }
  }

  // do check for parse street address.
  require_once 'CRM/Core/BAO/Setting.php';
  $parseAddress = CRM_Utils_Array::value('street_address_parsing',
    CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options'
    ),
    FALSE
  );
  $parseStreetAddress = FALSE;
  if (!$parseAddress) {
    if ($parse == 'true') {
      echo ts('Error: You need to enable Street Address Parsing under Global Settings >> Address Settings.');
      exit();
    }
  }
  else {
    $parseStreetAddress = TRUE;
    // user might want to over-ride.
    if ($parse == 'false') {
      $parseStreetAddress = FALSE;
    }
  }

  // don't process.
  if (!$parseStreetAddress && !$processGeocode) {
    echo ts('Error: Both Geocode mapping as well as Street Address Parsing are disabled. You must configure one or both options to use this script.');
    exit();
  }

  // we have an exclusive lock - run the mail queue
  processContacts($config, $processGeocode, $parseStreetAddress, $start, $end);
}

/**
 * @param $config
 * @param $processGeocode
 * @param $parseStreetAddress
 * @param null $start
 * @param null $end
 */
function processContacts(&$config, $processGeocode, $parseStreetAddress, $start = NULL, $end = NULL) {
  // build where clause.
  $clause = array('( c.id = a.contact_id )');
  if ($start) {
    $clause[] = "( c.id >= $start )";
  }
  if ($end) {
    $clause[] = "( c.id <= $end )";
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

  require_once 'CRM/Core/DAO/Address.php';
  require_once 'CRM/Core/BAO/Address.php';

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
        if (defined('THROTTLE_REQUESTS') &&
          THROTTLE_REQUESTS
        ) {
          usleep(50000);
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
      $address = new CRM_Core_DAO_Address();
      $address->id = $dao->address_id;
      $address->copyValues($addressParams);
      $address->save();
      $address->free();
    }
  }

  echo ts("Addresses Evaluated: $totalAddresses\n");
  if ($processGeocode) {
    echo ts("Addresses Geocoded : $totalGeocoded\n");
  }
  if ($parseStreetAddress) {
    echo ts("Street Address Parsed : $totalAddressParsed\n");
    if ($unparseableContactAddress) {
      echo ts("<br />\nFollowing is the list of contacts whose address is not parsed :<br />\n");
      foreach ($unparseableContactAddress as $contactLink) {
        echo ts("%1<br />\n", array(1 => $contactLink));
      }
    }
  }

  return;
}

run();

