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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Address utilties
 */
class CRM_Utils_Address_USPS {

  /**
   * @param $values
   *
   * @return bool
   */
  static function checkAddress(&$values) {
    if (!isset($values['street_address']) ||
      (!isset($values['city']) &&
        !isset($values['state_province']) &&
        !isset($values['postal_code'])
      )
    ) {
      return FALSE;
    }


    $userID = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME,
      'address_standardization_userid'
    );
    $url = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME,
      'address_standardization_url'
    );

    if (empty($userID) ||
      empty($url)
    ) {
      return FALSE;
    }

    $address2 = str_replace(',', '', $values['street_address']);

    $XMLQuery = '<AddressValidateRequest USERID="' . $userID . '"><Address ID="0"><Address1>' . CRM_Utils_Array::value('supplemental_address_1', $values, '') . '</Address1><Address2>' . $address2 . '</Address2><City>' . $values['city'] . '</City><State>' . $values['state_province'] . '</State><Zip5>' . $values['postal_code'] . '</Zip5><Zip4>' . CRM_Utils_Array::value('postal_code_suffix', $values, '') . '</Zip4></Address></AddressValidateRequest>';

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request();

    $request->setURL($url);

    $request->addQueryString('API', 'Verify');
    $request->addQueryString('XML', $XMLQuery);

    $response = $request->sendRequest();

    $session = CRM_Core_Session::singleton();

    $code = $request->getResponseCode();
    if ($code != 200) {
      $session->setStatus(ts('USPS Address Lookup Failed with HTTP status code: %1',
          array(1 => $code)
        ));
      return FALSE;
    }

    $responseBody = $request->getResponseBody();

    $xml = simplexml_load_string($responseBody);

    if (is_null($xml) || is_null($xml->Address)) {
      $session->setStatus(ts('Your USPS API Lookup has Failed.'));
      return FALSE;
    }

    if ($xml->Number == '80040b1a') {
      $session->setStatus(ts('Your USPS API Authorization has Failed.'));
      return FALSE;
    }

    if (array_key_exists('Error', $xml->Address)) {
      $session->setStatus(ts('Address not found in USPS database.'));
      return FALSE;
    }

    $values['street_address'] = (string)$xml->Address->Address2;
    $values['city'] = (string)$xml->Address->City;
    $values['state_province'] = (string)$xml->Address->State;
    $values['postal_code'] = (string)$xml->Address->Zip5;
    $values['postal_code_suffix'] = (string)$xml->Address->Zip4;

    if (array_key_exists('Address1', $xml->Address)) {
      $values['supplemental_address_1'] = (string)$xml->Address->Address1;
    }

    return TRUE;
  }
}

