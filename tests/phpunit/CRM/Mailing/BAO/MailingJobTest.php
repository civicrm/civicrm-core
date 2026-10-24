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
