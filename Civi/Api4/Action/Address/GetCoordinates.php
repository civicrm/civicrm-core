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

namespace Civi\Api4\Action\Address;

use Civi\Api4\Generic\Result;

/**
 * Converts an address string to lat/long coordinates.
 *
 * @method $this setAddress(string $address)
 * @method string getAddress()
 */
class GetCoordinates extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Address string to convert to lat/long
   *
   * @var string
   * @required
   */
  protected $address;

  public function _run(Result $result) {
    $geocodingClassName = \CRM_Utils_GeocodeProvider::getUsableClassName();
    $geocodingProvider = \CRM_Utils_GeocodeProvider::getConfiguredProvider();
    if (!is_callable([$geocodingProvider, 'getCoordinates'])) {
      throw new \CRM_Core_Exception('Geocoding provider does not support getCoordinates');
    }
    $coord = $geocodingClassName::getCoordinates($this->address);
    if (isset($coord['geo_code_1'], $coord['geo_code_2'])) {
      $result[] = $coord;
    }
    elseif (!empty($coord['geo_code_error'])) {
      throw new \CRM_Core_Exception('Geocoding failed. ' . $coord['geo_code_error']);
    }
  }

}
