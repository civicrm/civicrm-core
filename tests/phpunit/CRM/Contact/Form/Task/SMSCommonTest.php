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
 * Test class for CRM_Contact_Form_Task_SMSCommon.
 * @group headless
 */
class CRM_Contact_Form_Task_SMSCommonTest extends CiviUnitTestCase {

  protected $_contactMobileNumbers = [];

  /**
   * Set up for tests.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setUp() {
    parent::setUp();
    $mobile_type_id = CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Phone', 'phone_type_id', 'Mobile');
    $phone_type_id = CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Phone', 'phone_type_id', 'Phone');
 
    $contact1 = $this->individualCreate([
        'first_name' => 'First',
        'last_name' => 'Person',
        'do_not_sms' => 0,
        'phone' => [
          1 => [
            'phone_type_id' => $mobile_type_id,
            'location_type_id' => 1,
            'phone' => '1111111111'
          ]
        ]
    ]);
    $contact2 = $this->individualCreate([
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
            'phone' => '2222222222'
          ]
        ]
    ]);
    $contact3 = $this->individualCreate([
        'first_name' => 'Third',
        'last_name' => 'Person',
        'do_not_sms' => 0,
        'phone' => [
          1 => [
            'phone_type_id' => $mobile_type_id,
            'location_type_id' => 1,
            'phone' => '3333333333',
            'is_primary' => 0,
          ]
        ]
    ]);

    // Track the contact id to correct mobile phone number.
    $this->_contactMobileNumbers = [
      $contact1 => "1111111111",
      $contact2 => "2222222222",
      $contact3 => "3333333333"
    ];

    $this->_contactIds = array_keys($this->_contactMobileNumbers);

  }

  /**
   * Test to ensure SMS Activity QuickForm displays the right phone numbers.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testQuickFormMobileNumbersDisplay() {
    $form = $this->getFormObject('CRM_Core_Form');
    $form->_contactIds = $this->_contactIds;
    $form->_single = FALSE;
    CRM_Contact_Form_Task_SMSCommon::buildQuickForm($form);
    $contacts = json_decode($form->get_template_vars('toContact'));
    foreach ($contacts as $contact) {
      $id = $contact->id;
      // id is in the format: contact_id::phone_number, e.g.: 5::2222222222
      $ret = preg_match('/^([0-9]+)::([0-9]+)/', $id, $matches);
      $this->assertEquals(1, $ret, "Failed to find mobile number.");
      $contact_id = $matches[1];
      $phone_number = $matches[2];
      $this->assertEquals($phone_number, $this->_contactMobileNumbers[$contact_id], "Returned incorrect mobile number in SMS send quick form.");
    }
  }

}
