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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 */
class CRM_Contribute_Form_Contribution_MainTest extends CiviUnitTestCase {

  /**
   * Clean up DB.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunction() {
    $membershipTypeID = $this->membershipTypeCreate(array('auto_renew' => 2, 'minimum_fee' => 80));
    $form = $this->getContributionForm();
    $form->testSubmit(array(
      'selectMembership' => $membershipTypeID,
    ));
    $this->assertEquals(1, $form->_params['is_recur']);
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunctionOptionalYes() {
    $membershipTypeID = $this->membershipTypeCreate(array('auto_renew' => 1, 'minimum_fee' => 80));
    $form = $this->getContributionForm();
    $form->testSubmit(array(
      'selectMembership' => $membershipTypeID,
      'is_recur' => 1,
    ));
    $this->assertEquals(1, $form->_params['is_recur']);
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunctionOptionalNo() {
    $membershipTypeID = $this->membershipTypeCreate(array('auto_renew' => 1, 'minimum_fee' => 80));
    $form = $this->getContributionForm();
    $form->testSubmit(array(
      'selectMembership' => $membershipTypeID,
      'is_recur' => 0,
    ));
    $this->assertEquals(0, $form->_params['is_recur']);
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunctionNotAvailable() {
    $membershipTypeID = $this->membershipTypeCreate(array('auto_renew' => 0, 'minimum_fee' => 80));
    $form = $this->getContributionForm();
    $form->testSubmit(array(
      'selectMembership' => $membershipTypeID,
    ));
    $this->assertArrayNotHasKey('is_recur', $form->_params);
  }

  /**
   * Get a contribution form object for testing.
   *
   * @return \CRM_Contribute_Form_Contribution_Main
   */
  protected function getContributionForm() {
    $form = new CRM_Contribute_Form_Contribution_Main();
    $form->_values['is_monetary'] = 1;
    $form->_values['is_pay_later'] = 0;
    $form->_priceSetId = $this->callAPISuccessGetValue('PriceSet', array(
      'name' => 'default_membership_type_amount',
      'return' => 'id',
    ));
    $priceFields = $this->callAPISuccess('PriceField', 'get', array('id' => $form->_priceSetId));
    $form->_priceSet['fields'] = $priceFields['values'];
    $paymentProcessorID = $this->paymentProcessorCreate(array('payment_processor_type_id' => 'Dummy'));
    $form->_paymentProcessor = array(
      'billing_mode' => CRM_Core_Payment::BILLING_MODE_FORM,
      'object' => Civi\Payment\System::singleton()->getById($paymentProcessorID),
      'is_recur' => TRUE,
    );
    $form->_values = array(
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => 6000,
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'pay_later_text' => 'Front up',
      'pay_later_receipt' => 'Ta',
    );
    return $form;
  }

}
