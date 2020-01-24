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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


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
 * @see https://docs.civicrm.org/user/en/latest/email/scheduled-reminders/
 * @package Civi\Api4
 */
class ActionSchedule extends Generic\DAOEntity {

}
