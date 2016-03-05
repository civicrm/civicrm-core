<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Mailing_Event_BAO_Queue extends CRM_Mailing_Event_DAO_Queue {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Queue a new recipient.
   *
   * @param array $params
   *   Values of the new EventQueue.
   *
   * @return CRM_Mailing_Event_BAO_Queue
   *   The new EventQueue
   */
  public static function create($params) {
    $eq = new CRM_Mailing_Event_BAO_Queue();
    $eq->copyValues($params);
    if (empty($params['id']) && empty($params['hash'])) {
      $eq->hash = self::hash($params);
    }
    $eq->save();
    return $eq;
  }

  /**
   * Create a security hash from the job, email and contact ids.
   *
   * @param array $params
   *
   * @return int
   *   The hash
   */
  public static function hash($params) {
    $jobId = $params['job_id'];
    $emailId = CRM_Utils_Array::value('email_id', $params, '');
    $contactId = $params['contact_id'];

    return substr(sha1("{$jobId}:{$emailId}:{$contactId}:" . time()),
      0, 16
    );
  }

  /**
   * Verify that a queue event exists with the specified id/job id/hash.
   *
   * @param int $job_id
   *   The job ID of the event to find.
   * @param int $queue_id
   *   The Queue Event ID to find.
   * @param string $hash
   *   The hash to validate against.
   *
   * @return object|null
   *   The queue event if verified, or null
   */
  public static function &verify($job_id, $queue_id, $hash) {
    $success = NULL;
    $q = new CRM_Mailing_Event_BAO_Queue();
    if (!empty($job_id) && !empty($queue_id) && !empty($hash)) {
      $q->id = $queue_id;
      $q->job_id = $job_id;
      $q->hash = $hash;
      if ($q->find(TRUE)) {
        $success = $q;
      }
    }
    return $success;
  }

  /**
   * Given a queue event ID, find the corresponding email address.
   *
   * @param int $queue_id
   *   The queue event ID.
   *
   * @return string
   *   The email address
   */
  public static function getEmailAddress($queue_id) {
    $email = CRM_Core_BAO_Email::getTableName();
    $eq = self::getTableName();
    $query = "  SELECT      $email.email as email
                    FROM        $email
                    INNER JOIN  $eq
                    ON          $eq.email_id = $email.id
                    WHERE       $eq.id = " . CRM_Utils_Type::rule($queue_id, 'Integer');

    $q = new CRM_Mailing_Event_BAO_Queue();
    $q->query($query);
    if (!$q->fetch()) {
      return NULL;
    }

    return $q->email;
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
    $dao = new CRM_Core_Dao();

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
            SELECT      $contact.display_name as display_name,
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

    $results = array();

    while ($dao->fetch()) {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[] = array(
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        'date' => CRM_Utils_Date::customFormat($dao->date),
      );
    }
    return $results;
  }

  /**
   * Get the mailing object for this queue event instance.
   *
   * @param
   *
   * @return object
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

    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    $displayName = 'Unknown';
    $email = 'Unknown';
    if ($dao->fetch()) {
      $displayName = $dao->display_name;
      $email = $dao->email;
    }

    return array($displayName, $email);
  }

  /**
   * @param array $params
   * @param null $now
   */
  public static function bulkCreate($params, $now = NULL) {
    if (!$now) {
      $now = time();
    }

    // construct a bulk insert statement
    $values = array();
    foreach ($params as $param) {
      $values[] = "( {$param[0]}, {$param[1]}, {$param[2]}, {$param[3]}, '" . substr(sha1("{$param[0]}:{$param[1]}:{$param[2]}:{$param[3]}:{$now}"),
          0, 16
        ) . "' )";
    }

    while (!empty($values)) {
      $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
      $str = implode(',', $input);
      $sql = "INSERT INTO civicrm_mailing_event_queue ( job_id, email_id, contact_id, phone_id, hash ) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

}
