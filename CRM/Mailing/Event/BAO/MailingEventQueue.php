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
class CRM_Mailing_Event_BAO_MailingEventQueue extends CRM_Mailing_Event_DAO_MailingEventQueue {

  /**
   * Queue a new recipient.
   *
   * @param array $params
   *   Values of the new EventQueue.
   *
   * @return CRM_Mailing_Event_BAO_MailingEventQueue
   *   The new EventQueue
   */
  public static function create($params) {
    $eq = new CRM_Mailing_Event_BAO_MailingEventQueue();
    $eq->copyValues($params);
    if (empty($params['id']) && empty($params['hash'])) {
      $eq->hash = self::hash();
    }
    if (empty($params['id']) && !empty($params['job_id']) && empty($params['mailing_id'])) {
      // mailing_id is a new field in 5.67. Calling code should pass it in going forwards
      // but temporary handling will set it. (We should make the field required
      // when we remove this in future)
      CRM_Core_Error::deprecatedWarning('mailing_id should be passed into EventQueue create calls. Temporary handling has set it for now');
      $query = CRM_Core_DAO::executeQuery('SELECT mailing_id, is_test
        FROM civicrm_mailing_job job LEFT JOIN civicrm_mailing m ON m.id = mailing_id WHERE job.id = %1', [1 => [$params['job_id'], 'Integer']]);
      $eq->mailing_id = $query->mailing_id;
      $eq->is_test = $query->is_test;
    }
    $eq->save();
    return $eq;
  }

  /**
   * Create a unique-ish string to stare in the hash table.
   *
   * This is included in verp emails such that bounces go to a unique
   * address (e.g. b.123456.456ABC456ABC.my-email-address@example.com). In this case
   * b is the action (bounce), 123456 is the queue_id and the last part is the
   * random string from this function. Note that the local part of the email
   * can have a max of 64 characters
   *
   * https://issues.civicrm.org/jira/browse/CRM-2574
   *
   * The hash combined with the queue id provides a fairly unguessable combo for the emails
   * (enough that a sysadmin should notice if someone tried to brute force it!)
   *
   * @return string
   *   The hash
   */
  public static function hash() {
    // Case-insensitive. Some b64 chars are awkward in VERP+URL contexts. Over-generate (24 bytes) and then cut-back (16 alphanums).
    $random = random_bytes(24);
    return strtolower(substr(str_replace(['+', '/', '='], ['', '', ''], base64_encode($random)), 0, 16));
  }

  /**
   * Verify that a queue event exists with the specified id/job id/hash.
   *
   * @param null $unused
   * @param int $queue_id
   *   The Queue Event ID to find.
   * @param string $hash
   *   The hash to validate against.
   *
   * @return object|null
   *   The queue event if verified, or null
   */
  public static function verify($unused, $queue_id, $hash) {
    $success = NULL;
    $q = new CRM_Mailing_Event_BAO_MailingEventQueue();
    if ($queue_id && $hash) {
      $q->id = $queue_id;
      $q->hash = $hash;
      if ($q->find(TRUE)) {
        $success = $q;
      }
    }
    return $success;
  }

  /**
   * Count up events given a mailing id and optional job id.
   *
   * @param int $mailing_id
   *   ID of the mailing to count.
   * @param int $job_id
   *   Optional ID of a job to limit results.
   *
   * @return int
   *   Number of matching events
   */
  public static function getTotalCount($mailing_id, $job_id = NULL) {
    $dao = new CRM_Core_DAO();

    $queue = self::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $dao->query("
            SELECT      COUNT(*) as queued
            FROM        $queue
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer') . ($job_id ? " AND $job.id = " . CRM_Utils_Type::escape($job_id,
          'Integer'
        ) : '')
    );

