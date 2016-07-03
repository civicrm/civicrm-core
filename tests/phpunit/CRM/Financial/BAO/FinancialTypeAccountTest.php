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
 * Class CRM_Financial_BAO_FinancialTypeAccountTest
 * @group headless
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
    list($financialAccount, $financialType, $financialAccountType) = $this->createFinancialAccount(
      'Revenue',
      'Income Account is'
    );
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
    list($financialAccount, $financialType, $financialAccountType) = $this->createFinancialAccount(
      'Expenses',
      'Expense Account is'
    );

    CRM_Financial_BAO_FinancialTypeAccount::del($financialAccountType->id);
    $params = array('id' => $financialAccountType->id);
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
  }

  /**
   * Check method getFinancialAccount()
   */
  public function testRetrieve() {
    list($financialAccount, $financialType, $financialAccountType) = $this->createFinancialAccount(
      'Asset',
      'Asset Account is'
    );
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount->id,
    );

    $defaults = array();
    $financialAccountType = CRM_Financial_BAO_FinancialTypeAccount::retrieve($financialParams, $defaults);
    $this->assertEquals($financialAccountType['entity_id'], $financialType->id, 'Verify Entity Id.');
    $this->assertEquals($financialAccountType['financial_account_id'], $financialAccount->id, 'Verify Financial Account Id.');
  }

  /**
   * Check method getFinancialAccount()
   */
  public function testGetFinancialAccount() {
    list($financialAccount, $financialType, $financialAccountType) = $this->createFinancialAccount(
      'Asset'
    );
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
    $this->assertEquals($account, $financialAccount->name, 'Verify Financial Account Name');
  }

  /**
   * Check method getInstrumentFinancialAccount()
   */
  public function testGetInstrumentFinancialAccount() {
    $paymentInstrumentValue = 1;
    list($financialAccount, $financialType, $financialAccountType) = $this->createFinancialAccount(
      'Asset'
    );
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

    CRM_Financial_BAO_FinancialTypeAccount::add($financialParams);
    $financialAccountId = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($paymentInstrumentValue);

    $this->assertEquals($financialAccountId, $financialAccount->id, 'Verify Payment Instrument');
  }

  /**
   * Test validate account relationship with financial account type.
   */
  public function testValidateRelationship() {
    $params = array('labelColumn' => 'name');
    $financialAccount = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $accountRelationships = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship', $params);
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    $financialAccountType = new CRM_Financial_DAO_EntityFinancialAccount();
    $financialAccountType->entity_table = 'civicrm_financial_type';
    $financialAccountType->entity_id = array_search('Member Dues', $financialType);
    $financialAccountType->account_relationship = array_search('Credit/Contra Revenue Account is', $accountRelationships);
    $financialAccountType->financial_account_id = array_search('Liability', $financialAccount);
    try {
      CRM_Financial_BAO_FinancialTypeAccount::validateRelationship($financialAccountType);
      $this->fail("Missed expected exception");
    }
    catch (Exception $e) {
      $this->assertTrue(TRUE, 'Received expected exception');
      $this->assertEquals($e->getMessage(), "This financial account cannot have 'Credit/Contra Revenue Account is' relationship.");
    }
  }

  /**
   * Function to create Financial Account.
   *
   * @param string $financialAccountType
   *
   * @param string $relationType
   *
   * @return array
   *   obj CRM_Financial_DAO_FinancialAccount, obj CRM_Financial_DAO_FinancialType, obj CRM_Financial_DAO_EntityFinancialAccount
   */
  public function createFinancialAccount($financialAccountType, $relationType = NULL) {
    $params = array('labelColumn' => 'name');
    $relationTypes = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship', $params);
    $financialAccountTypes = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $params = array(
      'name' => 'TestFinancialAccount_' . rand(),
      'contact_id' => 1,
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
      'financial_account_type_id' => array_search($financialAccountType, $financialAccountTypes),
    );
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params);
    $financialType = $financialAccountType = NULL;
    if ($relationType) {
      $params['name'] = 'test_financialType1';
      $financialType = CRM_Financial_BAO_FinancialType::add($params);
      $financialParams = array(
        'entity_table' => 'civicrm_financial_type',
        'entity_id' => $financialType->id,
        'account_relationship' => array_search($relationType, $relationTypes),
        'financial_account_id' => $financialAccount->id,
      );
      $financialAccountType = CRM_Financial_BAO_FinancialTypeAccount::add($financialParams);
    }
    return array($financialAccount, $financialType, $financialAccountType);
  }

}
