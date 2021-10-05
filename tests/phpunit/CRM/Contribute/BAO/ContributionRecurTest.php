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

use Civi\Api4\ContributionRecur;

/**
 * Class CRM_Contribute_BAO_ContributionRecurTest
 * @group headless
 */
class CRM_Contribute_BAO_ContributionRecurTest extends CiviUnitTestCase {

  use CRMTraits_Financial_OrderTrait;

  protected $isValidateFinancialsOnPostAssert = TRUE;

  /**
   * Set up for test.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->ids['payment_processor'] = $this->paymentProcessorCreate();
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
      'payment_processor_id' => $this->ids['payment_processor'],
      'is_email_receipt' => 1,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'campaign_id' => NULL,
    ];
  }

  /**
   * Cleanup after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function teardown():void {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test that an object can be retrieved & saved (per CRM-14986).
   *
   * This has been causing a DB error so we are checking for absence of error
   *
   * @throws \CRM_Core_Exception
   */
  public function testFindSave(): void {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testCancelRecur(): void {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution(['id' => $contributionRecur['id']]);
  }

  /**
   * Test checking if contribution recur object can allow for changes to financial types.
   *
   * @throws \CRM_Core_Exception|\CiviCRM_API3_Exception
   */
  public function testSupportFinancialTypeChange(): void {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdateRecur(): void {
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testGetTemplateContributionMatchTest1(): void {
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testGetTemplateContributionMatchTest(): void {
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
   * Check whether template contribution is created based on the first contribution.
   *
   * There are three contributions created. Each of them with a different value at a custom field.
   * The first contribution created should be copied as a template contribution.
   * The other two should not be used as a template.
   *
   * Then we delete the template contribution and make sure a new one exists.
   * At that time the second contribution should be used a template as that is the most recent one (according to the date).
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testCreateTemplateContributionFromFirstContributionTest(): void {
    $custom_group = $this->customGroupCreate(['extends' => 'Contribution', 'name' => 'template']);
    $custom_field = $this->customFieldCreate(['custom_group_id' => $custom_group['id'], 'name' => 'field']);

    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    // Create a first test contrib
    $date = new DateTime();
    $firstContrib = $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->_params['contact_id'],
      'contribution_status_id' => 1,
      'receive_date' => $date->format('YmdHis'),
      'custom_' . $custom_field['id'] => 'First Contribution',
    ]);
    $date->modify('+2 days');
    $secondContrib = $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->_params['contact_id'],
      'contribution_status_id' => 1,
      'receive_date' => $date->format('YmdHis'),
      'custom_' . $custom_field['id'] => 'Second and most recent Contribution',
    ]);

    $date->modify('-1 week');
    $thirdContrib = $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->_params['contact_id'],
      'contribution_status_id' => 1,
      'receive_date' => $date->format('YmdHis'),
      'custom_' . $custom_field['id'] => 'Third Contribution',
    ]);

    // Make sure a template contribution exists.
    $templateContributionId = CRM_Contribute_BAO_ContributionRecur::ensureTemplateContributionExists($contributionRecur['id']);
    $fetchedTemplate = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($contributionRecur['id']);
    $templateContribution = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('*', 'custom.*')
      ->addWhere('contribution_recur_id', '=', $contributionRecur['id'])
      ->addWhere('is_template', '=', 1)
      ->addWhere('is_test', '=', 0)
      ->addOrderBy('id', 'DESC')
      ->execute();

    $this->assertNotEquals($firstContrib['id'], $fetchedTemplate['id']);
    $this->assertNotEquals($secondContrib['id'], $fetchedTemplate['id']);
    $this->assertNotEquals($thirdContrib['id'], $fetchedTemplate['id']);
    $this->assertEquals($templateContributionId, $fetchedTemplate['id']);
    $this->assertTrue($fetchedTemplate['is_template']);
    $this->assertFalse($fetchedTemplate['is_test']);
    $this->assertEquals(1, $templateContribution->count());
    $templateContribution = $templateContribution->first();
    $this->assertNotNull($templateContribution['template.field']);
    $this->assertEquals('Second and most recent Contribution', $templateContribution['template.field']);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $custom_field['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $custom_group['id']]);
  }

