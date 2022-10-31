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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Address utilities.
 */
class CRM_Utils_Address_USPS {

  /**
   * Whether USPS validation should be disabled during import.
   *
   * @var bool
   */
  protected static $_disabled = FALSE;

  /**
   * Disable the USPS validation.
   *
   * @param bool $disable
   */
  public static function disable($disable = TRUE) {
    self::$_disabled = $disable;
  }

  /**
   * Check address against USPS.
   *
   * @param array $values
   *
   * @return bool
   */
  public static function checkAddress(&$values) {
    if (self::$_disabled) {
      return FALSE;
    }
    if (!isset($values['street_address']) ||
      (!isset($values['city']) &&
        !isset($values['state_province']) &&
        !isset($values['postal_code'])
      )
    ) {
      return FALSE;
    }

    $userID = Civi::settings()->get('address_standardization_userid');
    $url = Civi::settings()->get('address_standardization_url');

    if (empty($userID) ||
      empty($url)
    ) {
      return FALSE;
    }

    $address2 = str_replace(',', '', $values['street_address']);

    $XMLQuery = '<AddressValidateRequest USERID="' . $userID . '"><Address ID="0"><Address1>' . CRM_Utils_Array::value('supplemental_address_1', $values, '') . '</Address1><Address2>' . $address2 . '</Address2><City>' . $values['city'] . '</City><State>' . $values['state_province'] . '</State><Zip5>' . $values['postal_code'] . '</Zip5><Zip4>' . CRM_Utils_Array::value('postal_code_suffix', $values, '') . '</Zip4></Address></AddressValidateRequest>';

    $client = new GuzzleHttp\Client();
    $request = $client->request('GET', $url, [
      'query' => [
        'API' => 'Verify',
        'XML' => $XMLQuery,
      ],
      'timeout' => \Civi::settings()->get('http_timeout'),
    ]);

    $session = CRM_Core_Session::singleton();

    $code = $request->getStatusCode();
    if ($code != 200) {
      $session->setStatus(ts('USPS Address Lookup Failed with HTTP status code: %1',
        [1 => $code]
      ));
      return FALSE;
    }

    $responseBody = $request->getBody();

    $xml = simplexml_load_string($responseBody);

    if (is_null($xml) || is_null($xml->Address)) {
      $session->setStatus(ts('Your USPS API Lookup has Failed.'));
      return FALSE;
    }

    if ($xml->Number == '80040b1a') {
      $session->setStatus(ts('Your USPS API Authorization has Failed.'));
      return FALSE;
    }

    if (property_exists($xml->Address, 'Error')) {
      $session->setStatus(ts('Address not found in USPS database.'));
      return FALSE;
    }

    $values['street_address'] = (string) $xml->Address->Address2;
    $values['city'] = (string) $xml->Address->City;
    $values['state_province'] = (string) $xml->Address->State;
    $values['postal_code'] = (string) $xml->Address->Zip5;
    $values['postal_code_suffix'] = (string) $xml->Address->Zip4;

    if (property_exists($xml->Address, 'Address1')) {
      $values['supplemental_address_1'] = (string) $xml->Address->Address1;
    }

    return TRUE;
  }

}
