<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Test\Invasive;

/**
 * Class CRM_Mailing_BAO_MailingTest
 * @group headless
 */
class CRM_Mailing_BAO_MailingJobTest extends CiviUnitTestCase {

  /**
   * Tests CRM_Mailing_BAO_MailingJob::isTemporaryError() method.
   */
  public function testIsTemporaryError(): void {
    $testcases[] = ['return' => TRUE, 'message' => 'Failed to set sender: test@example.org [SMTP: Invalid response code received from SMTP server while sending email. This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 421, response: Timeout waiting for data from client.)]'];
    $testcases[] = ['return' => TRUE, 'message' => 'Failed to send data [SMTP: Invalid response code received from SMTP server while sending email. This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 454, response: Throttling failure: Maximum sending rate exceeded.)]'];
    $testcases[] = ['return' => TRUE, 'message' => 'Failed to set sender: test@example.org [SMTP: Failed to write to socket: not connected (code: -1, response: )]'];
    // @fixme: These errors also seem to be temporary, but are not yet handled as temporary.
    $testcases[] = ['return' => FALSE, 'message' => 'Failed to connect to email.example.com:587 [SMTP: Failed to connect socket: Connection timed out (code: -1, response: )]'];
    $testcases[] = ['return' => FALSE, 'message' => 'Failed to send data [SMTP: Invalid response code received from SMTP server while sending email. This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 554, response: Message rejected: Sending suspended for this account. For more information, please check the inbox of the email address associated with your AWS account.)]'];
    $testcases[] = ['return' => FALSE, 'message' => 'authentication failure [SMTP: Invalid response code received from SMTP server while sending email.  This is often caused by a misconfiguration in Outbound Email settings. Please verify the settings at Administer CiviCRM >> Global Settings >> Outbound Email (SMTP). (code: 454, response: Temporary authentication failure)]'];
    $object = new CRM_Mailing_BAO_MailingJob();
    foreach ($testcases as $testcase) {
      $isTemporaryError = Invasive::call([$object, 'isTemporaryError'], [$testcase['message']]);
      if ($testcase['return']) {
        $this->assertTrue($isTemporaryError);
      }
      else {
        $this->assertFalse($isTemporaryError);
      }
    }
  }

}