  /**
   * Test that is_template contribution is used where available
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testGetTemplateContributionNewTemplate(): void {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    // Create the template
    $templateContrib = $this->callAPISuccess('Contribution', 'create', [
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => '3.00',
      'financial_type_id' => 1,
      'source' => 'Template Contribution',
      'payment_instrument_id' => 1,
      'currency' => 'AUD',
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
      'source' => 'Non-template Contribution',
      'payment_instrument_id' => 1,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 1,
      'receive_date' => 'yesterday',
    ]);
    $fetchedTemplate = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($contributionRecur['id']);
    // Fetched template should be the is_template, not the latest contrib
    $this->assertEquals($fetchedTemplate['id'], $templateContrib['id']);

    $repeatContribution = $this->callAPISuccess('Contribution', 'repeattransaction', [
      'contribution_status_id' => 'Completed',
      'contribution_recur_id' => $contributionRecur['id'],
    ]);
    $this->assertEquals('Template Contribution', $repeatContribution['values'][$repeatContribution['id']]['source']);
    $this->assertEquals('AUD', $repeatContribution['values'][$repeatContribution['id']]['currency']);
  }

  /**
   * Test to check if correct membership is auto renewed.
   *
   * @throws \CRM_Core_Exception|\CiviCRM_API3_Exception
   */
  public function testAutoRenewalWhenOneMemberIsDeceased(): void {
    $contactId1 = $this->individualCreate();
    $contactId2 = $this->individualCreate();
    $membershipOrganizationId = $this->organizationCreate();

    $this->createExtraneousContribution();
    $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contactId1,
      'receive_date' => '2010-01-20',
      'financial_type_id' => 'Member Dues',
      'contribution_status_id' => 'Completed',
      'total_amount' => 150,
    ]);

    // create membership type
    $membershipTypeId1 = (int) $this->callAPISuccess('MembershipType', 'create', [
      'domain_id' => 1,
      'member_of_contact_id' => $membershipOrganizationId,
      'financial_type_id' => 'Member Dues',
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'minimum_fee' => 100,
      'name' => 'Parent',
    ])['id'];

    $membershipTypeID = (int) $this->callAPISuccess('MembershipType', 'create', [
      'domain_id' => 1,
      'member_of_contact_id' => $membershipOrganizationId,
      'financial_type_id' => 'Member Dues',
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'minimum_fee' => 50,
      'name' => 'Child',
    ])['id'];

    $contactIDs = [
      $contactId1 => $membershipTypeId1,
      $contactId2 => $membershipTypeID,
    ];

    $contributionRecurId = $this->callAPISuccess('contribution_recur', 'create', $this->_params)['id'];

    $priceFields = CRM_Price_BAO_PriceSet::getDefaultPriceSet('membership');

    // prepare order api params.
    $params = [
      'contact_id' => $contactId1,
      'receive_date' => '2010-01-20',
      'financial_type_id' => 'Member Dues',
      'contribution_recur_id' => $contributionRecurId,
      'total_amount' => 150,
      'api.Payment.create' => ['total_amount' => 150],
    ];

    foreach ($priceFields as $priceField) {
      $lineItems = [];
      $contactId = array_search((int) $priceField['membership_type_id'], $contactIDs, TRUE);
      $lineItems[1] = [
        'price_field_id' => $priceField['priceFieldID'],
        'price_field_value_id' => $priceField['priceFieldValueID'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
        'entity_table' => 'civicrm_membership',
        'membership_type_id' => $priceField['membership_type_id'],
      ];
      $params['line_items'][] = [
        'line_item' => $lineItems,
        'params' => [
          'contact_id' => $contactId,
          'membership_type_id' => $priceField['membership_type_id'],
          'source' => 'Payment',
          'join_date' => date('Y-m', strtotime('1 month ago')) . '-28',
          'start_date' => date('Y-m') . '-28',
          'contribution_recur_id' => $contributionRecurId,
          'status_id' => 'Pending',
          'is_override' => 1,
        ],
      ];
    }
    $order = $this->callAPISuccess('Order', 'create', $params);
    $contributionId = $order['id'];
    $membershipId1 = $this->callAPISuccessGetValue('Membership', [
      'contact_id' => $contactId1,
      'membership_type_id' => $membershipTypeId1,
      'return' => 'id',
    ]);

    $membershipId2 = $this->callAPISuccessGetValue('Membership', [
      'contact_id' => $contactId2,
      'membership_type_id' => $membershipTypeID,
      'return' => 'id',
    ]);

    // First renewal (2nd payment).
    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $contributionId,
      'contribution_status_id' => 'Completed',
    ]);

    // Second Renewal (3rd payment).
    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $contributionId,
      'contribution_status_id' => 'Completed',
    ]);

    // Third renewal (4th payment).
    $this->callAPISuccess('Contribution', 'repeattransaction', ['original_contribution_id' => $contributionId, 'contribution_status_id' => 'Completed']);

    // check line item and membership payment count.
    $this->validateAllCounts($membershipId1, 4);
    $this->validateAllCounts($membershipId2, 4);

    $expectedDate = $this->getYearAndMonthFromOffset(4);
    // check membership end date.
    foreach ([$membershipId1, $membershipId2] as $mId) {
      $endDate = $this->callAPISuccessGetValue('Membership', [
        'id' => $mId,
        'return' => 'end_date',
      ]);
      $this->assertEquals("{$expectedDate['year']}-{$expectedDate['month']}-27", $endDate, ts('End date incorrect.'));
    }

    // At this moment Contact 2 is deceased, but we wait until payment is recorded in civi before marking the contact deceased.
    // At payment Gateway we update the amount from 150 to 100
    // IPN is recorded for subsequent payment (5th payment).
    $contribution = $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $contributionId,
      'contribution_status_id' => 'Completed',
      'total_amount' => '100',
    ]);

    // now we mark the contact2 as deceased.
    $this->callAPISuccess('Contact', 'create', [
      'id' => $contactId2,
      'is_deceased' => 1,
    ]);

    // set membership recurring to null.
    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId2,
      'contribution_recur_id' => NULL,
    ]);

    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution['id']]);
    unset($params['line_items'][1]);
    $params['total_amount'] = 100;
    $params['line_items'][0]['params']['id'] = $membershipId1;
    $params['api.Payment.create']['total_amount'] = 100;

    $order = $this->callAPISuccess('Order', 'create', $params);

    // check line item and membership payment count.
    $this->validateAllCounts($membershipId1, 5);
    $this->validateAllCounts($membershipId2, 4);

    $checkAgainst = $this->callAPISuccessGetSingle('Membership', [
      'id' => $membershipId2,
      'return' => ['end_date', 'status_id'],
    ]);

    // record next subsequent payment (6th payment).
    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $order['id'],
      'contribution_status_id' => 'Completed',
      'total_amount' => '100',
    ]);

    // check membership id 1 is renewed
    $endDate = $this->callAPISuccessGetValue('Membership', [
      'id' => $membershipId1,
      'return' => 'end_date',
    ]);
    $expectedDate = $this->getYearAndMonthFromOffset(6);
    $this->assertEquals("{$expectedDate['year']}-{$expectedDate['month']}-27", $endDate, ts('End date incorrect.'));
    // check line item and membership payment count.
    $this->validateAllCounts($membershipId1, 6);
    $this->validateAllCounts($membershipId2, 4);

    // check if membership status and end date is not changed.
    $membership2 = $this->callAPISuccessGetSingle('Membership', [
      'id' => $membershipId2,
      'return' => ['end_date', 'status_id'],
    ]);
    $this->assertSame($membership2, $checkAgainst);
  }

  /**
   * Check line item and membership payment count.
   *
   * @param int $membershipId
   * @param int $count
   *
   * @throws \CRM_Core_Exception
   */
  public function validateAllCounts(int $membershipId, int $count): void {
    $memPayParams = [
      'membership_id' => $membershipId,
    ];
    $lineItemParams = [
      'entity_id' => $membershipId,
      'entity_table' => 'civicrm_membership',
      'contribution_id' => ['>' => 0],
    ];
    $this->callAPISuccessGetCount('LineItem', $lineItemParams, $count);
    $this->callAPISuccessGetCount('MembershipPayment', $memPayParams, $count);
  }

  /**
   * Given a number of months offset, get the year and month.
   * Note the way php arithmetic works, using strtotime('+x months') doesn't
   * work because it will roll over the day accounting for different number
   * of days in the month, but we want the same day of the month, x months
   * from now.
   * e.g. July 31 + 4 months will return Dec 1 if using php functions, but
   * we want Nov 31.
   *
   * @param int $offset
   * @param int|null $year Optional input year to start
   * @param int|null $month Optional input month to start
   *
   * @return array
   *   ['year' => int, 'month' => int]
   */
  private function getYearAndMonthFromOffset(int $offset, int $year = NULL, int $month = NULL): array {
    $dateInfo = [
      'year' => $year ?? (int) date('Y'),
      'month' => ($month ?? (int) date('m')) + $offset,
    ];
    if ($dateInfo['month'] > 12) {
      $dateInfo['year']++;
      $dateInfo['month'] -= 12;
    }
    if ($dateInfo['month'] < 10) {
      $dateInfo['month'] = "0{$dateInfo['month']}";
    }

    return $dateInfo;
  }

  /**
   * Test getYearAndMonthFromOffset
   *
   * @dataProvider yearMonthProvider
   *
   * @param array $input
   * @param array $expected
   */
  public function testGetYearAndMonthFromOffset(array $input, array $expected): void {
    $this->assertEquals($expected, $this->getYearAndMonthFromOffset($input[0], $input[1], $input[2]));
  }

  /**
   * data provider for testGetYearAndMonthFromOffset
   */
  public function yearMonthProvider(): array {
    return [
      // input = offset, year, current month
      ['input' => [4, 2020, 1], 'output' => ['year' => '2020', 'month' => '05']],
      ['input' => [6, 2020, 1], 'output' => ['year' => '2020', 'month' => '07']],
      ['input' => [4, 2020, 2], 'output' => ['year' => '2020', 'month' => '06']],
      ['input' => [6, 2020, 2], 'output' => ['year' => '2020', 'month' => '08']],
      ['input' => [4, 2020, 3], 'output' => ['year' => '2020', 'month' => '07']],
      ['input' => [6, 2020, 3], 'output' => ['year' => '2020', 'month' => '09']],
      ['input' => [4, 2020, 4], 'output' => ['year' => '2020', 'month' => '08']],
      ['input' => [6, 2020, 4], 'output' => ['year' => '2020', 'month' => '10']],
      ['input' => [4, 2020, 5], 'output' => ['year' => '2020', 'month' => '09']],
      ['input' => [6, 2020, 5], 'output' => ['year' => '2020', 'month' => '11']],
      ['input' => [4, 2020, 6], 'output' => ['year' => '2020', 'month' => '10']],
      ['input' => [6, 2020, 6], 'output' => ['year' => '2020', 'month' => '12']],
      ['input' => [4, 2020, 7], 'output' => ['year' => '2020', 'month' => '11']],
      ['input' => [6, 2020, 7], 'output' => ['year' => '2021', 'month' => '01']],
      ['input' => [4, 2020, 8], 'output' => ['year' => '2020', 'month' => '12']],
      ['input' => [6, 2020, 8], 'output' => ['year' => '2021', 'month' => '02']],
      ['input' => [4, 2020, 9], 'output' => ['year' => '2021', 'month' => '01']],
      ['input' => [6, 2020, 9], 'output' => ['year' => '2021', 'month' => '03']],
      ['input' => [4, 2020, 10], 'output' => ['year' => '2021', 'month' => '02']],
      ['input' => [6, 2020, 10], 'output' => ['year' => '2021', 'month' => '04']],
      ['input' => [4, 2020, 11], 'output' => ['year' => '2021', 'month' => '03']],
      ['input' => [6, 2020, 11], 'output' => ['year' => '2021', 'month' => '05']],
      ['input' => [4, 2020, 12], 'output' => ['year' => '2021', 'month' => '04']],
      ['input' => [6, 2020, 12], 'output' => ['year' => '2021', 'month' => '06']],
    ];
  }

  /**
   * Test Recurring Contribution Email Receipt Flag
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionEmailReceipt(): void {
    $createParams = $this->_params;
    unset($createParams['trxn_id'], $createParams['invoice_id']);

    // pass null value to is_email_receipt
    $createParams['is_email_receipt'] = NULL;
    $recurring1 = $this->callAPISuccess('ContributionRecur', 'create', $createParams);
    $recurring1Get = $this->callAPISuccess('ContributionRecur', 'getsingle', ['id' => $recurring1['id']]);
    // default is_email_receipt column value is 1
    $this->assertEquals('1', $recurring1Get['is_email_receipt']);

    // pass empty value to is_email_receipt
    $createParams['is_email_receipt'] = '';
    $recurring2 = $this->callAPISuccess('ContributionRecur', 'create', $createParams);
    $recurring2 = ContributionRecur::get(FALSE)->addWhere('id', '=', $recurring2['id'])->addSelect('is_email_receipt')->execute()->first();
    $this->assertEquals(NULL, $recurring2['is_email_receipt']);

    // Pass 0 value to is_email_receipt.
    $createParams['is_email_receipt'] = 0;
    $recurring3 = $this->callAPISuccess('ContributionRecur', 'create', $createParams);
    $recurring3Get = $this->callAPISuccess('ContributionRecur', 'getsingle', ['id' => $recurring3['id']]);
    $this->assertEquals('0', $recurring3Get['is_email_receipt']);
  }

}
