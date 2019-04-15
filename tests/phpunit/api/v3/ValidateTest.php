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
    $validation = $this->callAPISuccess('Contact', 'validate', array('action' => "create"));
    $expectedOut = array(
      'contact_type' => array(
        'message' => "Mandatory key(s) missing from params array: contact_type",
        'code' => "mandatory_missing",
      ),
    );
    $this->assertEquals($validation['values'][0], $expectedOut);
  }

  public function testContributionValidate() {
    $validation = $this->callAPISuccess('Contribution', 'validate', array('action' => "create", 'total_amount' => "100w"));
    $totalAmountErrors = array(
      'message' => "total_amount is  not a valid amount: 100w",
      'code' => "incorrect_value",
    );

    $contactIdErrors = array(
      'message' => "Mandatory key(s) missing from params array: contact_id",
      'code' => "mandatory_missing",
    );

    $this->assertEquals($validation['values'][0]['total_amount'], $totalAmountErrors);
    $this->assertEquals($validation['values'][0]['contact_id'], $contactIdErrors);
  }

  public function testContributionDateValidate() {
    $params = array(
      'action' => "create",
      'financial_type_id' => "1",
      'total_amount' => "100",
      'contact_id' => "1",
      'receive_date' => 'abc',
    );
    $validation = $this->callAPISuccess('Contribution', 'validate', $params);

    $expectedOut = array(
      'receive_date' => array(
        'message' => "receive_date is not a valid date: abc",
        'code' => "incorrect_value",
      ),
    );

    $this->assertEquals($validation['values'][0], $expectedOut);
  }

}
