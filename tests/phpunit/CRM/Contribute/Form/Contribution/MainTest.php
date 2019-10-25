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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
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
    $membershipTypeID = $this->membershipTypeCreate(['auto_renew' => 2, 'minimum_fee' => 80]);
    $form = $this->getContributionForm();
    $form->testSubmit([
      'selectMembership' => $membershipTypeID,
    ]);
    $this->assertEquals(1, $form->_params['is_recur']);
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunctionOptionalYes() {
    $membershipTypeID = $this->membershipTypeCreate(['auto_renew' => 1, 'minimum_fee' => 80]);
    $form = $this->getContributionForm();
    $form->testSubmit([
      'selectMembership' => $membershipTypeID,
      'is_recur' => 1,
    ]);
    $this->assertEquals(1, $form->_params['is_recur']);
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunctionOptionalNo() {
    $membershipTypeID = $this->membershipTypeCreate(['auto_renew' => 1, 'minimum_fee' => 80]);
    $form = $this->getContributionForm();
    $form->testSubmit([
      'selectMembership' => $membershipTypeID,
      'is_recur' => 0,
    ]);
    $this->assertEquals(0, $form->_params['is_recur']);
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunctionNotAvailable() {
    $membershipTypeID = $this->membershipTypeCreate(['auto_renew' => 0, 'minimum_fee' => 80]);
    $form = $this->getContributionForm();
    $form->testSubmit([
      'selectMembership' => $membershipTypeID,
    ]);
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
    $form->_priceSetId = $this->callAPISuccessGetValue('PriceSet', [
      'name' => 'default_membership_type_amount',
      'return' => 'id',
    ]);
    $priceFields = $this->callAPISuccess('PriceField', 'get', ['id' => $form->_priceSetId]);
    $form->_priceSet['fields'] = $priceFields['values'];
    $paymentProcessorID = $this->paymentProcessorCreate(['payment_processor_type_id' => 'Dummy']);
    $form->_paymentProcessor = [
      'billing_mode' => CRM_Core_Payment::BILLING_MODE_FORM,
      'object' => Civi\Payment\System::singleton()->getById($paymentProcessorID),
      'is_recur' => TRUE,
    ];
    $form->_values = [
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => 6000,
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'pay_later_text' => 'Front up',
      'pay_later_receipt' => 'Ta',
    ];
    return $form;
  }

  /**
   * Test expired priceset are not returned from buildPriceSet() Function
   */
  public function testExpiredPriceSet() {
    $form = $this->getContributionForm();
    $priceSetParams1 = [
      'name' => 'priceset',
      'title' => 'Priceset with Multiple Terms',
      'is_active' => 1,
      'extends' => 3,
      'financial_type_id' => 2,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];
    $priceSet = $this->callAPISuccess('price_set', 'create', $priceSetParams1);
    $form->_priceSetId = $priceSet['id'];

    $form->controller = new CRM_Core_Controller();
    $form->set('priceSetId', $form->_priceSetId);
    $params = [
      'price_set_id' => $form->_priceSetId,
      'name' => 'testvalidpf',
      'label' => 'test valid pf',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField1 = $this->callAPISuccess('PriceField', 'create', $params);

    //Create expired price field.
    $params = [
      'price_set_id' => $form->_priceSetId,
      'name' => 'testexpiredpf',
      'label' => 'test expired pf',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
      'expire_on' => date('Y-m-d', strtotime("-1 days")),
    ];
    $priceField2 = $this->callAPISuccess('PriceField', 'create', $params);

    //Create price options.
    $membershipOrgId = $this->organizationCreate(NULL);
    $memtype = $this->membershipTypeCreate(['member_of_contact_id' => $membershipOrgId]);
    foreach ([$priceField1, $priceField2] as $priceField) {
      $priceFieldValueParams = [
        'price_field_id' => $priceField['id'],
        'name' => 'rye grass',
        'membership_type_id' => $memtype,
        'label' => 'juicy and healthy',
        'amount' => 1,
        'membership_num_terms' => 2,
        'financial_type_id' => 1,
      ];
      $this->callAPISuccess('PriceFieldValue', 'create', $priceFieldValueParams);
    }

    $priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSet['id']));
    $form->_values['fee'] = $form->_feeBlock = $priceSet['fields'];
    foreach ($priceSet['fields'] as $pField) {
      foreach ($pField['options'] as $opId => $opValues) {
        $membershipTypeIds[$opValues['membership_type_id']] = $opValues['membership_type_id'];
      }
    }
    $form->_membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($form, $membershipTypeIds);

    //This function should not update form priceSet with the expired one.
    CRM_Price_BAO_PriceSet::buildPriceSet($form);

    $this->assertEquals(count($form->_priceSet['fields']), 1);
    $field = current($form->_priceSet['fields']);
    $this->assertEquals($field['name'], 'testvalidpf');
  }

}
