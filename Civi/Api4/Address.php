<?php

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
