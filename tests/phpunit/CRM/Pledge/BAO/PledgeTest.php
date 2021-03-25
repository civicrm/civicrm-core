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
 * Test class for CRM_Pledge_BAO_Pledge BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Pledge_BAO_PledgeTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ids['Contact'][0] = $this->individualCreate();
    $this->_params = [
      'contact_id' => $this->ids['Contact'][0],
      'frequency_unit' => 'month',
      'original_installment_amount' => 25.00,
      'frequency_interval' => 1,
      'frequency_day' => 1,
      'installments' => 12,
      'financial_type_id' => 1,
      'create_date' => '2010-05-13 00:00:00',
      'acknowledge_date' => '2010-05-13 00:00:00',
      'start_date' => '2010-05-13 00:00:00',
      'status_id' => 2,
      'currency' => 'USD',
      'amount' => 300,
    ];
  }

  /**
   *  Test for Add/Update Pledge.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAdd(): void {
    //do test for normal add.
    $pledgeID = $this->callAPISuccess('Pledge', 'create', $this->_params)['id'];
    $pledge = new CRM_Pledge_DAO_Pledge();
    $pledge->id = $pledgeID;
    $pledge->find(TRUE);
    unset($this->_params['status_id']);
    foreach ($this->_params as $param => $value) {
      $this->assertEquals($value, $pledge->$param, $param);
    }
  }

  /**
   * Test Pledge Payment Status with 1 installment
   * and not passing status id.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPledgePaymentStatus(): void {
    $scheduledDate = date('Ymd', mktime(0, 0, 0, date("m"), date("d") + 2, date("y")));
    $this->_params['installments'] = 1;
    $this->_params['scheduled_date'] = $scheduledDate;

    unset($this->_params['status_id']);
    $pledge = $this->callAPISuccess('Pledge', 'create', $this->_params);
    $pledgePayment = CRM_Pledge_BAO_PledgePayment::getPledgePayments($pledge['id']);

    $this->assertCount(1, $pledgePayment);
    $payment = array_pop($pledgePayment);
    // Assert that we actually have no pledge Payments
    $this->assertEquals(0, CRM_Pledge_BAO_Pledge::pledgeHasFinancialTransactions($pledge['id'], array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'))));
    $this->assertEquals('Pending', $payment['status']);
    $this->assertEquals($payment['scheduled_date'], date('Y-m-d 00:00:00', strtotime($scheduledDate)));
  }

  /**
   *  Test that payment retrieve wrks based on known pledge id.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRetrieveKnownPledgeID(): void {
    $params = [
      'contact_id' => $this->ids['Contact'][0],
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

    $pledge = $this->callAPISuccess('Pledge', 'create', $params);

    $defaults = [];
    $pledgeParams = ['pledge_id' => $pledge['id']];

    $pledgeId = CRM_Pledge_BAO_Pledge::retrieve($pledgeParams, $defaults);

    $this->assertEquals(1, $pledgeId->N, "Pledge was retrieved");
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
