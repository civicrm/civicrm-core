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
 * Class that uses google geocoder
 */
class CRM_Utils_Geocode_Google {

  /**
   * Server to retrieve the lat/long
   *
   * @var string
   */
  static protected $_server = 'maps.googleapis.com';

  /**
   * Uri of service.
   *
   * @var string
   */
  static protected $_uri = '/maps/api/geocode/xml?sensor=false&address=';

  /**
   * Function that takes an address object and gets the latitude / longitude for this
   * address. Note that at a later stage, we could make this function also clean up
   * the address into a more valid format
   *
   * @param array $values
   * @param bool $stateName
   *
   * @return bool
   *   true if we modified the address, false otherwise
   */
  public static function format(&$values, $stateName = FALSE) {
    // we need a valid country, else we ignore
    if (empty($values['country'])) {
      return FALSE;
    }

    $config = CRM_Core_Config::singleton();

    $add = '';

    if (!empty($values['street_address'])) {
      $add = urlencode(str_replace('', '+', $values['street_address']));
      $add .= ',+';
    }

    $city = CRM_Utils_Array::value('city', $values);
    if ($city) {
      $add .= '+' . urlencode(str_replace('', '+', $city));
      $add .= ',+';
    }

    if (!empty($values['state_province']) || (!empty($values['state_province_id']) && $values['state_province_id'] != 'null')) {
      if (!empty($values['state_province_id'])) {
        $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince', $values['state_province_id']);
      }
      else {
        if (!$stateName) {
          $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
            $values['state_province'],
            'name',
            'abbreviation'
          );
        }
        else {
          $stateProvince = $values['state_province'];
        }
      }

      // dont add state twice if replicated in city (happens in NZ and other countries, CRM-2632)
      if ($stateProvince != $city) {
        $add .= '+' . urlencode(str_replace('', '+', $stateProvince));
        $add .= ',+';
      }
    }

    if (!empty($values['postal_code'])) {
      $add .= '+' . urlencode(str_replace('', '+', $values['postal_code']));
      $add .= ',+';
    }

    if (!empty($values['country'])) {
      $add .= '+' . urlencode(str_replace('', '+', $values['country']));
    }

    if (!empty($config->geoAPIKey)) {
      $add .= '&key=' . urlencode($config->geoAPIKey);
    }

    $query = 'https://' . self::$_server . self::$_uri . $add;

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request($query);
    $request->sendRequest();
    $string = $request->getResponseBody();

    libxml_use_internal_errors(TRUE);
    $xml = @simplexml_load_string($string);
    CRM_Utils_Hook::geocoderFormat('Google', $values, $xml);
    if ($xml === FALSE) {
      // account blocked maybe?
      CRM_Core_Error::debug_var('Geocoding failed.  Message from Google:', $string);
      return FALSE;
    }

    if (isset($xml->status)) {
      if ($xml->status == 'OK' &&
        is_a($xml->result->geometry->location,
          'SimpleXMLElement'
        )
      ) {
        $ret = $xml->result->geometry->location->children();
        if ($ret->lat && $ret->lng) {
          $values['geo_code_1'] = (float) $ret->lat;
          $values['geo_code_2'] = (float) $ret->lng;
          return TRUE;
        }
      }
      elseif ($xml->status == 'ZERO_RESULTS') {
        // reset the geo code values if we did not get any good values
        $values['geo_code_1'] = $values['geo_code_2'] = 'null';
        return FALSE;
      }
      else {
        CRM_Core_Error::debug_var("Geocoding failed. Message from Google: ({$xml->status})", (string ) $xml->error_message);
        $values['geo_code_1'] = $values['geo_code_2'] = 'null';
        $values['geo_code_error'] = $xml->status;
        return FALSE;
      }
    }
  }

}
