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
use Civi\Api4\ActivityContact;
use Civi\Api4\Mailing;
use Civi\Api4\MailingJob;
use Civi\FlexMailer\FlexMailer;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

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
  public static $mailsProcessed = 0;

  /**
   * Create mailing job.
   *
   * @param array $params
   *
   * @deprecated since 5.71 will be removed around 5.85
   *
   * @return \CRM_Mailing_BAO_MailingJob
   * @throws \CRM_Core_Exception
   */
  public static function create(array $params): self {
    CRM_Core_Error::deprecatedWarning('use the api');
    $jobDAO = self::writeRecord($params);
    if (!empty($params['mailing_id']) && empty('is_calling_function_updated_to_reflect_deprecation')) {
      CRM_Core_Error::deprecatedWarning('mail recipients should not be generated during MailingJob::create');
      CRM_Mailing_BAO_Mailing::getRecipients($params['mailing_id']);
    }
    return $jobDAO;
  }

  /**
   * Initiate all pending/ready jobs.
   *
   * @param array $testParams
   * @param string|null $mode
   *   Either 'sms' or null
   *
   * @return bool|null
   */
  public static function runJobs($testParams = NULL, $mode = NULL) {
    $mailerBatchLimit = Civi::settings()->get('mailerBatchLimit');

    if (!empty($testParams)) {
      $query = "
      SELECT *
        FROM civicrm_mailing_job
       WHERE id = {$testParams['job_id']}";
      $result = CRM_Core_DAO::executeQuery($query);
    }
    else {
      $currentTime = date('YmdHis');
      $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL('m');
      $domainID = CRM_Core_Config::domainID();

      $modeClause = 'AND m.sms_provider_id ' . ($mode === 'sms' ? 'IS NOT NULL' : 'IS NULL');

      // Select the first child job that is scheduled
      // CRM-6835
      $query = "
      SELECT   j.*
        FROM   civicrm_mailing_job     j,
           civicrm_mailing m
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

      $result = CRM_Core_DAO::executeQuery($query);
    }

    while ($result->fetch()) {
      $mailingID = $result->mailing_id;
      // still use job level lock for each child job
      $lock = Civi::lockManager()->acquire("data.mailing.job.{$result->id}");
      if (!$lock->isAcquired()) {
        continue;
      }

      // for test jobs we do not change anything, since its on a short-circuit path
      if (empty($testParams)) {
        // we've got the lock, but while we were waiting and processing
        // other emails, this job might have changed under us
        // lets get the job status again and check
        $result->status = CRM_Core_DAO::getFieldValue(
          'CRM_Mailing_DAO_MailingJob',
          $result->id,
          'status',
          'id',
          TRUE
        );

        if (
          $result->status !== 'Running' &&
          $result->status !== 'Scheduled'
        ) {
          // this includes Cancelled and other statuses, CRM-4246
          $lock->release();
          continue;
        }
      }

      /* Queue up recipients for the child job being launched */

      if ($result->status !== 'Running') {
        $transaction = new CRM_Core_Transaction();

        // have to queue it up based on the offset and limits
        // get the parent ID, and limit and offset
        if (!empty($testParams)) {
          CRM_Mailing_BAO_Mailing::getTestRecipients($testParams, (int) $mailingID);
        }
        else {
          self::queue((int) $result->mailing_id, (int) $result->job_offset, (int) $result->job_limit, (int) $result->id);
        }

        // Update to show job has started.
        $startDate = date('YmdHis');
        MailingJob::update(FALSE)->setValues([
          'id' => $result->id,
          'start_date' => date('YmdHis'),
          'status' => 'Running',
        ])->execute();

        $transaction->commit();
      }

      // Compose and deliver each child job
      if ($mode === NULL) {
        $job = new CRM_Mailing_BAO_MailingJob();
        $job->id = $result->id;
        $job->find(TRUE);
        $mailer = \Civi::service('pear_mail');
        $isComplete = FlexMailer::createAndRun($job, $mailer, $testParams);
      }
      elseif ($mode === 'sms') {
        $smsJob = new CRM_Mailing_BAO_SMSJob();
        $smsJob->id = $result->id;
        $smsJob->find(TRUE);
        $isComplete = $smsJob->deliver(NULL, !empty($testParams));
      }

      CRM_Utils_Hook::post('create', 'CRM_Mailing_DAO_Spool', $result->id, $isComplete);

      // Mark the child complete
      if ($isComplete) {
        // Finish the job.

        MailingJob::update(FALSE)->setValues([
          'id' => $result->id,
          'end_date' => 'now',
          'status' => 'Complete',
        ])->execute();
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
   * @param string|null $mode
   *   Either 'sms' or null
   */
  public static function runJobs_post($mode = NULL) {

    $job = new CRM_Mailing_BAO_MailingJob();

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();

    $currentTime = date('YmdHis');
    $domainID = CRM_Core_Config::domainID();

    $query = "
                SELECT   j.*
                  FROM   civicrm_mailing_job     j,
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
        $mailing->status = 'Complete';
        $mailing->end_date = date('Y-m-d H:i:s');
        $mailing->save();
        $transaction->commit();

        // CRM-17763
        CRM_Utils_Hook::postMailing($job->mailing_id);
      }
    }
  }

  /**
   * before we run jobs, we need to split the jobs
   *
   * @param int $offset
   * @param string|null $mode
   *   Either 'sms' or null
   *
   * @throws \CRM_Core_Exception
   */
  public static function runJobs_pre(int $offset = 200, $mode = NULL): void {
    $currentTime = date('YmdHis');
    $workflowClause = CRM_Mailing_BAO_MailingJob::workflowClause();

    $domainID = CRM_Core_Config::domainID();

    $modeClause = 'AND m.sms_provider_id IS NULL';
    if ($mode === 'sms') {
      $modeClause = 'AND m.sms_provider_id IS NOT NULL';
    }

    // Select all the mailing jobs that are created from
    // when the mailing is submitted or scheduled.
    $query = "
    SELECT   j.*
      FROM civicrm_mailing_job j,
         civicrm_mailing m
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

    $job = CRM_Core_DAO::executeQuery($query);

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
      if ($job->status !== 'Scheduled') {
        $lock->release();
        continue;
      }

      $transaction = new CRM_Core_Transaction();

      self::split_job((int) $offset, (int) $job->id, (int) $job->mailing_id, $job->scheduled_date);

      // Update the status of the parent job
      MailingJob::update(FALSE)->setValues([
        'id' => $job->id,
        'start_date' => 'now',
        'status' => 'Running',
      ])->execute();
      // Update Mailing record as we have now started the sending process
      Mailing::update(FALSE)->setValues([
        'id' => $job->mailing_id,
        'start_date' => 'now',
        'status' => 'Running',
      ])->execute();
      $transaction->commit();

      // Release the job lock
      $lock->release();
    }
  }

  /**
   * Split the parent job into n number of child job based on an offset.
   * If null or 0 , we create only one child job
   *
   * @param int $offset
   * @param int $jobID
   * @param int $mailingID
   * @param string $scheduledDate
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  private static function split_job(int $offset, int $jobID, int $mailingID, string $scheduledDate): void {
    $recipient_count = CRM_Mailing_BAO_MailingRecipients::mailingSize($mailingID);
    $sql = '
INSERT INTO civicrm_mailing_job
(`mailing_id`, `scheduled_date`, `status`, `job_type`, `parent_id`, `job_offset`, `job_limit`)
VALUES (%1, %2, %3, %4, %5, %6, %7)
';
    $params = [
      1 => [$mailingID, 'Integer'],
      2 => [$scheduledDate, 'String'],
      3 => ['Scheduled', 'String'],
      4 => ['child', 'String'],
      5 => [$jobID, 'Integer'],
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
      $scheduled_unix_time = strtotime($scheduledDate);
      for ($i = 0, $s = 0; $i < $recipient_count; $i += $offset, $s++) {
        $params[2][0] = date('Y-m-d H:i:s', $scheduled_unix_time + $s);
        $params[6][0] = $i;
        $params[7][0] = $offset;
        CRM_Core_DAO::executeQuery($sql, $params);
      }
    }

  }

  /**
   * @param int $mailingID
   * @param int $jobOffset
   * @param int $limit
   * @param int $jobID
   *
   * @return void
   */
  private static function queue(int $mailingID, int $jobOffset, int $limit, int $jobID): void {
    // We are still getting all the recipients from the parent job
    // so we don't mess with the include/exclude logic.
    $recipients = CRM_Mailing_BAO_MailingRecipients::mailingQuery($mailingID, $jobOffset, $limit);

    $params = [];
    $count = 0;
    // dev/core#1768 Get the mail sync interval.
    $mail_sync_interval = Civi::settings()->get('civimail_sync_interval');
    while ($recipients->fetch()) {
      // CRM-18543: there are situations when both the email and phone are null.
      // Skip the recipient in this case.
      if (empty($recipients->email_id) && empty($recipients->phone_id)) {
        continue;
      }
      $params[] = [
        'job_id' => $jobID,
        'email_id' => $recipients->email_id ? (int) $recipients->email_id : NULL,
        'phone_id' => $recipients->phone_id ? (int) $recipients->phone_id : NULL,
        'contact_id' => $recipients->contact_id ? (int) $recipients->contact_id : NULL,
        'mailing_id' => (int) $mailingID,
        'is_test' => FALSE,
      ];
      $count++;
      /*
      The mail sync interval is used here to determine how
      many rows to insert in each insert statement.
      The discussion & name of the setting implies that the intent of the
      setting is the frequency with which the mailing tables are updated
      with information about actions taken on the mailings (ie if you send
      an email & quickly update the delivered table that impacts information
      availability.

      However, here it is used to manage the size of each individual
      insert statement. It is unclear why as the trade offs are out of sync
      ie. you want you insert statements here to be 'big, but not so big they
      stall out' but in the delivery context it's a trade off between
      information availability & performance.
      https://github.com/civicrm/civicrm-core/pull/17367 */

      if ($count % $mail_sync_interval === 0) {
        CRM_Mailing_Event_BAO_MailingEventQueue::writeRecords($params);
        $count = 0;
        $params = [];
      }
    }

    if (!empty($params)) {
      CRM_Mailing_Event_BAO_MailingEventQueue::writeRecords($params);
    }
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

    if (str_contains($message, 'Failed to write to socket')) {
      return TRUE;
    }

    // Register 5xx SMTP response code (permanent failure) as bounce.
    if (isset($code[0]) && $code[0] === '5') {
      return FALSE;
    }

    if (str_contains($message, 'Failed to set sender')) {
      return TRUE;
    }

    if (str_contains($message, 'Failed to add recipient')) {
      return TRUE;
    }

    if (str_contains($message, 'Failed to send data')) {
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
      Mailing::update(FALSE)
        ->setValues(['end_date' => date('YmdHis'), 'status' => 'Canceled'])
        ->addWhere('id', '=', $mailingId)
        ->execute();

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
    Mailing::update(FALSE)
      ->setValues(['status:name' => 'Paused'])
      ->addWhere('id', '=', $mailingID)
      ->execute();
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
    return $translation[$status] ?? ts('Not scheduled');
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
      CRM_Mailing_Event_BAO_MailingEventDelivered::bulkCreate($deliveredParams);
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
          throw new CRM_Core_Exception(ts('No relevant activity type found when recording Mailing Event delivered Activity'));
        }
      }

      $activity = [
        'source_contact_id' => $mailing->scheduled_id,
        'activity_type_id' => $activityTypeID,
        'source_record_id' => $this->mailing_id,
        'activity_date_time' => $job_date,
        'subject' => $mailing->subject,
        'status_id' => 'Completed',
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
      $targetRecordID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets');

      $activityTargets = [];
      foreach ($targetParams as $id) {
        $activityTargets[$id] = ['contact_id' => (int) $id];
      }
      if ($activityID) {
        $activity['id'] = $activityID;

        // CRM-9519
        if (CRM_Core_BAO_Email::isMultipleBulkMail()) {
          // make sure we don't attempt to duplicate the target activity
          // @todo - we don't have to do one contact at a time....
          foreach ($activityTargets as $key => $target) {
            $sql = "
SELECT id
FROM   civicrm_activity_contact
WHERE  activity_id = $activityID
AND    contact_id = {$target['contact_id']}
AND    record_type_id = $targetRecordID
";
            if (CRM_Core_DAO::singleValueQuery($sql)) {
              unset($activityTargets[$key]);
            }
          }
        }
      }

      try {
        $activity = civicrm_api3('Activity', 'create', $activity);
        ActivityContact::save(FALSE)->setRecords($activityTargets)->setDefaults(['activity_id' => $activity['id'], 'record_type_id' => $targetRecordID])->execute();
      }
      catch (Exception $e) {
        $result = FALSE;
      }

      $targetParams = [];
    }

    return $result;
  }

  /**
   * Delete the mailing job.
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
  }

}
