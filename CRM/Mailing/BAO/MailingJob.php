<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

require_once 'Mail.php';

/**
 * Class CRM_Mailing_BAO_MailingJob
 */
class CRM_Mailing_BAO_MailingJob extends CRM_Mailing_DAO_MailingJob {
  const MAX_CONTACTS_TO_PROCESS = 1000;

  /**
   * (Dear God Why) Keep a global count of mails processed within the current
   * request.
   *
   * @var int
   */
  static $mailsProcessed = 0;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Create mailing job.
   *
   * @param array $params
   *
   * @return \CRM_Mailing_BAO_MailingJob
   * @throws \CRM_Core_Exception
   */
  static public function create($params) {
    if (empty($params['id']) && empty($params['mailing_id'])) {
      throw new CRM_Core_Exception("Failed to create job: Unknown mailing ID");
    }
    $op = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($op, 'MailingJob', CRM_Utils_Array::value('id', $params), $params);

    $jobDAO = new CRM_Mailing_BAO_MailingJob();
    $jobDAO->copyValues($params, TRUE);
    $jobDAO->save();
    if (!empty($params['mailing_id'])) {
      CRM_Mailing_BAO_Mailing::getRecipients($params['mailing_id']);
    }
    CRM_Utils_Hook::post($op, 'MailingJob', $jobDAO->id, $jobDAO);
    return $jobDAO;
  }

  /**
   * Initiate all pending/ready jobs.
   *
   * @param array $testParams
   * @param string $mode
   *
   * @return bool|null
   */
  public static function runJobs($testParams = NULL, $mode = NULL) {
    $job = new CRM_Mailing_BAO_MailingJob();

    $jobTable = CRM_Mailing_DAO_MailingJob::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();
    $mailerBatchLimit = Civi::settings()->get('mailerBatchLimit');

    if (!empty($testParams)) {
      $query = "
      SELECT *
        FROM $jobTable
       WHERE id = {$testParams['job_id']}";
      $job->query($query);
    }
    else {
      $currentTime = date('YmdHis');
      $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL('m');
      $domainID = CRM_Core_Config::domainID();

      $modeClause = 'AND m.sms_provider_id IS NULL';
      if ($mode == 'sms') {
        $modeClause = 'AND m.sms_provider_id IS NOT NULL';
      }

      // Select the first child job that is scheduled
      // CRM-6835
      $query = "
      SELECT   j.*
        FROM   $jobTable     j,
           $mailingTable m
       WHERE   m.id = j.mailing_id AND m.domain_id = {$domainID}
                     {$modeClause}
         AND   j.is_test = 0
         AND   ( ( j.start_date IS null
         AND       j.scheduled_date <= $currentTime
         AND       j.status = 'Scheduled' )
                OR     ( j.status = 'Running'
         AND       j.end_date IS null ) )
         AND (j.job_type = 'child')
         AND   {$mailingACL}
      ORDER BY j.scheduled_date ASC,
        j.id
      ";

      $job->query($query);
    }

    while ($job->fetch()) {
      // still use job level lock for each child job
      $lock = Civi::lockManager()->acquire("data.mailing.job.{$job->id}");
      if (!$lock->isAcquired()) {
        continue;
      }

      // for test jobs we do not change anything, since its on a short-circuit path
      if (empty($testParams)) {
        // we've got the lock, but while we were waiting and processing
        // other emails, this job might have changed under us
        // lets get the job status again and check
        $job->status = CRM_Core_DAO::getFieldValue(
          'CRM_Mailing_DAO_MailingJob',
          $job->id,
          'status',
          'id',
          TRUE
        );

        if (
          $job->status != 'Running' &&
          $job->status != 'Scheduled'
        ) {
          // this includes Cancelled and other statuses, CRM-4246
          $lock->release();
          continue;
        }
      }

      /* Queue up recipients for the child job being launched */

      if ($job->status != 'Running') {
        $transaction = new CRM_Core_Transaction();

        // have to queue it up based on the offset and limits
        // get the parent ID, and limit and offset
        $job->queue($testParams);

        // Update to show job has started.
        self::create([
          'id' => $job->id,
          'start_date' => date('YmdHis'),
          'status' => 'Running',
        ]);

        $transaction->commit();
      }

      // Get the mailer
      if ($mode === NULL) {
        $mailer = \Civi::service('pear_mail');
      }
      elseif ($mode == 'sms') {
        $mailer = CRM_SMS_Provider::singleton(['mailing_id' => $job->mailing_id]);
      }

      // Compose and deliver each child job
      if (\CRM_Utils_Constant::value('CIVICRM_FLEXMAILER_HACK_DELIVER')) {
        $isComplete = Civi\Core\Resolver::singleton()->call(CIVICRM_FLEXMAILER_HACK_DELIVER, [$job, $mailer, $testParams]);
      }
      else {
        $isComplete = $job->deliver($mailer, $testParams);
      }

      CRM_Utils_Hook::post('create', 'CRM_Mailing_DAO_Spool', $job->id, $isComplete);

      // Mark the child complete
      if ($isComplete) {
        // Finish the job.

        $transaction = new CRM_Core_Transaction();
        self::create(['id' => $job->id, 'end_date' => date('YmdHis'), 'status' => 'Complete']);
        $transaction->commit();

        // don't mark the mailing as complete
      }

      // Release the child joblock
      $lock->release();

      if ($testParams) {
        return $isComplete;
      }

      // CRM-17629: Stop processing jobs if mailer batch limit reached
      if ($mailerBatchLimit > 0 && self::$mailsProcessed >= $mailerBatchLimit) {
        break;
      }

    }
  }

