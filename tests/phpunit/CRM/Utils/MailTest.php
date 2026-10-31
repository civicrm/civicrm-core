<?php

/**
 * Class CRM_Utils_MailTest
 *
 * @group headless
 */
class CRM_Utils_MailTest extends CiviUnitTestCase {
  public $_apiversion = 4;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * test with empty params.
   */
  public function testFormatRFC822(): void {

    $values = [
      [
        'name' => 'Test User',
        'email' => 'foo@bar.com',
        'result' => 'Test User <foo@bar.com>',
      ],
      [
        'name' => '"Test User"',
        'email' => 'foo@bar.com',
        'result' => 'Test User <foo@bar.com>',
      ],
      [
        'name' => 'User, Test',
        'email' => 'foo@bar.com',
        'result' => '"User, Test" <foo@bar.com>',
      ],
      [
        'name' => '"User, Test"',
        'email' => 'foo@bar.com',
        'result' => '"User, Test" <foo@bar.com>',
      ],
      [
        'name' => '"Test User"',
        'email' => 'foo@bar.com',
        'result' => '"Test User" <foo@bar.com>',
        'useQuote' => TRUE,
      ],
      [
        'name' => 'User, Test',
        'email' => 'foo@bar.com',
        'result' => '"User, Test" <foo@bar.com>',
        'useQuote' => TRUE,
      ],
      [
        'name' => '',
        'email' => 'foo@bar.com',
        'result' => '<foo@bar.com>',
      ],
      [
        'name' => NULL,
        'email' => 'foo@bar.com',
        'result' => '<foo@bar.com>',
      ],
      [
        'name' => '',
        'email' => 'foo@bar.com, baz@bar.com',
        'result' => '<foo@bar.com>, <baz@bar.com>',
      ],
      [
        'name' => NULL,
        'email' => 'foo@bar.com, baz@bar.com',
        'result' => '<foo@bar.com>, <baz@bar.com>',
      ],
      [
        'name' => '',
        'email' => 'foo@bar.com; baz@bar.com',
        'result' => '<foo@bar.com>, <baz@bar.com>',
      ],
    ];
    foreach ($values as $value) {
      $result = CRM_Utils_Mail::formatRFC822Email($value['name'],
        $value['email'],
        $value['useQuote'] ?? FALSE
      );
      $this->assertEquals($value['result'], $result, 'Expected encoding does not match');
    }
  }

  public function testSetEmailHeadersMultipleRecipients(): void {
    $params = [
      'toEmail' => 'foo@bar.com, baz@bar.com',
      'from' => 'admin@example.com',
      'subject' => 'Test',
      'text' => 'Hello',
    ];
    [$headers, $message] = CRM_Utils_Mail::setEmailHeaders($params);
    $this->assertEquals('<foo@bar.com>, <baz@bar.com>', $headers['To']);
  }

  /**
   * Test exception handling in mail function.
   */
  public function testMailException(): void {
    $params = [
      'toEmail' => 'a@example.com',
      'from' => 'b@example.com',
    ];
    Civi::settings()->set('mailing_backend', [
      'outBound_option' => CRM_Mailing_Config::OUTBOUND_OPTION_MOCK,
      'preSendCallback' => ['CRM_Utils_MailTest', 'mailerError'],
    ]);

    $this->assertFalse(CRM_Utils_Mail::send($params));
    $this->assertEquals('Unable to send email. Please report this message to the site administrator', CRM_Core_Session::singleton()->getStatus()[0]['text']);
  }

  public function testEmptyText(): void {
    $mailHelper = new CiviMailUtils($this);
    $params = [
      'toEmail' => 'a@example.com',
      'from' => 'b@example.com',
      'html' => '<p>hi</p><p>How are <b>you</b></p>',
      'text' => " \n\t",
      'subject' => 'hey',
    ];
    CRM_Utils_Mail::send($params);
    $mailHelper->checkAllMailLog(['How are you']);
  }

  /**
   * CRM-1367: every mailer built by CRM_Utils_Mail::_createMailer() should
   * backfill a Date header on any message that doesn't already have one,
   * rather than relying on a vendor patch to pear/mail.
   */
  public function testCreateMailerAddsDateHeaderIfMissing(): void {
    $mut = new CiviMailUtils($this, TRUE);

    \Civi::service('pear_mail')->send('test@example.com', [
      'To' => 'test@example.com',
      'Subject' => 'No date here',
    ], 'body text');

    $raw = $mut->getMostRecentEmail('raw');
    $this->assertMatchesRegularExpression('/^Date: .+$/m', $raw);
  }

  /**
   * The Date backfill should never override a Date header the caller
   * already set.
   */
  public function testCreateMailerDoesNotOverrideExistingDateHeader(): void {
    $mut = new CiviMailUtils($this, TRUE);

    \Civi::service('pear_mail')->send('test@example.com', [
      'To' => 'test@example.com',
      'Subject' => 'Already dated',
      'Date' => 'Wed, 01 Jan 2020 00:00:00 +0000',
    ], 'body text');

    $raw = $mut->getMostRecentEmail('raw');
    $this->assertStringContainsString('Date: Wed, 01 Jan 2020 00:00:00 +0000', $raw);
  }

