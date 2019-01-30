<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

 /**
  * Test SMS provider to allow for testing
  */
class CiviTestSMSProvider extends CRM_SMS_Provider {
  protected $_providerInfo = array();
  protected $_id = 0;
  static private $_singleton = array();

  public function __construct($provider, $skipAuth = TRUE) {
    $this->provider = $provider;
  }

  public static function &singleton($providerParams = array(), $force = FALSE) {
    if (isset($providerParams['provider'])) {
      $providers = CRM_SMS_BAO_Provider::getProviders(NULL, array('name' => $providerParams['provider']));
      $provider = current($providers);
      $providerID = $provider['id'];
    }
    else {
      $providerID = CRM_Utils_Array::value('provider_id', $providerParams);
    }
    $skipAuth   = $providerID ? FALSE : TRUE;
    $cacheKey   = (int) $providerID;

    if (!isset(self::$_singleton[$cacheKey]) || $force) {
      $provider = array();
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
