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

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_mailing_recipients', 'civicrm_mailing', 'civicrm_mailing_event_delivered']);
    parent::tearDown();
  }

  public function testMailingContactGetFields(): void {
    $result = $this->callAPISuccess('MailingContact', 'getfields', [
      'action' => 'get',
    ]);
    $this->assertEquals('Delivered', $result['values']['type']['api.default']);
  }

  /**
   * Test that the API returns a mailing properly when there is only one.
   */
  public function testMailingContactDelivered(): void {
    list($contactID, $mailingID, $eventQueueID) = $this->setupEventQueue();
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing_event_delivered (event_queue_id) VALUES(%1)", [1 => [$eventQueueID, 'Integer']]);

    $params = [
      'contact_id' => $contactID,
      'type' => 'Delivered',
    ];

    $result = $this->callAPISuccess('MailingContact', 'get', $params);
    $count = $this->callAPISuccess('MailingContact', 'getcount', $params);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(1, $count);
    $this->assertFalse(empty($result['values']));
    $this->assertEquals(1, $result['values'][1]['mailing_id']);
    $this->assertEquals("Some Subject", $result['values'][1]['subject']);
    $this->assertEquals(CRM_Core_Session::getLoggedInContactID(), $result['values'][1]['creator_id']);
  }

  /**
   * Test that the API returns only the "Bounced" mailings when instructed to
   * do so.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMailingContactBounced(): void {
    list($contactID, $mailingID, $eventQueueID) = $this->setupEventQueue();
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing_event_bounce (event_queue_id, bounce_type_id) VALUES(%1, 6)", [1 => [$eventQueueID, 'Integer']]);

    $params = [
      'contact_id' => $contactID,
      'type' => 'Bounced',
    ];

    $result = $this->callAPISuccess('MailingContact', 'get', $params)['values'];
    $this->assertCount(1, $result);
    $this->assertEquals($mailingID, $result[$mailingID]['mailing_id']);
    $this->assertEquals('Some Subject', $result[$mailingID]['subject']);
    $this->assertEquals(CRM_Core_Session::getLoggedInContactID(), $result[$mailingID]['creator_id'], 3);
  }

  /**
   * @return array
   */
  public function setupEventQueue(): array {
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
