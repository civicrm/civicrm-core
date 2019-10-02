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


namespace Civi\Api4\Action\GroupContact;

/**
 * @inheritDoc
 *
 * @method $this setMethod(string $method) Indicate who added/removed the group.
 * @method string getMethod()
 * @method $this setTracking(string $tracking) Specify ip address or other tracking info.
 * @method string getTracking()
 */
trait GroupContactSaveTrait {

  /**
   * String to indicate who added/removed the group.
   *
   * @var string
   */
  protected $method = 'API';

  /**
   * IP address or other tracking info about who performed this group subscription.
   *
   * @var string
   */
  protected $tracking = '';

  /**
   * @inheritDoc
   */
  protected function writeObjects($items) {
    foreach ($items as &$item) {
      $item['method'] = $this->method;
      $item['tracking'] = $this->tracking;
    }
    return parent::writeObjects($items);
  }

}
