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
 * Class CRM_Financial_BAO_FinancialAccountTest
 */
class CRM_Financial_BAO_FinancialAccountTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->organizationCreate();
  }

  public function teardown() {
    $this->financialAccountDelete('Donations');
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
    $contributionType = CRM_Financial_BAO_FinancialAccount::add($params, $ids);

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

}
