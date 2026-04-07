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
   * USPS API Base URL
   */
  const USPS_API_BASE = 'https://api.usps.com';

  /**
   * OAuth Token URL
   */
  const OAUTH_TOKEN_URL = 'https://apis.usps.com/oauth2/v3/token';

  /**
   * Address validation endpoint
   */
  const ADDRESS_VALIDATE_ENDPOINT = '/addresses/v3/address';

  /**
   * Cached OAuth token
   * @var array
   */
  protected static $accessToken = NULL;

  /**
   * Token expiration time
   * @var int
   */
  protected static $tokenExpiry = 0;

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
  public static function disable($disable = TRUE): void {
    self::$_disabled = $disable;
  }

  /**
   * Check if the USPS provider is configured and available
   *
   * @return bool
   */
  public static function isConfigured() {
    $provider = Civi::settings()->get('address_standardization_provider');

    if ($provider !== 'USPS') {
      return FALSE;
    }

    $consumerKey = Civi::settings()->get('address_standardization_key');
    $consumerSecret = Civi::settings()->get('address_standardization_secret');

    return !empty($consumerKey) && !empty($consumerSecret);
  }

  /**
   * Get OAuth Access Token
   *
   * @return string|null
   * @throws CRM_Core_Exception
   */
  protected static function getAccessToken() {
    // Return cached token if still valid
    $domainID = CRM_Core_Config::domainID();
    $settings = Civi::settings($domainID);

    $accessTokenUSPS = $settings->get('address_standardization_provider_usps_access_token');
    $tokenExpiryUSPS = $settings->get('address_standardization_provider_usps_token_expiry');
    if (self::$accessToken !== NULL && time() < self::$tokenExpiry) {
      return self::$accessToken;
    }

    if ($accessTokenUSPS !== NULL && time() < $tokenExpiryUSPS) {
      self::$accessToken = $accessTokenUSPS;
      self::$tokenExpiry = $tokenExpiryUSPS;
      return self::$accessToken;
    }
    $consumerKey = Civi::settings()->get('address_standardization_key');
    $consumerSecret = Civi::settings()->get('address_standardization_secret');

    if (empty($consumerKey) || empty($consumerSecret)) {
      throw new CRM_Core_Exception('USPS API credentials not configured');
    }

    $ch = curl_init(self::OAUTH_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
    ]);

    $postData = json_encode([
      'client_id' => $consumerKey,
      'client_secret' => $consumerSecret,
      'grant_type' => 'client_credentials',
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($curlError) {
      \Civi::log()->debug('USPS OAuth Error: ' . $curlError);
      return NULL;
    }

    if ($httpCode !== 200) {
      \Civi::log()->debug('USPS OAuth failed with HTTP ' . $httpCode . ': ' . $response);
      return NULL;
    }

    $data = json_decode($response, TRUE);
    if (empty($data['access_token'])) {
      \Civi::log()->debug('USPS OAuth response missing access_token');
      return NULL;
    }

    self::$accessToken = $data['access_token'];
    // Set expiry to 5 minutes before actual expiry for safety
    self::$tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 300;
    // Cache token in settings
    $settings->set('address_standardization_provider_usps_access_token', self::$accessToken);
    $settings->set('address_standardization_provider_usps_token_expiry', self::$tokenExpiry);

    return self::$accessToken;
  }

  /**
   * Format address values for submission to USPS
   *
   * @param array $values
   * @return array
   */
  protected static function formatAddressForAPI(array $values): array {
    $address = [];

    // Map CiviCRM fields to USPS API fields
    if (!empty($values['street_address'])) {
      $address['streetAddress'] = $values['street_address'];
    }

    if (!empty($values['supplemental_address_1'])) {
      $address['secondaryAddress'] = $values['supplemental_address_1'];
    }

    if (!empty($values['city'])) {
      $address['city'] = $values['city'];
    }

    if (!empty($values['state_province'])) {
      $address['state'] = $values['state_province'];
    }

    if (!empty($values['postal_code'])) {
      // Remove any non-numeric characters except hyphen
      $zipCode = preg_replace('/[^0-9-]/', '', $values['postal_code']);
      $address['ZIPCode'] = $zipCode;
    }

    return $address;
  }

  /**
   * Parse the USPS API response and format for CiviCRM
   *
   * @param array $response
   * @param array $originalValues
   * @return array|null
   */
  protected static function parseResponse($response, $originalValues) {
    if (empty($response['address'])) {
      return NULL;
    }

    $addr = $response['address'];
    $values = [];

    // Map USPS response fields to CiviCRM fields
    if (!empty($addr['streetAddress'])) {
      $values['street_address'] = $addr['streetAddress'];
    }

    if (!empty($addr['secondaryAddress'])) {
      $values['supplemental_address_1'] = $addr['secondaryAddress'];
    }

    if (!empty($addr['city'])) {
      $values['city'] = $addr['city'];
    }

    if (!empty($addr['state'])) {
      $values['state_province'] = $addr['state'];
    }

    if (!empty($addr['ZIPCode'])) {
      $values['postal_code'] = $addr['ZIPCode'];
    }

    if (!empty($addr['ZIPPlus4'])) {
      $values['postal_code_suffix'] = $addr['ZIPPlus4'];
      // $values['postal_code'] = $addr['ZIPCode'] . '-' . $addr['ZIPPlus4'];
    }

    // Preserve original country
    if (!empty($originalValues['country'])) {
      $values['country'] = $originalValues['country'];
    }

    return $values;
  }

  /**
   * Format address values according to USPS
   *
   * @param array $values
   * @return bool
   */
  public static function checkAddress(&$values): bool {
    // Check if disabled due to import.
    if (self::$_disabled) {
      return FALSE;
    }

    // Check if USPS is configured
    if (!self::isConfigured()) {
      return FALSE;
    }

    // Validate that we have minimum required fields
    if (empty($values['street_address']) || empty($values['city']) ||
      empty($values['state_province']) || empty($values['postal_code'])) {
      \Civi::log()->debug('USPS: Missing required address fields');
      return FALSE;
    }

    try {
      // Get OAuth token
      $accessToken = self::getAccessToken();
      if (!$accessToken) {
        \Civi::log()->debug('USPS: Failed to obtain access token');
        return FALSE;
      }

      // Format address for API
      $addressData = self::formatAddressForAPI($values);

      // Make API request
      $url = self::USPS_API_BASE . self::ADDRESS_VALIDATE_ENDPOINT . '?' . http_build_query($addressData);
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
      ]);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlError = curl_error($ch);

      if ($curlError) {
        \Civi::log()->debug('USPS API Error: ' . $curlError);

        return FALSE;
      }

      if ($httpCode !== 200) {
        \Civi::log()->debug('USPS API returned HTTP ' . $httpCode . ': ' . $response);
        return FALSE;
      }

      $data = json_decode($response, TRUE);
      if (empty($data)) {
        \Civi::log()->debug('USPS API returned invalid JSON');
        return FALSE;
      }

      // Parse and update values
      $standardizedAddress = self::parseResponse($data, $values);
      if ($standardizedAddress) {
        $values = array_merge($values, $standardizedAddress);
        return TRUE;
      }

      return FALSE;
    }
    catch (Exception $e) {
      \Civi::log()->debug('USPS Exception: ' . $e->getMessage());
      return FALSE;
    }
  }

}
