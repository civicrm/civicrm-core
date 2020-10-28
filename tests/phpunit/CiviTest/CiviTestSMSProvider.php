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
  * Test SMS provider to allow for testing
  */
class CiviTestSMSProvider extends CRM_SMS_Provider {
  protected $_providerInfo = [];
  protected $_id = 0;
  static private $_singleton = [];

  public function __construct($provider, $skipAuth = TRUE) {
    $this->provider = $provider;
  }

  public static function &singleton($providerParams = [], $force = FALSE) {
    if (isset($providerParams['provider'])) {
      $providers = CRM_SMS_BAO_Provider::getProviders(NULL, array('name' => $providerParams['provider']));
      $provider = current($providers);
      $providerID = $provider['id'] ?? NULL;
    }
    else {
      $providerID = $providerParams['provider_id'] ?? NULL;
    }
    $skipAuth   = $providerID ? FALSE : TRUE;
    $cacheKey   = (int) $providerID;

    if (!isset(self::$_singleton[$cacheKey]) || $force) {
      $provider = [];
      if ($providerID) {
        $provider = CRM_SMS_BAO_Provider::getProviderInfo($providerID);
      }
      self::$_singleton[$cacheKey] = new CiviTestSMSProvider($provider, $skipAuth);
    }
    return self::$_singleton[$cacheKey];
  }

  public function send($recipients, $header, $message, $dncID = NULL) {
  }

}
