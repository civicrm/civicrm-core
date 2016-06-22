<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    $params = array(
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
    );

    //do test for normal add.
    $pledge = CRM_Pledge_BAO_Pledge::add($params);

    foreach ($params as $param => $value) {
      $this->assertEquals($value, $pledge->$param);
    }
  }

  /**
   *  Retrieve a pledge based on a pledge id = 0
   */
  public function testRetrieveZeroPledeID() {
    $defaults = array();
    $params = array('pledge_id' => 0);
    $pledgeId = CRM_Pledge_BAO_Pledge::retrieve($params, $defaults);

    $this->assertEquals(count($pledgeId), 0, "Pledge Id must be greater than 0");
  }

  /**
   *  Retrieve a payment based on a Null pledge id random string.
   */
  public function testRetrieveStringPledgeID() {
    $defaults = array();
    $params = array('pledge_id' => 'random text');
    $pledgeId = CRM_Pledge_BAO_Pledge::retrieve($params, $defaults);

    $this->assertEquals(count($pledgeId), 0, "Pledge Id must be a string");
  }

  /**
   *  Test that payment retrieve wrks based on known pledge id.
   */
  public function testRetrieveKnownPledgeID() {
    $params = array(
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
    );

    $pledge = CRM_Pledge_BAO_Pledge::add($params);

    $defaults = array();
    $pledgeParams = array('pledge_id' => $pledge->id);

    $pledgeId = CRM_Pledge_BAO_Pledge::retrieve($pledgeParams, $defaults);

    $this->assertEquals(count($pledgeId), 1, "Pledge was retrieved");
  }

  /**
   *  Test create recur record.
   */
  public function testCreateRecurRecord() {
    //when user creating pledge record.
    $contributionPageId = ContributionPage::create();
    $processorId = $this->paymentProcessorAuthorizeNetCreate(array('is_test' => 0));
    $contribution = $this->contributionCreate(array('contact_id' => $this->_contactId));
    $pledgeParams = array(
      'contact_id' => $this->_contactId,
      'installment_amount' => 100,
      'actual_amount' => 100,
      'contribution_id' => $contribution,
      'contribution_page_id' => $contributionPageId,
      'financial_type_id' => 1,
      'frequency_interval' => 1,
      'installments' => 3,
      'frequency_unit' => 'month',
      'frequency_day' => intval(date("d")),
      'start_date' => date("Ymd"),
      'create_date' => date("Ymd"),
      'scheduled_date' => date("Ymd"),
      'status_id' => 1,
      'max_reminders' => 1,
      'initial_reminder_day' => 1,
      'additional_reminder_day' => 1,
      'original_installment_amount' => 100,
    );

    $pledge = CRM_Pledge_BAO_Pledge::create($pledgeParams);
    $params = array(
      'invoiceID' => '123456789',
      'payment_processor_id' => $processorId,
      'financial_type_id' => 1,
    );
    $recurRecord = CRM_Pledge_BAO_Pledge::createRecurRecord($pledge, $params, CRM_Core_DAO::$_nullObject);
    $this->assertAPISuccess($recurRecord);
    $recur = CRM_Contribute_BAO_ContributionRecur::getRecurContributions($this->_contactId);
    foreach ($recurRecord['values'] as $key => $values) {
      foreach ($values as $k => $value) {
        if ($k == 'contact_id') {
          $k = 'contactId';
        }
        if ($k == 'start_date') {
          $recur[$key][$k] = date('YmdHis', strtotime($recur[$key][$k]));
        }
        if ($k == 'is_test') {
          continue;
        }
        if (isset($recur[$key][$k])) {
          $this->assertEquals($value, $recur[$key][$k], "Recur information does not match");
        }
      }
    }
  }

}
