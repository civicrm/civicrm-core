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
  use \Civi\Test\Api4TestTrait;

  protected function tearDown(): void {
    $this->quickCleanup(['civicrm_mailing', 'civicrm_activity', 'civicrm_activity_contact']);
    Civi::settings()->revert('write_activity_record');
    Civi::settings()->revert('civimail_multiple_bulk_emails');
    parent::tearDown();
  }

  /**
   * Tests writeToDB() records each target once on the Bulk Email activity.
   *
   * @dataProvider getBooleanDataProvider
   */
  public function testWriteToDBActivityTargets(bool $isMultipleBulkMail): void {
    Civi::settings()->set('write_activity_record', TRUE);
    Civi::settings()->set('civimail_multiple_bulk_emails', $isMultipleBulkMail);

    $contactIDs = [];
    foreach ([1, 2, 3] as $i) {
      $contactIDs[] = $this->createTestEntity('Contact', ['first_name' => 'Target' . $i], 'target' . $i)['id'];
    }
    $mailingID = $this->createTestEntity('Mailing', [
      'subject' => 'Test Subject',
      'scheduled_id' => $contactIDs[0],
    ])['id'];

    $job = new CRM_Mailing_BAO_MailingJob();
    $job->mailing_id = $mailingID;
    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $mailingID;
    $mailing->find(TRUE);

    $deliveredParams = [];
    $targetParams = array_slice($contactIDs, 0, 2);
    $this->assertTrue($job->writeToDB($deliveredParams, $targetParams, $mailing, date('YmdHis')));
    $this->assertEquals([], $targetParams);

    // The second batch repeats a target already recorded by the first.
    $targetParams = array_slice($contactIDs, 1, 2);
    $this->assertTrue($job->writeToDB($deliveredParams, $targetParams, $mailing, date('YmdHis')));

    $activity = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('source_record_id', '=', $mailingID)
      ->addWhere('activity_type_id:name', '=', 'Bulk Email')
      ->addSelect('id')
      ->execute()->single();
    $targets = \Civi\Api4\ActivityContact::get(FALSE)
      ->addWhere('activity_id', '=', $activity['id'])
      ->addWhere('record_type_id:name', '=', 'Activity Targets')
      ->addSelect('contact_id')
      ->execute()->column('contact_id');
    sort($targets);
    $this->assertEquals($contactIDs, $targets);
  }

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

  /**
   * Tests CRM_Mailing_BAO_MailingJob::queueMissingRecipients() stopgap.
   */
  public function testQueueMissingRecipients(): void {
    $contact = $this->createTestRecord('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Reconcile',
      'last_name' => 'TestUser',
    ]);
    $contactID = $contact['id'];

    $email = $this->createTestRecord('Email', [
      'contact_id' => $contactID,
      'email' => 'reconcile_test@example.org',
      'is_primary' => 1,
    ]);
    $emailID = $email['id'];

    // Create a dummy mailing
    $mailing = $this->createTestRecord('Mailing', [
      'name' => 'Reconciliation Test Mailing',
      'subject' => 'Test Subject',
      'body_text' => 'Test Body',
      'status' => 'Running',
    ]);
    $mailingID = $mailing['id'];

    // Create parent job
    $parentJob = $this->createTestRecord('MailingJob', [
      'mailing_id' => $mailingID,
      'status' => 'Running',
      'is_test' => 0,
      'job_type' => NULL,
    ]);
    $parentJobID = $parentJob['id'];

    // Create completed child job
    $childJob = $this->createTestRecord('MailingJob', [
      'mailing_id' => $mailingID,
      'parent_id' => $parentJobID,
      'status' => 'Complete',
      'is_test' => 0,
      'job_type' => 'child',
      'job_offset' => 0,
      'job_limit' => 10,
    ]);

    // Insert a recipient record into civicrm_mailing_recipients without queueing in civicrm_mailing_event_queue
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_mailing_recipients (mailing_id, contact_id, email_id)
      VALUES (%1, %2, %3)
    ", [
      1 => [$mailingID, 'Integer'],
      2 => [$contactID, 'Integer'],
      3 => [$emailID, 'Integer'],
    ]);

    // Initial check: recipient is not queued
    $reconciled = Invasive::call(['CRM_Mailing_BAO_MailingJob', 'queueMissingRecipients'], [$mailingID, $parentJobID]);
    $this->assertTrue($reconciled, 'queueMissingRecipients should return true when unqueued recipients are found');

    // Verify a new child job was created
    $childJobs = \Civi\Api4\MailingJob::get(FALSE)
      ->addWhere('parent_id', '=', $parentJobID)
      ->addWhere('job_type', '=', 'child')
      ->execute();
    $this->assertCount(2, $childJobs, 'A new child job should be created for unqueued recipients');

    // Get the new child job ID
    $newJob = NULL;
    foreach ($childJobs as $job) {
      if ($job['id'] !== $childJob['id']) {
        $newJob = $job;
        break;
      }
    }
    $this->assertNotNull($newJob, 'New child job found');
    $this->assertEquals('Scheduled', $newJob['status']);

    // Verify recipient was added to civicrm_mailing_event_queue for the new job
    $queuedCount = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*)
      FROM civicrm_mailing_event_queue
      WHERE job_id = %1 AND email_id = %2 AND contact_id = %3
    ", [
      1 => [$newJob['id'], 'Integer'],
      2 => [$emailID, 'Integer'],
      3 => [$contactID, 'Integer'],
    ]);
    $this->assertEquals(1, $queuedCount, 'Unqueued recipient should now be present in event queue');

    // Second check: no more unqueued recipients
    $reconciledAgain = Invasive::call(['CRM_Mailing_BAO_MailingJob', 'queueMissingRecipients'], [$mailingID, $parentJobID]);
    $this->assertFalse($reconciledAgain, 'queueMissingRecipients should return false when all recipients are queued');
  }

}
