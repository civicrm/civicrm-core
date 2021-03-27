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
  public function setUp(): void {
    parent::setUp();
    $option = $this->callAPISuccess('option_value', 'create', ['option_group_id' => 'sms_provider_name', 'name' => 'test_provider_name', 'label' => 'test_provider_name', 'value' => 1]);
    $this->option_value = $option['id'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown(): void {
    parent::tearDown();
    $this->quickCleanup(['civicrm_email', 'civicrm_phone', 'civicrm_activity', 'civicrm_activity_contact']);
    $this->callAPISuccess('option_value', 'delete', ['id' => $this->option_value]);
  }

  /**
   * CRM-20238 Add test of the processInbound function for SMSs
   */
  public function testProcessInbound() {
    $testSourceContact = $this->individualCreate(['phone' => [1 => ['phone_type_id' => 'Phone', 'location_type_id' => 'Home', 'phone' => '+61487654321']]]);
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
    $activity = $this->callAPISuccess('activity', 'getsingle', ['id' => $result->id, 'return' => ['source_contact_id', 'target_contact_id', 'assignee_contact_id']]);
    $contact = $this->callAPISuccess('contact', 'getsingle', ['phone' => '61412345678']);
    // Verify that when no to is passed in by default the same contact is used for the source and target.
    $this->assertEquals($contact['id'], $activity['source_contact_id']);
    $this->assertEquals($contact['id'], $activity['target_contact_id'][0]);
  }

  /**
   * CRM-20238 Add test of ProcessInbound function where no To number is passed into the function but the toContactId gets set in a hook
   */
  public function testProcessInboundSetToContactIDUsingHook() {
    $provider = new CiviTestSMSProvider('CiviTestSMSProvider');
    $this->hookClass->setHook('civicrm_inboundSMS', [$this, 'smsHookTest']);
    $result = $provider->processInbound('+61412345678', 'This is a test message', NULL, '12345');
    $this->assertEquals('This is a test message', $result->details);
    $this->assertEquals('+61412345678', $result->phone_number);
    $this->assertEquals('12345', $result->result);
    $contact = $this->callAPISuccess('contact', 'getsingle', ['phone' => '+61487654321']);
    $activity = $this->callAPISuccess('activity', 'getsingle', ['id' => $result->id, 'return' => ['source_contact_id', 'target_contact_id', 'assignee_contact_id']]);
    $this->assertEquals($contact['id'], $activity['source_contact_id']);
  }

  public function smsHookTest(&$message) {
    $testSourceContact = $this->individualCreate(['phone' => [1 => ['phone' => '+61487654321']]]);
    $message->toContactID = $testSourceContact;
  }

  /**
   * Some providers, like the mock one for these tests at the time of writing,
   * or the dummy SMS provider extension, might not provide a default url,
   * but the form shouldn't fail because of that.
   */
  public function testMissingUrl() {
    $form = $this->getFormObject('CRM_SMS_Form_Provider');
    $_REQUEST['key'] = 'CiviTestSMSProvider';

    // This shouldn't give a notice
    $defaults = $form->setDefaultValues();

    $this->assertEquals([
      'name' => 'CiviTestSMSProvider',
      'api_url' => '',
      'is_default' => 1,
      'is_active' => 1,
    ], $defaults);

    unset($_REQUEST['key']);
  }

}
