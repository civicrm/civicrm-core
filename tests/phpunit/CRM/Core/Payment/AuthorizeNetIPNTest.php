<?php

use Civi\Api4\ContributionRecur;
use Civi\Api4\Contribution;

/**
 * Class CRM_Core_Payment_PayPalProIPNTest
 * @group headless
 */
class CRM_Core_Payment_AuthorizeNetIPNTest extends CiviUnitTestCase {
  use CRMTraits_Financial_OrderTrait;

  protected $_financialTypeID = 1;
  protected $_contactID;
  protected $_contributionRecurID;
  protected $_contributionPageID;
  protected $_paymentProcessorID;

  /**
   * Setup for test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->_paymentProcessorID = $this->paymentProcessorAuthorizeNetCreate(['is_test' => 0], 'test');
    $this->_contactID = $this->individualCreate();
    $contributionPage = $this->callAPISuccess('ContributionPage', 'create', [
      'title' => 'Test Contribution Page',
      'financial_type_id' => 'Donation',
      'currency' => 'USD',
      'payment_processor' => $this->ids['PaymentProcessor']['test'],
      'max_amount' => 1000,
      'receipt_from_email' => 'gaia@the.cosmos',
      'receipt_from_name' => 'Pachamama',
      'is_email_receipt' => TRUE,
    ]);
    $this->_contributionPageID = $this->ids['ContributionPage'][0] = $contributionPage['id'];
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_campaign']);
    $this->restoreMembershipTypes();
    parent::tearDown();
  }

  /**
   * Ensure recurring contributions from Contribution Pages
   * with receipt turned off don't send a receipt.
   *
   * @throws \CRM_Core_Exception
   */
  public function testIPNPaymentRecurNoReceipt(): void {
    $mut = new CiviMailUtils($this, TRUE);
    // Turn off receipts in contribution page.
    $api_params = [
      'id' => $this->ids['ContributionPage'][0],
      'is_email_receipt' => FALSE,
    ];
    $this->callAPISuccess('ContributionPage', 'update', $api_params);

    // Create initial recurring payment and initial contribution.
    // Note - we can't use setupRecurringPaymentProcessorTransaction(), which
    // would be convenient because it does not fully mimic the real user
    // experience. Using setupRecurringPaymentProcessorTransaction() doesn't
    // specify is_email_receipt so it is always set to 1. We need to more
    // closely mimic what happens with a live transaction to test that
    // is_email_receipt is not set to 1 if the originating contribution page
    // has is_email_receipt set to 0.
    $_REQUEST['mode'] = 'live';
    /* @var \CRM_Contribute_Form_Contribution $form */
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution', [
      'total_amount' => 200,
      'financial_type_id' => 1,
      'receive_date' => date('m/d/Y'),
      'receive_date_time' => date('H:i:s'),
      'contact_id' => $this->ids['Contact']['individual_0'],
      'contribution_status_id' => 1,
      'credit_card_number' => 4444333322221111,
      'cvv2' => 123,
      'credit_card_exp_date' => [
        'M' => 9,
        'Y' => 2025,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Junko',
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => time() . ' Lincoln St S',
      'billing_city-5' => 'Mary-knoll',
      'billing_state_province_id-5' => 1031,
      'billing_postal_code-5' => 10545,
      'billing_country_id-5' => 1228,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => 2,
      'hidden_AdditionalDetail' => 1,
      'hidden_Premium' => 1,
      'payment_processor_id' => $this->ids['PaymentProcessor']['test'],
      'currency' => 'USD',
      'source' => 'bob sled race',
      'contribution_page_id' => $this->ids['ContributionPage'][0],
      'is_recur' => TRUE,
    ]);
    $form->buildForm();
    $form->postProcess();
    $contribution = Contribution::get()->setLimit(1)->addWhere('contribution_page_id', '=', $this->ids['ContributionPage'][0])->execute()->first();
    $this->ids['Contribution'][0] = $contribution['id'];
    $this->_contributionRecurID = $contribution['contribution_recur_id'];

