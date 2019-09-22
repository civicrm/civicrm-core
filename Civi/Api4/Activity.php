<?php

namespace Civi\Api4;

/**
 * Activity entity.
 *
 * This entity adds record of any scheduled or completed interaction with one or more contacts.
 * Each activity record is tightly linked to other CiviCRM constituents. With this API you can manually
 * create an activity of desired type for your organisation or any other contact.
 *
 * Creating a new Activity requires at minimum a activity_type_id, entity ID and object_table
 *
 * An activity is a record of some type of interaction with one or more contacts.
 *
 * @package Civi\Api4
 */
class Activity extends Generic\DAOEntity {

}
