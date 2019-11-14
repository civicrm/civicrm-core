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
 *  Tests for the generic validate API action.
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_ValidateTest extends CiviUnitTestCase {

  /**
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  public function testEmptyContactValidate() {
    $validation = $this->callAPISuccess('Contact', 'validate', ['action' => "create"]);
    $expectedOut = [
      'contact_type' => [
        'message' => "Mandatory key(s) missing from params array: contact_type",
        'code' => "mandatory_missing",
      ],
    ];
    $this->assertEquals($validation['values'][0], $expectedOut);
  }

  public function testContributionValidate() {
    $validation = $this->callAPISuccess('Contribution', 'validate', ['action' => "create", 'total_amount' => "100w"]);
    $totalAmountErrors = [
      'message' => "total_amount is  not a valid amount: 100w",
      'code' => "incorrect_value",
    ];

    $contactIdErrors = [
      'message' => "Mandatory key(s) missing from params array: contact_id",
      'code' => "mandatory_missing",
    ];

    $this->assertEquals($validation['values'][0]['total_amount'], $totalAmountErrors);
    $this->assertEquals($validation['values'][0]['contact_id'], $contactIdErrors);
  }

  public function testContributionDateValidate() {
    $params = [
      'action' => "create",
      'financial_type_id' => "1",
      'total_amount' => "100",
      'contact_id' => "1",
      'receive_date' => 'abc',
    ];
    $validation = $this->callAPISuccess('Contribution', 'validate', $params);

    $expectedOut = [
      'receive_date' => [
        'message' => "receive_date is not a valid date: abc",
        'code' => "incorrect_value",
      ],
    ];

    $this->assertEquals($validation['values'][0], $expectedOut);
  }

}
