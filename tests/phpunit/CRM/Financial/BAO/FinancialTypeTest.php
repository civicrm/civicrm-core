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
 * Class CRM_Financial_BAO_FinancialTypeTest
 * @group headless
 */
class CRM_Financial_BAO_FinancialTypeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
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
      'is_active' => 1,
      'is_deductible' => 1,
      'is_reserved' => 1,
    );
    $ids = array();
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_FinancialType',
      $financialType->id,
      'name',
      'id',
      'Database check on added financial type record.'
    );
    $this->assertEquals($result, 'Donations', 'Verify Name for Financial Type');
  }

  /**
   * Check method retrive()
   */
  public function testRetrieve() {
    $params = array(
      'name' => 'Donations',
      'is_active' => 1,
      'is_deductible' => 1,
      'is_reserved' => 1,
    );

    $ids = array();
    CRM_Financial_BAO_FinancialType::add($params, $ids);

    $defaults = array();
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'Donations', 'Verify Name for Financial Type');
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
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $result = CRM_Financial_BAO_FinancialType::setIsActive($financialType->id, 0);
    $this->assertEquals($result, TRUE, 'Verify financial type record updation for is_active.');
    $isActive = $this->assertDBNotNull(
      'CRM_Financial_DAO_FinancialType',
      $financialType->id,
      'is_active',
      'id',
      'Database check on updated for financial type is_active.'
    );
    $this->assertEquals($isActive, 0, 'Verify financial types is_active.');
  }

  /**
   * Check method del()
   */
  public function testDel() {
    $params = array(
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    );
    $ids = array();
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);

    CRM_Financial_BAO_FinancialType::del($financialType->id);
    $params = array('id' => $financialType->id);
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
  }

}