  /**
   * Post process to determine if the parent job
   * as well as the mailing is complete after the run.
   * @param null $mode
   */
  public static function runJobs_post($mode = NULL) {

    $job = new CRM_Mailing_BAO_MailingJob();

    $mailing = new CRM_Mailing_BAO_Mailing();

    $config = CRM_Core_Config::singleton();
    $jobTable = CRM_Mailing_DAO_MailingJob::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();

    $currentTime = date('YmdHis');
    $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL('m');
    $domainID = CRM_Core_Config::domainID();

    $query = "
                SELECT   j.*
                  FROM   $jobTable     j,
                                 $mailingTable m
                 WHERE   m.id = j.mailing_id AND m.domain_id = {$domainID}
                   AND   j.is_test = 0
                   AND       j.scheduled_date <= $currentTime
                   AND       j.status = 'Running'
                   AND       j.end_date IS null
                   AND       (j.job_type != 'child' OR j.job_type is NULL)
                ORDER BY j.scheduled_date,
                                 j.start_date";

    $job->query($query);

    // For each parent job that is running, let's look at their child jobs
    while ($job->fetch()) {

      $child_job = new CRM_Mailing_BAO_MailingJob();

      $child_job_sql = "
            SELECT count(j.id)
                        FROM civicrm_mailing_job j, civicrm_mailing m
                        WHERE m.id = j.mailing_id
                        AND j.job_type = 'child'
                        AND j.parent_id = %1
            AND j.status <> 'Complete'";
      $params = [1 => [$job->id, 'Integer']];

      $anyChildLeft = CRM_Core_DAO::singleValueQuery($child_job_sql, $params);

      // all of the child jobs are complete, update
      // the parent job as well as the mailing status
      if (!$anyChildLeft) {

        $transaction = new CRM_Core_Transaction();

        $saveJob = new CRM_Mailing_DAO_MailingJob();
        $saveJob->id = $job->id;
        $saveJob->end_date = date('YmdHis');
        $saveJob->status = 'Complete';
        $saveJob->save();

        $mailing->reset();
        $mailing->id = $job->mailing_id;
        $mailing->is_completed = TRUE;
        $mailing->save();
        $transaction->commit();

        // CRM-17763
        CRM_Utils_Hook::postMailing($job->mailing_id);
      }
    }
  }


