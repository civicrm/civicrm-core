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

    $add = '';

    if (!empty($values['street_address'])) {
      $add = urlencode(str_replace('', '+', $values['street_address']));
      $add .= ',+';
    }

    $city = $values['city'] ?? NULL;
    if ($city) {
      $add .= '+' . urlencode(str_replace('', '+', $city));
      $add .= ',+';
    }

    if (!empty($values['state_province']) || (!empty($values['state_province_id']) && $values['state_province_id'] != 'null')) {
      if (!empty($values['state_province_id'])) {
        $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince', $values['state_province_id']) ?? '';
      }
      else {
        if (!$stateName) {
          $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
            $values['state_province'],
            'name',
            'abbreviation'
          ) ?? '';
        }
        else {
          $stateProvince = $values['state_province'] ?? '';
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

    $coord = self::makeRequest($add);

    $values['geo_code_1'] = $coord['geo_code_1'] ?? 'null';
    $values['geo_code_2'] = $coord['geo_code_2'] ?? 'null';

    if (isset($coord['geo_code_error'])) {
      $values['geo_code_error'] = $coord['geo_code_error'];
    }

    CRM_Utils_Hook::geocoderFormat('Google', $values, $coord['request_xml']);

    return isset($coord['geo_code_1'], $coord['geo_code_2']);
  }

  /**
   * @param string $address
   *   Plain text address
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public static function getCoordinates($address) {
    return self::makeRequest(urlencode($address));
  }

  /**
   * @param string $add
   *   Url-encoded address
   *
   * @return array
   *   An array of values with the following possible keys:
   *     geo_code_error: String error message
   *     geo_code_1: Float latitude
   *     geo_code_2: Float longitude
   *     request_xml: SimpleXMLElement parsed xml from geocoding API
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private static function makeRequest($add) {
    $coords = [];
    $config = CRM_Core_Config::singleton();
    if (!empty($config->geoAPIKey)) {
      $add .= '&key=' . urlencode($config->geoAPIKey);
    }

    $query = 'https://' . self::$_server . self::$_uri . $add;

    $client = new GuzzleHttp\Client();
    try {
      $request = $client->request('GET', $query, ['timeout' => \Civi::settings()->get('http_timeout')]);
    }
    catch (GuzzleHttp\Exception\ClientException $e) {
      CRM_Core_Error::debug_var('Geocoding failed.  Message from Guzzle:', $e->getMessage());
      $coords['geo_code_error'] = $e->getMessage();
      return $coords;
    }
    $string = $request->getBody();

    libxml_use_internal_errors(TRUE);
    $xml = @simplexml_load_string($string);
    $coords['request_xml'] = $xml;
    if ($xml === FALSE) {
      // account blocked maybe?
      CRM_Core_Error::debug_var('Geocoding failed.  Message from Google:', $string);
      $coords['geo_code_error'] = $string;
    }

    if (isset($xml->status)) {
      if ($xml->status == 'OK' &&
        is_a($xml->result->geometry->location,
          'SimpleXMLElement'
        )
      ) {
        $ret = $xml->result->geometry->location->children();
        if ($ret->lat && $ret->lng) {
          $coords['geo_code_1'] = (float) $ret->lat;
          $coords['geo_code_2'] = (float) $ret->lng;
        }
      }
      elseif ($xml->status != 'ZERO_RESULTS') {
        // 'ZERO_RESULTS' is a valid status, in which case we'll change nothing in $ret;
        // but if the status is anything else, we need to note the error.
        CRM_Core_Error::debug_var("Geocoding failed. Message from Google: ({$xml->status})", (string ) $xml->error_message);
        $coords['geo_code_error'] = $xml->status;
      }
    }
    return $coords;
  }

}
