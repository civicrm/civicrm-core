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
class CRM_Mailing_Event_BAO_Opened extends CRM_Mailing_Event_DAO_Opened {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Register an open event.
   *
   * @param int $queue_id
   *   The Queue Event ID of the recipient.
   */
  public static function open($queue_id) {
    // First make sure there's a matching queue event.

    $success = FALSE;

    $q = new CRM_Mailing_Event_BAO_Queue();
    $q->id = $queue_id;
    if ($q->find(TRUE)) {
      $oe = new CRM_Mailing_Event_BAO_Opened();
      $oe->event_queue_id = $queue_id;
      $oe->time_stamp = date('YmdHis');
      $oe->save();
      $success = TRUE;
    }

    return $success;
  }

  /**
   * Get row count for the event selector.
   *
   * @param int $mailing_id
   *   ID of the mailing.
   * @param int $job_id
   *   Optional ID of a job to filter on.
   * @param bool $is_distinct
   *   Group by queue ID?.
   *
   * @param string $toDate
   *
   * @return int
   *   Number of rows in result set
   */
  public static function getTotalCount(
    $mailing_id,
    $job_id = NULL,
    $is_distinct = FALSE,
    $toDate = NULL
  ) {
    $dao = new CRM_Core_DAO();

    $open = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $query = "
            SELECT      COUNT($open.id) as opened
            FROM        $open
            INNER JOIN  $queue
                    ON  $open.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($toDate)) {
      $query .= " AND $open.time_stamp <= $toDate";
    }

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $dao->query($query);
    $dao->fetch();
    if ($is_distinct) {
      return $dao->N;
    }
    else {
      return $dao->opened ? $dao->opened : 0;
    }
  }

  /**
   * CRM-12814
   * Get opened count for each mailing for a given set of mailing IDs
   *
   * @param $mailingIDs
   *
   * @return array
   *   Opened count per mailing ID
   */
  public static function getMailingTotalCount($mailingIDs) {
    $dao = new CRM_Core_DAO();
    $openedCount = array();

    $open = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailingIDs = implode(',', $mailingIDs);

    $query = "
      SELECT $job.mailing_id as mailingID, COUNT($open.id) as opened
      FROM $open
      INNER JOIN $queue
        ON  $open.event_queue_id = $queue.id
      INNER JOIN $job
        ON  $queue.job_id = $job.id
        AND $job.is_test = 0
      WHERE $job.mailing_id IN ({$mailingIDs})
      GROUP BY civicrm_mailing_job.mailing_id
    ";

    $dao->query($query);

    while ($dao->fetch()) {
      $openedCount[$dao->mailingID] = $dao->opened;
    }
    return $openedCount;
  }

  /**
   * Get opened count for each mailing for a given set of mailing IDs and a specific contact.
   *
   * @param int $mailingIDs
   *   IDs of the mailing (comma separated).
   * @param int $contactID
   *   ID of the contact.
   *
   * @return array
   *   Count per mailing ID
   */
  public static function getMailingContactCount($mailingIDs, $contactID) {
    $dao = new CRM_Core_DAO();
    $openedCount = array();

    $open = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailingIDs = implode(',', $mailingIDs);

    $query = "
      SELECT $job.mailing_id as mailingID, COUNT($open.id) as opened
      FROM $open
      INNER JOIN $queue
        ON  $open.event_queue_id = $queue.id
        AND $queue.contact_id = $contactID
      INNER JOIN $job
        ON  $queue.job_id = $job.id
        AND $job.is_test = 0
      WHERE $job.mailing_id IN ({$mailingIDs})
      GROUP BY civicrm_mailing_job.mailing_id
    ";

    $dao->query($query);

    while ($dao->fetch()) {
      $openedCount[$dao->mailingID] = $dao->opened;
    }

    return $openedCount;
  }

  /**
   * Get rows for the event browser.
   *
   * @param int $mailing_id
   *   ID of the mailing.
   * @param int $job_id
   *   Optional ID of the job.
   * @param bool $is_distinct
   *   Group by queue id?.
   * @param int $offset
   *   Offset.
   * @param int $rowCount
   *   Number of rows.
   * @param array $sort
   *   Sort array.
   *
   * @param int $contact_id
   *
   * @return array
   *   Result set
   */
  public static function &getRows(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $offset = NULL, $rowCount = NULL, $sort = NULL, $contact_id = NULL
  ) {
    $dao = new CRM_Core_Dao();

    $open = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $open.time_stamp as date
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $open
                    ON  $open.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if (!empty($contact_id)) {
      $query .= " AND $contact.id = " . CRM_Utils_Type::escape($contact_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $orderBy = "sort_name ASC, {$open}.time_stamp DESC";
    if ($sort) {
      if (is_string($sort)) {
        $sort = CRM_Utils_Type::escape($sort, 'String');
        $orderBy = $sort;
      }
      else {
        $orderBy = trim($sort->orderBy());
      }
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

}
