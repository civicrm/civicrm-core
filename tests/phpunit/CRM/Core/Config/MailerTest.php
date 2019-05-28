<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CiviCRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id: $
 *
 */

/**
 * Class CRM_Core_Config_MailerTest
 * @group headless
 */
class CRM_Core_Config_MailerTest extends CiviUnitTestCase {

  /**
   * @var array (string=>int) Keep count of the #times different functions are called
   */
  public $calls;

  public function setUp() {
    $this->calls = array(
      'civicrm_alterMailer' => 0,
      'send' => 0,
    );
    parent::setUp();
  }

  public function testHookAlterMailer() {
    $test = $this;
    $mockMailer = new CRM_Utils_FakeObject(array(
      'send' => function ($recipients, $headers, $body) use ($test) {
        $test->calls['send']++;
        $test->assertEquals(array('to@example.org'), $recipients);
        $test->assertEquals('Subject Example', $headers['Subject']);
      },
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