    $dao->fetch();
    return $dao->queued;
  }

  /**
   * Get rows for the event browser.
   *
   * @param int $mailing_id
   *   ID of the mailing.
   * @param int $job_id
   *   Optional ID of the job.
   * @param int $offset
   *   Offset.
   * @param int $rowCount
   *   Number of rows.
   * @param array $sort
   *   Sort array.
   *
   * @return array
   *   Result set
   */
  public static function &getRows(
    $mailing_id, $job_id = NULL, $offset = NULL,
    $rowCount = NULL, $sort = NULL
  ) {
    $dao = new CRM_Core_DAO();

    $queue = self::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $orderBy = "sort_name ASC, {$job}.start_date DESC";
    if ($sort) {
      if (is_string($sort)) {
        $sort = CRM_Utils_Type::escape($sort, 'String');
        $orderBy = $sort;
      }
      else {
        $orderBy = trim($sort->orderBy());
      }
    }

    $query = "
            SELECT      $queue.id as queue_id,
                        $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $job.start_date as date
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    $query .= " ORDER BY {$orderBy} ";

    if ($offset || $rowCount) {
      //Added "||$rowCount" to avoid displaying all records on first page
      $query .= ' LIMIT ' . CRM_Utils_Type::escape($offset, 'Integer') . ', ' . CRM_Utils_Type::escape($rowCount, 'Integer');
    }

    $dao->query($query);

    $results = [];

    while ($dao->fetch()) {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[$dao->queue_id] = [
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

  /**
   * Get the mailing object for this queue event instance.
   *
   * @return CRM_Mailing_BAO_Mailing
   *   Mailing BAO
   */
  public function &getMailing() {
    $mailing = new CRM_Mailing_BAO_Mailing();
    $jobs = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailings = CRM_Mailing_BAO_Mailing::getTableName();
    $queue = self::getTableName();

    $mailing->query("
                SELECT      $mailings.*
                FROM        $mailings
                INNER JOIN  $jobs
                        ON  $jobs.mailing_id = $mailings.id
                INNER JOIN  $queue
                        ON  $queue.job_id = $jobs.id
                WHERE       $queue.id = {$this->id}");
    $mailing->fetch();
    return $mailing;
  }

  /**
   * @param int $queueID
   *
   * @return array
   */
  public static function getContactInfo($queueID) {
    $query = "
SELECT DISTINCT(civicrm_mailing_event_queue.contact_id) as contact_id,
       civicrm_contact.display_name as display_name,
       civicrm_email.email as email
  FROM civicrm_mailing_event_queue,
       civicrm_contact,
       civicrm_email
 WHERE civicrm_mailing_event_queue.contact_id = civicrm_contact.id
   AND civicrm_mailing_event_queue.email_id = civicrm_email.id
   AND civicrm_mailing_event_queue.id = " . CRM_Utils_Type::escape($queueID, 'Integer');

    $dao = CRM_Core_DAO::executeQuery($query);

    $displayName = 'Unknown';
    $email = 'Unknown';
    if ($dao->fetch()) {
      $displayName = $dao->display_name;
      $email = $dao->email;
      $contact_id = $dao->contact_id;
    }

    return [$displayName, $email, $contact_id];
  }

  /**
   * Bulk save multiple records.
   *
   * For performance reasons hooks are not called here.
   *
   * @param array[] $records
   *
   * @return array
   */
  public static function writeRecords(array $records): array {
    $rows = [];
    foreach ($records as $record) {
      $record['hash'] = self::hash();
      $rows[] = $record;
      if (count($rows) >= CRM_Core_DAO::BULK_INSERT_COUNT) {
        CRM_Utils_SQL_Insert::into('civicrm_mailing_event_queue')->rows($rows)->execute();
        $rows = [];
      }
    }
    if ($rows) {
      CRM_Utils_SQL_Insert::into('civicrm_mailing_event_queue')->rows($rows)->execute();
    }
    // No point returning a big array but the standard function signature is to return an array
    // records
    return [];
  }

  /**
   * @deprecated
   * @param array $params
   * @param null $now
   */
  public static function bulkCreate($params, $now = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecords');
    if (!$now) {
      $now = time();
    }

    // construct a bulk insert statement
    $values = [];
    foreach ($params as $param) {
      $hash = static::hash();
      $values[] = "( {$param[0]}, {$param[1]}, {$param[2]}, {$param[3]}, '" . $hash . "' )";
      // FIXME: This (non)escaping is valid as currently used but is not robust to change. This should use CRM_Utils_SQL_Insert...
    }

    while (!empty($values)) {
      $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
      $str = implode(',', $input);
      $sql = "INSERT INTO civicrm_mailing_event_queue ( job_id, email_id, contact_id, phone_id, hash ) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

}
