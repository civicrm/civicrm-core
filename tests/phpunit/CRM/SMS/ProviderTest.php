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
 *  Test CRM_SMS_Provider functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
require_once 'CiviTest/CiviTestSMSProvider.php';

class CRM_SMS_ProviderTest extends CiviUnitTestCase {

  /**
   * Set Up Funtion
   */
  public function setUp() {
    parent::setUp();
    $option = $this->callAPISuccess('option_value', 'create', array('option_group_id' => 'sms_provider_name', 'name' => 'test_provider_name', 'label' => 'test_provider_name', 'value' => 1));
    $this->option_value = $option['id'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    parent::tearDown();
    $this->quickCleanup(array('civicrm_email', 'civicrm_phone', 'civicrm_activity', 'civicrm_activity_contact'));
    $this->callAPISuccess('option_value', 'delete', array('id' => $this->option_value));
  }

  /**
   * CRM-20238 Add test of the processInbound function for SMSs
   */
  public function testProcessInbound() {
    $testSourceContact = $this->individualCreate(array('phone' => array(1 => array('phone_type_id' => 'Phone', 'location_type_id' => 'Home', 'phone' => '+61487654321'))));
    $provider = new CiviTestSMSProvider('CiviTestSMSProvider');
    $result = $provider->processInbound('+61412345678', 'This is a test message', '+61487654321');
    $this->assertEquals('This is a test message', $result->details);
    $this->assertEquals('+61412345678', $result->phone_number);
  }

  /**
   * CRM-20238 Add test of processInbound function where no To is passed into the function
   */
  public function testProcessInboundNoTo() {
    $provider = new CiviTestSMSProvider('CiviTestSMSProvider');
    $result = $provider->processInbound('+61412345678', 'This is a test message', NULL, '12345');
    $this->assertEquals('This is a test message', $result->details);
    $this->assertEquals('+61412345678', $result->phone_number);
    $this->assertEquals('12345', $result->result);
    $activity = $this->callAPISuccess('activity', 'getsingle', array('id' => $result->id, 'return' => array('source_contact_id', 'target_contact_id', 'assignee_contact_id')));
    $contact = $this->callAPISuccess('contact', 'getsingle', array('phone' => '61412345678'));
    // Verify that when no to is passed in by default the same contact is used for the source and target.
    $this->assertEquals($contact['id'], $activity['source_contact_id']);
    $this->assertEquals($contact['id'], $activity['target_contact_id'][0]);
  }

  /**
   * CRM-20238 Add test of ProcessInbound function where no To number is passed into the function but the toContactId gets set in a hook
   */
  public function testProcessInboundSetToContactIDUsingHook() {
    $provider = new CiviTestSMSProvider('CiviTestSMSProvider');
    $this->hookClass->setHook('civicrm_inboundSMS', array($this, 'smsHookTest'));
    $result = $provider->processInbound('+61412345678', 'This is a test message', NULL, '12345');
    $this->assertEquals('This is a test message', $result->details);
    $this->assertEquals('+61412345678', $result->phone_number);
    $this->assertEquals('12345', $result->result);
    $contact = $this->callAPISuccess('contact', 'getsingle', array('phone' => '+61487654321'));
    $activity = $this->callAPISuccess('activity', 'getsingle', array('id' => $result->id, 'return' => array('source_contact_id', 'target_contact_id', 'assignee_contact_id')));
    $this->assertEquals($contact['id'], $activity['source_contact_id']);
  }

  public function smsHookTest(&$message) {
    $testSourceContact = $this->individualCreate(array('phone' => array(1 => array('phone' => '+61487654321'))));
    $message->toContactID = $testSourceContact;
  }

}
