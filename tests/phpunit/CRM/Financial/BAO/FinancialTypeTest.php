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
    $this->_orgContactID = $this->organizationCreate();
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

  /**
   * Set ACLs for Financial Types()
   */
  public function setACL() {
    CRM_Core_BAO_Setting::setItem(array('acl_financial_type' => 1), NULL, 'contribution_invoice_settings');
  }

  /**
   * Check method testgetAvailableFinancialTypes()
   */
  public function testgetAvailableFinancialTypes() {
    $this->setACL();
    $config = &CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array(
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    );
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
    $expectedResult = array(
      1 => "Donation",
      2 => "Member Dues",
    );
    $this->assertEquals($expectedResult, $types, 'Verify that only certain financial types can be retrieved');
    CRM_Financial_BAO_FinancialType::$_availableFinancialTypes = NULL;
    $config->userPermissionClass->permissions = array(
      'view contributions of type Donation',
    );
    unset($expectedResult[2]);
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
    $this->assertEquals($expectedResult, $types, 'Verify that removing permission for a financial type restricts the available financial types');
  }

  /**
   * Check method testgetAvailableMembershipTypes()
   */
  public function testgetAvailableMembershipTypes() {
    // Create Membership types
    $ids = array();
    $params = array(
      'name' => 'Type One',
      'domain_id' => 1,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => 1,
      'visibility' => 'Public',
      'is_active' => 1,
    );

    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);
    // Add another
    $params['name'] = 'Type Two';
    $params['financial_type_id'] = 2;
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

    $this->setACL();
    $config = &CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array(
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    );
    CRM_Financial_BAO_FinancialType::getAvailableMembershipTypes($types);
    $expectedResult = array(
      1 => "Type One",
      2 => "Type Two",
    );
    $this->assertEquals($expectedResult, $types, 'Verify that only certain membership types can be retrieved');
    $config->userPermissionClass->permissions = array(
      'view contributions of type Donation',
    );
    unset($expectedResult[2]);
    CRM_Financial_BAO_FinancialType::getAvailableMembershipTypes($types);
    $this->assertEquals($expectedResult, $types, 'Verify that removing permission for a financial type restricts the available membership types');
  }
  
  public function testpermissionedFinancialTypes() {
    // First get all core permissions
    $permissions = $checkPerms = CRM_Core_Permission::getCorePermissions();
    $this->setACL();
    CRM_Financial_BAO_FinancialType::permissionedFinancialTypes($permissions, TRUE);
    $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    $prefix = ts('CiviCRM') . ': ';
    $actions = array('add', 'view', 'edit', 'delete');
    foreach ($financialTypes as $id => $type) {
      foreach ($actions as $action) {
        $checkPerms[$action . ' contributions of type ' . $type] = array(
          $prefix . ts($action . ' contributions of type ') . $type,
          ts(ucfirst($action) . ' contributions of type ') . $type,
        );
      }
    }
    $checkPerms['administer CiviCRM Financial Types'] = array(
      $prefix . ts('administer CiviCRM Financial Types'),
      ts('Administer access to Financial Types'),
    );
    $this->assertEquals($permissions, $checkPerms, 'Verify that permissions for each financial type have been added');
  }

}
