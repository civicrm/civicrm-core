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
   * Attachments.
   *
   * The files for these have been moved to the files directory.
   *
   * @var array
   */
  private $attachments;

  /**
   * Unprocessed attachments.
   *
   * @var array
   */
  private $attachmentsRaw = [];

  /**
   * @var string
   */
  private $body;

  /**
   * @return null|string
   */
  public function getVerpAction(): ?string {
    return [
      'b' => 'bounce',
      'c' => 'confirm',
      'u' => 'unsubscribe',
      'r' => 'reply',
      'o' => 'opt_out',
      'e' => 'resubscribe',
      's' => 'subscribe',
    ][$this->action] ?? NULL;
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
   * @var string
   */
  private $hash;

  public function getEmailSubject(): string {
    return (string) $this->mail->subject;
  }

  public function getDate() : string {
    return date('YmdHis', strtotime($this->mail->getHeader('Date')));
  }

  /**
   * @return string|null
   */
  public function getBody(): ?string {
    return $this->body;
  }

  /**
   * Return the attachments to meet current requirement in EmailProcessor class.
   *
   * @internal may change without notice.
   *
   * @return array
   */
  public function getAttachments(): array {
    $attachmentArray = [];
    // format and move attachments to the civicrm area
    if ($this->attachments === NULL) {
      $date = date('YmdHis');
      $this->attachments = [];
      $config = CRM_Core_Config::singleton();
      foreach ($this->attachmentsRaw as $i => $iValue) {
        $attachNum = $i + 1;
        $fileName = basename($iValue['fullName']);
        $newName = CRM_Utils_File::makeFileName($fileName);
        $location = $config->uploadDir . $newName;

        // move file to the civicrm upload directory
        rename($iValue['fullName'], $location);

        $mimeType = "{$iValue['contentType']}/{$iValue['mimeType']}";

        $attachmentArray[$attachNum] = [
          'uri' => $fileName,
          'type' => $mimeType,
          'upload_date' => $date,
          'location' => $location,
        ];
      }
    }
    return $attachmentArray;
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
    $this->mail = $mail;
    $this->body = CRM_Utils_Mail_Incoming::formatMailPart($this->mail->body, $this->attachmentsRaw);

    $verpSeparator = preg_quote(\Civi::settings()->get('verpSeparator') ?: '');
    $emailDomain = preg_quote($emailDomain);
    $emailLocalPart = preg_quote($emailLocalPart);
    $twoDigitStringMin = $verpSeparator . '(\d+)' . $verpSeparator . '(\d+)';
    $twoDigitString = $twoDigitStringMin . $verpSeparator;

    // a common-for-all-actions regex to handle CiviCRM 2.2 address patterns
    $regex = '/^' . $emailLocalPart . '(b|c|e|o|r|u)' . $twoDigitString . '([0-9a-f]{16})@' . $emailDomain . '$/';

    // a tighter regex for finding bounce info in soft bounces’ mail bodies
    $rpRegex = '/Return-Path:\s*' . $emailLocalPart . '(b)' . $twoDigitString . '([0-9a-f]{16})@' . $emailDomain . '/';

    // a regex for finding bound info X-Header
    $rpXHeaderRegex = '/X-CiviMail-Bounce: ' . $emailLocalPart . '(b)' . $twoDigitString . '([0-9a-f]{16})@' . $emailDomain . '/i';
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
      $queue = CRM_Mailing_Event_BAO_MailingEventQueue::verify($this->getJobID(), $this->getQueueID(), $this->getHash());
      if (!$queue) {
        throw new CRM_Core_Exception('Contact could not be found from civimail response');
      }
      $this->define('Queue', 'Queue', [
        'id' => $queue->id,
        'hash' => $queue->hash,
        'contact_id' => $queue->contact_id,
        'job_id' => $queue->contact_id,
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
