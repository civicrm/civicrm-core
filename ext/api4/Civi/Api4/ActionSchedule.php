<?php

namespace Civi\Api4;

/**
 * ActionSchedule Entity.
 *
 * This entity exposes CiviCRM schedule reminders, which allows us to send messages (through email or SMS)
 * to contacts when certain criteria are met. Using this API you can create schedule reminder for
 * supported entities like Contact, Activity, Event, Membership or Contribution.
 *
 * Creating a new ActionSchedule requires at minimum a title, mapping_id and entity_value.
 *
 * @package Civi\Api4
 */
class ActionSchedule extends Generic\DAOEntity {

}
