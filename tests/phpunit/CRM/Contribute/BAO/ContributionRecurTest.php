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
 * Class CRM_Contribute_BAO_ContributionRecurTest
 * @group headless
 */
class CRM_Contribute_BAO_ContributionRecurTest extends CiviUnitTestCase {
  protected $_params = [];

  public function setUp() {
    parent::setUp();
    $this->_ids['payment_processor'] = $this->paymentProcessorCreate();
    $this->_params = [
      'contact_id' => $this->individualCreate(),
      'amount' => 3.00,
      'frequency_unit' => 'week',
      'frequency_interval' => 1,
      'installments' => 2,
      'start_date' => 'yesterday',
      'create_date' => 'yesterday',
      'modified_date' => 'yesterday',
      'cancel_date' => NULL,
      'end_date' => '+ 2 weeks',
      'processor_id' => '643411460836',
      'trxn_id' => 'e0d0808e26f3e661c6c18eb7c039d363',
      'invoice_id' => 'e0d0808e26f3e661c6c18eb7c039d363',
      'contribution_status_id' => 1,
      'is_test' => 0,
      'cycle_day' => 1,
      'next_sched_contribution_date' => '+ 1 week',
      'failure_count' => 0,
      'failure_retry_date' => NULL,
      'auto_renew' => 0,
      'currency' => 'USD',
      'payment_processor_id' => $this->_ids['payment_processor'],
      'is_email_receipt' => 1,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'campaign_id' => NULL,
    ];
  }

  public function teardown() {
    $this->quickCleanup(['civicrm_contribution_recur', 'civicrm_payment_processor']);
  }

  /**
   * Test that an object can be retrieved & saved (per CRM-14986).
   *
   * This has been causing a DB error so we are checking for absence of error
   */
  public function testFindSave() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    $dao = new CRM_Contribute_BAO_ContributionRecur();
    $dao->id = $contributionRecur['id'];
    $dao->find(TRUE);
    $dao->is_email_receipt = 0;
    $dao->save();
  }

  /**
   * Test cancellation works per CRM-14986.
   *
   * We are checking for absence of error.
   */
  public function testCancelRecur() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution(['id' => $contributionRecur['id']]);
  }

  /**
   * Test checking if contribution recur object can allow for changes to financial types.
   *
   */
  public function testSupportFinancialTypeChange() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
    ]);
    $this->assertTrue(CRM_Contribute_BAO_ContributionRecur::supportsFinancialTypeChange($contributionRecur['id']));
  }

  /**
   * Test we don't change unintended fields on API edit
   */
  public function testUpdateRecur() {
    $createParams = $this->_params;
    $createParams['currency'] = 'XAU';
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $createParams);
    $editParams = [
      'id' => $contributionRecur['id'],
      'end_date' => '+ 4 weeks',
    ];
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $editParams);
    $dao = new CRM_Contribute_BAO_ContributionRecur();
    $dao->id = $contributionRecur['id'];
    $dao->find(TRUE);
    $this->assertEquals('XAU', $dao->currency, 'Edit clobbered recur currency');
  }

  /**
   * Check test contributions aren't picked up as template for non-test recurs
   *
   */
  public function testGetTemplateContributionMatchTest1() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    // Create a first contrib
    $firstContrib = $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
    ]);
    // Create a test contrib - should not be picked up as template for non-test recur
    $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
      'is_test' => 1,
    ]);
    $fetchedTemplate = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($contributionRecur['id']);
    $this->assertEquals($firstContrib['id'], $fetchedTemplate['id']);
  }

  /**
   * Check non-test contributions aren't picked up as template for test recurs
   *
   */
  public function testGetTemplateContributionMatchTest() {
    $params = $this->_params;
    $params['is_test'] = 1;
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $params);
    // Create a first test contrib
    $firstContrib = $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
      'is_test' => 1,
    ]);
    // Create a non-test contrib - should not be picked up as template for non-test recur
    // This shouldn't occur - a live contrib against a test recur, but that's not the point...
    $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
      'is_test' => 0,
    ]);
    $fetchedTemplate = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($contributionRecur['id']);
    $this->assertEquals($firstContrib['id'], $fetchedTemplate['id']);
  }

  /**
   * Test that is_template contribution is used where available
   *
   */
  public function testGetTemplateContributionNewTemplate() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    // Create the template
    $templateContrib = $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
      'is_template' => 1,
    ]);
    // Create another normal contrib
    $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
    ]);
    $fetchedTemplate = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($contributionRecur['id']);
    // Fetched template should be the is_template, not the latest contrib
    $this->assertEquals($fetchedTemplate['id'], $templateContrib['id']);
  }

}
