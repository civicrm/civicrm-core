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

/**
 *
 * @package CiviCRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id: $
 *
 */

/**
 * Class CRM_Core_Config_MailerTest
 * @group headless
 */
class CRM_Core_Config_MailerTest extends CiviUnitTestCase {

  /**
   * Keep count of the #times different functions are called
   * @var array
   * (string=>int)
   */
  public $calls;

  public function setUp() {
    $this->calls = [
      'civicrm_alterMailer' => 0,
      'send' => 0,
    ];
    parent::setUp();
  }

  public function testHookAlterMailer() {
    $test = $this;
    $mockMailer = new CRM_Utils_FakeObject([
      'send' => function ($recipients, $headers, $body) use ($test) {
        $test->calls['send']++;
        $test->assertEquals(['to@example.org'], $recipients);
        $test->assertEquals('Subject Example', $headers['Subject']);
      },
    ]);

    CRM_Utils_Hook::singleton()->setHook('civicrm_alterMailer',
    function (&$mailer, $driver, $params) use ($test, $mockMailer) {
      $test->calls['civicrm_alterMailer']++;
      $test->assertTrue(is_string($driver) && !empty($driver));
      $test->assertTrue(is_array($params));
      $test->assertTrue(is_callable([$mailer, 'send']));
      $mailer = $mockMailer;
    }
    );

    $params = [];
    $params['groupName'] = 'CRM_Core_Config_MailerTest';
    $params['from'] = 'From Example <from@example.com>';
    $params['toName'] = 'To Example';
    $params['toEmail'] = 'to@example.org';
    $params['subject'] = 'Subject Example';
    $params['text'] = 'Example text';
    $params['html'] = '<p>Example HTML</p>';
    CRM_Utils_Mail::send($params);

    $this->assertEquals(1, $this->calls['civicrm_alterMailer']);
    $this->assertEquals(1, $this->calls['send']);

    // once more, just to make sure the hooks are called right #times
    CRM_Utils_Mail::send($params);
    CRM_Utils_Mail::send($params);
    $this->assertEquals(1, $this->calls['civicrm_alterMailer']);
    $this->assertEquals(3, $this->calls['send']);
  }

}
