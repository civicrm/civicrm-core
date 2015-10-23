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
class CRM_Mailing_Event_BAO_Bounce extends CRM_Mailing_Event_DAO_Bounce {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Create a new bounce event, update the email address if necessary
   *
   * @param $params
   *
   * @return bool|null
   */
  public static function &create(&$params) {
    $q = &CRM_Mailing_Event_BAO_Queue::verify($params['job_id'],
      $params['event_queue_id'],
      $params['hash']
    );
    $success = NULL;

    if (!$q) {
      return $success;
    }

    $transaction = new CRM_Core_Transaction();
    $bounce = new CRM_Mailing_Event_BAO_Bounce();
    $bounce->time_stamp = date('YmdHis');

    // if we dont have a valid bounce type, we should set it
    // to bounce_type_id 11 which is Syntax error. this allows such email
    // addresses to be bounce a few more time before being put on hold
    // CRM-4814
    // we changed this behavior since this bounce type might be due to some issue
    // with the connection or smtp server etc
    if (empty($params['bounce_type_id'])) {
      $params['bounce_type_id'] = 11;
      if (empty($params['bounce_reason'])) {
        $params['bounce_reason'] = ts('Unknown bounce type: Could not parse bounce email');
      }
    }

    // CRM-11989
    $params['bounce_reason'] = substr($params['bounce_reason'], 0, 254);

    $bounce->copyValues($params);
    $bounce->save();
    $success = TRUE;

    $bounceTable = CRM_Mailing_Event_BAO_Bounce::getTableName();
    $bounceType = CRM_Mailing_DAO_BounceType::getTableName();
    $emailTable = CRM_Core_BAO_Email::getTableName();
    $queueTable = CRM_Mailing_Event_BAO_Queue::getTableName();

    $bounce->reset();
    // might want to put distinct inside the count
    $query = "SELECT     count($bounceTable.id) as bounces,
                            $bounceType.hold_threshold as threshold
                FROM        $bounceTable
                INNER JOIN  $bounceType
                        ON  $bounceTable.bounce_type_id = $bounceType.id
                INNER JOIN  $queueTable
                        ON  $bounceTable.event_queue_id = $queueTable.id
                INNER JOIN  $emailTable
                        ON  $queueTable.email_id = $emailTable.id
                WHERE       $emailTable.id = {$q->email_id}
                    AND     ($emailTable.reset_date IS NULL
                        OR  $bounceTable.time_stamp >= $emailTable.reset_date)
                GROUP BY    $bounceTable.bounce_type_id
                ORDER BY    threshold, bounces desc";

    $bounce->query($query);

    while ($bounce->fetch()) {
      if ($bounce->bounces >= $bounce->threshold) {
        $email = new CRM_Core_BAO_Email();
        $email->id = $q->email_id;
        $email->on_hold = TRUE;
        $email->hold_date = date('YmdHis');
        $email->save();
        break;
      }
    }
    $transaction->commit();

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
   * @param string|null $toDate
   *
   * @return int
   *   Number of rows in result set
   */
  public static function getTotalCount($mailing_id, $job_id = NULL, $is_distinct = FALSE, $toDate = NULL) {
    $dao = new CRM_Core_DAO();

    $bounce = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $query = "
            SELECT      COUNT($bounce.id) as bounce
            FROM        $bounce
            INNER JOIN  $queue
                    ON  $bounce.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($toDate)) {
      $query .= " AND $bounce.time_stamp <= $toDate";
    }

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    // query was missing
    $dao->query($query);

    if ($dao->fetch()) {
      return $dao->bounce;
    }

    return NULL;
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
   * @return array
   *   Result set
   */
  public static function &getRows(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $offset = NULL, $rowCount = NULL, $sort = NULL
  ) {

    $dao = new CRM_Core_Dao();

    $bounce = self::getTableName();
    $bounceType = CRM_Mailing_DAO_BounceType::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $bounce.time_stamp as date,
                        $bounce.bounce_reason as reason,
                        $bounceType.name as bounce_type
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $bounce
                    ON  $bounce.event_queue_id = $queue.id
            LEFT JOIN   $bounceType
                    ON  $bounce.bounce_type_id = $bounceType.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
                    AND $job.is_test = 0
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $orderBy = "sort_name ASC, {$bounce}.time_stamp DESC";
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
        // FIXME: translate this
        'type' => (empty($dao->bounce_type) ? ts('Unknown') : $dao->bounce_type
        ),
        'reason' => $dao->reason,
        'date' => CRM_Utils_Date::customFormat($dao->date),
      );
    }
    return $results;
  }

}
