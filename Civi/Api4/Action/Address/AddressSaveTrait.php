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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4\Action\Address;

/**
 * @inheritDoc
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
  protected function writeObjects($items) {
    foreach ($items as &$item) {
      if ($this->streetParsing && !empty($item['street_address'])) {
        $item = array_merge($item, \CRM_Core_BAO_Address::parseStreetAddress($item['street_address']));
      }
      $item['skip_geocode'] = $this->skipGeocode;
    }
    return parent::writeObjects($items);
  }

}
