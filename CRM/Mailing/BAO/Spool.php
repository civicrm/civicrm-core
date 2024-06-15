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
 */
class CRM_Mailing_BAO_Spool extends CRM_Mailing_DAO_Spool {

  /**
   * Store Mails into Spool table.
   *
   * @param string|array $recipient
   *   Either a comma-seperated list of recipients
   *   (RFC822 compliant), or an array of recipients,
   *   each RFC822 valid. This may contain recipients not
   *   specified in the headers, for Bcc:, resending
   *   messages, etc.
   * @param array $headers
   *   The array of headers to send with the mail.
   *
   * @param string $body
   *   The full text of the message body, including any mime parts, etc.
   *
   * @param int $job_id
   *
   * @return bool|CRM_Core_Error
   *   true if successful
   */
  public function send($recipient, $headers, $body, $job_id = NULL) {
    $headerStr = [];
    foreach ($headers as $name => $value) {
      $headerStr[] = "$name: $value";
    }
    $headerStr = implode("\n", $headerStr);

    if (is_null($job_id)) {
      // This is not a bulk mailing. Create a dummy job for it.

      $session = CRM_Core_Session::singleton();
      $params = [];
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
      $params['scheduled_id'] = $params['created_id'];
      $params['scheduled_date'] = $params['created_date'];
      $params['is_completed'] = 1;
      $params['status'] = 'Complete';
      $params['end_date'] = date('Y-m-d H:i:s');
      $params['is_archived'] = 1;
      $params['body_html'] = htmlspecialchars($headerStr) . "\n\n" . $body;
      $params['subject'] = $headers['Subject'];
      $params['name'] = $headers['Subject'];
      $mailing = CRM_Mailing_BAO_Mailing::create($params);

      if (empty($mailing) || is_a($mailing, 'CRM_Core_Error')) {
        return PEAR::raiseError('Unable to create spooled mailing.');
      }

      $job = new CRM_Mailing_BAO_MailingJob();
      // if set to 1 it doesn't show in the UI
      $job->is_test = 0;
      $job->status = 'Complete';
      $job->scheduled_date = CRM_Utils_Date::processDate(date('Y-m-d'), date('H:i:s'));
      $job->start_date = $job->scheduled_date;
      $job->end_date = $job->scheduled_date;
      $job->mailing_id = $mailing->id;
      $job->save();
      // need this for parent_id below
      $job_id = $job->id;

      $job = new CRM_Mailing_BAO_MailingJob();
      $job->is_test = 0;
      $job->status = 'Complete';
      $job->scheduled_date = CRM_Utils_Date::processDate(date('Y-m-d'), date('H:i:s'));
      $job->start_date = $job->scheduled_date;
      $job->end_date = $job->scheduled_date;
      $job->mailing_id = $mailing->id;
      $job->parent_id = $job_id;
      $job->job_type = 'child';
      $job->save();
      // this is the one we want for the spool
      $job_id = $job->id;

      if (is_array($recipient)) {
        $recipient = implode(';', $recipient);
      }
    }

    $session = CRM_Core_Session::singleton();

    $params = [
      'job_id' => $job_id,
      'recipient_email' => $recipient,
      'headers' => $headerStr,
      'body' => $body,
      'added_at' => date("YmdHis"),
      'removed_at' => NULL,
    ];

    $spoolMail = new CRM_Mailing_DAO_Spool();
    $spoolMail->copyValues($params);
    $spoolMail->save();

    return TRUE;
  }

}
