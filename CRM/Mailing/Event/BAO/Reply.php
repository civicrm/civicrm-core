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

require_once 'Mail/mime.php';

/**
 * Class CRM_Mailing_Event_BAO_Reply
 */
class CRM_Mailing_Event_BAO_Reply extends CRM_Mailing_Event_DAO_Reply {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Register a reply event.
   *
   * @param int $job_id
   *   The job ID of the reply.
   * @param int $queue_id
   *   The queue event id.
   * @param string $hash
   *   The hash.
   *
   * @param null $replyto
   *
   * @return object|null
   *   The mailing object, or null on failure
   */
  public static function &reply($job_id, $queue_id, $hash, $replyto = NULL) {
    // First make sure there's a matching queue event.
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);

    $success = NULL;

    if (!$q) {
      return $success;
    }

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailings = CRM_Mailing_BAO_Mailing::getTableName();
    $jobs = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailing->query(
      "SELECT * FROM  $mailings
            INNER JOIN      $jobs
                ON          $jobs.mailing_id = $mailings.id
            WHERE           $jobs.id = {$q->job_id}"
    );
    $mailing->fetch();
    if ($mailing->auto_responder) {
      self::autoRespond($mailing, $queue_id, $replyto);
    }

    $re = new CRM_Mailing_Event_BAO_Reply();
    $re->event_queue_id = $queue_id;
    $re->time_stamp = date('YmdHis');
    $re->save();

    if (!$mailing->forward_replies || empty($mailing->replyto_email)) {
      return $success;
    }

