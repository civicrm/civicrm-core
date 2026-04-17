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

namespace Civi\SymfonyMailer;

use Civi\Core\Service\AutoService;
use Civi\FlexMailer\Event\SendBatchEvent;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * FlexMailer send listener that bypasses PEAR Mail_mime entirely.
 *
 * Builds Symfony Email objects directly from mailParams and sends
 * via the Symfony transport. Runs at priority -1000, ahead of
 * DefaultSender at -2000.
 *
 * @service civi_symfony_mailer_flex_sender
 */
class FlexSender extends AutoService {

  const BULK_MAIL_INSERT_COUNT = 10;

  public function onSend(SendBatchEvent $e): void {
    $mailerService = \Civi::service('pear_mail');
    if (!($mailerService instanceof SymfonyMailerAdapter)) {
      return;
    }

    $e->stopPropagation();

    $transport = $mailerService->getTransport();
    $job = $e->getJob();
    $mailing = $e->getMailing();
    $job_date = \CRM_Utils_Date::isoToMysql($job->scheduled_date);

    $targetParams = $deliveredParams = [];
    $count = 0;
    static $smtpConnectionErrors = 0;
    $retryBatch = FALSE;

    foreach ($e->getTasks() as $key => $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      if (!$task->hasContent()) {
        continue;
      }

      $params = $task->getMailParams();
      if (!empty($params['abortMailSend'])) {
        continue;
      }

      $sent = FALSE;
      try {
        $email = MailParamsConverter::fromMailParams($params);

        if ($job_date) {
          $errorScope = \CRM_Core_TemporaryErrorScope::ignoreException();
        }

        $transport->send($email);
        $sent = TRUE;

        if ($job_date) {
          unset($errorScope);
        }
      }
      catch (TransportExceptionInterface $ex) {
        if ($job_date) {
          unset($errorScope);
        }

        if ($this->isTemporaryError($ex)) {
          $smtpConnectionErrors++;
          if ($smtpConnectionErrors <= 5) {
            if ($transport instanceof EsmtpTransport) {
              $transport->stop();
            }
            $retryBatch = TRUE;
            unset($email, $params);
            $task->setMailParams([]);
            continue;
          }

          $job->writeToDB($deliveredParams, $targetParams, $mailing, $job_date);
          \CRM_Core_Error::debug_log_message("Too many SMTP Socket Errors. Exiting");
          \CRM_Utils_System::civiExit();
        }
        else {
          $this->recordBounce($job, $task, $ex->getMessage());
        }
      }

      unset($email, $params);
      $task->setMailParams([]);

      if (!$sent) {
        continue;
      }

      $deliveredParams[] = $task->getEventQueueId();
      $targetParams[] = $task->getContactId();

      $count++;
      if ($count % self::BULK_MAIL_INSERT_COUNT == 0) {
        $job->writeToDB($deliveredParams, $targetParams, $mailing, $job_date);
        $count = 0;

        $status = \CRM_Core_DAO::getFieldValue(
          'CRM_Mailing_DAO_MailingJob',
          $job->id,
          'status',
          'id',
          TRUE
        );
        if ($status != 'Running') {
          $e->setCompleted(FALSE);
          return;
        }
      }

      if ($smtpConnectionErrors > 0) {
        $smtpConnectionErrors--;
      }

      $mailThrottleTime = \CRM_Core_Config::singleton()->mailThrottleTime;
      if (!empty($mailThrottleTime)) {
        usleep((int) $mailThrottleTime);
      }
    }

    $completed = $job->writeToDB(
      $deliveredParams,
      $targetParams,
      $mailing,
      $job_date
    );
    if ($retryBatch) {
      $completed = FALSE;
    }
    $e->setCompleted($completed);
  }

  private function isTemporaryError(TransportExceptionInterface $e): bool {
    $message = $e->getMessage();
    $code = preg_match('/ \(code: (.+), response: /', $message, $matches) ? $matches[1] : '';

    if (str_contains($message, 'Failed to write to socket')
      || str_contains($message, 'Connection could not be established')
      || str_contains($message, 'Connection reset by peer')) {
      return TRUE;
    }

    // 5xx = permanent failure.
    if (isset($code[0]) && $code[0] === '5') {
      return FALSE;
    }

    if ($code === '450' && \Civi::settings()->get('smtp_450_is_permanent')) {
      if (str_contains($message, '4.1.2')) {
        return FALSE;
      }
    }

    if (str_contains($message, 'Failed to set sender')
      || str_contains($message, 'Failed to add recipient')
      || str_contains($message, 'Failed to send data')) {
      return TRUE;
    }

    return FALSE;
  }

  private function recordBounce($job, $task, string $errorMessage): void {
    $params = [
      'event_queue_id' => $task->getEventQueueId(),
      'job_id' => $job->id,
      'hash' => $task->getHash(),
    ];
    $params = array_merge($params,
      \CRM_Mailing_BAO_BouncePattern::match($errorMessage)
    );
    \CRM_Mailing_Event_BAO_MailingEventBounce::recordBounce($params);
  }

}
