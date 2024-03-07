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
    ], 'first');
    $this->createTestEntity('Phone', [
      'phone_type_id' => $mobile_type_id,
      'location_type_id' => 1,
      'phone' => '1111111111',
      'contact_id' => $this->ids['Contact']['first'],
    ], 'first');
    $this->individualCreate([
      'first_name' => 'Second',
      'last_name' => 'Person',
      'do_not_sms' => 0,
    ], 'second');
    $this->createTestEntity('Phone', [
      'phone_type_id' => $phone_type_id,
      'location_type_id' => 1,
      'phone' => '9999999999',
      'contact_id' => $this->ids['Contact']['second'],
    ], 'second_phone');
    $this->createTestEntity('Phone', [
      'phone_type_id' => $mobile_type_id,
      'location_type_id' => 1,
      'phone' => '2222222222',
      'contact_id' => $this->ids['Contact']['second'],
    ], 'second');
    $this->individualCreate([
      'first_name' => 'Third',
      'last_name' => 'Person',
      'do_not_sms' => 0,
    ], 'third');
    $this->createTestEntity('Phone', [
      'phone_type_id' => $mobile_type_id,
      'location_type_id' => 1,
      'phone' => '3333333333',
      'contact_id' => $this->ids['Contact']['third'],
    ], 'third');
    $this->individualCreate([
      'first_name' => 'Fourth',
      'last_name' => 'Person',
      'do_not_sms' => 1,
    ], 'fourth');
    $this->createTestEntity('Phone', [
      'phone_type_id' => $mobile_type_id,
      'location_type_id' => 1,
      'phone' => '4444444444',
      'contact_id' => $this->ids['Contact']['fourth'],
    ], 'fourth');
    $this->individualCreate([
      'first_name' => 'Fifth',
      'last_name' => 'Person',
      'do_not_sms' => 0,
      'is_deceased' => 1,
    ], 'fifth');
    $this->createTestEntity('Phone', [
      'phone_type_id' => $mobile_type_id,
      'location_type_id' => 1,
      'phone' => '5555555555',
      'contact_id' => $this->ids['Contact']['fifth'],
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
        $this->ids['Phone']['first'],
        $this->ids['Phone']['second'],
        $this->ids['Phone']['third'],
      ]),
    ], ['action' => 1])
      ->addSubsequentForm('CRM_Contact_Form_Task_SMS', [
        'sms_provider_id' => 1,
        'to' => implode(',', [
          $this->ids['Phone']['first'],
          $this->ids['Phone']['second'],
          $this->ids['Phone']['third'],
        ]),
        'activity_subject' => 'Your SMS Reminder',
        'sms_text_message' => 'Do not forget',
      ]);
    $form->processForm();
    $activities = $this->callAPISuccess('Activity', 'get', ['sequential' => 1])['values'];
    $this->assertEquals('Your SMS Reminder', $activities[0]['subject']);
  }

}
