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

use Civi\API\EntityLookupTrait;
use Civi\Api4\MailingJob;

/**
 * Incoming mail class.
 *
 * @internal - this is not supported for use from outside of code.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Mail_IncomingMail {

  use EntityLookupTrait;

  /**
   * @var \ezcMail
   */
  private $mail;

  /**
   * @var string
   */
  private $action;

  /**
   * @var int
   */
  private $queueID;

  /**
   * @var int
   */
  private $jobID;

  /**
   * @var string
   */
  private $hash;

  /**
   * @var string|null
   */
  private $body;

  /**
   * @return string|null
   */
  public function getBody(): ?string {
    return $this->body;
  }

  /**
   * @return array
   */
  public function getAttachments(): array {
    return $this->attachments;
  }

  private $attachments = [];

  public function getAction() : ?string {
    return $this->action;
  }

  /**
   * @return int|null
   */
  public function getQueueID(): ?int {
    return $this->queueID;
  }

  /**
   * @return int|null
   */
  public function getJobID(): ?int {
    return $this->jobID;
  }

  /**
   * @return string|null
   */
  public function getHash(): ?string {
    return $this->hash;
  }

  /**
   * @return ezcMailAddress
   */
  public function getFrom(): ezcMailAddress {
    return $this->mail->from;
  }

  /**
   * @return string
   */
  public function getSubject(): string {
    return (string) $this->mail->subject;
  }

  public function getDate(): string {
    return date('YmdHis', strtotime($this->mail->getHeader('Date')));
  }

  /**
   * Is this a verp email.
   *
   * If the regex didn't find a match then no.
   *
   * @return bool
   */
  public function isVerp(): bool {
    return (bool) $this->action;
  }

  /**
   * Is this a bounce email.
   *
   * At the moment we are only able to detect verp bounces but maybe in the future...
   *
   * @return bool
   */
  public function isBounce() : bool {
    return $this->getAction() === 'b';
  }

  /**
   * @param \ezcMail $mail
   * @param string $emailDomain
   * @param string $emailLocalPart
   *
   * @throws \ezcBasePropertyNotFoundException
   * @throws \CRM_Core_Exception
   */
  public function __construct(ezcMail $mail, string $emailDomain, string $emailLocalPart) {
    // Sometimes $mail->from is unset because ezcMail didn't handle format
    // of From header. CRM-19215 (https://issues.civicrm.org/jira/browse/CRM-19215).
    if (!isset($mail->from)) {
      if (preg_match('/^([^ ]*)( (.*))?$/', $mail->getHeader('from'), $matches)) {
        $mail->from = new ezcMailAddress($matches[1], trim($matches[2]));
      }
    }
    $this->mail = $mail;
    $this->body = CRM_Utils_Mail_Incoming::formatMailPart($mail->body, $this->attachments);

    $verpSeparator = preg_quote(\Civi::settings()->get('verpSeparator') ?: '');
    $emailDomain = preg_quote($emailDomain);
    $emailLocalPart = preg_quote($emailLocalPart);
    $twoDigitStringMin = $verpSeparator . '(\d+)' . $verpSeparator . '(\d+)';
    $twoDigitString = $twoDigitStringMin . $verpSeparator;

    // a common-for-all-actions regex to handle CiviCRM 2.2 address patterns
    $regex = '/^' . $emailLocalPart . '(b|c|e|o|r|u)' . $twoDigitString . '([0-9a-z]{16})@' . $emailDomain . '$/';

    // a tighter regex for finding bounce info in soft bounces’ mail bodies
    $rpRegex = '/Return-Path:\s*' . $emailLocalPart . '(b)' . $twoDigitString . '([0-9a-z]{16})@' . $emailDomain . '/';

    // a regex for finding bound info X-Header
    $rpXHeaderRegex = '/X-CiviMail-Bounce: ' . $emailLocalPart . '(b)' . $twoDigitString . '([0-9a-z]{16})@' . $emailDomain . '/i';
    // CiviMail in regex and Civimail in header !!!
    $matches = NULL;
    foreach ($this->mail->to as $address) {
      if (preg_match($regex, ($address->email ?? ''), $matches)) {
        [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
        break;
      }
    }

    // CRM-5471: if $matches is empty, it still might be a soft bounce sent
    // to another address, so scan the body for ‘Return-Path: …bounce-pattern…’
    if (!$matches && preg_match($rpRegex, ($mail->generateBody() ?? ''), $matches)) {
      [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
    }

    // if $matches is still empty, look for the X-CiviMail-Bounce header
    // CRM-9855
    if (!$matches && preg_match($rpXHeaderRegex, ($mail->generateBody() ?? ''), $matches)) {
      [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
    }
    // With Mandrill, the X-CiviMail-Bounce header is produced by generateBody
    // is base64 encoded
    // Check all parts
    if (!$matches) {
      $all_parts = $mail->fetchParts();
      foreach ($all_parts as $v_part) {
        if ($v_part instanceof ezcMailFile) {
          $p_file = $v_part->__get('fileName');
          $c_file = file_get_contents($p_file);
          if (preg_match($rpXHeaderRegex, ($c_file ?? ''), $matches)) {
            [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
          }
        }
      }
    }

    // if all else fails, check Delivered-To for possible pattern
    if (!$matches && preg_match($regex, ($mail->getHeader('Delivered-To') ?? ''), $matches)) {
      [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
    }
    if ($this->isVerp()) {
      $queue = CRM_Mailing_Event_BAO_MailingEventQueue::verify(NULL, $this->getQueueID(), $this->getHash());
      if (!$queue) {
        throw new CRM_Core_Exception('Contact could not be found from civimail response');
      }
      $this->define('Queue', 'Queue', [
        'id' => $queue->id,
        'hash' => $queue->hash,
        'contact_id' => $queue->contact_id,
        'job_id' => $queue->job_id,
      ]);
      $this->define('Mailing', 'Mailing', [
        'id' => MailingJob::get(FALSE)
          ->addWhere('id', '=', $this->getJobID())
          ->addSelect('mailing_id')
          ->execute()
          ->first()['mailing_id'],
      ]);
    }

  }

}
