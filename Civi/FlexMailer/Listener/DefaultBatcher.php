<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
namespace Civi\FlexMailer\Listener;

use Civi\FlexMailer\Event\WalkBatchesEvent;
use Civi\FlexMailer\FlexMailerTask;

class DefaultBatcher extends BaseListener {

  /**
   * Given a MailingJob (`$e->getJob()`), enumerate the recipients as
   * a batch of FlexMailerTasks and visit each batch (`$e->visit($tasks)`).
   *
   * @param \Civi\FlexMailer\Event\WalkBatchesEvent $e
   */
  public function onWalkBatches(WalkBatchesEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    $e->stopPropagation();

    $job = $e->getJob();

    // CRM-12376
    // This handles the edge case scenario where all the mails
    // have been delivered in prior jobs.
    $isDelivered = TRUE;

    // make sure that there's no more than $mailerBatchLimit mails processed in a run
    $mailerBatchLimit = \CRM_Core_Config::singleton()->mailerBatchLimit;

    $eq = \CRM_Mailing_BAO_MailingJob::findPendingTasks($job->id, 'email');
    $tasks = array();
    while ($eq->fetch()) {
      if ($mailerBatchLimit > 0 && \CRM_Mailing_BAO_MailingJob::$mailsProcessed >= $mailerBatchLimit) {
        if (!empty($tasks)) {
          $e->visit($tasks);
        }
        $eq->free();
        $e->setCompleted(FALSE);
        return;
      }
      \CRM_Mailing_BAO_MailingJob::$mailsProcessed++;

      // FIXME: To support SMS, the address should be $eq->phone instead of $eq->email
      $tasks[] = new FlexMailerTask($eq->id, $eq->contact_id, $eq->hash,
        $eq->email);
      if (count($tasks) == \CRM_Mailing_BAO_MailingJob::MAX_CONTACTS_TO_PROCESS) {
        $isDelivered = $e->visit($tasks);
        if (!$isDelivered) {
          $eq->free();
          $e->setCompleted($isDelivered);
          return;
        }
        $tasks = array();
      }
    }

    $eq->free();

    if (!empty($tasks)) {
      $isDelivered = $e->visit($tasks);
    }
    $e->setCompleted($isDelivered);
  }

}
