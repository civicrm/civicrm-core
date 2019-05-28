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
class CRM_Mailing_Event_BAO_TrackableURLOpen extends CRM_Mailing_Event_DAO_TrackableURLOpen {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Track a click-through and return the URL to redirect.
   *
   * If the numbers don't match up, return the base url.
   *
   * @param int $queue_id
   *   The Queue Event ID of the clicker.
   * @param int $url_id
   *   The ID of the trackable URL.
   *
   * @return string
   *   The redirection url, or base url on failure.
   */
  public static function track($queue_id, $url_id) {
    // To find the url, we also join on the queue and job tables.  This
    // prevents foreign key violations.
    $job = CRM_Utils_Type::escape(CRM_Mailing_BAO_MailingJob::getTableName(), 'MysqlColumnNameOrAlias');
    $eq = CRM_Utils_Type::escape(CRM_Mailing_Event_BAO_Queue::getTableName(), 'MysqlColumnNameOrAlias');
    $turl = CRM_Utils_Type::escape(CRM_Mailing_BAO_TrackableURL::getTableName(), 'MysqlColumnNameOrAlias');

    if (!$queue_id) {
      $search = CRM_Core_DAO::executeQuery(
        "SELECT url
           FROM $turl
          WHERE $turl.id = %1",
        [
          1 => [$url_id, 'Integer'],
        ]
      );

      if (!$search->fetch()) {
        return CRM_Utils_System::baseURL();
      }

      return $search->url;
    }

    $search = CRM_Core_DAO::executeQuery(
      "SELECT $turl.url as url
         FROM $turl
        INNER JOIN $job ON $turl.mailing_id = $job.mailing_id
        INNER JOIN $eq ON $job.id = $eq.job_id
        WHERE $eq.id = %1 AND $turl.id = %2",
      [
        1 => [$queue_id, 'Integer'],
        2 => [$url_id, 'Integer'],
      ]
    );

    if (!$search->fetch()) {
      // Can't find either the URL or the queue. If we can find the URL then
      // return the URL without tracking.  Otherwise return the base URL.
      $search = CRM_Core_DAO::executeQuery(
        "SELECT $turl.url as url
           FROM $turl
          WHERE $turl.id = %1",
        [
          1 => [$url_id, 'Integer'],
        ]
      );

      if (!$search->fetch()) {
        return CRM_Utils_System::baseURL();
      }

      return $search->url;
    }

    $open = new CRM_Mailing_Event_BAO_TrackableURLOpen();
    $open->event_queue_id = $queue_id;
    $open->trackable_url_id = $url_id;
    $open->time_stamp = date('YmdHis');
    $open->save();

    return $search->url;
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
   * @param int $url_id
   *   Optional ID of a url to filter on.
   *
   * @param string $toDate
   *
   * @return int
   *   Number of rows in result set
   */
  public static function getTotalCount(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $url_id = NULL, $toDate = NULL
  ) {
    $dao = new CRM_Core_DAO();

    $click = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $distinct = NULL;
    if ($is_distinct) {
      $distinct = 'DISTINCT ';
    }
    $query = "
            SELECT      COUNT($distinct $click.event_queue_id) as opened
            FROM        $click
            INNER JOIN  $queue
                    ON  $click.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($toDate)) {
      $query .= " AND $click.time_stamp <= $toDate";
    }

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if (!empty($url_id)) {
      $query .= " AND $click.trackable_url_id = " . CRM_Utils_Type::escape($url_id, 'Integer');
    }

    // query was missing
    $dao->query($query);

    if ($dao->fetch()) {
      return $dao->opened;
    }

    return NULL;
  }

  /**
   * Get tracked url count for each mailing for a given set of mailing IDs.
   *
   * CRM-12814
   *
   * @param array $mailingIDs
   *
   * @return array
   *   trackable url count per mailing ID
   */
  public static function getMailingTotalCount($mailingIDs) {
    $dao = new CRM_Core_DAO();
    $clickCount = [];

    $click = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailingIDs = implode(',', $mailingIDs);

    $query = "
      SELECT $job.mailing_id as mailingID, COUNT($click.id) as opened
      FROM $click
      INNER JOIN $queue
        ON  $click.event_queue_id = $queue.id
      INNER JOIN $job
        ON  $queue.job_id = $job.id
        AND $job.is_test = 0
      WHERE $job.mailing_id IN ({$mailingIDs})
      GROUP BY civicrm_mailing_job.mailing_id
    ";

    $dao->query($query);

    while ($dao->fetch()) {
      $clickCount[$dao->mailingID] = $dao->opened;
    }
    return $clickCount;
  }

  /**
   * Get tracked url count for each mailing for a given set of mailing IDs.
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
    $clickCount = [];

    $click = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailingIDs = implode(',', $mailingIDs);

    $query = "
      SELECT $job.mailing_id as mailingID, COUNT($click.id) as opened
      FROM $click
      INNER JOIN $queue
        ON  $click.event_queue_id = $queue.id
        AND $queue.contact_id = $contactID
      INNER JOIN $job
        ON  $queue.job_id = $job.id
        AND $job.is_test = 0
      WHERE $job.mailing_id IN ({$mailingIDs})
      GROUP BY civicrm_mailing_job.mailing_id
    ";

    $dao->query($query);

    while ($dao->fetch()) {
      $clickCount[$dao->mailingID] = $dao->opened;
    }

    return $clickCount;
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
   * @param int $url_id
   *   Optional ID of a trackable URL to filter on.
   * @param int $offset
   *   Offset.
   * @param int $rowCount
   *   Number of rows.
   * @param array $sort
   *   Sort array.
   * @param int $contact_id
   *   Optional contact ID.
   *
   * @return array
   *   Result set
   */
  public static function &getRows(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $url_id,
    $offset = NULL, $rowCount = NULL, $sort = NULL, $contact_id = NULL
  ) {

    $dao = new CRM_Core_Dao();

    $click = self::getTableName();
    $url = CRM_Mailing_BAO_TrackableURL::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,";

    if ($is_distinct) {
      $query .= "MIN($click.time_stamp) as date,";
    }
    else {
      $query .= "$click.time_stamp as date,";
    }

    $query .= "$url.url as url
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $click
                    ON  $click.event_queue_id = $queue.id
            INNER JOIN  $url
                    ON  $click.trackable_url_id = $url.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($contact_id)) {
      $query .= " AND $contact.id = " . CRM_Utils_Type::escape($contact_id, 'Integer');
    }

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if (!empty($url_id)) {
      $query .= " AND $url.id = " . CRM_Utils_Type::escape($url_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id, $url.url ";
    }

    $orderBy = "sort_name ASC, {$click}.time_stamp DESC";
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
    CRM_Core_DAO::disableFullGroupByMode();
    $dao->query($query);
    CRM_Core_DAO::reenableFullGroupByMode();
    $results = [];

    while ($dao->fetch()) {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[] = [
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        'url' => $dao->url,
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

}
