<?php

namespace Civi\Api4;
use Civi\Api4\Generic\AbstractEntity;

/**
 * Address Entity.
 *
 * This entity holds the address informatiom of a contact. Each contact may hold
 * one or more addresses but must have different location types respectively.
 *
 * Creating a new address requires at minimum a contact's ID and location type ID
 *  and other attributes (although optional) like street address, city, country etc.
 *
 * @method static Action\Address\Create create
 * @method static Action\Address\Update update
 *
 * @package Civi\Api4
 */
class Address extends AbstractEntity {

}
