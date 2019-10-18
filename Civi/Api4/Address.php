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


namespace Civi\Api4;

/**
 * Address Entity.
 *
 * This entity holds the address informatiom of a contact. Each contact may hold
 * one or more addresses but must have different location types respectively.
 *
 * Creating a new address requires at minimum a contact's ID and location type ID
 *  and other attributes (although optional) like street address, city, country etc.
 *
 * @package Civi\Api4
 */
class Address extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Address\Create
   */
  public static function create() {
    return new \Civi\Api4\Action\Address\Create(__CLASS__, __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Action\Address\Save
   */
  public static function save() {
    return new \Civi\Api4\Action\Address\Save(__CLASS__, __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Action\Address\Update
   */
  public static function update() {
    return new \Civi\Api4\Action\Address\Update(__CLASS__, __FUNCTION__);
  }

}
