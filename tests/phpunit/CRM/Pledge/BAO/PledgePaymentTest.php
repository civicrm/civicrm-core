<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Test class for CRM_Pledge_BAO_PledgePayment BAO
 *
 * @package   CiviCRM
 */
class CRM_Pledge_BAO_PledgePaymentTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
  }

  /**
   *  Test for Add/Update Pledge Payment.
   */
  public function testAdd() {
    $pledge = CRM_Core_DAO::createTestObject('CRM_Pledge_BAO_Pledge');
    $params = array(
      'pledge_id' => $pledge->id,
      'scheduled_amount' => 100.55,
      'currency' => 'USD',
      'scheduled_date' => '20100512000000',
      'reminder_date' => '20100520000000',
      'reminder_count' => 5,
      'status_id' => 1,
    );

    //do test for normal add.
    $payment = CRM_Pledge_BAO_PledgePayment::add($params);
    foreach ($params as $param => $value) {
      $this->assertEquals($value, $payment->$param);
    }

    //do test for update mode.
    $params = array(
      'id' => $payment->id,
      'pledge_id' => $pledge->id,
      'scheduled_amount' => 55.55,
      'currency' => 'USD',
      'scheduled_date' => '20100415000000',
      'reminder_date' => '20100425000000',
      'reminder_count' => 10,
      'status_id' => 2,
    );

    $payment = CRM_Pledge_BAO_PledgePayment::add($params);
    foreach ($params as $param => $value) {
      $this->assertEquals($value, $payment->$param);
    }
    $result = CRM_Pledge_BAO_Pledge::deletePledge($pledge->id);
  }

  /**
   *  Retrieve a payment based on a pledge id = 0
   */
  public function testRetrieveZeroPledeID() {
    $payment = CRM_Core_DAO::createTestObject('CRM_Pledge_BAO_PledgePayment');
    $params = array('pledge_id' => 0);
    $defaults = array();
    $paymentid = CRM_Pledge_BAO_PledgePayment::retrieve($params, $defaults);

    $this->assertEquals(count($paymentid), 0, "Pledge Id must be greater than 0");
    $result = CRM_Pledge_BAO_Pledge::deletePledge($payment->pledge_id);
  }

  /**
   *  Retrieve a payment based on a Null pledge id.
   */
  public function testRetrieveStringPledgeID() {
    $payment = CRM_Core_DAO::createTestObject('CRM_Pledge_BAO_PledgePayment');
    $params = array('pledge_id' => 'Test');
    $defaults = array();
    $paymentid = CRM_Pledge_BAO_PledgePayment::retrieve($params, $defaults);

    $this->assertEquals(count($paymentid), 0, "Pledge Id cannot be a string");
    $result = CRM_Pledge_BAO_Pledge::deletePledge($payment->pledge_id);
  }

  /**
   *  Test that payment retrieve wrks based on known pledge id.
   */
  public function testRetrieveKnownPledgeID() {
    $payment = CRM_Core_DAO::createTestObject('CRM_Pledge_BAO_PledgePayment');
    $pledgeId = $payment->pledge_id;
    $params = array('pledge_id' => $pledgeId);
    $defaults = array();
    $paymentid = CRM_Pledge_BAO_PledgePayment::retrieve($params, $defaults);

    $this->assertEquals(count($paymentid), 1, "Pledge was retrieved");
    $result = CRM_Pledge_BAO_Pledge::deletePledge($pledgeId);
  }

  /**
   *  Delete Payments payments for one pledge.
   */
  public function testDeletePledgePaymentsNormal() {
    $payment = CRM_Core_DAO::createTestObject('CRM_Pledge_BAO_PledgePayment');
    $paymentid = CRM_Pledge_BAO_PledgePayment::deletePayments($payment->pledge_id);
    $this->assertEquals(count($paymentid), 1, "Deleted one payment");
    $result = CRM_Pledge_BAO_Pledge::deletePledge($payment->pledge_id);
  }

  /**
   *  Pass Null Id for a payment deletion for one pledge.
   */
  public function testDeletePledgePaymentsNullId() {
    $payment = CRM_Core_DAO::createTestObject('CRM_Pledge_BAO_PledgePayment');
    $paymentid = CRM_Pledge_BAO_PledgePayment::deletePayments(NULL);
    $this->assertEquals(count($paymentid), 1, "No payments deleted");
    $result = CRM_Pledge_BAO_Pledge::deletePledge($payment->pledge_id);
  }

  /**
   *  Pass Zero Id for a payment deletion for one pledge.
   */
  public function testDeletePaymentsZeroId() {
    $payment = CRM_Core_DAO::createTestObject('CRM_Pledge_BAO_PledgePayment');
    $paymentid = CRM_Pledge_BAO_PledgePayment::deletePayments(0);
    $result = CRM_Pledge_BAO_Pledge::deletePledge($payment->pledge_id);
  }

  /**
   *  Test calculateBaseScheduleDate - should give 15th day of month
   */
  public function testcalculateBaseScheduleDateMonth() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'month',
      'frequency_day' => 15,
      'frequency_interval' => 2,
    );

    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110515000000', $date);
  }

  /**
   *  Test calculateBaseScheduleDate - should give original date
   */
  public function testcalculateBaseScheduleDateDay() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'day',
      'frequency_day' => 15,
      'frequency_interval' => 2,
    );

    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110510000000', $date);
  }

  /**
   * Test calculateBaseScheduleDateWeek - should give the day in the week as indicated
   * testing each day as this is really the only unit that does anything
   */
  public function testcalculateBaseScheduleDateWeek() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'week',
      'frequency_day' => 1,
      'frequency_interval' => 2,
    );

    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110509000000', $date);
    $params['frequency_day'] = 2;
    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110510000000', $date);
    $params['frequency_day'] = 3;
    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110511000000', $date);
    $params['frequency_day'] = 4;
    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110512000000', $date);
    $params['frequency_day'] = 5;
    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110513000000', $date);
    $params['frequency_day'] = 6;
    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110514000000', $date);
    $params['frequency_day'] = 7;
    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110515000000', $date);
  }

  /**
   *  Test calculateBaseScheduleDate - should give original date
   */
  public function testcalculateBaseScheduleDateYear() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'frequency_interval' => 2,
    );

    $date = CRM_Pledge_BAO_PledgePayment::calculateBaseScheduleDate($params);
    $this->assertEquals('20110510000000', $date);
  }

  /**
   *  Test calculateNextScheduledDate - no date provided
   */
  public function testcalculateNextScheduledDateYear() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'frequency_interval' => 2,
    );

    $date = CRM_Pledge_BAO_PledgePayment::calculateNextScheduledDate($params, 1);
    $this->assertEquals('20130510000000', $date);
  }

  /**
   *  Test calculateNextScheduledDate - no date provided
   */
  public function testcalculateNextScheduledDateYearDateProvided() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'frequency_interval' => 2,
    );

    $date = CRM_Pledge_BAO_PledgePayment::calculateNextScheduledDate($params, 3, '20080510');
    $this->assertEquals('20140510000000', $date);
  }

}
