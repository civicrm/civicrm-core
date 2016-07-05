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
 * Class CRM_Financial_BAO_FinancialAccountTest
 * @group headless
 */
class CRM_Financial_BAO_FinancialAccountTest extends CiviUnitTestCase {

  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->organizationCreate();
  }

  /**
   * Check method add()
   */
  public function testAdd() {
    $params = array(
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    );
    $ids = array();
    $contributionType = CRM_Financial_BAO_FinancialAccount::add($params, $ids);

    $result = $this->assertDBNotNull(
      'CRM_Financial_BAO_FinancialAccount',
      $contributionType->id,
      'name',
      'id',
      'Database check on updated financial type record.'
    );

    $this->assertEquals($result, 'Donations', 'Verify financial type name.');
  }

  /**
   * Check method retrive()
   */
  public function testRetrieve() {
    $params = array(
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    );
    $ids = $defaults = array();
    CRM_Financial_BAO_FinancialAccount::add($params);

    $result = CRM_Financial_BAO_FinancialAccount::retrieve($params, $defaults);

    $this->assertEquals($result->name, 'Donations', 'Verify financial type name.');
  }

  /**
   * Check method setIsActive()
   */
  public function testSetIsActive() {
    $params = array(
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    );
    $ids = array();
    $contributionType = CRM_Financial_BAO_FinancialAccount::add($params, $ids);
    $result = CRM_Financial_BAO_FinancialAccount::setIsActive($contributionType->id, 0);
    $this->assertEquals($result, TRUE, 'Verify financial type record updation for is_active.');

    $isActive = $this->assertDBNotNull(
      'CRM_Financial_BAO_FinancialAccount',
      $contributionType->id,
      'is_active',
      'id',
      'Database check on updated for financial type is_active.'
    );
    $this->assertEquals($isActive, 0, 'Verify financial types is_active.');
  }

  /**
   * Check method del()
   */
  public function testdel() {
    $params = array(
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    );
    $ids = array();
    $contributionType = CRM_Financial_BAO_FinancialAccount::add($params, $ids);

    CRM_Financial_BAO_FinancialAccount::del($contributionType->id);
    $params = array('id' => $contributionType->id);
    $result = CRM_Financial_BAO_FinancialAccount::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
  }

  /**
   * Check method getAccountingCode()
   */
  public function testGetAccountingCode() {
    $params = array(
      'name' => 'Donations',
      'is_active' => 1,
      'is_reserved' => 0,
    );

    $ids = array();
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $financialAccountid = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', 'Donations', 'id', 'name');
    CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_FinancialAccount', $financialAccountid, 'accounting_code', '4800');
    $accountingCode = CRM_Financial_BAO_FinancialAccount::getAccountingCode($financialType->id);
    $this->assertEquals($accountingCode, 4800, 'Verify accounting code.');
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltIn() {
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Income Account Is'));
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltInRefunded() {
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Credit/Contra Revenue Account Is'));
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltInChargeBack() {
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Chargeback Account Is'));
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipCustomAddedRefunded() {
    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', array(
      'name' => 'Refund Account',
      'is_active' => TRUE,
    ));

    $this->callAPISuccess('EntityFinancialAccount', 'create', array(
      'entity_id' => 2,
      'entity_table' => 'civicrm_financial_type',
      'account_relationship' => 'Credit/Contra Revenue Account is',
      'financial_account_id' => 'Refund Account',
    ));
    $this->assertEquals($financialAccount['id'],
      CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Credit/Contra Revenue Account is'));
  }

  /**
   * Test getting financial account relations for a given financial type.
   */
  public function testGetFinancialAccountRelations() {
    $fAccounts = $rAccounts = array();
    $relations = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations();
    $links = array(
      'Expense Account is' => 'Expenses',
      'Accounts Receivable Account is' => 'Asset',
      'Income Account is' => 'Revenue',
      'Asset Account is' => 'Asset',
      'Cost of Sales Account is' => 'Cost of Sales',
      'Premiums Inventory Account is' => 'Asset',
      'Discounts Account is' => 'Revenue',
      'Sales Tax Account is' => 'Liability',
      'Deferred Revenue Account is' => 'Liability',
    );
    $dao = CRM_Core_DAO::executeQuery("SELECT ov.value, ov.name
      FROM civicrm_option_value ov
      INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id
      AND og.name = 'financial_account_type'");
    while ($dao->fetch()) {
      $fAccounts[$dao->value] = $dao->name;
    }
    $dao = CRM_Core_DAO::executeQuery("SELECT ov.value, ov.name
      FROM civicrm_option_value ov
      INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id
      AND og.name = 'account_relationship'");
    while ($dao->fetch()) {
      $rAccounts[$dao->value] = $dao->name;
    }
    foreach ($links as $accountRelation => $accountType) {
      $financialAccountLinks[array_search($accountRelation, $rAccounts)] = array_search($accountType, $fAccounts);
    }
    $this->assertTrue(($relations == $financialAccountLinks), "The two arrays are not the same");
  }

  /**
   * Test getting deferred financial type.
   */
  public function testGetDeferredFinancialType() {
    $result = $this->_createDeferredFinancialAccount();
    $financialTypes = CRM_Financial_BAO_FinancialAccount::getDeferredFinancialType();
    $this->assertTrue(array_key_exists($result, $financialTypes), "The financial type created does not have a deferred account relationship");
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testValidateFinancialAccount() {
    // Create a record with financial item having financial account as Event Fee.
    $this->createParticipantWithContribution();
    $financialAccounts = CRM_Contribute_PseudoConstant::financialAccount();
    $financialAccountId = array_search('Event Fee', $financialAccounts);
    $message = CRM_Financial_BAO_FinancialAccount::validateFinancialAccount($financialAccountId);
    $this->assertTrue($message, "The financial account cannot be deleted. Failed asserting this was true.");
    $financialAccountId = array_search('Member Dues', $financialAccounts);
    $message = CRM_Financial_BAO_FinancialAccount::validateFinancialAccount($financialAccountId);
    $this->assertFalse($message, "The financial account can be deleted. Failed asserting this was true.");
  }

  /**
   * Test for validating financial type has deferred revenue account relationship.
   */
  public function testcheckFinancialTypeHasDeferred() {
    Civi::settings()->set('contribution_invoice_settings', array('deferred_revenue_enabled' => '1'));
    $params = array();
    $valid = CRM_Financial_BAO_FinancialAccount::checkFinancialTypeHasDeferred($params);
    $this->assertFalse($valid, "This should have been false");
    $cid = $this->individualCreate();
    $params = array(
      'contact_id' => $cid,
      'receive_date' => '2010-01-20',
      'total_amount' => 100,
      'financial_type_id' => 3,
      'revenue_recognition_date' => date('Ymd', strtotime("+1 month")),
      'line_items' => array(
        array(
          'line_item' => array(
            array(
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 16,
              'label' => 'test 1',
              'qty' => 1,
              'unit_price' => 100,
              'line_total' => 100,
            ),
            array(
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 17,
              'label' => 'Test 2',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => 1,
            ),
          ),
          'params' => array(),
        ),
      ),
    );
    $contribution = CRM_Contribute_BAO_Contribution::create($params);
    $valid = CRM_Financial_BAO_FinancialAccount::checkFinancialTypeHasDeferred($params, $contribution->id);
    $message = "Revenue recognition date can only be specified if the financial type selected has a deferred revenue account configured. Please have an administrator set up the deferred revenue account at Administer > CiviContribute > Financial Accounts, then configure it for financial types at Administer > CiviContribution > Financial Types, Accounts";
    $this->assertEquals($valid, $message, "The messages do not match");
  }

  /**
   * Test if financial type has Deferred Revenue Account is relationship with Financial Account.
   *
   */
  public function testValidateFinancialType() {
    Civi::settings()->set('contribution_invoice_settings', array('deferred_revenue_enabled' => '1'));
    $deferred = CRM_Financial_BAO_FinancialAccount::getDeferredFinancialType();
    $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    foreach ($financialTypes as $key => $value) {
      $validate = CRM_Financial_BAO_FinancialAccount::validateFinancialType($key);
      if (array_key_exists($key, $deferred)) {
        $this->assertFalse($validate, "This should not have thrown an error.");
      }
      else {
        $this->assertEquals($validate, TRUE);
      }
    }
  }

  /**
   * Test Validate if Deferred Account is set for Financial Type.
   */
  public function testValidateTogglingDeferredRevenue() {
    $orgContactID = $this->organizationCreate();

    //create relationship
    $params = array(
      'name_a_b' => 'Relation 1',
      'name_b_a' => 'Relation 2',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $relationshipTypeId = $this->relationshipTypeCreate($params);
    $ids = array();
    $params = array(
      'name' => 'test type',
      'domain_id' => 1,
      'description' => NULL,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $orgContactID,
      'relationship_type_id' => $relationshipTypeId,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => 1,
      'visibility' => 'Public',
      'is_active' => 1,
    );

    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

    $membership = $this->assertDBNotNull('CRM_Member_BAO_MembershipType', $orgContactID,
      'name', 'member_of_contact_id',
      'Database check on updated membership record.'
    );
    $error = CRM_Financial_BAO_FinancialAccount::validateTogglingDeferredRevenue();
    $this->assertTrue(!empty($error), "Error message did not appear");
    $this->membershipTypeDelete(array('id' => $membershipType->id));
  }

  /**
   * Test testGetAllDeferredFinancialAccount.
   */
  public function testGetAllDeferredFinancialAccount() {
    $financialAccount = CRM_Financial_BAO_FinancialAccount::getAllDeferredFinancialAccount();
    // The two deferred financial accounts which are created by default.
    $expected = array(
      "Deferred Revenue - Event Fee",
      "Deferred Revenue - Member Dues",
    );
    $this->assertEquals(array_count_values($expected), array_count_values($financialAccount), "The two arrays are not the same");
    $this->_createDeferredFinancialAccount();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::getAllDeferredFinancialAccount();
    $expected[] = "TestFinancialAccount_1";
    $this->assertEquals(array_count_values($expected), array_count_values($financialAccount), "The two arrays are not the same");
  }

  /**
   * Helper function to create deferred financial account.
   */
  public function _createDeferredFinancialAccount() {
    $params = array(
      'name' => 'TestFinancialAccount_1',
      'accounting_code' => 4800,
      'contact_id' => 1,
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    );

    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', $params);
    $params['name'] = 'test_financialType1';
    $financialType = $this->callAPISuccess('FinancialType', 'create', $params);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType['id'],
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount['id'],
    );

    $this->callAPISuccess('EntityFinancialAccount', 'create', $financialParams);
    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialAccount',
      $financialAccount['id'],
      'entity_id',
      'financial_account_id',
      'Database check on added financial type record.'
    );
    $this->assertEquals($result, $financialType['id'], 'Verify Account Type');
    return $result;
  }

}
