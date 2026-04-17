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

use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Wraps a Symfony transport behind the send($recipients, $headers, $body)
 * interface that CiviCRM's FilteredPearMailer exposes.
 *
 * This allows CRM_Utils_Mail::send() and other callers of the pear_mail
 * service to send via Symfony without any code changes.
 */
class SymfonyMailerAdapter {

  private TransportInterface $transport;
  private string $driver;

  /**
   * Exposed for CRM_Utils_Mail::pickDefaultEol() compatibility.
   * Symfony uses RFC 5322 \r\n natively.
   */
  public string $sep = "\r\n";

  public function __construct(TransportInterface $transport, string $driver) {
    $this->transport = $transport;
    $this->driver = $driver;
  }

  /**
   * Send an email, matching the FilteredPearMailer::send() signature.
   *
   * @param string|array $recipients
   * @param array $headers
   * @param string $body
   * @param array $originalValues
   *   Structured data from CRM_Utils_Mail::send() (html, text, attachments, bcc).
   * @return true
   * @throws \Exception
   */
  public function send($recipients, $headers, $body, array $originalValues = []) {
    if ($this->logIfNeeded($recipients, $headers, $body)) {
      return TRUE;
    }

    try {
      if (!empty($originalValues['html']) || !empty($originalValues['text'])) {
        $email = MailParamsConverter::fromOriginalValues($headers, $originalValues);
      }
      else {
        // Degraded path: raw MIME body from Mail_mime (when FlexSender is not active).
        $rawMessage = '';
        foreach ($headers as $key => $value) {
          $rawMessage .= "$key: $value\r\n";
        }
        $rawMessage .= "\r\n" . $body;
        $email = new \Symfony\Component\Mime\RawMessage($rawMessage);
      }
      $this->transport->send($email);
    }
    catch (TransportExceptionInterface $e) {
      throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
    }

    return TRUE;
  }

  public function disconnect(): bool {
    if ($this->transport instanceof EsmtpTransport) {
      $this->transport->stop();
    }
    return TRUE;
  }

  public function getDriver(): string {
    return $this->driver;
  }

  public function getTransport(): TransportInterface {
    return $this->transport;
  }

  private function logIfNeeded($recipients, $headers, $body): bool {
    if (!defined('CIVICRM_MAIL_LOG')) {
      return FALSE;
    }
    \CRM_Utils_Mail_Logger::filter($this, $recipients, $headers, $body);
    return !defined('CIVICRM_MAIL_LOG_AND_SEND');
  }

}
