<?php

/**
 * Class CRM_Core_BAO_EmailTest
 * @group headless
 */
class CRM_Core_BAO_EmailTest extends CiviUnitTestCase {

  /**
   * Set up for class.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp() {
    parent::setUp();
    $this->quickCleanup(['civicrm_contact', 'civicrm_email']);
  }

  /**
   * Add() method (create and update modes)
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreate() {
    $contactId = $this->individualCreate();
    $params = [
      'email' => 'jane.doe@example.com',
      'is_primary' => 1,
      'location_type_id' => 1,
      'contact_id' => $contactId,
    ];

    CRM_Core_BAO_Email::create($params);

    $emailId = $this->assertDBNotNull('CRM_Core_DAO_Email', 'jane.doe@example.com', 'id', 'email',
      'Database check for created email address.'
    );

    // Now call create() to modify an existing email address
    $params = [
      'id' => $emailId,
      'contact_id' => $contactId,
      'is_bulkmail' => 1,
      'on_hold' => 1,
    ];

    CRM_Core_BAO_Email::create($params);

    $isBulkMail = $this->assertDBNotNull('CRM_Core_DAO_Email', $emailId, 'is_bulkmail', 'id',
      'Database check on updated email record.'
    );
    $this->assertEquals($isBulkMail, 1, 'Verify bulkmail value is 1.');

    $this->contactDelete($contactId);
  }

  /**
   * HoldEmail() method (set and reset on_hold condition).
   *
   * @throws \CRM_Core_Exception
   */
  public function testHoldEmail() {
    $contactId = $this->individualCreate();

    $params = [
      'email' => 'jane.doe@example.com',
      'is_primary' => 1,
      'location_type_id' => 1,
      'contact_id' => $contactId,
    ];

    CRM_Core_BAO_Email::create($params);

    $emailId = $this->assertDBNotNull('CRM_Core_DAO_Email', 'jane.doe@example.com', 'id', 'email',
      'Database check for created email address.'
    );

    // Now call create() to update on_hold=1 ("On Hold Bounce") and check record state
    $params = [
      'id' => $emailId,
      'contact_id' => $contactId,
      'on_hold' => 1,
    ];

    CRM_Core_BAO_Email::create($params);

    // Use assertDBNotNull to get back value of hold_date and check if it's in the current year.
    // NOTE: The assertEquals will fail IF this test is run just as the year is changing (low likelihood).
    $holdDate = $this->assertDBNotNull('CRM_Core_DAO_Email', $emailId, 'hold_date', 'id',
      'Retrieve hold_date from the updated email record.'
    );

    $this->assertEquals(substr($holdDate, 0, 4), substr(date('YmdHis'), 0, 4),
      'Compare hold_date (' . $holdDate . ') in DB to current year.'
    );

    $this->assertDBCompareValue('CRM_Core_DAO_Email', $emailId, 'on_hold', 'id', 1,
      'Check if on_hold=1 in updated email record.'
    );

    // Now call create() to update on_hold=2 ("On Hold Opt-out") and check record state
    $params = [
      'id' => $emailId,
      'contact_id' => $contactId,
      'on_hold' => 2,
    ];

    CRM_Core_BAO_Email::create($params);

    // Use assertDBNotNull to get back value of hold_date and check that it's in the current year.
    // NOTE: The assertEquals will fail IF this test is run just as the year is changing (low likelihood).
    $holdDate = $this->assertDBNotNull('CRM_Core_DAO_Email', $emailId, 'hold_date', 'id',
      'Retrieve hold_date from the updated email record.'
    );

    $this->assertEquals(substr($holdDate, 0, 4), substr(date('YmdHis'), 0, 4),
      'Compare hold_date (' . $holdDate . ') in DB to current year.'
    );

    $this->assertDBCompareValue('CRM_Core_DAO_Email', $emailId, 'on_hold', 'id', 2,
      'Check if on_hold=2 in updated email record.'
    );

    // Now call create() with on_hold=null (not on hold) and verify that reset_date is set.
    $params = [
      'id' => $emailId,
      'contact_id' => $contactId,
      'on_hold' => 'null',
    ];

    CRM_Core_BAO_Email::create($params);
    $this->assertDBCompareValue('CRM_Core_DAO_Email', $emailId, 'on_hold', 'id', 0,
      'Check if on_hold=0 in updated email record.'
    );
    $this->assertDBCompareValue('CRM_Core_DAO_Email', $emailId, 'hold_date', 'id', '',
      'Check if hold_date has been set to empty string.'
    );

    // Use assertDBNotNull to get back value of reset_date and check if it's in the current year.
    // NOTE: The assertEquals will fail IF this test is run just as the year is changing (low likelihood).
    $resetDate = $this->assertDBNotNull('CRM_Core_DAO_Email', $emailId, 'reset_date', 'id',
      'Retrieve reset_date from the updated email record.'
    );

    $this->assertEquals(substr($resetDate, 0, 4), substr(date('YmdHis'), 0, 4),
      'Compare reset_date (' . $resetDate . ') in DB to current year.'
    );

    $this->contactDelete($contactId);
  }

  /**
   * AllEmails() method - get all emails for our contact, with primary email first
   *
   * @throws \CRM_Core_Exception
   */
  public function testAllEmails() {
    $contactParams = [
      'first_name' => 'Alan',
      'last_name' => 'Smith',
      'email' => 'alan.smith1@example.com',
      'api.email.create.0' => ['email' => 'alan.smith2@example.com', 'location_type_id' => 'Home'],
      'api.email.create.1' => ['email' => 'alan.smith3@example.com', 'location_type_id' => 'Main'],
    ];

    $contactId = $this->individualCreate($contactParams);

    $emails = CRM_Core_BAO_Email::allEmails($contactId);

    $this->assertEquals(count($emails), 3, 'Checking number of returned emails.');

    $firstEmailValue = array_slice($emails, 0, 1);

    $this->assertEquals('alan.smith1@example.com', $firstEmailValue[0]['email'], 'Confirm primary email address value.');
    $this->assertEquals(1, $firstEmailValue[0]['is_primary'], 'Confirm first email address is primary.');

    $this->contactDelete($contactId);
  }

  /**
   * Test getting list of Emails for use in Receipts and Single Email sends
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetFromEmail() {
    $this->createLoggedInUser();
    $fromEmails = CRM_Core_BAO_Email::getFromEmail();
    $emails = array_values($fromEmails);
    $this->assertContains("(preferred)", $emails[0]);
    Civi::settings()->set("allow_mail_from_logged_in_contact", 0);
    $this->callAPISuccess('system', 'flush', []);
    $fromEmails = CRM_Core_BAO_Email::getFromEmail();
    $emails = array_values($fromEmails);
    $this->assertNotContains("(preferred)", $emails[0]);
    $this->assertContains("info@EXAMPLE.ORG", $emails[0]);
    Civi::settings()->set("allow_mail_from_logged_in_contact", 1);
    $this->callAPISuccess('system', 'flush', []);
  }

}
