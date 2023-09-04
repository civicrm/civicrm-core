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

/**
 * Code shared by Address create/update/save actions
 *
 * @method bool getStreetParsing()
 * @method $this setStreetParsing(bool $streetParsing)
 * @method bool getSkipGeocode()
 * @method $this setSkipGeocode(bool $skipGeocode)
 * @method bool getFixAddress()
 * @method $this setFixAddress(bool $fixAddress)
 */
trait AddressSaveTrait {

  /**
   * Optional param to indicate you want the street_address field parsed into individual params
   *
   * @var bool
   */
  protected $streetParsing = FALSE;

  /**
   * Optional param to indicate you want to skip geocoding (useful when importing a lot of addresses at once, the job Geocode and Parse Addresses can execute this task after the import)
   *
   * @var bool
   */
  protected $skipGeocode = FALSE;

  /**
   * When true, apply various fixes to the address before insert.
   *
   * @var bool
   */
  protected $fixAddress = TRUE;

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    $saved = [];
    foreach ($items as $item) {
      if ($this->streetParsing && !empty($item['street_address'])) {
        $item = array_merge($item, \CRM_Core_BAO_Address::parseStreetAddress($item['street_address']));
      }
      $item['skip_geocode'] = $this->skipGeocode;
      if ($this->fixAddress) {
        \CRM_Core_BAO_Address::fixAddress($item);
      }
      $saved[] = \CRM_Core_BAO_Address::writeRecord($item);
    }
    return $saved;
  }

}
