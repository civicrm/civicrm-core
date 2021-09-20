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
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
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
  protected function getContributionForm($params = []) {
    $params['priceSetID'] = $params['priceSetID'] ?? $this->callAPISuccessGetValue('PriceSet', [
      'name' => 'default_membership_type_amount',
      'return' => 'id',
    ]);

    $contributionPageParams = (array_merge($params, [
      'currency' => 'NZD',
      'goal_amount' => 6000,
      'is_pay_later' => 0,
      'is_monetary' => 1,
      'pay_later_text' => 'Front up',
      'pay_later_receipt' => 'Ta',
      'is_email_receipt' => 1,
      'payment_processor' => $this->paymentProcessorCreate([
        'payment_processor_type_id' => 'Dummy',
        'is_test' => 0,
      ]),
      'amount_block_is_active' => 1,
    ]));

    /** @var \CRM_Contribute_Form_Contribution_Main $form */
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution_Main');
    $contributionPage = reset($this->contributionPageCreate($contributionPageParams)['values']);
    $form->set('id', $contributionPage['id']);
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPage['id'], $params['priceSetID']);
    $form->preProcess();
    $form->buildQuickForm();
    return $form;
  }

  /**
   * Test expired priceset are not returned from buildPriceSet() Function
   */
  public function testExpiredPriceSet() {
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

    // Create valid price field.
    $params = [
      'price_set_id' => $priceSet['id'],
      'name' => 'testvalidpf',
      'label' => 'test valid pf',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField1 = $this->callAPISuccess('PriceField', 'create', $params);

    // Create expired price field.
    $params = [
      'price_set_id' => $priceSet['id'],
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

    $form = $this->getContributionForm(['priceSetID' => $priceSet['id']]);
    foreach ($form->_priceSet['fields'] as $pField) {
      foreach ($pField['options'] as $opId => $opValues) {
        $membershipTypeIds[$opValues['membership_type_id']] = $opValues['membership_type_id'];
      }
    }
    $form->_membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($form, $membershipTypeIds);

    //This function should not update form priceSet with the expired one.
    CRM_Price_BAO_PriceSet::buildPriceSet($form);

    $this->assertEquals(1, count($form->_priceSet['fields']));
    $field = current($form->_priceSet['fields']);
    $this->assertEquals('testvalidpf', $field['name']);
  }

}
