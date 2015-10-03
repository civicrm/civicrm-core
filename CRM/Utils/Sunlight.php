<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Utils_Sunlight {
  static $_apiURL = 'http://api.sunlightlabs.com/';
  static $_apiKey = NULL;

  /**
   * @param $uri
   *
   * @return SimpleXMLElement
   * @throws Exception
   */
  public static function makeAPICall($uri) {
    require_once 'HTTP/Request.php';
    $params = array(
      'method' => HTTP_REQUEST_METHOD_GET,
      'allowRedirects' => FALSE,
    );

    $request = new HTTP_Request(self::$_apiURL . $uri, $params);
    $result = $request->sendRequest();
    if (PEAR::isError($result)) {
      CRM_Core_Error::fatal($result->getMessage());
    }
    if ($request->getResponseCode() != 200) {
      CRM_Core_Error::fatal(ts('Invalid response code received from Sunlight servers: %1',
        array(1 => $request->getResponseCode())
      ));
    }
    $string = $request->getResponseBody();
    return simplexml_load_string($string);
  }

  /**
   * @param $zipcode
   *
   * @return array
   */
  public static function getCityState($zipcode) {
    $key = self::$_apiKey;
    $uri = "places.getCityStateFromZip.php?zip={$zipcode}&apikey={$key}&output=xml";
    $xml = self::makeAPICall($uri);

    return array($xml->city, $xml->state);
  }

  /**
   * @param int $peopleID
   *
   * @return array
   */
  public static function getDetailedInfo($peopleID) {
    $key = self::$_apiKey;
    $uri = "people.getPersonInfo.php?id={$peopleID}&apikey={$key}&output=xml";
    $xml = self::makeAPICall($uri);

    $result = array();
    $fields = array(
      'title' => 'title',
      'firstname' => 'first_name',
      'lastname' => 'last_name',
      'gender' => 'gender',
      'party' => 'party',
      'congress_office' => 'address',
      'phone' => 'phone',
      'email' => 'email',
      'congresspedia' => 'url',
      'photo' => 'image_url',
      'webform' => 'contact_url',
    );

    foreach ($fields as $old => $new) {
      $result[$new] = (string ) $xml->$old;
    }

    $result['image_url'] = 'http://sunlightlabs.com/widgets/popuppoliticians/resources/images/' . $result['image_url'];

    return $result;
  }

  /**
   * @param $uri
   *
   * @return array
   */
  public static function getPeopleInfo($uri) {
    $xml = self::makeAPICall($uri);

    $result = array();
    foreach ($xml->entity_id_list->entity_id as $key => $value) {
      $result[] = self::getDetailedInfo($value);
    }
    return $result;
  }

  /**
   * @param $city
   * @param $state
   *
   * @return array|null
   */
  public static function getRepresentativeInfo($city, $state) {
    if (!$city ||
      !$state
    ) {
      return NULL;
    }
    $key = self::$_apiKey;
    $city = urlencode($city);
    $uri = "people.reps.getRepsFromCityState.php?city={$city}&state={$state}&apikey={$key}&output=xml";
    return self::getPeopleInfo($uri);
  }

  /**
   * @param $state
   *
   * @return array|null
   */
  public static function getSenatorInfo($state) {
    if (!$state) {
      return NULL;
    }

    $key = self::$_apiKey;
    $uri = "people.sens.getSensFromState.php?state={$state}&apikey={$key}&output=xml";
    return self::getPeopleInfo($uri);
  }

  /**
   * @param $city
   * @param $state
   * @param null $zipcode
   *
   * @return array
   */
  public static function getInfo($city, $state, $zipcode = NULL) {
    if ($zipcode) {
      list($city, $state) = self::getCityState($zipcode);
    }

    $reps = self::getRepresentativeInfo($city, $state);
    $sens = self::getSenatorInfo($state);

    $result = array();
    if (is_array($reps)) {
      $result = array_merge($result, $reps);
    }
    if (is_array($sens)) {
      $result = array_merge($result, $sens);
    }

    return $result;
  }

}
