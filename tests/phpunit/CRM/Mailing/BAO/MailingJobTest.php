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
 * Class CRM_Mailing_BAO_MailingTest
 * @group headless
 */
class CRM_Mailing_BAO_MailingJobTest extends CiviUnitTestCase {

  /**
   * Calls a protected method.
   */
  public static function callMethod($obj, $name, $args) {
    $class = new ReflectionClass($obj);
    $method = $class->getMethod($name);
    $method->setAccessible(TRUE);
    return $method->invokeArgs($obj, $args);
  }

  /**
   * Tests CRM_Mailing_BAO_MailingJob::isTemporaryError() method.
   */
  public function testIsTemporaryError() {
    $testcases[] = ['return' => TRUE, 'message' => 'Failed to set sender: test@example.org [SMTP: Invalid response code received from SMTP server while sending email. This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 421, response: Timeout waiting for data from client.)]'];
    $testcases[] = ['return' => TRUE, 'message' => 'Failed to send data [SMTP: Invalid response code received from SMTP server while sending email. This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 454, response: Throttling failure: Maximum sending rate exceeded.)]'];
    $testcases[] = ['return' => TRUE, 'message' => 'Failed to set sender: test@example.org [SMTP: Failed to write to socket: not connected (code: -1, response: )]'];
    // @fixme: These errors also seem to be temporary, but are not yet handled as temporary.
    $testcases[] = ['return' => FALSE, 'message' => 'Failed to connect to email.example.com:587 [SMTP: Failed to connect socket: Connection timed out (code: -1, response: )]'];
    $testcases[] = ['return' => FALSE, 'message' => 'Failed to send data [SMTP: Invalid response code received from SMTP server while sending email. This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 554, response: Message rejected: Sending suspended for this account. For more information, please check the inbox of the email address associated with your AWS account.)]'];
    $testcases[] = ['return' => FALSE, 'message' => 'authentication failure [SMTP: Invalid response code received from SMTP server while sending email.  This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 454, response: Temporary authentication failure)]'];
    $object = new CRM_Mailing_BAO_MailingJob();
    foreach ($testcases as $testcase) {
      $isTemporaryError = self::callMethod($object, 'isTemporaryError', [$testcase['message']]);
      if ($testcase['return']) {
        $this->assertTrue($isTemporaryError);
      }
      else {
        $this->assertFalse($isTemporaryError);
      }
    }
  }

}
