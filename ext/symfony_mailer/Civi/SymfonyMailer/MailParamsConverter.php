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

namespace Civi\SymfonyMailer;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailParamsConverter {

  /**
   * Keys consumed from $mailParams that are not email headers.
   */
  private const NON_HEADER_KEYS = [
    'toName', 'toEmail', 'text', 'html', 'attachments', 'headers',
    'job_id', 'abortMailSend',
  ];

  /**
   * Build a Symfony Email from FlexMailer's $mailParams array.
   *
   * Used by FlexSender to bypass Mail_mime entirely.
   */
  public static function fromMailParams(array $params): Email {
    $email = new Email();

    $toName = trim($params['toName'] ?? '');
    $toEmail = trim($params['toEmail'] ?? '');
    if ($toName && $toName !== $toEmail && !str_contains($toName, '@')) {
      $email->to(new Address($toEmail, $toName));
    }
    else {
      $email->to($toEmail);
    }

    if (!empty($params['From'])) {
      $email->from(Address::create($params['From']));
    }
    if (!empty($params['Subject'])) {
      $email->subject($params['Subject']);
    }
    if (!empty($params['text'])) {
      $email->text($params['text']);
    }
    if (!empty($params['html'])) {
      $email->html($params['html']);
    }
    if (!empty($params['Reply-To'])) {
      $email->replyTo(Address::create($params['Reply-To']));
    }
    if (!empty($params['Return-Path'])) {
      $email->returnPath(Address::create($params['Return-Path']));
    }
    if (!empty($params['Cc'])) {
      foreach (self::parseAddressList($params['Cc']) as $addr) {
        $email->addCc($addr);
      }
    }
    if (!empty($params['Bcc'])) {
      foreach (self::parseAddressList($params['Bcc']) as $addr) {
        $email->addBcc($addr);
      }
    }

    if (!empty($params['attachments'])) {
      foreach ($params['attachments'] as $attach) {
        $email->attachFromPath(
          $attach['fullPath'],
          $attach['cleanName'] ?? NULL,
          $attach['mime_type'] ?? NULL
        );
      }
    }

    if (!empty($params['Message-ID'])) {
      $msgId = trim($params['Message-ID'], '<>');
      $email->getHeaders()->remove('Message-ID');
      $email->getHeaders()->addIdHeader('Message-ID', $msgId);
    }

    // Apply remaining string values as custom headers.
    $skip = array_flip(self::NON_HEADER_KEYS);
    $standardHeaders = ['From', 'Subject', 'Reply-To', 'Return-Path', 'Cc', 'Bcc', 'To', 'Message-ID'];
    $skip += array_flip($standardHeaders);
    foreach ($params as $key => $value) {
      if (isset($skip[$key]) || $value === NULL || $value === '') {
        continue;
      }
      if (is_string($value)) {
        $email->getHeaders()->addTextHeader($key, $value);
      }
    }

    return $email;
  }

  /**
   * Build a Symfony Email from the $originalValues passed by CRM_Utils_Mail::send().
   *
   * This is the single-email path (non-FlexMailer).
   */
  public static function fromOriginalValues(array $headers, array $originalValues): Email {
    $email = new Email();

    if (!empty($headers['To'])) {
      $email->to(Address::create($headers['To']));
    }
    if (!empty($headers['From'])) {
      $email->from(Address::create($headers['From']));
    }
    if (!empty($headers['Subject'])) {
      $email->subject($headers['Subject']);
    }
    if (!empty($originalValues['text'])) {
      $email->text($originalValues['text']);
    }
    if (!empty($originalValues['html'])) {
      $email->html($originalValues['html']);
    }
    if (!empty($headers['Reply-To'])) {
      $email->replyTo(Address::create($headers['Reply-To']));
    }
    if (!empty($headers['Return-Path'])) {
      $email->returnPath(Address::create($headers['Return-Path']));
    }
    if (!empty($headers['Return-Path'])) {
      $email->returnPath(Address::create($headers['Return-Path']));
    }
    if (!empty($headers['Cc'])) {
      foreach (self::parseAddressList($headers['Cc']) as $addr) {
        $email->addCc($addr);
      }
    }
    if (!empty($originalValues['bcc'])) {
      foreach (self::parseAddressList($originalValues['bcc']) as $addr) {
        $email->addBcc($addr);
      }
    }

    if (!empty($originalValues['attachments'])) {
      foreach ($originalValues['attachments'] as $attach) {
        $email->attachFromPath(
          $attach['fullPath'],
          $attach['cleanName'] ?? NULL,
          $attach['mime_type'] ?? NULL
        );
      }
    }

    if (!empty($headers['Message-ID'])) {
      $msgId = trim($headers['Message-ID'], '<>');
      $email->getHeaders()->remove('Message-ID');
      $email->getHeaders()->addIdHeader('Message-ID', $msgId);
    }

    // Pass through remaining headers.
    $skip = ['To', 'From', 'Subject', 'Reply-To', 'Return-Path', 'Cc', 'Bcc',
      'Content-Type', 'Content-Disposition', 'Content-Transfer-Encoding', 'Message-ID'];
    $skip = array_flip($skip);
    foreach ($headers as $key => $value) {
      if (isset($skip[$key]) || $value === NULL || $value === '') {
        continue;
      }
      if (is_string($value)) {
        $email->getHeaders()->addTextHeader($key, $value);
      }
    }

    return $email;
  }

  /**
   * @return \Symfony\Component\Mime\Address[]
   */
  private static function parseAddressList(string $list): array {
    return array_map(
      fn(string $addr) => Address::create(trim($addr)),
      explode(',', $list)
    );
  }

}
