<?php

require_once 'CiviTest/Contact.php';

/**
 * Class CRM_Core_BAO_EmailTest
 * @group headless
 */
class CRM_Core_BAO_EmailTest extends CiviUnitTestCase {
  public function setUp() {
    parent::setUp();

    $this->quickCleanup(array('civicrm_contact', 'civicrm_email'));
  }

  /**
   * Add() method (create and update modes)
   */
  public function testAdd() {
    $contactId = Contact::createIndividual();

    $params = array();
    $params = array(
      'email' => 'jane.doe@example.com',
      'is_primary' => 1,
      'location_type_id' => 1,
      'contact_id' => $contactId,
    );

    CRM_Core_BAO_Email::add($params);

    $emailId = $this->assertDBNotNull('CRM_Core_DAO_Email', 'jane.doe@example.com', 'id', 'email',
      'Database check for created email address.'
    );

    // Now call add() to modify an existing email address

    $params = array();
    $params = array(
      'id' => $emailId,
      'contact_id' => $contactId,
      'is_bulkmail' => 1,
      'on_hold' => 1,
    );

    CRM_Core_BAO_Email::add($params);

    $isBulkMail = $this->assertDBNotNull('CRM_Core_DAO_Email', $emailId, 'is_bulkmail', 'id',
      'Database check on updated email record.'
    );
    $this->assertEquals($isBulkMail, 1, 'Verify bulkmail value is 1.');

    Contact::delete($contactId);
  }

  /**
   * HoldEmail() method (set and reset on_hold condition)
   */
  public function testHoldEmail() {
    $contactId = Contact::createIndividual();

    $params = array();
    $params = array(
      'email' => 'jane.doe@example.com',
      'is_primary' => 1,
      'location_type_id' => 1,
      'contact_id' => $contactId,
    );

    CRM_Core_BAO_Email::add($params);

    $emailId = $this->assertDBNotNull('CRM_Core_DAO_Email', 'jane.doe@example.com', 'id', 'email',
      'Database check for created email address.'
    );

    // Now call add() to update on_hold=true and check record state
    $params = array();
    $params = array(
      'id' => $emailId,
      'contact_id' => $contactId,
      'on_hold' => 1,
    );

    CRM_Core_BAO_Email::add($params);

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

    // Now call add() with on_hold=false and verify that reset_date is set.
    $params = array();
    $params = array(
      'id' => $emailId,
      'contact_id' => $contactId,
      'on_hold' => 'null',
    );

    CRM_Core_BAO_Email::add($params);
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

    Contact::delete($contactId);
  }

  /**
   * AllEmails() method - get all emails for our contact, with primary email first
   */
  public function testAllEmails() {
    $contactParams = array(
      'first_name' => 'Alan',
      'last_name' => 'Smith',
      'email-1' => 'alan.smith1@example.com',
      'email-2' => 'alan.smith2@example.com',
      'email-3' => 'alan.smith3@example.com',
    );

    $contactId = Contact::createIndividual($contactParams);

    $emails = CRM_Core_BAO_Email::allEmails($contactId);

    $this->assertEquals(count($emails), 3, 'Checking number of returned emails.');

    $firstEmailValue = array_slice($emails, 0, 1);

    $this->assertEquals('alan.smith1@example.com', $firstEmailValue[0]['email'], 'Confirm primary email address value.');
    $this->assertEquals(1, $firstEmailValue[0]['is_primary'], 'Confirm first email address is primary.');

    Contact::delete($contactId);
  }

}
