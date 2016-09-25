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
 * $Id$
 *
 */

/**
 * This class contains functions for managing Action Logs
 */
class CRM_Core_BAO_Country extends CRM_Core_DAO_Country {

  /**
   * Get the list of countries for which we offer provinces.
   *
   * @return mixed
   */
  public static function provinceLimit() {
    if (!isset(Civi::$statics[__CLASS__]['provinceLimit'])) {
      $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode();
      $provinceLimit = Civi::settings()->get('provinceLimit');
      $country = array();
      if (is_array($provinceLimit)) {
        foreach ($provinceLimit as $val) {
          // CRM-12007
          // some countries have disappeared and hence they might be in country limit
          // but not in the country table
          if (isset($countryIsoCodes[$val])) {
            $country[] = $countryIsoCodes[$val];
          }
        }
      }
      else {
        $country[] = $countryIsoCodes[$provinceLimit];
      }
      Civi::$statics[__CLASS__]['provinceLimit'] = $country;
    }
    return Civi::$statics[__CLASS__]['provinceLimit'];
  }

  /**
   * Get the list of countries (with names) which are available to user.
   *
   * @return mixed
   */
  public static function countryLimit() {
    if (!isset(Civi::$statics[__CLASS__]['countryLimit'])) {
      $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode();
      $country = array();
      $countryLimit = Civi::settings()->get('countryLimit');
      if (is_array($countryLimit)) {
        foreach ($countryLimit as $val) {
          // CRM-12007
          // some countries have disappeared and hence they might be in country limit
          // but not in the country table
          if (isset($countryIsoCodes[$val])) {
            $country[] = $countryIsoCodes[$val];
          }
        }
      }
      else {
        $country[] = $countryIsoCodes[$countryLimit];
      }
      Civi::$statics[__CLASS__]['countryLimit'] = $country;
    }
    return Civi::$statics[__CLASS__]['countryLimit'];
  }

  /**
   * Provide cached default contact country.
   *
   * @return string
   */
  public static function defaultContactCountry() {
    static $cachedContactCountry = NULL;
    $defaultContactCountry = Civi::settings()->get('defaultContactCountry');

    if (!empty($defaultContactCountry) && !$cachedContactCountry) {
      $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode();
      $cachedContactCountry = CRM_Utils_Array::value($defaultContactCountry,
        $countryIsoCodes
      );
    }
    return $cachedContactCountry;
  }

  /**
   * Provide cached default country name.
   *
   * @return string
   */
  public static function defaultContactCountryName() {
    static $cachedContactCountryName = NULL;
    $defaultContactCountry = Civi::settings()->get('defaultContactCountry');
    if (!$cachedContactCountryName && $defaultContactCountry) {
      $countryCodes = CRM_Core_PseudoConstant::country();
      $cachedContactCountryName = $countryCodes[$defaultContactCountry];
    }
    return $cachedContactCountryName;
  }

  /**
   * Provide cached default currency symbol.
   *
   * @param string $defaultCurrency
   *
   * @return string
   */
  public static function defaultCurrencySymbol($defaultCurrency = NULL) {
    static $cachedSymbol = NULL;
    if (!$cachedSymbol || $defaultCurrency) {
      $currency = $defaultCurrency ? $defaultCurrency : Civi::settings()->get('defaultCurrency');
      if ($currency) {
        $currencySymbols = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', array(
          'labelColumn' => 'symbol',
          'orderColumn' => TRUE,
        ));
        $cachedSymbol = CRM_Utils_Array::value($currency, $currencySymbols, '');
      }
      else {
        $cachedSymbol = '$';
      }
    }
    return $cachedSymbol;
  }

  public static function getDefaultCurrencySymbol($k = NULL) {
    $config = CRM_Core_Config::singleton();
    return $config->defaultCurrencySymbol(Civi::settings()->get('defaultCurrency'));
  }

}
