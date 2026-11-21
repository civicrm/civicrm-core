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

use Civi\Api4\Mailing;
use Civi\Api4\MailingJob;
use Civi\Test\Invasive;

/**
 * Class CRM_Mailing_BAO_MailingTest
 * @group headless
 */
class CRM_Mailing_BAO_MailingJobTest extends CiviUnitTestCase {
  use \Civi\Test\Api4TestTrait;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
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
    $this->assertSame('Scheduled', $newJob['status']);

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
    $this->assertSame('1', $queuedCount, 'Unqueued recipient should now be present in event queue');

    // Second check: no more unqueued recipients
    $reconciledAgain = Invasive::call(['CRM_Mailing_BAO_MailingJob', 'queueMissingRecipients'], [$mailingID, $parentJobID]);
    $this->assertFalse($reconciledAgain, 'queueMissingRecipients should return false when all recipients are queued');
  }

  /**
   * Unscheduling a mailing whose delivery has not started makes it a draft and drops its non-test jobs.
   */
  public function testRevertScheduledMailing(): void {
    $mailingID = $this->createRevertTestMailing('Scheduled', ['status' => 'Scheduled']);
    CRM_Mailing_BAO_MailingJob::revert($mailingID);

    $mailing = Mailing::get(FALSE)->addWhere('id', '=', $mailingID)->execute()->single();
    $this->assertSame('Draft', $mailing['status']);
    $this->assertNull($mailing['scheduled_date']);
    $this->assertNull($mailing['scheduled_id']);
    $jobs = MailingJob::get(FALSE)->addWhere('mailing_id', '=', $mailingID)->addWhere('is_test', 'IN', [TRUE, FALSE])->execute();
    $this->assertCount(1, $jobs, 'Only the test job should remain');
    $this->assertTrue($jobs->first()['is_test']);
  }

  /**
   * Unscheduling is refused, and changes nothing, once delivery has started.
   *
   * @dataProvider startedMailingProvider
   */
  public function testRevertStartedMailingIsRefused(string $mailingStatus, array $parentJob, bool $withChildJob): void {
    $mailingID = $this->createRevertTestMailing($mailingStatus, $parentJob, $withChildJob);
    $mailingBefore = Mailing::get(FALSE)->addWhere('id', '=', $mailingID)->execute()->single();
    $jobsBefore = (array) MailingJob::get(FALSE)->addWhere('mailing_id', '=', $mailingID)->addWhere('is_test', 'IN', [TRUE, FALSE])->execute();

    try {
      CRM_Mailing_BAO_MailingJob::revert($mailingID);
      $this->fail('revert() should refuse a mailing whose delivery has started');
    }
    catch (CRM_Core_Exception $e) {
      // Expected.
    }

    $this->assertEquals($mailingBefore, Mailing::get(FALSE)->addWhere('id', '=', $mailingID)->execute()->single());
    $this->assertEquals($jobsBefore, (array) MailingJob::get(FALSE)->addWhere('mailing_id', '=', $mailingID)->addWhere('is_test', 'IN', [TRUE, FALSE])->execute());
  }

  /**
   * A parent job created after revert() took its locks makes it refuse.
   */
  public function testRevertRefusedWhenParentJobAppears(): void {
    $mailingID = $this->createRevertTestMailing('Scheduled', ['status' => 'Scheduled']);
    $inserted = FALSE;
    \Civi::dispatcher()->addListener('civi.api.prepare', function ($event) use ($mailingID, &$inserted) {
      if (!$inserted && $event->getEntityName() === 'Mailing' && $event->getActionName() === 'get') {
        $inserted = TRUE;
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing_job (mailing_id, status, is_test) VALUES (%1, 'Scheduled', 0)", [
          1 => [$mailingID, 'Integer'],
        ]);
      }
    });

    try {
      CRM_Mailing_BAO_MailingJob::revert($mailingID);
      $this->fail('revert() should refuse when a parent job appears after it took its locks');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertStringContainsString('being processed right now', $e->getMessage());
    }
    $this->assertTrue($inserted);
    $this->assertSame('Scheduled', Mailing::get(FALSE)->addWhere('id', '=', $mailingID)->execute()->single()['status']);
    $this->assertCount(2, MailingJob::get(FALSE)->addWhere('mailing_id', '=', $mailingID)->addWhere('is_test', '=', FALSE)->execute());
  }

  /**
   * Mailing states in which delivery has started.
   */
  public static function startedMailingProvider(): array {
    $started = ['start_date' => '2026-01-01 10:00:00'];
    return [
      'running' => ['Running', ['status' => 'Running'] + $started, TRUE],
      'paused' => ['Paused', ['status' => 'Paused'] + $started, TRUE],
      'complete' => ['Complete', ['status' => 'Complete', 'end_date' => '2026-01-01 11:00:00'] + $started, TRUE],
      // The mailing still reads Scheduled, but cron has started its job.
      'scheduled but started' => ['Scheduled', ['status' => 'Running'] + $started, FALSE],
      'scheduled with a start date' => ['Scheduled', ['status' => 'Scheduled'] + $started, FALSE],
      'scheduled with a child job' => ['Scheduled', ['status' => 'Scheduled'], TRUE],
    ];
  }

  /**
   * Create a mailing with a parent job, optionally a child job, and a test job.
   */
  private function createRevertTestMailing(string $mailingStatus, array $parentJob, bool $withChildJob = FALSE): int {
    $mailingID = $this->createTestEntity('Mailing', [
      'name' => 'Revert Test Mailing',
      'subject' => 'Test Subject',
      'body_text' => 'Test Body',
      'status' => $mailingStatus,
    ])['id'];
    // Set directly: a scheduled_date passed to create() would also create a job.
    CRM_Core_DAO::executeQuery("UPDATE civicrm_mailing SET scheduled_date = '2026-01-01 09:00:00', scheduled_id = %1 WHERE id = %2", [
      1 => [$this->createLoggedInUser(), 'Integer'],
      2 => [$mailingID, 'Integer'],
    ]);
    $parentJobID = $this->createTestEntity('MailingJob', $parentJob + [
      'mailing_id' => $mailingID,
      'is_test' => 0,
      'job_type' => NULL,
    ], 'parent')['id'];
    if ($withChildJob) {
      $this->createTestEntity('MailingJob', [
        'mailing_id' => $mailingID,
        'parent_id' => $parentJobID,
        'status' => $parentJob['status'],
        'is_test' => 0,
        'job_type' => 'child',
        'job_offset' => 0,
        'job_limit' => 10,
      ], 'child');
    }
    $this->createTestEntity('MailingJob', [
      'mailing_id' => $mailingID,
      'status' => 'Complete',
      'is_test' => 1,
    ], 'test');
    return $mailingID;
  }

}