  /**
   * before we run jobs, we need to split the jobs
   * @param int $offset
   * @param null $mode
   */
  public static function runJobs_pre($offset = 200, $mode = NULL) {
    $job = new CRM_Mailing_BAO_MailingJob();

    $jobTable = CRM_Mailing_DAO_MailingJob::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();

    $currentTime = date('YmdHis');
    $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL('m');

    $workflowClause = CRM_Mailing_BAO_MailingJob::workflowClause();

    $domainID = CRM_Core_Config::domainID();

    $modeClause = 'AND m.sms_provider_id IS NULL';
    if ($mode == 'sms') {
      $modeClause = 'AND m.sms_provider_id IS NOT NULL';
    }

    // Select all the mailing jobs that are created from
    // when the mailing is submitted or scheduled.
    $query = "
    SELECT   j.*
      FROM   $jobTable     j,
         $mailingTable m
     WHERE   m.id = j.mailing_id AND m.domain_id = {$domainID}
                 $workflowClause
                 $modeClause
       AND   j.is_test = 0
       AND   ( ( j.start_date IS null
       AND       j.scheduled_date <= $currentTime
       AND       j.status = 'Scheduled'
       AND       j.end_date IS null ) )
       AND ((j.job_type is NULL) OR (j.job_type <> 'child'))
    ORDER BY j.scheduled_date,
         j.start_date";

    $job->query($query);

    // For each of the "Parent Jobs" we find, we split them into
    // X Number of child jobs
    while ($job->fetch()) {
      // still use job level lock for each child job
      $lock = Civi::lockManager()->acquire("data.mailing.job.{$job->id}");
      if (!$lock->isAcquired()) {
        continue;
      }

      // Re-fetch the job status in case things
      // changed between the first query and now
      // to avoid race conditions
      $job->status = CRM_Core_DAO::getFieldValue(
        'CRM_Mailing_DAO_MailingJob',
        $job->id,
        'status',
        'id',
        TRUE
      );
      if ($job->status != 'Scheduled') {
        $lock->release();
        continue;
      }

      $transaction = new CRM_Core_Transaction();

      $job->split_job($offset);

      // Update the status of the parent job
      self::create(['id' => $job->id, 'start_date' => date('YmdHis'), 'status' => 'Running']);
      $transaction->commit();

      // Release the job lock
      $lock->release();
    }
  }

