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
 * Test class for CRM_Pledge_BAO_Pledge BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Pledge_BAO_PledgeTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->_contactId = $this->individualCreate();
    $this->_params = [
      'contact_id' => $this->_contactId,
      'frequency_unit' => 'month',
      'original_installment_amount' => 25.00,
      'frequency_interval' => 1,
      'frequency_day' => 1,
      'installments' => 12,
      'financial_type_id' => 1,
      'create_date' => '20100513000000',
      'acknowledge_date' => '20100513000000',
      'start_date' => '20100513000000',
      'status_id' => 2,
      'currency' => 'USD',
      'amount' => 300,
    ];
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
  }

  /**
   *  Test for Add/Update Pledge.
   */
  public function testAdd() {
    //do test for normal add.
    $pledge = CRM_Pledge_BAO_Pledge::add($this->_params);

    foreach ($this->_params as $param => $value) {
      $this->assertEquals($value, $pledge->$param);
    }
  }

  /**
   * Test Pledge Payment Status with 1 installment
   * and not passing status id.
   */
  public function testPledgePaymentStatus() {
    $scheduledDate = date('Ymd', mktime(0, 0, 0, date("m"), date("d") + 2, date("y")));
    $this->_params['installments'] = 1;
    $this->_params['scheduled_date'] = $scheduledDate;

    unset($this->_params['status_id']);
    $pledge = CRM_Pledge_BAO_Pledge::create($this->_params);
    $pledgePayment = CRM_Pledge_BAO_PledgePayment::getPledgePayments($pledge->id);

    $this->assertEquals(count($pledgePayment), 1);
    $payment = array_pop($pledgePayment);
    // Assert that we actually have no pledge Payments
    $this->assertEquals(0, CRM_Pledge_BAO_Pledge::pledgeHasFinancialTransactions($pledge->id, array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'))));
    $this->assertEquals($payment['status'], 'Pending');
    $this->assertEquals($payment['scheduled_date'], date('Y-m-d 00:00:00', strtotime($scheduledDate)));
  }

  /**
   *  Retrieve a pledge based on a pledge id = 0
   */
  public function testRetrieveZeroPledeID() {
    $defaults = [];
    $params = ['pledge_id' => 0];
    $pledgeId = CRM_Pledge_BAO_Pledge::retrieve($params, $defaults);

    $this->assertEquals(is_null($pledgeId), 1, "Pledge Id must be greater than 0");
  }

  /**
   *  Retrieve a payment based on a Null pledge id random string.
   */
  public function testRetrieveStringPledgeID() {
    $defaults = [];
    $params = ['pledge_id' => 'random text'];
    $pledgeId = CRM_Pledge_BAO_Pledge::retrieve($params, $defaults);

    $this->assertEquals(is_null($pledgeId), 1, "Pledge Id must be a string");
  }

  /**
   *  Test that payment retrieve wrks based on known pledge id.
   */
  public function testRetrieveKnownPledgeID() {
    $params = [
      'contact_id' => $this->_contactId,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'frequency_day' => 1,
      'original_installment_amount' => 25.00,
      'installments' => 12,
      'financial_type_id' => 1,
      'create_date' => '20100513000000',
      'acknowledge_date' => '20100513000000',
      'start_date' => '20100513000000',
      'status_id' => 2,
      'currency' => 'USD',
      'amount' => 300,
    ];

    $pledge = CRM_Pledge_BAO_Pledge::add($params);

    $defaults = [];
    $pledgeParams = ['pledge_id' => $pledge->id];

    $pledgeId = CRM_Pledge_BAO_Pledge::retrieve($pledgeParams, $defaults);

    $this->assertEquals($pledgeId->N, 1, "Pledge was retrieved");
  }

  /**
   *  Test build recur params.
   */
  public function testGetPledgeStartDate() {
    $startDate = json_encode(['calendar_month' => 6]);

    $params = [
      'pledge_start_date' => $startDate,
      'is_pledge_start_date_editable' => TRUE,
      'is_pledge_start_date_visible' => TRUE,
    ];

    // Try with relative date
    $date = CRM_Pledge_BAO_Pledge::getPledgeStartDate(6, $params);
    $paymentDate = CRM_Pledge_BAO_Pledge::getPaymentDate(6);

    $this->assertEquals(date('m/d/Y', strtotime($date)), $paymentDate, "The two dates do not match");

    // Try with fixed date
    $date = NULL;
    $params = [
      'pledge_start_date' => json_encode(['calendar_date' => '06/10/2016']),
      'is_pledge_start_date_visible' => FALSE,
    ];

    $date = CRM_Pledge_BAO_Pledge::getPledgeStartDate($date, $params);
    $this->assertEquals(date('m/d/Y', strtotime($date)), '06/10/2016', "The two dates do not match");
  }

}
