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
 * Class CRM_Financial_BAO_FinancialTypeAccountTest
 */
class CRM_Financial_BAO_FinancialTypeAccountTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->organizationCreate();
  }

  /**
   * Check method add()
   */
  public function testAdd() {
    $params = array(
      'name' => 'TestFinancialAccount_1',
      'accounting_code' => 4800,
      'contact_id' => 1,
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );

    $ids = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
    $params['name'] = 'test_financialType1';
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount->id,
    );

    CRM_Financial_BAO_FinancialTypeAccount::add($financialParams, $ids);
    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialAccount',
      $financialAccount->id,
      'entity_id',
      'financial_account_id',
      'Database check on added financial type record.'
    );
    $this->assertEquals($result, $financialType->id, 'Verify Account Type');
  }

  /**
   * Check method del()
   */
  public function testDel() {
    $params = array(
      'name' => 'TestFinancialAccount_2',
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );

    $ids = array();
    $defaults = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
    $params['name'] = 'test_financialType2';
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Expense Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount->id,
    );
    $financialAccountType = CRM_Financial_BAO_FinancialTypeAccount::add($financialParams, $ids);
    CRM_Financial_BAO_FinancialTypeAccount::del($financialAccountType->id);
    $params = array('id' => $financialAccountType->id);
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
  }

  /**
   * Check method getFinancialAccount()
   */
  public function testRetrieve() {
    $params = array(
      'name' => 'TestFinancialAccount_3',
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );
    $ids = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
    $params['name'] = 'test_financialType3';
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount->id,
    );

    CRM_Financial_BAO_FinancialTypeAccount::add($financialParams, $ids);
    $defaults = array();
    $financialAccountType = CRM_Financial_BAO_FinancialTypeAccount::retrieve($financialParams, $defaults);
    $this->assertEquals($financialAccountType['entity_id'], $financialType->id, 'Verify Entity Id.');
    $this->assertEquals($financialAccountType['financial_account_id'], $financialAccount->id, 'Verify Financial Account Id.');
  }

  /**
   * Check method getFinancialAccount()
   */
  public function testGetFinancialAccount() {
    $params = array(
      'name' => 'TestFinancialAccount',
      'accounting_code' => 4800,
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );
    $ids = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
    $params = array(
      'financial_account_id' => $financialAccount->id,
      'payment_processor_type_id' => 1,
      'domain_id' => 1,
      'billing_mode' => 1,
      'name' => 'paymentProcessor',
    );
    $processor = CRM_Financial_BAO_PaymentProcessor::create($params);

    $account = CRM_Financial_BAO_FinancialTypeAccount::getFinancialAccount(
      $processor->id,
      'civicrm_payment_processor'
    );
    $this->assertEquals($account, 'TestFinancialAccount', 'Verify Financial Account Name');
  }

  /**
   * Check method getInstrumentFinancialAccount()
   */
  public function testGetInstrumentFinancialAccount() {
    $paymentInstrumentValue = 1;
    $params = array(
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    );
    $ids = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
    $optionParams = array(
      'name' => 'Credit Card',
      'value' => $paymentInstrumentValue,
    );
    $optionValue = CRM_Core_BAO_OptionValue::retrieve($optionParams, $defaults);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_option_value',
      'entity_id' => $optionValue->id,
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount->id,
    );

    CRM_Financial_BAO_FinancialTypeAccount::add($financialParams, $ids);
    $financialAccountId = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($paymentInstrumentValue);

    $this->assertEquals($financialAccountId, $financialAccount->id, 'Verify Payment Instrument');
  }

}
