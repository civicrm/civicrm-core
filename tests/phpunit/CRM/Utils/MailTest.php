<?php

/**
 * Class CRM_Utils_MailTest
 * @group headless
 */
class CRM_Utils_MailTest extends CiviUnitTestCase {

  /**
   * Test case for add( )
   * test with empty params.
   */
  public function testFormatRFC822(): void {

    $values = [
      [
        'name' => "Test User",
        'email' => "foo@bar.com",
        'result' => "Test User <foo@bar.com>",
      ],
      [
        'name' => '"Test User"',
        'email' => "foo@bar.com",
        'result' => "Test User <foo@bar.com>",
      ],
      [
        'name' => "User, Test",
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
      ],
      [
        'name' => '"User, Test"',
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
      ],
      [
        'name' => '"Test User"',
        'email' => "foo@bar.com",
        'result' => '"Test User" <foo@bar.com>',
        'useQuote' => TRUE,
      ],
      [
        'name' => "User, Test",
        'email' => "foo@bar.com",
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
