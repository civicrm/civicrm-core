<?php

namespace Civi\Api4;

/**
 * Contacts - Individuals, Organizations, Households.
 *
 * This is the central entity in the CiviCRM database, and links to
 * many other entities (Email, Phone, Participant, etc.).
 *
 * Creating a new contact requires at minimum a name or email address.
 *
 * @package Civi\Api4
 */
class Contact extends Generic\DAOEntity {

  /**
   * @return Action\Contact\Create
   */
  public static function create() {
    return new Action\Contact\Create(__CLASS__, __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Generic\DAOUpdateAction
   */
  public static function update() {
    // For some reason the contact bao requires this for updating
    return new Generic\DAOUpdateAction(__CLASS__, __FUNCTION__, ['id', 'contact_type']);
  }

}