  /**
   * Split the parent job into n number of child job based on an offset.
   * If null or 0 , we create only one child job
   * @param int $offset
   */
  public function split_job($offset = 200) {
    $recipient_count = CRM_Mailing_BAO_Recipients::mailingSize($this->mailing_id);

    $jobTable = CRM_Mailing_DAO_MailingJob::getTableName();

    $dao = new CRM_Core_DAO();

    $sql = "
INSERT INTO civicrm_mailing_job
(`mailing_id`, `scheduled_date`, `status`, `job_type`, `parent_id`, `job_offset`, `job_limit`)
VALUES (%1, %2, %3, %4, %5, %6, %7)
";
    $params = [
      1 => [$this->mailing_id, 'Integer'],
      2 => [$this->scheduled_date, 'String'],
      3 => ['Scheduled', 'String'],
      4 => ['child', 'String'],
      5 => [$this->id, 'Integer'],
      6 => [0, 'Integer'],
      7 => [$recipient_count, 'Integer'],
    ];

    // create one child job if the mailing size is less than the offset
    // probably use a CRM_Mailing_DAO_MailingJob( );
    if (empty($offset) ||
      $recipient_count <= $offset
    ) {
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    else {
      // Creating 'child jobs'
      $scheduled_unixtime = strtotime($this->scheduled_date);
      for ($i = 0, $s = 0; $i < $recipient_count; $i = $i + $offset, $s++) {
        $params[2][0] = date('Y-m-d H:i:s', $scheduled_unixtime + $s);
        $params[6][0] = $i;
        $params[7][0] = $offset;
        CRM_Core_DAO::executeQuery($sql, $params);
      }
    }

  }

  /**
   * @param array $testParams
   */
  public function queue($testParams = NULL) {
    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->mailing_id;
    if (!empty($testParams)) {
      $mailing->getTestRecipients($testParams);
    }
    else {
      // We are still getting all the recipients from the parent job
      // so we don't mess with the include/exclude logic.
      $recipients = CRM_Mailing_BAO_Recipients::mailingQuery($this->mailing_id, $this->job_offset, $this->job_limit);

      // FIXME: this is not very smart, we should move this to one DB call
      // INSERT INTO ... SELECT FROM ..
      // the thing we need to figure out is how to generate the hash automatically
      $now = time();
      $params = [];
      $count = 0;
      while ($recipients->fetch()) {
        // CRM-18543: there are situations when both the email and phone are null.
        // Skip the recipient in this case.
        if (empty($recipients->email_id) && empty($recipients->phone_id)) {
          continue;
        }

        if ($recipients->phone_id) {
          $recipients->email_id = "null";
        }
        else {
          $recipients->phone_id = "null";
        }

        $params[] = [
          $this->id,
          $recipients->email_id,
          $recipients->contact_id,
          $recipients->phone_id,
        ];
        $count++;
        if ($count % CRM_Mailing_Config::BULK_MAIL_INSERT_COUNT == 0) {
          CRM_Mailing_Event_BAO_Queue::bulkCreate($params, $now);
          $count = 0;
          $params = [];
        }
      }

      if (!empty($params)) {
        CRM_Mailing_Event_BAO_Queue::bulkCreate($params, $now);
      }
    }
  }

  /**
   * Send the mailing.
   *
   * @deprecated
   *   This is used by CiviMail but will be made redundant by FlexMailer.
   * @param object $mailer
   *   A Mail object to send the messages.
   *
   * @param array $testParams
   * @return bool
   */
  public function deliver(&$mailer, $testParams = NULL) {
    if (\Civi::settings()->get('experimentalFlexMailerEngine')) {
      throw new \RuntimeException("Cannot use legacy deliver() when experimentalFlexMailerEngine is enabled");
    }

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->mailing_id;
    $mailing->find(TRUE);
    $mailing->free();

    $config = NULL;

    if ($config == NULL) {
      $config = CRM_Core_Config::singleton();
    }

    if (property_exists($mailing, 'language') && $mailing->language && $mailing->language != 'en_US') {
      $swapLang = CRM_Utils_AutoClean::swap('global://dbLocale?getter', 'call://i18n/setLocale', $mailing->language);
    }

    $job_date = CRM_Utils_Date::isoToMysql($this->scheduled_date);
    $fields = [];

    if (!empty($testParams)) {
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
    $eq = self::findPendingTasks($this->id, $mailing->sms_provider_id ? 'sms' : 'email');
    while ($eq->fetch()) {
      if ($mailerBatchLimit > 0 && self::$mailsProcessed >= $mailerBatchLimit) {
        if (!empty($fields)) {
          $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
        }
        $eq->free();
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
          $eq->free();
          return $isDelivered;
        }
        $fields = [];
      }
    }

    $eq->free();

    if (!empty($fields)) {
      $isDelivered = $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
    }
    return $isDelivered;
  }

  /**
   * @deprecated
   *   This is used by CiviMail but will be made redundant by FlexMailer.
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
    static $smtpConnectionErrors = 0;

    if (!is_object($mailer) || empty($fields)) {
      CRM_Core_Error::fatal();
    }

    // get the return properties
    $returnProperties = $mailing->getReturnProperties();
    $params = $targetParams = $deliveredParams = [];
    $count = 0;
    $retryGroup = FALSE;

    // CRM-15702: Sending bulk sms to contacts without e-mail address fails.
    // Solution is to skip checking for on hold
    $skipOnHold = TRUE;    //do include a statement to check wether e-mail address is on hold
    if ($mailing->sms_provider_id) {
      $skipOnHold = FALSE; //do not include a statement to check wether e-mail address is on hold
    }

    foreach ($fields as $key => $field) {
      $params[] = $field['contact_id'];
    }

    $details = CRM_Utils_Token::getTokenDetails(
      $params,
      $returnProperties,
      $skipOnHold, TRUE, NULL,
      $mailing->getFlattenedTokens(),
      get_class($this),
      $this->id
    );

    $config = CRM_Core_Config::singleton();
    foreach ($fields as $key => $field) {
      $contactID = $field['contact_id'];
      if (!array_key_exists($contactID, $details[0])) {
        $details[0][$contactID] = [];
      }

      // Compose the mailing.
      $recipient = $replyToEmail = NULL;
      $replyValue = strcmp($mailing->replyto_email, $mailing->from_email);
      if ($replyValue) {
        $replyToEmail = $mailing->replyto_email;
      }

      $message = $mailing->compose(
        $this->id, $field['id'], $field['hash'],
        $field['contact_id'], $field['email'],
        $recipient, FALSE, $details[0][$contactID], $attachments,
        FALSE, NULL, $replyToEmail
      );
      if (empty($message)) {
        // lets keep the message in the queue
        // most likely a permissions related issue with smarty templates
        // or a bad contact id? CRM-9833
        continue;
      }

      // Send the mailing.

      $body = $message->get();
      $headers = $message->headers();

      if ($mailing->sms_provider_id) {
        $provider = CRM_SMS_Provider::singleton(['mailing_id' => $mailing->id]);
        $body = $provider->getMessage($message, $field['contact_id'], $details[0][$contactID]);
        $headers = $provider->getRecipientDetails($field, $details[0][$contactID]);
      }

      // make $recipient actually be the *encoded* header, so as not to baffle Mail_RFC822, CRM-5743
      $recipient = $headers['To'];
      $result = NULL;

      // disable error reporting on real mailings (but leave error reporting for tests), CRM-5744
      if ($job_date) {
        $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      }

      $result = $mailer->send($recipient, $headers, $body, $this->id);

      if ($job_date) {
        unset($errorScope);
      }

      if (is_a($result, 'PEAR_Error') && !$mailing->sms_provider_id) {
        // CRM-9191
        $message = $result->getMessage();
        if ($this->isTemporaryError($message)) {
          // lets log this message and code
          $code = $result->getCode();
          CRM_Core_Error::debug_log_message("SMTP Socket Error or failed to set sender error. Message: $message, Code: $code");

          // these are socket write errors which most likely means smtp connection errors
          // lets skip them and reconnect.
          $smtpConnectionErrors++;
          if ($smtpConnectionErrors <= 5) {
            $mailer->disconnect();
            $retryGroup = TRUE;
            continue;
          }

          // seems like we have too many of them in a row, we should
          // write stuff to disk and abort the cron job
          $this->writeToDB(
            $deliveredParams,
            $targetParams,
            $mailing,
            $job_date
          );

          CRM_Core_Error::debug_log_message("Too many SMTP Socket Errors. Exiting");
          CRM_Utils_System::civiExit();
        }

        // Register the bounce event.

        $params = [
          'event_queue_id' => $field['id'],
          'job_id' => $this->id,
          'hash' => $field['hash'],
        ];
        $params = array_merge($params,
          CRM_Mailing_BAO_BouncePattern::match($result->getMessage())
        );
        CRM_Mailing_Event_BAO_Bounce::create($params);
      }
      elseif (is_a($result, 'PEAR_Error') && $mailing->sms_provider_id) {
        // Handle SMS errors: CRM-15426
        $job_id = intval($this->id);
        $mailing_id = intval($mailing->id);
        CRM_Core_Error::debug_log_message("Failed to send SMS message. Vars: mailing_id: ${mailing_id}, job_id: ${job_id}. Error message follows.");
        CRM_Core_Error::debug_log_message($result->getMessage());
      }
      else {
        // Register the delivery event.
        $deliveredParams[] = $field['id'];
        $targetParams[] = $field['contact_id'];

        $count++;
        if ($count % CRM_Mailing_Config::BULK_MAIL_INSERT_COUNT == 0) {
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

          if ($status != 'Running') {
            return FALSE;
          }
        }
      }

      unset($result);

      // seems like a successful delivery or bounce, lets decrement error count
      // only if we have smtp connection errors
      if ($smtpConnectionErrors > 0) {
        $smtpConnectionErrors--;
      }

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

  /**
   * Determine if an SMTP error is temporary or permanent.
   *
   * @param string $message
   *   PEAR error message.
   * @return bool
   *   TRUE - Temporary/retriable error
   *   FALSE - Permanent/non-retriable error
   */
  protected function isTemporaryError($message) {
    // SMTP response code is buried in the message.
    $code = preg_match('/ \(code: (.+), response: /', $message, $matches) ? $matches[1] : '';

    if (strpos($message, 'Failed to write to socket') !== FALSE) {
      return TRUE;
    }

    // Register 5xx SMTP response code (permanent failure) as bounce.
    if (isset($code{0}) && $code{0} === '5') {
      return FALSE;
    }

    if (strpos($message, 'Failed to set sender') !== FALSE) {
      return TRUE;
    }

    if (strpos($message, 'Failed to add recipient') !== FALSE) {
      return TRUE;
    }

    if (strpos($message, 'Failed to send data') !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Cancel a mailing.
   *
   * @param int $mailingId
   *   The id of the mailing to be canceled.
   */
  public static function cancel($mailingId) {
    $sql = "
SELECT *
FROM   civicrm_mailing_job
WHERE  mailing_id = %1
AND    is_test = 0
AND    ( ( job_type IS NULL ) OR
           job_type <> 'child' )
";
    $params = [1 => [$mailingId, 'Integer']];
    $job = CRM_Core_DAO::executeQuery($sql, $params);
    if ($job->fetch() &&
      in_array($job->status, ['Scheduled', 'Running', 'Paused'])
    ) {

      self::create(['id' => $job->id, 'end_date' => date('YmdHis'), 'status' => 'Canceled']);

      // also cancel all child jobs
      $sql = "
UPDATE civicrm_mailing_job
SET    status = 'Canceled',
       end_date = %2
WHERE  parent_id = %1
AND    is_test = 0
AND    job_type = 'child'
AND    status IN ( 'Scheduled', 'Running', 'Paused' )
";
      $params = [
        1 => [$job->id, 'Integer'],
        2 => [date('YmdHis'), 'Timestamp'],
      ];
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }

  /**
   * Pause a mailing
   *
   * @param int $mailingID
   *   The id of the mailing to be paused.
   */
  public static function pause($mailingID) {
    $sql = "
      UPDATE civicrm_mailing_job
      SET status = 'Paused'
      WHERE mailing_id = %1
      AND is_test = 0
      AND status IN ('Scheduled', 'Running')
    ";
    CRM_Core_DAO::executeQuery($sql, [1 => [$mailingID, 'Integer']]);
  }

  /**
   * Resume a mailing
   *
   * @param int $mailingID
   *   The id of the mailing to be resumed.
   */
  public static function resume($mailingID) {
    $sql = "
      UPDATE civicrm_mailing_job
      SET status = 'Scheduled'
      WHERE mailing_id = %1
      AND is_test = 0
      AND start_date IS NULL
      AND status = 'Paused'
    ";
    CRM_Core_DAO::executeQuery($sql, [1 => [$mailingID, 'Integer']]);

    $sql = "
      UPDATE civicrm_mailing_job
      SET status = 'Running'
      WHERE mailing_id = %1
      AND is_test = 0
      AND start_date IS NOT NULL
      AND status = 'Paused'
    ";
    CRM_Core_DAO::executeQuery($sql, [1 => [$mailingID, 'Integer']]);
  }

  /**
   * Return a translated status enum string.
   *
   * @param string $status
   *   The status enum.
   *
   * @return string
   *   The translated version
   */
  public static function status($status) {
    static $translation = NULL;

    if (empty($translation)) {
      $translation = [
        'Scheduled' => ts('Scheduled'),
        'Running' => ts('Running'),
        'Complete' => ts('Complete'),
        'Paused' => ts('Paused'),
        'Canceled' => ts('Canceled'),
      ];
    }
    return CRM_Utils_Array::value($status, $translation, ts('Not scheduled'));
  }

  /**
   * Return a workflow clause for use in SQL queries,
   * to only process jobs that are approved.
   *
   * @return string
   *   For use in a WHERE clause
   */
  public static function workflowClause() {
    // add an additional check and only process
    // jobs that are approved
    if (CRM_Mailing_Info::workflowEnabled()) {
      $approveOptionID = CRM_Core_PseudoConstant::getKey('CRM_Mailing_BAO_Mailing', 'approval_status_id', 'Approved');
      if ($approveOptionID) {
        return " AND m.approval_status_id = $approveOptionID ";
      }
    }
    return '';
  }

  /**
   * @param array $deliveredParams
   * @param array $targetParams
   * @param $mailing
   * @param $job_date
   *
   * @return bool
   * @throws CRM_Core_Exception
   * @throws Exception
   */
  public function writeToDB(
    &$deliveredParams,
    &$targetParams,
    &$mailing,
    $job_date
  ) {
    static $activityTypeID = NULL;
    static $writeActivity = NULL;

    if (!empty($deliveredParams)) {
      CRM_Mailing_Event_BAO_Delivered::bulkCreate($deliveredParams);
      $deliveredParams = [];
    }

    if ($writeActivity === NULL) {
      $writeActivity = Civi::settings()->get('write_activity_record');
    }

    if (!$writeActivity) {
      return TRUE;
    }

    $result = TRUE;
    if (!empty($targetParams) && !empty($mailing->scheduled_id)) {
      if (!$activityTypeID) {
        if ($mailing->sms_provider_id) {
          $mailing->subject = $mailing->name;
          $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Mass SMS'
          );
        }
        else {
          $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Bulk Email');
        }
        if (!$activityTypeID) {
          CRM_Core_Error::fatal();
        }
      }

      $activity = [
        'source_contact_id' => $mailing->scheduled_id,
        // CRM-9519
        'target_contact_id' => array_unique($targetParams),
        'activity_type_id' => $activityTypeID,
        'source_record_id' => $this->mailing_id,
        'activity_date_time' => $job_date,
        'subject' => $mailing->subject,
        'status_id' => 2,
        'deleteActivityTarget' => FALSE,
        'campaign_id' => $mailing->campaign_id,
      ];

      //check whether activity is already created for this mailing.
      //if yes then create only target contact record.
      $query = "
SELECT id
FROM   civicrm_activity
WHERE  civicrm_activity.activity_type_id = %1
AND    civicrm_activity.source_record_id = %2
";

      $queryParams = [
        1 => [$activityTypeID, 'Integer'],
        2 => [$this->mailing_id, 'Integer'],
      ];
      $activityID = CRM_Core_DAO::singleValueQuery($query, $queryParams);

      if ($activityID) {
        $activity['id'] = $activityID;

        // CRM-9519
        if (CRM_Core_BAO_Email::isMultipleBulkMail()) {
          static $targetRecordID = NULL;
          if (!$targetRecordID) {
            $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
            $targetRecordID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
          }

          // make sure we don't attempt to duplicate the target activity
          foreach ($activity['target_contact_id'] as $key => $targetID) {
            $sql = "
SELECT id
FROM   civicrm_activity_contact
WHERE  activity_id = $activityID
AND    contact_id = $targetID
AND    record_type_id = $targetRecordID
";
            if (CRM_Core_DAO::singleValueQuery($sql)) {
              unset($activity['target_contact_id'][$key]);
            }
          }
        }
      }

      if (is_a(CRM_Activity_BAO_Activity::create($activity), 'CRM_Core_Error')) {
        $result = FALSE;
      }

      $targetParams = [];
    }

    return $result;
  }

  /**
   * Search the mailing-event queue for a list of pending delivery tasks.
   *
   * @param int $jobId
   * @param string $medium
   *   Ex: 'email' or 'sms'.
   *
   * @return \CRM_Mailing_Event_BAO_Queue
   *   A query object whose rows provide ('id', 'contact_id', 'hash') and ('email' or 'phone').
   */
  public static function findPendingTasks($jobId, $medium) {
    $eq = new CRM_Mailing_Event_BAO_Queue();
    $queueTable = CRM_Mailing_Event_BAO_Queue::getTableName();
    $emailTable = CRM_Core_BAO_Email::getTableName();
    $phoneTable = CRM_Core_BAO_Phone::getTableName();
    $contactTable = CRM_Contact_BAO_Contact::getTableName();
    $deliveredTable = CRM_Mailing_Event_BAO_Delivered::getTableName();
    $bounceTable = CRM_Mailing_Event_BAO_Bounce::getTableName();

    $query = "  SELECT      $queueTable.id,
                                $emailTable.email as email,
                                $queueTable.contact_id,
                                $queueTable.hash,
                                NULL as phone
                    FROM        $queueTable
                    INNER JOIN  $emailTable
                            ON  $queueTable.email_id = $emailTable.id
                    INNER JOIN  $contactTable
                            ON  $contactTable.id = $emailTable.contact_id
                    LEFT JOIN   $deliveredTable
                            ON  $queueTable.id = $deliveredTable.event_queue_id
                    LEFT JOIN   $bounceTable
                            ON  $queueTable.id = $bounceTable.event_queue_id
                    WHERE       $queueTable.job_id = " . $jobId . "
                        AND     $deliveredTable.id IS null
                        AND     $bounceTable.id IS null
                        AND    $contactTable.is_opt_out = 0";

    if ($medium === 'sms') {
      $query = "
                    SELECT      $queueTable.id,
                                $phoneTable.phone as phone,
                                $queueTable.contact_id,
                                $queueTable.hash,
                                NULL as email
                    FROM        $queueTable
                    INNER JOIN  $phoneTable
                            ON  $queueTable.phone_id = $phoneTable.id
                    INNER JOIN  $contactTable
                            ON  $contactTable.id = $phoneTable.contact_id
                    LEFT JOIN   $deliveredTable
                            ON  $queueTable.id = $deliveredTable.event_queue_id
                    LEFT JOIN   $bounceTable
                            ON  $queueTable.id = $bounceTable.event_queue_id
                    WHERE       $queueTable.job_id = " . $jobId . "
                        AND     $deliveredTable.id IS null
                        AND     $bounceTable.id IS null
                        AND    ( $contactTable.is_opt_out = 0
                        OR       $contactTable.do_not_sms = 0 )";
    }
    $eq->query($query);
    return $eq;
  }

  /**
   * Delete the mailing job.
   *
   * @param int $id
   *   Mailing Job id.
   *
   * @return mixed
   */
  public static function del($id) {
    CRM_Utils_Hook::pre('delete', 'MailingJob', $id, CRM_Core_DAO::$_nullArray);

    $jobDAO = new CRM_Mailing_BAO_MailingJob();
    $jobDAO->id = $id;
    $result = $jobDAO->delete();

    CRM_Utils_Hook::post('delete', 'MailingJob', $jobDAO->id, $jobDAO);

    return $result;
  }

}
