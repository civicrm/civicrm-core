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

use Civi\Test\FormTrait;

/**
 * Test class for CRM_Contact_Form_Task_SMSCommon.
 * @group headless
 */
class CRM_Contact_Form_Task_SMSCommonTest extends CiviUnitTestCase {

  use FormTrait;

  /**
   * Set up SMS recipients.
   */
  protected function setUp(): void {
    parent::setUp();
    $mobile_type_id = CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Phone', 'phone_type_id', 'Mobile');
    $phone_type_id = CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Phone', 'phone_type_id', 'Phone');

    $this->individualCreate([
      'first_name' => 'First',
      'last_name' => 'Person',
      'do_not_sms' => 0,
      'phone' => [
        1 => [
          'phone_type_id' => $mobile_type_id,
          'location_type_id' => 1,
          'phone' => '1111111111',
        ],
      ],
    ], 'first');
    $this->individualCreate([
      'first_name' => 'Second',
      'last_name' => 'Person',
      'do_not_sms' => 0,
      'phone' => [
        1 => [
          'phone_type_id' => $phone_type_id,
          'location_type_id' => 1,
          'phone' => '9999999999',
          'is_primary' => 1,
        ],
        2 => [
          'phone_type_id' => $mobile_type_id,
          'location_type_id' => 1,
          'phone' => '2222222222',
        ],
      ],
    ], 'second');
    $this->individualCreate([
      'first_name' => 'Third',
      'last_name' => 'Person',
      'do_not_sms' => 0,
      'phone' => [
        1 => [
          'phone_type_id' => $mobile_type_id,
          'location_type_id' => 1,
          'phone' => '3333333333',
          'is_primary' => 0,
        ],
      ],
    ], 'third');
    $this->individualCreate([
      'first_name' => 'Fourth',
      'last_name' => 'Person',
      'do_not_sms' => 1,
      'phone' => [
        1 => [
          'phone_type_id' => $mobile_type_id,
          'location_type_id' => 1,
          'phone' => '4444444444',
        ],
      ],
    ], 'fourth');
    $this->individualCreate([
      'first_name' => 'Fifth',
      'last_name' => 'Person',
      'do_not_sms' => 0,
      'is_deceased' => 1,
      'phone' => [
        1 => [
          'phone_type_id' => $mobile_type_id,
          'location_type_id' => 1,
          'phone' => '5555555555',
        ],
      ],
    ], 'fifth');
  }

  /**
   * Test to ensure SMS Activity QuickForm displays the right phone numbers.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testQuickFormMobileNumbersDisplay(): void {
    $this->createLoggedInUser();
    $this->createTestEntity('OptionValue', ['option_group_id:name' => 'sms_provider_name', 'name' => 'dummy sms', 'label' => 'Dummy']);
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_sms_provider (name,title, api_type, api_params) VALUES ("SMS", "SMS", 1, "1=2")');
    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', [
      'radio_ts' => 'ts_all',
      'to' => implode(',', [
        $this->ids['Contact']['first'] . '::' . 1111111111,
        $this->ids['Contact']['second'] . '::' . 2222222222,
        $this->ids['Contact']['third'] . '::' . 3333333333,
      ]),
    ], ['action' => 1])
      ->addSubsequentForm('CRM_Contact_Form_Task_SMS', [
        'sms_provider_id' => 1,
        'to' => implode(',', [
          $this->ids['Contact']['first'] . '::' . 1111111111,
          $this->ids['Contact']['second'] . '::' . 2222222222,
          $this->ids['Contact']['third'] . '::' . 3333333333,
        ]),
        'activity_subject' => 'Your SMS Reminder',
        'sms_text_message' => 'Do not forget',
      ]);
    $form->processForm();
    $contacts = json_decode($form->getTemplateVariable('toContact'));
    $smsRecipientsActual = [];

    $phoneNumbers = [
      $this->ids['Contact']['first'] => 1111111111,
      $this->ids['Contact']['second'] => 2222222222,
      $this->ids['Contact']['third'] => 3333333333,
    ];

    foreach ($contacts as $contact) {
      $id = $contact->id;
      $ret = preg_match('/^([0-9]+)::([0-9]+)/', $id, $matches);
      // id is in the format: contact_id::phone_number, e.g.: 5::2222222222
      $this->assertEquals(1, $ret, 'Failed to extract the mobile number and contact id.');
      $contact_id = $matches[1];
      $phone_number = $matches[2];
      // Check if we are supposed to send an SMS to this contact.
      if (array_key_exists($contact_id, $phoneNumbers)) {
        // We are supposed to send an SMS to this contact, now make sure we have the right phone number.
        $this->assertEquals($phone_number, $phoneNumbers[$contact_id], "Returned incorrect mobile number in SMS send quick form.");
        $smsRecipientsActual[] = $contact_id;
      }
      else {
        // We are not supposed to send this contact an email.
        $this->fail("We should not be sending an SMS to contact_id: $contact_id.");
      }
    }

    // Make sure we sent to all the contacts.
    sort($smsRecipientsActual);
    $smsRecipientsIntended = array_keys($phoneNumbers);
    sort($smsRecipientsIntended);
    $this->assertEquals($smsRecipientsIntended, $smsRecipientsActual, 'We did not send an SMS to all the contacts.');
  }

}
