<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
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

    if (!empty($values['state_province'])) {
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
      if ($xml->status == 'OK') {

        if (!empty($xml->result->address_component)) {
          foreach($xml->result->address_component as $component) {
            if ($component->type == 'postal_code') {
              $ret_postal = $component->long_name;
              break;
            }
          }
          if (!empty($ret_postal)) {
            $current_pc = CRM_Utils_Array::value('postal_code', $values);
            $skip_postal = FALSE;

            if ($current_pc) {
              $current_pc_suffix = CRM_Utils_Array::value('postal_code_suffix', $values);
              $current_pc_complete = $current_pc . $current_pc_suffix;
              $new_pc_complete = preg_replace("/[+-]/", '', $ret_postal);

              // if a postal code was already entered, don't change it, except to make it more precise
              if (strpos($new_pc_complete, $current_pc_complete) !== 0) {
                // Don't bother anonymous users with the message - they can't change a form they just submitted anyway
                if (CRM_Utils_System::isUserLoggedIn()) {
                  $msg = ts('The Google Geocoding system returned a different postal code (%1) than the one you entered (%2). If you want the Google value, please delete the current postal code and save again.', array(
                    1 => $ret_postal,
                    2 => $current_pc_suffix ? "$current_pc-$current_pc_suffix" : $current_pc,
                  ));

                  CRM_Core_Session::setStatus($msg, ts('Postal Code Mismatch'), 'error');
                }
                $skip_postal = TRUE;
              }
            }

            if (!$skip_postal) {
              $values['postal_code'] = $ret_postal;

              /* the following logic to split the string was borrowed from
              CRM/Core/BAO/Address.php -- CRM_Core_BAO_Address::fixAddress.
              This is actually the function that calls the geocoding
              script to begin with, but the postal code business takes
              place before geocoding gets called.
               */

              if (preg_match('/^(\d{4,5})[+-](\d{4})$/',
                $ret_postal,
                $match
              )
              ) {
                $values['postal_code'] = $match[1];
                $values['postal_code_suffix'] = $match[2];
              }
            }
          }
        }

        if (is_a($xml->result->geometry->location, 'SimpleXMLElement')) {
          $ret = $xml->result->geometry->location->children();
          if ($ret->lat && $ret->lng) {
            $values['geo_code_1'] = (float) $ret->lat;
            $values['geo_code_2'] = (float) $ret->lng;
            return TRUE;
          }
        }
      }
      elseif ($xml->status == 'OVER_QUERY_LIMIT') {
        CRM_Core_Error::debug_var('Geocoding failed. Message from Google: ', (string ) $xml->status);
        $values['geo_code_1'] = $values['geo_code_2'] = 'null';
        $values['geo_code_error'] = $xml->status;
        return FALSE;
      }
    }

    // reset the geo code values if we did not get any good values
    $values['geo_code_1'] = $values['geo_code_2'] = 'null';
    return FALSE;
  }

}
