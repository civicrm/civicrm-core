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
class CRM_Mailing_Event_BAO_MailingEventOpened extends CRM_Mailing_Event_DAO_MailingEventOpened {

  /**
   * Register an open event.
   *
   * @param int $queue_id
   *   The Queue Event ID of the recipient.
   * @param string|null $dateTime
   *   When did the Open Event happen
   *
   * @return bool
   */
  public static function open($queue_id, $dateTime = NULL): bool {
    // First make sure there's a matching queue event.
    $q = new CRM_Mailing_Event_BAO_MailingEventQueue();
    $q->id = $queue_id;
    if ($q->find(TRUE)) {
      self::writeRecord([
        'event_queue_id' => $queue_id,
        'time_stamp' => $dateTime ?? date('YmdHis'),
      ]);
      return TRUE;
    }

    return FALSE;
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
    $queue = CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
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
      return $dao->opened ?: 0;
    }
  }

  /**
   * @see https://issues.civicrm.org/jira/browse/CRM-12814
   * Get opened count for each mailing for a given set of mailing IDs
   *
   * @param int[] $mailingIDs
   *
   * @return array
   *   Opened count per mailing ID
   */
  public static function getMailingTotalCount($mailingIDs) {
    $dao = new CRM_Core_DAO();
    $openedCount = [];

    $open = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
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
   * @param int[] $mailingIDs
   *   IDs of the mailing (comma separated).
   * @param int $contactID
   *   ID of the contact.
   *
   * @return array
   *   Count per mailing ID
   */
  public static function getMailingContactCount($mailingIDs, $contactID) {
    $dao = new CRM_Core_DAO();
    $openedCount = [];

    $open = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
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
    $dao = new CRM_Core_DAO();

    $open = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $selectClauses = [
      "$contact.display_name as display_name",
      "$contact.id as contact_id",
      "$email.email as email",
      ($is_distinct) ? "MIN({$open}.time_stamp) as date" : "{$open}.time_stamp as date",
    ];

    if ($is_distinct) {
      $groupBy = " GROUP BY $queue.id ";
      $select = CRM_Contact_BAO_Query::appendAnyValueToSelect($selectClauses, "$queue.id");
    }
    else {
      $groupBy = '';
      $select = " SELECT " . implode(', ', $selectClauses);
    }

    $query = "
            $select
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

    $query .= $groupBy;

    $orderBy = "sort_name ASC";
    if (!$is_distinct) {
      $orderBy .= ", {$open}.time_stamp DESC";
    }
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

    $results = [];

    while ($dao->fetch()) {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[] = [
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

  public static function queuedOpen(\CRM_Queue_TaskContext $ctx, $queue_id, $dateTime): bool {
    return self::open($queue_id, $dateTime);
  }

}
