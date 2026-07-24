<?php

/**
 * Class CRM_Utils_MailTest
 *
 * @group headless
 */
class CRM_Utils_MailTest extends CiviUnitTestCase {

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
    ];
    foreach ($values as $value) {
      $result = CRM_Utils_Mail::formatRFC822Email($value['name'],
        $value['email'],
        $value['useQuote'] ?? FALSE
      );
      $this->assertEquals($result, $value['result'], 'Expected encoding does not match');
    }
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
   * The default is unchanged, so existing installs keep sending 8bit.
   */
  public function testContentTransferEncodingDefaultsTo8bit(): void {
    $this->assertEquals('8bit', CRM_Utils_Mail::getContentTransferEncoding());
  }

  /**
   * An unrecognised value must not leak into the message headers.
   */
  public function testContentTransferEncodingFallsBackOnUnknownValue(): void {
    Civi::settings()->set('mail_content_transfer_encoding', 'base64');
    $this->assertEquals('8bit', CRM_Utils_Mail::getContentTransferEncoding());
  }

  /**
   * The setting must reach both the header and the MIME body encoding.
   *
   * @dataProvider contentTransferEncodings
   */
  public function testContentTransferEncodingIsApplied(string $encoding): void {
    Civi::settings()->set('mail_content_transfer_encoding', $encoding);
    $mailHelper = new CiviMailUtils($this);
    $params = [
      'toEmail' => 'a@example.com',
      'from' => 'b@example.com',
      'subject' => 'hey',
      'text' => 'Grüße aus München',
    ];
    CRM_Utils_Mail::send($params);

    $message = $mailHelper->getMostRecentEmail();
    $this->assertStringContainsString('Content-Transfer-Encoding: ' . $encoding, $message);
    // Whatever the wire encoding, the text must survive decoding intact.
    $this->assertStringContainsString('Grüße aus München', quoted_printable_decode($message));
  }

  public static function contentTransferEncodings(): array {
    return [['8bit'], ['quoted-printable']];
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