  /**
   * CRM-5946: for the 'mail' driver, CRM_Utils_Mail::_createMailer() should
   * set the sendmail envelope sender (-f) from the message's Return-Path,
   * rather than relying on the pear/mail patch.
   */
  public function testCreateMailerSetsEnvelopeSenderFromReturnPath(): void {
    $mailer = CRM_Utils_Mail::_createMailer('mail', []);
    $mockDelegate = $this->swapMailerDelegate($mailer);

    $mailer->send('to@example.com', [
      'To' => 'to@example.com',
      'From' => 'Bob <bob@example.com>',
      'Return-Path' => 'bounce@example.com',
    ], 'body text');

    $this->assertEquals('-fbounce@example.com', $mockDelegate->_params);
  }

  /**
   * Without a Return-Path, the envelope sender should fall back to the
   * email address in the From header.
   */
  public function testCreateMailerSetsEnvelopeSenderFromFromWhenNoReturnPath(): void {
    $mailer = CRM_Utils_Mail::_createMailer('mail', []);
    $mockDelegate = $this->swapMailerDelegate($mailer);

    $mailer->send('to@example.com', [
      'To' => 'to@example.com',
      'From' => 'Bob <bob@example.com>',
    ], 'body text');

    $this->assertEquals('-fbob@example.com', $mockDelegate->_params);
  }

  /**
   * CRM-8744: for the 'smtp' driver, CRM_Utils_Mail::_createMailer() should
   * rewrite Net_SMTP's generic 'Invalid response code received from server'
   * error into something that points admins at Outbound Email settings,
   * rather than relying on the pear/net_smtp patch.
   */
  public function testCreateMailerRewritesGenericSmtpResponseError(): void {
    $mailer = CRM_Utils_Mail::_createMailer('smtp', []);
    $this->swapMailerDelegate($mailer, PEAR::raiseError('Invalid response code received from server', 550, PEAR_ERROR_RETURN));
    $this->clearPreSendFilters($mailer);

    $result = $mailer->send('to@example.com', ['To' => 'to@example.com'], 'body text');

    $this->assertTrue(is_a($result, 'PEAR_Error'));
    $this->assertStringContainsString('Outbound Email', $result->getMessage());
    $this->assertEquals(550, $result->getCode());
  }

  /**
   * The rewrite is specific to that one message; other smtp errors should
   * be passed through unaltered.
   */
  public function testCreateMailerLeavesOtherSmtpErrorsAlone(): void {
    $mailer = CRM_Utils_Mail::_createMailer('smtp', []);
    $this->swapMailerDelegate($mailer, PEAR::raiseError('Failed to set sender', NULL, PEAR_ERROR_RETURN));
    $this->clearPreSendFilters($mailer);

    $result = $mailer->send('to@example.com', ['To' => 'to@example.com'], 'body text');

    $this->assertTrue(is_a($result, 'PEAR_Error'));
    $this->assertEquals('Failed to set sender', $result->getMessage());
  }

  /**
   * Replace a CRM_Utils_Mail_FilteredPearMailer's real delegate with a mock
   * that returns $sendReturns instead of actually sending anything.
   *
   * @return object
   *   The mock delegate, so its '_params' can be asserted on afterwards.
   */
  private function swapMailerDelegate(CRM_Utils_Mail_FilteredPearMailer $mailer, $sendReturns = TRUE): object {
    $mockDelegate = new class($sendReturns) extends Mail {
      public $_params = '';
      private $sendReturns;

      public function __construct($sendReturns) {
        $this->sendReturns = $sendReturns;
      }

      public function send($recipients, $headers, $body) {
        return $this->sendReturns;
      }

    };
    $reflection = new ReflectionProperty($mailer, '_delegate');
    $reflection->setValue($mailer, $mockDelegate);
    return $mockDelegate;
  }

  /**
   * Clear a CRM_Utils_Mail_FilteredPearMailer's pre-send filters.
   *
   * CRM_Utils_Mail_Logger::filter() (registered as '2000_log' on every
   * smtp/mail/sendmail mailer) short-circuits send() with TRUE whenever
   * CIVICRM_MAIL_LOG is defined, as it is on standard test sites - before
   * the mock delegate or any result-filter runs. Tests that only care
   * about result-filter behaviour need this cleared first.
   */
  private function clearPreSendFilters(CRM_Utils_Mail_FilteredPearMailer $mailer): void {
    (new ReflectionProperty($mailer, '_filters'))->setValue($mailer, []);
  }

  /**
   * Mimic exception in mailer class.
   *
   * @throws \PEAR_Exception
   *
   * @param Mail $mailer
   */
  public static function mailerError(&$mailer): void {
    $mailer = PEAR::raiseError('You shall not pass');
  }

}
