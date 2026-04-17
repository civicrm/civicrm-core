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

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;

class TransportFactory {

  /**
   * Build a Symfony transport from CiviCRM's mailing_backend settings.
   *
   * @return \Symfony\Component\Mailer\Transport\TransportInterface|null
   *   NULL when the outbound option is spool/mock (not a real transport).
   */
  public static function createFromSettings(): ?TransportInterface {
    $settings = \Civi::settings()->get('mailing_backend');
    $option = (int) $settings['outBound_option'];

    return match ($option) {
      \CRM_Mailing_Config::OUTBOUND_OPTION_SMTP => self::createSmtp($settings),
      \CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL => self::createSendmail($settings),
      \CRM_Mailing_Config::OUTBOUND_OPTION_MAIL => Transport::fromDsn('native://default'),
      default => NULL,
    };
  }

  private static function createSmtp(array $settings): EsmtpTransport {
    $host = $settings['smtpServer'] ?: 'localhost';
    $port = (int) ($settings['smtpPort'] ?: 25);
    $tls = ($port === 465);

    $transport = new EsmtpTransport($host, $port, $tls);
    $transport->setTimeout(30);

    if (!empty($settings['smtpAuth'])) {
      $transport->setUsername($settings['smtpUsername']);
      $password = \Civi::service('crypto.token')->decrypt($settings['smtpPassword']);
      $transport->setPassword($password);
    }

    $localhost = $_SERVER['SERVER_NAME']
      ?? parse_url(CIVICRM_UF_BASEURL)['host']
      ?? 'localhost';
    $transport->setLocalDomain($localhost);

    return $transport;
  }

  private static function createSendmail(array $settings): SendmailTransport {
    $command = ($settings['sendmail_path'] ?? '/usr/sbin/sendmail')
      . ' ' . ($settings['sendmail_args'] ?? '-bs');
    return new SendmailTransport($command);
  }

}
