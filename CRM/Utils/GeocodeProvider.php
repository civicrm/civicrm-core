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
 */
class CRM_Utils_GeocodeProvider {

  /**
   * Caches the provider class name. Disables geocoding when set to FALSE.
   *
   * @var string|bool
   */
  private static $providerClassName;

  /**
   * Instantiate a geocode object of the system-configured type.
   *
   * @return CRM_Utils_Geocode
   * @throws CRM_Core_Exception
   */
  public static function getConfiguredProvider() {
    $geoCodeClassName = self::getUsableClassName();
    if ($geoCodeClassName === FALSE) {
      throw new CRM_Core_Exception('No valid geocoding provider enabled');
    }
    return new $geoCodeClassName();
  }

  /**
   * Get the name of the geocoding class if enabled.
   *
   * This retrieves the geocoding class, checking it can be accessed.
   * Checks are done to mitigate the possibility it has been configured
   * and then the file has been removed.
   *
   * @return string|bool
   *   Class name if usable, else false.
   */
  public static function getUsableClassName() {
    if (self::$providerClassName === NULL) {
      $provider = Civi::settings()->get('geoProvider') ?? '';
      if (!class_exists($provider)) {
        if (class_exists('CRM_Utils_Geocode_' . $provider)) {
          $provider = 'CRM_Utils_Geocode_' . $provider;
        }
        else {
          if (strlen($provider)) {
            Civi::log()
              ->error('Configured geocoder has been removed from the system', ['geocode_class' => $provider]);
          }
          $provider = FALSE;
        }
      }

      // Ideally geocoding providers would be required to implement an interface
      // or extend a base class. While we identify and implement a geocoding
      // abstraction library (rather than continue to roll our own), we settle for
      // this check.
      if ($provider !== FALSE && !method_exists($provider, 'format')) {
        Civi::log()->error('Configured geocoder is invalid, must provide a format method', ['geocode_class' => $provider]);
        $provider = FALSE;
      }

      self::$providerClassName = $provider;
    }

    return self::$providerClassName;
  }

  /**
   * Disable GeoProvider within a session.
   *
   * This disables geocoding by causing getUsableClassName() to bail out.
   */
  public static function disableForSession() {
    self::$providerClassName = FALSE;
  }

  /**
   * Reset geoprovider (after settting has been changed).
   */
  public static function reset() {
    self::$providerClassName = NULL;
  }

}
