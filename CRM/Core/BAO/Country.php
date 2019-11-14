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
      $country = [];
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
      $country = [];
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
        $currencySymbols = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', [
          'labelColumn' => 'symbol',
          'orderColumn' => TRUE,
        ]);
        $cachedSymbol = CRM_Utils_Array::value($currency, $currencySymbols, '');
      }
      else {
        $cachedSymbol = '$';
      }
    }
    return $cachedSymbol;
  }

  /**
   * Get the default currency symbol.
   *
   * @param string $k Unused variable
   *
   * @return string
   */
  public static function getDefaultCurrencySymbol($k = NULL) {
    return CRM_Core_BAO_Country::defaultCurrencySymbol(\Civi::settings()->get('defaultCurrency'));
  }

}
