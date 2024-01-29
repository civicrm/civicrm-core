<?php

/**
 * Job for SMS deliery functions.
 */
class CRM_Mailing_BAO_SMSJob extends CRM_Mailing_BAO_MailingJob {

  /**
   * Send the mailing.
   *
   * @param null $unused
   * @param bool $isTest
   *
   * @return bool
   * @throws \CRM_Core_Exception
   * @internal
   *
   */
  public function deliver($unused, $isTest) {
    // Just in case flexmailer is passing something odd.
    $isTest = !empty($isTest);
    $mailingID = $this->mailing_id;
    $mailer = CRM_SMS_Provider::singleton(['mailing_id' => $mailingID]);
    if (\Civi::settings()->get('experimentalFlexMailerEngine')) {
      throw new \RuntimeException("Cannot use legacy deliver() when experimentalFlexMailerEngine is enabled");
    }

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->mailing_id;
    $mailing->find(TRUE);

    $config = NULL;

    if ($config == NULL) {
      $config = CRM_Core_Config::singleton();
    }

    if (property_exists($mailing, 'language') && $mailing->language && $mailing->language != CRM_Core_I18n::getLocale()) {
      $swapLang = CRM_Utils_AutoClean::swap('global://dbLocale?getter', 'call://i18n/setLocale', $mailing->language);
    }

    $job_date = $this->scheduled_date;
    $fields = [];

    if ($isTest) {
      $mailing->subject = ts('[CiviMail Draft]') . ' ' . $mailing->subject;
    }

    CRM_Mailing_BAO_Mailing::tokenReplace($mailing);

    // get and format attachments
    $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_mailing', $mailing->id);

    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      CRM_Core_Smarty::registerStringResource();
    }

    // CRM-12376
    // This handles the edge case scenario where all the mails
    // have been delivered in prior jobs.
    $isDelivered = TRUE;

    // make sure that there's no more than $mailerBatchLimit mails processed in a run
    $mailerBatchLimit = Civi::settings()->get('mailerBatchLimit');
    $eq = self::findPendingTasks((int) $this->id, 'sms');
    while ($eq->fetch()) {
      if ($mailerBatchLimit > 0 && self::$mailsProcessed >= $mailerBatchLimit) {
        if (!empty($fields)) {
          $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
        }
        return FALSE;
      }
      self::$mailsProcessed++;

      $fields[] = [
        'id' => $eq->id,
        'hash' => $eq->hash,
        'contact_id' => $eq->contact_id,
        'email' => $eq->email,
        'phone' => $eq->phone,
      ];
      if (count($fields) == self::MAX_CONTACTS_TO_PROCESS) {
        $isDelivered = $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
        if (!$isDelivered) {
          return $isDelivered;
        }
        $fields = [];
      }
    }

    if (!empty($fields)) {
      $isDelivered = $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
    }
    return $isDelivered;
  }

  /**
   * This is used by to deliver SMS.
   *
   * @internal only to be used by core CiviCRM code.
   *
   * @param array $fields
   *   List of intended recipients.
   *   Each recipient is an array with keys 'hash', 'contact_id', 'email', etc.
   * @param $mailing
   * @param $mailer
   * @param $job_date
   * @param $attachments
   *
   * @return bool|null
   * @throws Exception
   */
  public function deliverGroup(&$fields, &$mailing, &$mailer, &$job_date, &$attachments) {
    $count = 0;
    // dev/core#1768 Get the mail sync interval.
    $mail_sync_interval = Civi::settings()->get('civimail_sync_interval');
    $retryGroup = FALSE;

    foreach ($fields as $field) {
      $contact = civicrm_api3('Contact', 'getsingle', ['id' => $field['contact_id']]);

      $preview = civicrm_api3('Mailing', 'preview', [
        'id' => $mailing->id,
        'contact_id' => $field['contact_id'],
      ])['values'];
      $mailParams = [
        'text' => $preview['body_text'],
        'toName' => $contact['display_name'],
        'job_id' => $this->id,
      ];
      CRM_Utils_Hook::alterMailParams($mailParams, 'civimail');
      $body = $mailParams['text'];
      $headers = ['To' => $field['phone']];

      try {
        $result = $mailer->send($headers['To'], $headers, $body, $this->id);

        // Register the delivery event.
        $deliveredParams[] = $field['id'];
        $targetParams[] = $field['contact_id'];

        $count++;
        // dev/core#1768 Mail sync interval is now configurable.
        if ($count % $mail_sync_interval == 0) {
          $this->writeToDB(
            $deliveredParams,
            $targetParams,
            $mailing,
            $job_date
          );
          $count = 0;

          // hack to stop mailing job at run time, CRM-4246.
          // to avoid making too many DB calls for this rare case
          // lets do it when we snapshot
          $status = CRM_Core_DAO::getFieldValue(
            'CRM_Mailing_DAO_MailingJob',
            $this->id,
            'status',
            'id',
            TRUE
          );

          if ($status !== 'Running') {
            return FALSE;
          }
        }
      }
      catch (CRM_Core_Exception $e) {
        // Handle SMS errors: CRM-15426
        $job_id = (int) $this->id;
        $mailing_id = (int) $mailing->id;
        CRM_Core_Error::debug_log_message("Failed to send SMS message. Vars: mailing_id: {$mailing_id}, job_id: {$job_id}. Error message follows.");
        CRM_Core_Error::debug_log_message($e->getMessage());
      }

      unset($result);

      // If we have enabled the Throttle option, this is the time to enforce it.
      $mailThrottleTime = Civi::settings()->get('mailThrottleTime');
      if (!empty($mailThrottleTime)) {
        usleep((int ) $mailThrottleTime);
      }
    }

    $result = $this->writeToDB(
      $deliveredParams,
      $targetParams,
      $mailing,
      $job_date
    );

    if ($retryGroup) {
      return FALSE;
    }

    return $result;
  }

}
