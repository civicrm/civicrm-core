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
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_MailingContact
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 */

/**
 * Class api_v3_MailingContactTest
 * @group headless
 */
class api_v3_MailingContactTest extends CiviUnitTestCase {
  protected $_entity = 'mailing';

  public function setUp() {
    parent::setUp();
    $params = [
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    ];
    $this->_contact = $this->callAPISuccess("contact", "create", $params);
  }

  public function tearDown() {
    $this->callAPISuccess("contact", "delete", ['id' => $this->_contact['id']]);
    parent::tearDown();
  }

  public function testMailingContactGetFields() {
    $result = $this->callAPISuccess('MailingContact', 'getfields', [
      'action' => 'get',
    ]);
    $this->assertEquals('Delivered', $result['values']['type']['api.default']);
  }

  /**
   * Test for proper error when you do not supply the contact_id.
   *
   * Do not copy and paste.
   *
   * Test is of marginal if any value & testing of wrapper level functionality
   * belongs in the SyntaxConformance class
   */
  public function testMailingNoContactID() {
    $this->callAPIFailure('MailingContact', 'get', ['something' => 'This is not a real field']);
  }

  /**
   * Test that invalid contact_id return with proper error messages.
   *
   * Do not copy & paste.
   *
   * Test is of marginal if any value & testing of wrapper level functionality
   * belongs in the SyntaxConformance class
   */
  public function testMailingContactInvalidContactID() {
    $this->callAPIFailure('MailingContact', 'get', ['contact_id' => 'This is not a number']);
  }

  /**
   * Test that invalid types are returned with appropriate errors.
   */
  public function testMailingContactInvalidType() {
    $params = [
      'contact_id' => 23,
      'type' => 'invalid',
    ];
    $this->callAPIFailure('MailingContact', 'get', $params);
  }

  /**
   * Test for success result when there are no mailings for a the given contact.
   */
  public function testMailingContactNoMailings() {
    $params = [
      'contact_id' => $this->_contact['id'],
    ];
    $result = $this->callAPISuccess('MailingContact', 'get', $params);
    $this->assertEquals($result['count'], 0);
    $this->assertTrue(empty($result['values']));
  }

  /**
   * Test that the API returns a mailing properly when there is only one.
   */
  public function testMailingContactDelivered() {
    list($contactID, $mailingID, $eventQueueID) = $this->setupEventQueue();
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing_event_delivered (event_queue_id) VALUES(%1)", [1 => [$eventQueueID, 'Integer']]);

    $params = [
      'contact_id' => $contactID,
      'type' => 'Delivered',
    ];

    $result = $this->callAPISuccess('MailingContact', 'get', $params);
    $count = $this->callAPISuccess('MailingContact', 'getcount', $params);
    $this->assertEquals($result['count'], 1);
    $this->assertEquals($count, 1);
    $this->assertFalse(empty($result['values']));
    $this->assertEquals($result['values'][1]['mailing_id'], 1);
    $this->assertEquals($result['values'][1]['subject'], "Some Subject");
    $this->assertEquals(CRM_Core_Session::getLoggedInContactID(), $result['values'][1]['creator_id']);
  }

  /**
   * Test that the API returns only the "Bounced" mailings when instructed to
   * do so.
   *
   * @throws \Exception
   */
  public function testMailingContactBounced() {
    list($contactID, $mailingID, $eventQueueID) = $this->setupEventQueue();
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing_event_bounce (event_queue_id, bounce_type_id) VALUES(%1, 6)", [1 => [$eventQueueID, 'Integer']]);

    $params = [
      'contact_id' => $contactID,
      'type' => 'Bounced',
    ];

    $result = $this->callAPISuccess('MailingContact', 'get', $params)['values'];
    $this->assertEquals(1, count($result));
    $this->assertEquals($mailingID, $result[$mailingID]['mailing_id']);
    $this->assertEquals('Some Subject', $result[$mailingID]['subject']);
    $this->assertEquals(CRM_Core_Session::getLoggedInContactID(), $result[$mailingID]['creator_id'], 3);
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function setupEventQueue() {
    $contactID = $this->individualCreate(['first_name' => 'Test']);
    $emailID = $this->callAPISuccessGetValue('Email', [
      'return' => 'id',
      'contact_id' => $contactID,
    ]);
    $this->createLoggedInUser();
    $mailingID = $this->callAPISuccess('Mailing', 'create', [
      'name' => 'Test Mailing',
      'subject' => 'Some Subject',
    ])['id'];
    $mailingJobID = $this->callAPISuccess('MailingJob', 'create', ['mailing_id' => $mailingID])['id'];
    $eventQueueID = $this->callAPISuccess('MailingEventQueue', 'create', [
      'contact_id' => $contactID,
      'mailing_id' => $mailingID,
      'email_id' => $emailID,
      'job_id' => $mailingJobID,
    ])['id'];
    return [$contactID, $mailingID, $eventQueueID];
  }

}
