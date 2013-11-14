<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.4                                                |
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

/**
 * Class that uses google geocoder
 */
class CRM_Utils_Geocode_Google {

  /**
   * server to retrieve the lat/long
   *
   * @var string
   * @static
   */
  static protected $_server = 'maps.googleapis.com';

  /**
   * uri of service
   *
   * @var string
   * @static
   */
  static protected $_uri = '/maps/api/geocode/xml?sensor=false&address=';

  /**
   * function that takes an address object and gets the latitude / longitude for this
   * address. Note that at a later stage, we could make this function also clean up
   * the address into a more valid format
   *
   * @param object $address
   *
   * @return boolean true if we modified the address, false otherwise
   * @static
   */
  static function format(&$values, $stateName = FALSE) {
    // we need a valid country, else we ignore
    if (!CRM_Utils_Array::value('country', $values)) {
      return FALSE;
    }

    $config = CRM_Core_Config::singleton();

    $add = '';

    if (CRM_Utils_Array::value('street_address', $values)) {
      $add = urlencode(str_replace('', '+', $values['street_address']));
      $add .= ',+';
    }

    $city = CRM_Utils_Array::value('city', $values);
    if ($city) {
      $add .= '+' . urlencode(str_replace('', '+', $city));
      $add .= ',+';
    }

    if (CRM_Utils_Array::value('state_province', $values)) {
      if (CRM_Utils_Array::value('state_province_id', $values)) {
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

    if (CRM_Utils_Array::value('postal_code', $values)) {
      $add .= '+' . urlencode(str_replace('', '+', $values['postal_code']));
      $add .= ',+';
    }

    if (CRM_Utils_Array::value('country', $values)) {
      $add .= '+' . urlencode(str_replace('', '+', $values['country']));
    }

    $query = 'http://' . self::$_server . self::$_uri . $add;

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request($query);
    $request->sendRequest();
    $string = $request->getResponseBody();

    libxml_use_internal_errors(TRUE);
    $xml = @simplexml_load_string($string);
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
          $values['geo_code_1'] = (float)$ret->lat;
          $values['geo_code_2'] = (float)$ret->lng;
          return TRUE;
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

