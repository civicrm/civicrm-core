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

require_once '../civicrm.config.php';

require_once 'CRM/Core/Config.php';
require_once 'CRM/Core/Error.php';
require_once 'CRM/Core/I18n.php';

require_once 'CRM/Mailing/BAO/Mailing.php';
require_once 'CRM/Mailing/BAO/Job.php';
require_once 'CRM/Mailing/DAO/Group.php';

$config = CRM_Core_Config::singleton();

$tables = array(
  'civicrm_mailing_event_delivered',
  'civicrm_mailing_event_queue',
  'civicrm_mailing_job',
  'civicrm_mailing_group',
  'civicrm_mailing',
);
foreach ($tables as $t) {
  $query = "DELETE FROM $t";
  CRM_Core_DAO::executeQuery($query);
}

$prefix = 'Automated Mailing Gen: ';
$numGroups = 153;

$status = array('Scheduled', 'Running', 'Complete', 'Paused', 'Canceled', 'Testing');

for ($i = 1; $i <= $numGroups; $i++) {
  $mailing = new CRM_Mailing_BAO_Mailing();

  $alphabet = mt_rand(97, 122);

  $cnt = sprintf('%05d', $i);
  $mailing->name = chr($alphabet) . ": $prefix $cnt";
  $mailing->header_id = $mailing->footer_id = $mailing->reply_id = $mailing->unsubscribe_id = $mailing->optout_id = 1;
  $mailing->is_completed = 1;
  $mailing->save();

  $job                 = new CRM_Mailing_BAO_MailingJob();
  $job->mailing_id     = $mailing->id;
  $job->scheduled_date = generateRandomDate();
  $job->start_date     = generateRandomDate();
  $job->end_date       = generateRandomDate();
  $job->status         = 'Complete';
  $job->save();

  $group               = new CRM_Mailing_DAO_MailingGroup();
  $group->mailing_id   = $mailing->id;
  $group->group_type   = 'Include';
  $group->entity_table = 'civicrm_group';
  $group->entity_id    = 1;
  $group->save();
}

/**
 * @return string
 */
function generateRandomDate() {
  $year  = 2006 + mt_rand(0, 2);
  $month = 1 + mt_rand(0, 11);
  $day   = 1 + mt_rand(0, 27);

  $date = sprintf("%4d%02d%02d", $year, $month, $day) . '000000';
  return $date;

}
