<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Config_MailerTest extends CiviUnitTestCase {

  /**
   * @var array (string=>int) Keep count of the #times different functions are called
   */
  var $calls;

  function setUp() {
    $this->calls = array(
      'civicrm_alterMailer' => 0,
      'send' => 0,
    );
    parent::setUp();
  }

  function testHookAlterMailer() {
    $test = $this;
    $mockMailer = new CRM_Utils_FakeObject(array(
      'send' => function ($recipients, $headers, $body) use ($test) {
        $test->calls['send']++;
        $test->assertEquals(array('to@example.org'), $recipients);
        $test->assertEquals('Subject Example', $headers['Subject']);
      }
    ));

    CRM_Utils_Hook::singleton()->setHook('civicrm_alterMailer',
      function (&$mailer, $driver, $params) use ($test, $mockMailer) {
        $test->calls['civicrm_alterMailer']++;
        $test->assertTrue(is_string($driver) && !empty($driver));
        $test->assertTrue(is_array($params));
        $test->assertTrue(is_callable(array($mailer, 'send')));
        $mailer = $mockMailer;
      }
    );

    $params = array();
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
