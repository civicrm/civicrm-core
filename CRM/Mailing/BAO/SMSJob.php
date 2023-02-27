<?php

/**
 * Job for SMS deliery functions.
 */
class CRM_Mailing_BAO_SMSJob extends CRM_Mailing_BAO_MailingJob {

  /**
   * This is used by CiviMail but will be made redundant by FlexMailer.
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
