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
namespace Civi\Api4;

/**
 * Scheduled Reminders.
 *
 * Scheduled reminders send messages (through email or SMS) to contacts when
 * certain criteria are met. Using this API you can create schedule reminders for
 * supported entities like Contact, Activity, Event, Membership or Contribution.
 *
 * @searchable secondary
 * @see https://docs.civicrm.org/user/en/latest/email/scheduled-reminders/
 * @since 5.19
 * @package Civi\Api4
 */
class ActionSchedule extends Generic\DAOEntity {

}
