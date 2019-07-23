<?php

/*
 * @see also WebTest_Mailing_SpoolTest
 */

/**
 * Class CRM_Mailing_BAO_SpoolTest
 * @group headless
 */
class CRM_Mailing_BAO_SpoolTest extends CiviUnitTestCase {

  protected $_mut = NULL;

  protected static $bodytext = 'Unit tests keep children safe.';

  public function setUp() {
    parent::setUp();
    $this->_mut = new CiviMailUtils($this, TRUE);
  }

  public function tearDown() {
    $this->_mut->stop();
    parent::tearDown();
  }

  /**
   * Basic send.
   */
  public function testSend() {
    $contact_params_1 = [
      'first_name' => substr(sha1(rand()), 0, 7),
      'last_name' => 'Anderson',
      'email' => substr(sha1(rand()), 0, 7) . '@example.org',
      'contact_type' => 'Individual',
    ];
    $contact_id_1 = $this->individualCreate($contact_params_1);

    $contact_params_2 = [
      'first_name' => substr(sha1(rand()), 0, 7),
      'last_name' => 'Xylophone',
      'email' => substr(sha1(rand()), 0, 7) . '@example.org',
      'contact_type' => 'Individual',
    ];
    $contact_id_2 = $this->individualCreate($contact_params_2);

    $subject = 'Test spool';
    $params = [
      'from' => CRM_Utils_Mail::formatRFC822Email($contact_params_1['first_name'] . " " . $contact_params_1['last_name'], $contact_params_1['email']),
      'toName' => $contact_params_2['first_name'] . " " . $contact_params_2['last_name'],
      'toEmail' => $contact_params_2['email'],
      'subject' => $subject,
      'text' => self::$bodytext,
      'html' => "<p>\n" . self::$bodytext . '</p>',
    ];

    CRM_Utils_Mail::send($params);

    $mail = $this->_mut->getMostRecentEmail('raw');
    $this->assertContains("Subject: $subject", $mail);
    $this->assertContains(self::$bodytext, $mail);

    $mail = $this->_mut->getMostRecentEmail('ezc');

    $this->assertEquals($subject, $mail->subject);
    $this->assertContains($contact_params_1['email'], $mail->from->email, 'From address incorrect.');
    $this->assertContains($contact_params_2['email'], $mail->to[0]->email, 'Recipient incorrect.');

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
          self::assertContains(self::$bodytext, $mailPart->generateBody());
          break;

        case 'html':
          self::assertContains(self::$bodytext . '</p>', $mailPart->generateBody());
          break;
      }
    }
  }

}
