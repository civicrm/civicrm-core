<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Financial/DAO/FinancialAccount.php';
require_once 'CRM/Financial/BAO/FinancialAccount.php';
require_once 'CRM/Financial/BAO/FinancialTypeAccount.php';

class CRM_Financial_BAO_FinancialTypeAccountTest extends CiviUnitTestCase {

  function get_info() {
    return array(
      'name'        => 'FinancialTypeAccount BAOs',
      'description' => 'Test all Contribute_BAO_Contribution methods.',
      'group'       => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * check method add()
   */
  function testAdd() {
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
    $this->assertEquals( $result, $financialType->id, 'Verify Account Type');
  }

  /**
   * check method del()
   */
  function testDel() {
    $params = array(
      'name' => 'TestFinancialAccount_2',
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );

    $ids = array();
    $defaults = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
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
    $params = array('id' => $financialAccountType->id );
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals( empty($result), true, 'Verify financial types record deletion.');
  }

  /**
   * check method getFinancialAccount()
   */
  function testRetrieve() {
    $params = array(
      'name' => 'TestFinancialAccount_3',
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );
    $ids = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
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
    $this->assertEquals( $financialAccountType['entity_id'], $financialType->id, 'Verify Entity Id.');
    $this->assertEquals( $financialAccountType['financial_account_id'], $financialAccount->id, 'Verify Financial Account Id.');
  }

  /**
   * check method getFinancialAccount()
   */
  function testGetFinancialAccount() {
    $params = array(
      'name' => 'TestFinancialAccount',
      'accounting_code' => 4800,
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );
    $ids = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount->id,
    );
    $financialAccountType = CRM_Financial_BAO_FinancialTypeAccount::add($financialParams, $ids);
    $account = CRM_Financial_BAO_FinancialTypeAccount::getFinancialAccount(
      $financialAccountType->entity_id,
      $financialAccountType->entity_table
    );
    $this->assertEquals( $account, 'TestFinancialAccount', 'Verify Financial Account Name');
  }

  /**
   * check method getInstrumentFinancialAccount()
   */
  function testGetInstrumentFinancialAccount() {
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

    $this->assertEquals( $financialAccountId, $financialAccount->id, 'Verify Payment Instrument');
  }
}