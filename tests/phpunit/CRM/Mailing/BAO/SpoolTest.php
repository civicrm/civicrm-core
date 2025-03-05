<?php

/*
 * @see also WebTest_Mailing_SpoolTest
 */

/**
 * Class CRM_Mailing_BAO_SpoolTest
 * @group headless
 */
class CRM_Mailing_BAO_SpoolTest extends CiviUnitTestCase {

  protected static $bodytext = 'Unit tests keep children safe.';

  /**
   * Basic send.
   *
   * @throws \Random\RandomException
   */
  public function testSend(): void {
    $contact_params_1 = [
      'first_name' => bin2hex(random_bytes(4)),
      'last_name' => 'Anderson',
      'email' => bin2hex(random_bytes(4)) . '@example.org',
      'contact_type' => 'Individual',
    ];
    $this->individualCreate($contact_params_1);

    $contact_params_2 = [
      'first_name' => bin2hex(random_bytes(4)),
      'last_name' => 'Xylophone',
      'email' => bin2hex(random_bytes(4)) . '@example.org',
      'contact_type' => 'Individual',
    ];
    $this->individualCreate($contact_params_2);

    $subject = 'Test spool';
    $params = [
      'from' => CRM_Utils_Mail::formatRFC822Email($contact_params_1['first_name'] . " " . $contact_params_1['last_name'], $contact_params_1['email']),
      'toName' => $contact_params_2['first_name'] . " " . $contact_params_2['last_name'],
      'toEmail' => $contact_params_2['email'],
      'subject' => $subject,
      'text' => self::$bodytext,
      'html' => "<p>\n" . self::$bodytext . '</p>',
    ];
    $mailUtil = new CiviMailUtils($this, TRUE);
    CRM_Utils_Mail::send($params);

    $mail = $mailUtil->getMostRecentEmail('raw');
    $this->assertStringContainsString("Subject: $subject", $mail);
    $this->assertStringContainsString(self::$bodytext, $mail);

    $mail = $mailUtil->getMostRecentEmail('ezc');

    $this->assertEquals($subject, $mail->subject);
    $this->assertStringContainsString($contact_params_1['email'], $mail->from->email, 'From address incorrect.');
    $this->assertStringContainsString($contact_params_2['email'], $mail->to[0]->email, 'Recipient incorrect.');

    $context = new ezcMailPartWalkContext([get_class($this), 'mailWalkCallback']);
    $mail->walkParts($context, $mail);
  }

  /**
   * @param $context
   * @param $mailPart
   */
  public static function mailWalkCallback($context, $mailPart) {
    if ($mailPart instanceof ezcMailText) {
      switch ($mailPart->subType) {
        case 'plain':
          self::assertStringContainsString(self::$bodytext, $mailPart->generateBody());
          break;

        case 'html':
          self::assertStringContainsString(self::$bodytext . '</p>', $mailPart->generateBody());
          break;
      }
    }
  }

}
