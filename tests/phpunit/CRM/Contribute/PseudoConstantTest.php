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
 * CRM_Contribute_PseudoConstantTest
 * @group headless
 */
class CRM_Contribute_PseudoConstantTest extends CiviUnitTestCase {

  use CRMTraits_PCP_PCPTestTrait;

  /**
   * Clean up after tests.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test that getRelationalFinancialAccount works and returns the same as the performant alternative.
   *
   * Note this is to be changed to be a deprecated wrapper function.
   *
   * Future is CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship
   */
  public function testGetRelationalFinancialAccount(): void {
    $financialTypes = $this->callAPISuccess('FinancialType', 'get', [])['values'];
    $financialAccounts = $this->callAPISuccess('FinancialAccount', 'get', [])['values'];
    foreach ($financialTypes as $financialType) {
      $accountID = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialType['id'], 'Accounts Receivable Account is');
      $this->assertEquals('Accounts Receivable', $financialAccounts[$accountID]['name']);
      $accountIDFromBetterFunction = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $financialType['id'],
        'Accounts Receivable Account is'
      );
      $this->assertEquals($accountIDFromBetterFunction, $accountID);

      $accountID = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialType['id'], 'Income Account is');
      $this->assertEquals($financialType['name'], $financialAccounts[$accountID]['name']);
      $accountIDFromBetterFunction = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $financialType['id'],
        'Income Account is'
      );
      $this->assertEquals($accountIDFromBetterFunction, $accountID);
    }
  }

  /**
   * Test that getRelationalFinancialAccount works and returns the same as the performant alternative.
   *
   * Note this is to be changed to be a deprecated wrapper function.
   *
   * Future is CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship
   */
  public function testGetRelationalFinancialAccountForPaymentInstrument(): void {
    $paymentInstruments = $this->callAPISuccess('Contribution', 'getoptions', ['field' => 'payment_instrument_id'])['values'];
    $financialAccounts = $this->callAPISuccess('FinancialAccount', 'get', [])['values'];
    foreach ($paymentInstruments as $paymentInstrumentID => $paymentInstrumentName) {
      $financialAccountID = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount($paymentInstrumentID);
      if (in_array($paymentInstrumentName, ['Credit Card', 'Debit Card'])) {
        $this->assertEquals('Payment Processor Account', $financialAccounts[$financialAccountID]['name']);
      }
      else {
        $this->assertEquals('Deposit Bank Account', $financialAccounts[$financialAccountID]['name']);
      }
    }
  }

  public function testPcPages(): void {
    $blockParams = $this->pcpBlockParams();
    $pcpBlock = CRM_PCP_BAO_PCPBlock::writeRecord($blockParams);

    $params = $this->pcpParams();
    $params['pcp_block_id'] = $pcpBlock->id;
    CRM_PCP_BAO_PCP::writeRecord($params);
    $result = \CRM_Contribute_PseudoConstant::pcPage();
    $this->assertCount(1, $result);
  }

}
