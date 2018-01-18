<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * Class CRM_Utils_GeocodeFactory
 */
class CRM_Utils_GeocodeProviderFactory {

  /**
   * Create a geocode object of the system-configured type.
   *
   * @return CRM_Utils_Geocode
   * @throws CRM_Core_Exception
   */
  public static function create() {
    $geoCodeClassName = self::getUsableClassName();
    if ($geoCodeClassName === FALSE) {
      throw new CRM_Core_Exception('No valid geocoding provider enabled');
    }
    return new $geoCodeClassName;
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
    $provider = Civi::settings()->get('geoProvider');
    if (!is_subclass_of($provider, 'CRM_Utils_Geocode')) {
      // Checking if the method exists provides backwards compatibility for
      // contributed geocoders developed before the subclassing requirement
      if (method_exists($provider, 'format')) {
        Civi::log()->warning('Deprecation notice: Geocoders should extend core class CRM_Utils_Geocode', array('geocode_class' => $provider));
      }
      else {
        if (strlen($provider)) {
          Civi::log()->error('Configured geocoder has been removed from the system', array('geocode_class' => $provider));
        }
        return FALSE;
      }
    }
    return $provider;
  }

}