    $contributionRecur  = $this->callAPISuccessGetSingle('ContributionRecur', ['id' => $this->_contributionRecurID]);
    $processor_id = $contributionRecur['processor_id'];
    $this->assertEquals('Pending', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionRecur['contribution_status_id']));
    // Process the initial one after a second's break to ensure modified date really is later.
    sleep(1);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN(
      $this->getRecurTransaction(['x_subscription_id' => $processor_id])
    );
    $IPN->main();
    $updatedContributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', ['id' => $this->_contributionRecurID]);
    $this->assertEquals('In Progress', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $updatedContributionRecur['contribution_status_id']));
    $this->assertTrue(strtotime($updatedContributionRecur['modified_date']) > strtotime($contributionRecur['modified_date']));

    // Now send a second one (authorize seems to treat first and second contributions
    // differently.
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurSubsequentTransaction([
      'x_subscription_id' => $processor_id,
      'x_subscription_paynum' => 2,
    ]));
    $IPN->main();
    $updatedContributionRecurAgain = $this->callAPISuccessGetSingle('ContributionRecur', ['id' => $this->_contributionRecurID]);
    $this->assertEquals('Completed', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $updatedContributionRecurAgain['contribution_status_id']));
    $this->assertEquals(date('Y-m-d'), substr($updatedContributionRecurAgain['end_date'], 0, 10));
    // There should not be any email.
    $mut->assertMailLogEmpty();

    $contributions = Contribution::get()->addWhere('contribution_recur_id', '=', $this->_contributionRecurID)->addSelect('contribution_page_id')->execute();
    foreach ($contributions as $contribution) {
      $this->assertEquals($this->_contributionPageID, $contribution['contribution_page_id']);
    }
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first & second contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function testIPNPaymentRecurSuccess(): void {
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $this->setupRecurringPaymentProcessorTransaction([
      'installments' => 3,
    ], []);
    $this->assertRecurStatus('Pending');

    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $this->assertRecurStatus('In Progress');
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['id' => $this->ids['Contribution']['default']]);
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('6511143069', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertSame(strpos($contribution['contribution_source'], 'Online Contribution:'), 0);

    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurSubsequentTransaction());
    $IPN->main();
    $this->assertRecurStatus('In Progress');
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contribution_recur_id' => $this->ids['ContributionRecur']['default'],
      'sequential' => 1,
    ])['values'];
    $this->assertCount(2, $contribution);
    $secondContribution = $contribution[1];
    $this->assertEquals('second_one', $secondContribution['trxn_id']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($secondContribution['receive_date'])));
    $this->assertEquals('expensive', $secondContribution['amount_level']);
    $this->assertEquals($this->ids['Campaign']['default'], $secondContribution['campaign_id']);

    $IPN = new CRM_Core_Payment_AuthorizeNetIPN(array_merge($this->getRecurSubsequentTransaction(), ['x_subscription_paynum' => 3, 'x_trans_id' => 'three']));
    $IPN->main();
    $this->assertRecurStatus('Completed');
  }

  /**
   * Assertion for recurring status.
   *
   * @param string $status
   */
  public function assertRecurStatus(string $status): void {
    try {
      $contributionRecur = ContributionRecur::get()
        ->addWhere('id', '=', $this->ids['ContributionRecur']['default'])
        ->addSelect('contribution_status_id:name', 'end_date')
        ->execute()->first();
      $this->assertEquals($status, $contributionRecur['contribution_status_id:name']);
      if ($status === 'Completed') {
        $this->assertNotEmpty($contributionRecur['end_date']);
      }
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('Failed to get recurring' . $e->getMessage());
    }
  }

  /**
   * Test payment processor is correctly assigned for the IPN payment.
   *
   * @throws \CRM_Core_Exception
   */
  public function testIPNPaymentRecurSuccessMultiAuthNetProcessor(): void {
    //Create and set up recur payment using second instance of AuthNet Processor.
    $paymentProcessorID2 = $this->paymentProcessorAuthorizeNetCreate(['name' => 'Authorize2', 'is_test' => 0]);
    $this->setupRecurringPaymentProcessorTransaction(['payment_processor_id' => $paymentProcessorID2]);

    //Call IPN with processor id.
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction(['processor_id' => $paymentProcessorID2]));
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['id' => $this->ids['Contribution']['default']]);
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('6511143069', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertEquals('Online Contribution:', substr($contribution['contribution_source'], 0, 20));
    $this->assertRecurStatus('In Progress');
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first & second contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function testIPNPaymentRecurSuccessSuppliedReceiveDate(): void {
    $this->setupRecurringPaymentProcessorTransaction();
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['id' => $this->ids['Contribution']['default']]);
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('6511143069', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertEquals('Online Contribution:', substr($contribution['contribution_source'], 0, 20));

    $this->assertRecurStatus('In Progress');
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN(array_merge(['receive_date' => '2010-07-01'], $this->getRecurSubsequentTransaction()));
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'contribution_recur_id' => $this->_contributionRecurID,
      'sequential' => 1,
    ]);
    $this->assertEquals(2, $contribution['count']);
    $this->assertEquals('second_one', $contribution['values'][1]['trxn_id']);
    $this->assertEquals('2010-07-01', date('Y-m-d', strtotime($contribution['values'][1]['receive_date'])));
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first &
   * second contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function testIPNPaymentMembershipRecurSuccess(): void {
    $this->createRepeatMembershipOrder();
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['id' => $this->ids['Contribution'][0]]);
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('6511143069', $contribution['trxn_id']);

    // source gets set by processor
    $this->assertEquals('Online Contribution:', substr($contribution['contribution_source'], 0, 20));
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $this->_contributionRecurID]);
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurSubsequentTransaction());
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'contribution_recur_id' => $this->_contributionRecurID,
      'sequential' => 1,
    ]);
    $this->assertEquals(2, $contribution['count']);
    // Ensure both contributions are coded as credit card contributions.
    $this->assertEquals(1, $contribution['values'][0]['payment_instrument_id']);
    $this->assertEquals(1, $contribution['values'][1]['payment_instrument_id']);
    $this->assertEquals('second_one', $contribution['values'][1]['trxn_id']);
    $this->callAPISuccessGetSingle('membership_payment', ['contribution_id' => $contribution['values'][1]['id']]);
    $this->callAPISuccessGetSingle('line_item', [
      'contribution_id' => $contribution['values'][1]['id'],
      'entity_table' => 'civicrm_membership',
    ]);
    $this->validateAllContributions();
    $this->validateAllPayments();
  }

  /**
   * Test IPN response mails don't leak.
   *
   * @throws \CRM_Core_Exception
   */
  public function testIPNPaymentMembershipRecurSuccessNoLeakage(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setupMembershipRecurringPaymentProcessorTransaction(['is_email_receipt' => TRUE]);
    $this->addProfile('supporter_profile', $this->ids['ContributionPage'][0]);
    $this->addProfile('honoree_individual', $this->ids['ContributionPage'][0], 'soft_credit');

    $this->callAPISuccess('ContributionSoft', 'create', [
      'contact_id' => $this->individualCreate(),
      'contribution_id' => $this->ids['Contribution']['default'],
      'soft_credit_type_id' => 'in_memory_of',
      'amount' => 200,
    ]);

    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $mut->checkAllMailLog([
      'Membership Type: General',
      'Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      'Amount: $200.00',
      'Membership Start Date:',
      'Supporter Profile',
      'First Name: Anthony',
      'Last Name: Anderson',
      'Email Address: anthony_anderson@civicrm.org',
      'Honor',
      'This membership will be automatically renewed every',
      'Dear Anthony',
      'Thanks for your auto renew membership sign-up',
      'In Memory of',
    ]);
    $mut->clearMessages();
    $this->_contactID = $this->individualCreate(['first_name' => 'Antonia', 'prefix_id' => 'Mrs.', 'email' => 'antonia_anderson@civicrm.org']);

    // Note, the second contribution is not in honor of anyone and the
    // receipt should not mention honor at all.
    $this->setupMembershipRecurringPaymentProcessorTransaction(['is_email_receipt' => TRUE], ['invoice_id' => '345']);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction(['x_trans_id' => 'hers']));
    $IPN->main();

    $mut->checkAllMailLog([
      'Membership Type: General',
      'Mrs. Antonia Anderson II',
      'antonia_anderson@civicrm.org',
      'Amount: $200.00',
      'Membership Start Date:',
      'Transaction #: hers',
      'Supporter Profile',
      'First Name: Antonia',
      'Last Name: Anderson',
      'Email Address: antonia_anderson@civicrm.org',
      'This membership will be automatically renewed every',
      'Dear Antonia',
      'Thanks for your auto renew membership sign-up',
    ]);

    $shouldNotBeInMailing = [
      'Honor',
      'In Memory of',
    ];
    $mails = $mut->getAllMessages();
    foreach ($mails as $mail) {
      $mut->checkMailForStrings([], $shouldNotBeInMailing, '', $mail);
    }
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test IPN response mails don't leak.
   *
   * @throws \CRM_Core_Exception
   */
  public function testIPNPaymentMembershipRecurSuccessNoLeakageOnlineThenOffline(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setupMembershipRecurringPaymentProcessorTransaction(['is_email_receipt' => TRUE]);
    $this->addProfile('supporter_profile', $this->ids['ContributionPage'][0]);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $mut->checkAllMailLog([
      'Membership Type: General',
      'Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      'Amount: $200.00',
      'Membership Start Date:',
      'Supporter Profile',
      'First Name: Anthony',
      'Last Name: Anderson',
      'Email Address: anthony_anderson@civicrm.org',
      'This membership will be automatically renewed every',
      'Dear Anthony',
      'Thanks for your auto renew membership sign-up',
    ]);

    $this->_contactID = $this->individualCreate(['first_name' => 'Antonia', 'prefix_id' => 'Mrs.', 'email' => 'antonia_anderson@civicrm.org']);

    $this->setupMembershipRecurringPaymentProcessorTransaction(['is_email_receipt' => TRUE], ['invoice_id' => 8977, 'contribution_page_id' => NULL]);
    $mut->clearMessages();
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction(['x_trans_id' => 'hers']));
    $IPN->main();

    $mut->checkAllMailLog([
      'Membership Type: General',
      'Mrs. Antonia Anderson II',
      'antonia_anderson@civicrm.org',
      'Amount: $200.00',
      'Membership Start Date:',
      'Transaction #: hers',
      'This membership will be automatically renewed every',
      'Dear Antonia',
      'Thanks for your auto renew membership sign-up',
    ],
    [
      'First Name: Anthony',
      'First Name: Antonia',
      'Last Name: Anderson',
      'Supporter Profile',
      'Email Address: antonia_anderson@civicrm.org',
    ]);

    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Get detail for recurring transaction.
   *
   * @param array $params
   *   Additional parameters.
   *
   * @return array
   *   Parameters like AuthorizeNet silent post parameters.
   */
  public function getRecurTransaction(array $params = []): array {
    return array_merge([
      'x_amount' => '200.00',
      'x_country' => 'US',
      'x_phone' => '',
      'x_fax' => '',
      'x_email' => 'me@gmail.com',
      'x_description' => 'lots of money',
      'x_type' => 'auth_capture',
      'x_ship_to_first_name' => '',
      'x_ship_to_last_name' => '',
      'x_ship_to_company' => '',
      'x_ship_to_address' => '',
      'x_ship_to_city' => '',
      'x_ship_to_state' => '',
      'x_ship_to_zip' => '',
      'x_ship_to_country' => '',
      'x_tax' => '0.00',
      'x_duty' => '0.00',
      'x_freight' => '0.00',
      'x_tax_exempt' => 'FALSE',
      'x_po_num' => '',
      'x_MD5_Hash' => '1B7C0C5B4DED9CAD0636E35E22FC594',
      'x_cvv2_resp_code' => '',
      'x_cavv_response' => '',
      'x_test_request' => 'false',
      'x_subscription_id' => $this->_contactID,
      'x_subscription_paynum' => '1',
      'x_first_name' => 'Robert',
      'x_zip' => '90210',
      'x_state' => 'WA',
      'x_city' => 'Dallas',
      'x_address' => '41 My ST',
      'x_invoice_num' => $this->ids['Contribution'][0],
      'x_cust_id' => $this->_contactID,
      'x_company' => 'nowhere@civicrm.org',
      'x_last_name' => 'Roberts',
      'x_account_number' => 'XXXX5077',
      'x_card_type' => 'Visa',
      'x_method' => 'CC',
      'x_trans_id' => '6511143069',
      'x_auth_code' => '123456',
      'x_avs_code' => 'Z',
      'x_response_reason_text' => 'This transaction has been approved.',
      'x_response_reason_code' => '1',
      'x_response_code' => '1',
    ], $params);
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public function getRecurSubsequentTransaction(array $params = []): array {
    return array_merge($this->getRecurTransaction(), [
      'x_trans_id' => 'second_one',
      'x_MD5_Hash' => 'EA7A3CD65A85757827F51212CA1486A8',
    ], $params);
  }

}
