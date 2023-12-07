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
  public function onWalk(WalkBatchesEvent $e) {
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
    $tasks = [];
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
        $tasks = [];
      }
    }

    $eq->free();

    if (!empty($tasks)) {
      $isDelivered = $e->visit($tasks);
    }
    $e->setCompleted($isDelivered);
  }

}
