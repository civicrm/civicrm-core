<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Mailing_Event_BAO_Forward extends CRM_Mailing_Event_DAO_Forward {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Create a new forward event, create a new contact if necessary
   *
   * @param $job_id
   * @param $queue_id
   * @param $hash
   * @param $forward_email
   * @param null $fromEmail
   * @param null $comment
   *
   * @return bool
   */
  public static function &forward($job_id, $queue_id, $hash, $forward_email, $fromEmail = NULL, $comment = NULL) {
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);

    $successfulForward = FALSE;
    $contact_id = NULL;
    if (!$q) {
      return $successfulForward;
    }

    // Find the email address/contact, if it exists.

    $contact = CRM_Contact_BAO_Contact::getTableName();
    $location = CRM_Core_BAO_Location::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();
    $queueTable = CRM_Mailing_Event_BAO_Queue::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $forward = self::getTableName();

    $domain = CRM_Core_BAO_Domain::getDomain();

    $dao = new CRM_Core_Dao();
    $dao->query("
                SELECT      $contact.id as contact_id,
                            $email.id as email_id,
                            $contact.do_not_email as do_not_email,
                            $queueTable.id as queue_id
                FROM        ($email, $job as temp_job)
                INNER JOIN  $contact
                        ON  $email.contact_id = $contact.id
                LEFT JOIN   $queueTable
                        ON  $email.id = $queueTable.email_id
                LEFT JOIN   $job
                        ON  $queueTable.job_id = $job.id
                        AND temp_job.mailing_id = $job.mailing_id
                WHERE       $queueTable.job_id = $job_id
                    AND     $email.email = '" .
      CRM_Utils_Type::escape($forward_email, 'String') . "'"
    );

    $dao->fetch();

    $transaction = new CRM_Core_Transaction();

    if (isset($dao->queue_id) ||
      (isset($dao->do_not_email) && $dao->do_not_email == 1)
    ) {
      // We already sent this mailing to $forward_email, or we should
      // never email this contact.  Give up.

      return $successfulForward;
    }

    require_once 'api/api.php';
    $contactParams = array(
      'email' => $forward_email,
      'version' => 3,
    );
    $contactValues = civicrm_api('contact', 'get', $contactParams);
    $count = $contactValues['count'];

    if ($count == 0) {
      // If the contact does not exist, create one.

      $formatted = array(
        'contact_type' => 'Individual',
        'version' => 3,
      );
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $value = array(
        'email' => $forward_email,
        'location_type_id' => $locationType->id,
      );
      require_once 'CRM/Utils/DeprecatedUtils.php';
      _civicrm_api3_deprecated_add_formatted_param($value, $formatted);
      $formatted['onDuplicate'] = CRM_Import_Parser::DUPLICATE_SKIP;
      $formatted['fixAddress'] = TRUE;
      $contact = civicrm_api('contact', 'create', $formatted);
      if (civicrm_error($contact)) {
        return $successfulForward;
      }
      $contact_id = $contact['id'];
    }
    $email = new CRM_Core_DAO_Email();
    $email->email = $forward_email;
    $email->find(TRUE);
    $email_id = $email->id;
    if (!$contact_id) {
      $contact_id = $email->contact_id;
    }

    // Create a new queue event.

    $queue_params = array(
      'email_id' => $email_id,
      'contact_id' => $contact_id,
      'job_id' => $job_id,
    );

    $queue = CRM_Mailing_Event_BAO_Queue::create($queue_params);

    $forward = new CRM_Mailing_Event_BAO_Forward();
    $forward->time_stamp = date('YmdHis');
    $forward->event_queue_id = $queue_id;
    $forward->dest_queue_id = $queue->id;
    $forward->save();

    $dao->reset();
    $dao->query("   SELECT  $job.mailing_id as mailing_id
                        FROM    $job
                        WHERE   $job.id = " .
      CRM_Utils_Type::escape($job_id, 'Integer')
    );
    $dao->fetch();
    $mailing_obj = new CRM_Mailing_BAO_Mailing();
    $mailing_obj->id = $dao->mailing_id;
    $mailing_obj->find(TRUE);

    $config = CRM_Core_Config::singleton();
    $mailer = \Civi::service('pear_mail');

    $recipient = NULL;
    $attachments = NULL;
    $message = $mailing_obj->compose($job_id, $queue->id, $queue->hash,
      $queue->contact_id, $forward_email, $recipient, FALSE, NULL, $attachments, TRUE, $fromEmail
    );
    //append comment if added while forwarding.
    if (count($comment)) {
      $message->_txtbody = CRM_Utils_Array::value('body_text', $comment) . $message->_txtbody;
      if (!empty($comment['body_html'])) {
        $message->_htmlbody = $comment['body_html'] . '<br />---------------Original message---------------------<br />' . $message->_htmlbody;
      }
    }

    $body = $message->get();
    $headers = $message->headers();

    $result = NULL;
    if (is_object($mailer)) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      $result = $mailer->send($recipient, $headers, $body);
      unset($errorScope);
    }

    $params = array(
      'event_queue_id' => $queue->id,
      'job_id' => $job_id,
      'hash' => $queue->hash,
    );
    if (is_a($result, 'PEAR_Error')) {
      // Register the bounce event.

      $params = array_merge($params,
        CRM_Mailing_BAO_BouncePattern::match($result->getMessage())
      );
      CRM_Mailing_Event_BAO_Bounce::create($params);
    }
    else {
      $successfulForward = TRUE;
      // Register the delivery event.

      CRM_Mailing_Event_BAO_Delivered::create($params);
    }

    $transaction->commit();

    return $successfulForward;
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
   * @return int
   *   Number of rows in result set
   */
  public static function getTotalCount(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE
  ) {
    $dao = new CRM_Core_DAO();

    $forward = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $query = "
            SELECT      COUNT($forward.id) as forward
            FROM        $forward
            INNER JOIN  $queue
                    ON  $forward.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    // query was missing
    $dao->query($query);

    if ($dao->fetch()) {
      return $dao->forward;
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

    $forward = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as from_name,
                        $contact.id as from_id,
                        $email.email as from_email,
                        dest_contact.id as dest_id,
                        dest_email.email as dest_email,
                        $forward.time_stamp as date
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $forward
                    ON  $forward.event_queue_id = $queue.id
            INNER JOIN  $queue as dest_queue
                    ON  $forward.dest_queue_id = dest_queue.id
            INNER JOIN  $contact as dest_contact
                    ON  dest_queue.contact_id = dest_contact.id
            INNER JOIN  $email as dest_email
                    ON  dest_queue.email_id = dest_email.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $orderBy = "sort_name ASC, {$forward}.time_stamp DESC";
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
      $from_url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->from_id}"
      );
      $dest_url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->dest_id}"
      );
      $results[] = array(
        'from_name' => "<a href=\"$from_url\">{$dao->from_name}</a>",
        'from_email' => $dao->from_email,
        'dest_email' => "<a href=\"$dest_url\">{$dao->dest_email}</a>",
        'date' => CRM_Utils_Date::customFormat($dao->date),
      );
    }
    return $results;
  }

}
