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

  public function setUp(): void {
    parent::setUp();
    $this->organizationCreate();
  }

  public function tearDown(): void {
    $this->financialAccountDelete('Donations');
    parent::tearDown();
  }

  /**
   * Check method add()
   */
  public function testAdd(): void {
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $contributionType = CRM_Financial_BAO_FinancialType::writeRecord($params);

    $result = $this->assertDBNotNull('CRM_Financial_BAO_FinancialType', $contributionType->id,
      'name', 'id',
      'Database check on updated financial type record.'
    );

    $this->assertEquals($result, 'Donations', 'Verify financial type name.');
  }

}
