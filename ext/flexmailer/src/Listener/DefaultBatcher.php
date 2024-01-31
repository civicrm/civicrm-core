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

use Civi\Core\Service\AutoService;
use Civi\FlexMailer\Event\WalkBatchesEvent;
use Civi\FlexMailer\FlexMailerTask;

/**
 * @service civi_flexmailer_default_batcher
 */
class DefaultBatcher extends AutoService {

  use IsActiveTrait;

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

    $eq = $this->findPendingTasks((int) $job->id);
    $tasks = [];
    while ($eq->fetch()) {
      if ($mailerBatchLimit > 0 && \CRM_Mailing_BAO_MailingJob::$mailsProcessed >= $mailerBatchLimit) {
        if (!empty($tasks)) {
          $e->visit($tasks);
        }
        // This ->free() is required because ->query() function is called not CRM_Core_ExecuteQuery
        // (which cleans up it's own memory use)
        // @todo - there is probably no reason not to just switch
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
          // This ->free() is required because ->query() function is called not CRM_Core_ExecuteQuery
          // (which cleans up it's own memory use)
          // @todo - there is probably no reason not to just switch
          $eq->free();
          $e->setCompleted($isDelivered);
          return;
        }
        $tasks = [];
      }
    }
    // This ->free() is required because ->query() function is called not CRM_Core_ExecuteQuery
    // (which cleans up it's own memory use)
    // @todo - there is probably no reason not to just switch
    $eq->free();

    if (!empty($tasks)) {
      $isDelivered = $e->visit($tasks);
    }
    $e->setCompleted($isDelivered);
  }

  /**
   * Search the mailing-event queue for a list of pending delivery tasks.
   *
   * @param int $jobId
   *
   * @return \CRM_Mailing_Event_BAO_MailingEventQueue
   *   A query object whose rows provide ('id', 'contact_id', 'hash') and ('email' or 'phone').
   */
  private function findPendingTasks(int $jobId): \CRM_Mailing_Event_BAO_MailingEventQueue {
    $eq = new \CRM_Mailing_Event_BAO_MailingEventQueue();

    $query = "  SELECT      queue.id,
                                email.email as email,
                                queue.contact_id,
                                queue.hash,
                                NULL as phone
                    FROM        civicrm_mailing_event_queue queue
                    INNER JOIN  civicrm_email email
                            ON  queue.email_id = email.id
                    INNER JOIN  civicrm_contact contact
                            ON  contact.id = email.contact_id
                    LEFT JOIN   civicrm_mailing_event_delivered delivered
                            ON  queue.id = delivered.event_queue_id
                    LEFT JOIN   civicrm_mailing_event_bounce bounce
                            ON  queue.id = bounce.event_queue_id
                    WHERE       queue.job_id = " . $jobId . "
                        AND     delivered.id IS null
                        AND     bounce.id IS null
                        AND     contact.is_opt_out = 0";
    // note this `query` function leaks memory more than CRM_Core_DAO::ExecuteQuery()
    $eq->query($query);
    return $eq;
  }

}
