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
namespace Civi\FlexMailer\Listener;

use Civi\Test\Invasive;

/**
 * @group headless
 */
class DefaultSenderTest extends \CiviUnitTestCase {

  public function setUp(): void {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(['org.civicrm.flexmailer']);
    }

    parent::setUp();
  }

  public static function getTemporaryErrorExamples(): array {
    $smtpHelp = 'Invalid response code received from SMTP server while sending email. This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP).';
    return [
      'sender timeout' => [TRUE, FALSE, "Failed to set sender: test@example.org [SMTP: $smtpHelp (code: 421, response: Timeout waiting for data from client.)]"],
      'throttled' => [TRUE, FALSE, "Failed to send data [SMTP: $smtpHelp (code: 454, response: Throttling failure: Maximum sending rate exceeded.)]"],
      'socket write' => [TRUE, FALSE, 'Failed to set sender: test@example.org [SMTP: Failed to write to socket: not connected (code: -1, response: )]'],
      // @fixme: This also seems to be temporary, but is not yet handled as temporary.
      'connect timeout' => [FALSE, FALSE, 'Failed to connect to email.example.com:587 [SMTP: Failed to connect socket: Connection timed out (code: -1, response: )]'],
      '5xx' => [FALSE, FALSE, "Failed to send data [SMTP: $smtpHelp (code: 554, response: Message rejected: Sending suspended for this account.)]"],
      'auth failure' => [FALSE, FALSE, "authentication failure [SMTP: $smtpHelp (code: 454, response: Temporary authentication failure)]"],
      '450 domain not found' => [TRUE, FALSE, "Failed to add recipient: test@example.invalid [SMTP: $smtpHelp (code: 450, response: 4.1.2 <test@example.invalid>: Recipient address rejected: Domain not found)]"],
      '450 domain not found, permanent' => [FALSE, TRUE, "Failed to add recipient: test@example.invalid [SMTP: $smtpHelp (code: 450, response: 4.1.2 <test@example.invalid>: Recipient address rejected: Domain not found)]"],
      '450 mailbox busy, permanent' => [TRUE, TRUE, "Failed to add recipient: test@example.org [SMTP: $smtpHelp (code: 450, response: 4.2.1 Mailbox busy)]"],
    ];
  }

  /**
   * @dataProvider getTemporaryErrorExamples
   */
  public function testIsTemporaryError(bool $expected, bool $smtp450IsPermanent, string $message): void {
    \Civi::settings()->set('smtp_450_is_permanent', $smtp450IsPermanent);
    $this->assertSame($expected, Invasive::call([new DefaultSender(), 'isTemporaryError'], [$message]));
  }

}
