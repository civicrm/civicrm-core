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
 * Class CRM_Contribute_BAO_ContributionTypeTest
 * @group headless
 */
class CRM_Contribute_BAO_ContributionTypeTest extends CiviUnitTestCase {

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
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $ids = [];
    $contributionType = CRM_Financial_BAO_FinancialType::add($params, $ids);

    $result = $this->assertDBNotNull('CRM_Financial_BAO_FinancialType', $contributionType->id,
      'name', 'id',
      'Database check on updated financial type record.'
    );

    $this->assertEquals($result, 'Donations', 'Verify financial type name.');
  }

  /**
   * Check method retrive()
   */
  public function testRetrieve() {
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $ids = [];
    $contributionType = CRM_Financial_BAO_FinancialType::add($params, $ids);

    $defaults = [];
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);

    $this->assertEquals($result->name, 'Donations', 'Verify financial type name.');
  }

  /**
   * Check method setIsActive()
   */
  public function testSetIsActive() {
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $ids = [];
    $contributionType = CRM_Financial_BAO_FinancialType::add($params, $ids);
    $result = CRM_Financial_BAO_FinancialType::setIsActive($contributionType->id, 0);
    $this->assertEquals($result, TRUE, 'Verify financial type record updation for is_active.');

    $isActive = $this->assertDBNotNull('CRM_Financial_BAO_FinancialType', $contributionType->id,
      'is_active', 'id',
      'Database check on updated for financial type is_active.'
    );
    $this->assertEquals($isActive, 0, 'Verify financial types is_active.');
  }

  /**
   * Check method del()
   */
  public function testdel() {
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $ids = [];
    $contributionType = CRM_Financial_BAO_FinancialType::add($params, $ids);

    CRM_Financial_BAO_FinancialType::del($contributionType->id);
    $params = ['id' => $contributionType->id];
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
  }

}