    return $mailing;
  }

  /**
   * Forward a mailing reply.
   *
   * @param int $queue_id
   *   Queue event ID of the sender.
   * @param string $mailing
   *   The mailing object.
   * @param string $bodyTxt
   *   Text part of the body (ignored if $fullEmail provided).
   * @param string $replyto
   *   Reply-to of the incoming message.
   * @param string $bodyHTML
   *   HTML part of the body (ignored if $fullEmail provided).
   * @param string $fullEmail
   *   Whole email to forward in one string.
   */
  public static function send($queue_id, &$mailing, &$bodyTxt, $replyto, &$bodyHTML = NULL, &$fullEmail = NULL) {
    $domain = CRM_Core_BAO_Domain::getDomain();
    $emails = CRM_Core_BAO_Email::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $contacts = CRM_Contact_BAO_Contact::getTableName();
    $domain_id = CRM_Core_Config::domainID();
    $domainValues = civicrm_api3('Domain', 'get', ['sequential' => 1, 'id' => $domain_id]);
    $fromEmail = CRM_Core_BAO_Domain::getNoReplyEmailAddress();

    $eq = new CRM_Core_DAO();
    $eq->query("SELECT     $contacts.display_name as display_name,
                           $emails.email as email,
                           $queue.job_id as job_id,
                           $queue.hash as hash
                   FROM        $queue
                   INNER JOIN  $contacts
                           ON  $queue.contact_id = $contacts.id
                   INNER JOIN  $emails
                           ON  $queue.email_id = $emails.id
                   WHERE       $queue.id = " . CRM_Utils_Type::escape($queue_id, 'Integer')
    );
    $eq->fetch();

    if ($fullEmail) {
      // parse the email and set a new destination
      $parser = new ezcMailParser();
      $set = new ezcMailVariableSet($fullEmail);
      $parsed = array_shift($parser->parseMail($set));
      $parsed->to = [new ezcMailAddress($mailing->replyto_email)];

      // CRM-5567: we need to set Reply-To: so that any response
      // to the forward goes to the sender of the reply
      $parsed->setHeader('Reply-To', $replyto instanceof ezcMailAddress ? $replyto : $parsed->from->__toString());

      // Using the original from address may not be permitted by the mailer.
      $fromName = empty($parsed->from->name) ? $parsed->from->email : "{$parsed->from->name} ({$parsed->from->email})";
      $parsed->from = new ezcMailAddress($fromEmail, $fromName);

      // CRM-17754 Include re-sent headers to indicate that we have forwarded on the email
      $domainEmail = $domainValues['values'][0]['from_email'];
      $parsed->setHeader('Resent-From', $domainEmail);
      $parsed->setHeader('Resent-Date', date('r'));
      // Rewrite any invalid Return-Path headers.
      $parsed->setHeader('Return-Path', $fromEmail);

      // $h must be an array, so we can't use generateHeaders()'s result,
      // but we have to regenerate the headers because we changed To
      $parsed->generateHeaders();
      $h = $parsed->headers->getCaseSensitiveArray();
      $b = $parsed->generateBody();

      // FIXME: ugly hack - find the first MIME boundary in
      // the body and make the boundary in the header match it
      $ct = $h['Content-Type'];
      if (substr_count($ct, 'boundary=')) {
        $matches = [];
        preg_match('/^--(.*)$/m', $b, $matches);
        $boundary = rtrim($matches[1]);
        $parts = explode('boundary=', $ct);
        $ct = "{$parts[0]} boundary=\"$boundary\"";
      }
    }
    else {
      $fromName = empty($eq->display_name) ? $eq->email : "{$eq->display_name} ({$eq->email})";

      $message = new Mail_mime("\n");

      $headers = [
        'Subject' => "Re: {$mailing->subject}",
        'To' => $mailing->replyto_email,
        'From' => "\"$fromName\" <$fromEmail>",
        'Reply-To' => empty($replyto) ? $eq->email : $replyto,
        'Return-Path' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
        // CRM-17754 Include re-sent headers to indicate that we have forwarded on the email
        'Resent-From' => $domainValues['values'][0]['from_email'],
        'Resent-Date' => date('r'),
      ];

      $message->setTxtBody($bodyTxt);
      $message->setHTMLBody($bodyHTML);
      $b = CRM_Utils_Mail::setMimeParams($message);
      $h = $message->headers($headers);
    }

    CRM_Mailing_BAO_Mailing::addMessageIdHeader($h, 'r', $eq->job_id, $queue_id, $eq->hash);
    $config = CRM_Core_Config::singleton();
    $mailer = \Civi::service('pear_mail');

    if (is_object($mailer)) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      $mailer->send($mailing->replyto_email, $h, $b);
      unset($errorScope);
    }
  }

  /**
   * Send an automated response.
   *
   * @param object $mailing
   *   The mailing object.
   * @param int $queue_id
   *   The queue ID.
   * @param string $replyto
   *   Optional reply-to from the reply.
   */
  private static function autoRespond(&$mailing, $queue_id, $replyto) {
    $config = CRM_Core_Config::singleton();

    $contacts = CRM_Contact_DAO_Contact::getTableName();
    $email = CRM_Core_DAO_Email::getTableName();
    $queue = CRM_Mailing_Event_DAO_Queue::getTableName();

    $eq = new CRM_Core_DAO();
    $eq->query(
      "SELECT     $contacts.preferred_mail_format as format,
                  $email.email as email,
                  $queue.job_id as job_id,
                  $queue.hash as hash
        FROM        $contacts
        INNER JOIN  $queue ON $queue.contact_id = $contacts.id
        INNER JOIN  $email ON $queue.email_id = $email.id
        WHERE       $queue.id = " . CRM_Utils_Type::escape($queue_id, 'Integer')
    );
    $eq->fetch();

    $to = empty($replyto) ? $eq->email : $replyto;

    $component = new CRM_Mailing_BAO_MailingComponent();
    $component->id = $mailing->reply_id;
    $component->find(TRUE);

    $domain = CRM_Core_BAO_Domain::getDomain();
    list($domainEmailName, $_) = CRM_Core_BAO_Domain::getNameAndEmail();

    $params = [
      'subject' => $component->subject,
      'toEmail' => $to,
      'from' => "\"$domainEmailName\" <" . CRM_Core_BAO_Domain::getNoReplyEmailAddress() . '>',
      'replyTo' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
      'returnPath' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
    ];

    // TODO: do we need reply tokens?
    $html = $component->body_html;
    if ($component->body_text) {
      $text = $component->body_text;
    }
    else {
      $text = CRM_Utils_String::htmlToText($component->body_html);
    }

    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->body_text = $text;
    $bao->body_html = $html;
    $tokens = $bao->getTokens();

    if ($eq->format == 'HTML' || $eq->format == 'Both') {
      $html = CRM_Utils_Token::replaceDomainTokens($html, $domain, TRUE, $tokens['html']);
      $html = CRM_Utils_Token::replaceMailingTokens($html, $mailing, NULL, $tokens['html']);
    }
    if (!$html || $eq->format == 'Text' || $eq->format == 'Both') {
      $text = CRM_Utils_Token::replaceDomainTokens($text, $domain, FALSE, $tokens['text']);
      $text = CRM_Utils_Token::replaceMailingTokens($text, $mailing, NULL, $tokens['text']);
    }
    $params['html'] = $html;
    $params['text'] = $text;

    CRM_Mailing_BAO_Mailing::addMessageIdHeader($params, 'a', $eq->job_id, queue_id, $eq->hash);
    if (CRM_Core_BAO_MailSettings::includeMessageId()) {
      $params['messageId'] = $params['Message-ID'];
    }
    CRM_Utils_Mail::send($params);
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

    $reply = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $query = "
            SELECT      COUNT($reply.id) as reply
            FROM        $reply
            INNER JOIN  $queue
                    ON  $reply.event_queue_id = $queue.id
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
      return $dao->reply;
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

    $dao = new CRM_Core_DAO();

    $reply = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $reply.time_stamp as date
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $reply
                    ON  $reply.event_queue_id = $queue.id
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
      $query .= " GROUP BY $queue.id, $contact.id, $reply.time_stamp ";
    }

    $orderBy = "sort_name ASC, {$reply}.time_stamp DESC";
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

}
