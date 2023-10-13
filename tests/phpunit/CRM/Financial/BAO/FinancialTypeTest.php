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

use Civi\Api4\FinancialType;
use Civi\Api4\MembershipType;

/**
 * Class CRM_Financial_BAO_FinancialTypeTest
 * @group headless
 */
class CRM_Financial_BAO_FinancialTypeTest extends CiviUnitTestCase {

  public function tearDown(): void {
    global $dbLocale;
    if ($dbLocale) {
      $this->disableMultilingual();
    }
    $this->financialAccountDelete('Donations');
    parent::tearDown();
  }

  /**
   * Delete test for testGitLabIssue1108.
   *
   * @dataProvider getBooleanDataProvider
   * @group locale
   * @throws \CRM_Core_Exception
   */
  public function testDelete(bool $isMultiLingual): void {
    if ($isMultiLingual) {
      $this->enableMultilingual(['en_US' => 'fr_FR']);
    }
    $financialTypeID = FinancialType::create()->setValues([
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ])->execute()->first()['id'];

    if ($isMultiLingual) {
      global $dbLocale;
      $dbLocale = '_fr_FR';
    }
    FinancialType::delete()->addWhere('id', '=', $financialTypeID)->execute();
    $result = FinancialType::get()->addWhere('id', '=', $financialTypeID)->execute();
    $this->assertCount(0, $result, 'Verify financial types record deletion.');
    $results = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_entity_financial_account WHERE entity_id = %1', [1 => [$financialTypeID, 'Positive']])->fetchAll();
    $this->assertEquals(TRUE, empty($results), 'Assert related entity financial account has been deleted as well');
    if ($isMultiLingual) {
      global $dbLocale;
      $dbLocale = '_en_US';
    }
  }

  /**
   * Set ACLs for Financial Types()
   */
  public function setACL(): void {
    Civi::settings()->set('acl_financial_type', 1);
  }

  /**
   * Check method testGetAvailableFinancialTypes()
   */
  public function testGetAvailableFinancialTypes(): void {
    $this->setACL();
    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    ]);
    $types = [];
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
    $expectedResult = [
      1 => 'Donation',
      2 => 'Member Dues',
    ];
    $this->assertEquals($expectedResult, $types, 'Verify that only certain financial types can be retrieved');

    $this->setPermissions([
      'view contributions of type Donation',
    ]);
    unset($expectedResult[2]);
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
    $this->assertEquals($expectedResult, $types, 'Verify that removing permission for a financial type restricts the available financial types');
  }

  /**
   * Check method test getAvailableMembershipTypes()
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetAvailableMembershipTypes(): void {
    // Create Membership types
    $params = [
      'name' => 'Type One',
      'domain_id' => 1,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->organizationCreate(),
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => 1,
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    MembershipType::create()->setValues($params)->execute();
    // Add another
    $params['name'] = 'Type Two';
    $params['financial_type_id'] = 2;
    MembershipType::create()->setValues($params)->execute();

    $this->setACL();

    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    ]);
    CRM_Financial_BAO_FinancialType::getAvailableMembershipTypes($types);
    $expectedResult = [
      1 => 'Type One',
      2 => 'Type Two',
    ];
    $this->assertEquals($expectedResult, $types, 'Verify that only certain membership types can be retrieved');
    $this->setPermissions([
      'view contributions of type Donation',
    ]);
    unset($expectedResult[2]);
    CRM_Financial_BAO_FinancialType::getAvailableMembershipTypes($types);
    $this->assertEquals($expectedResult, $types, 'Verify that removing permission for a financial type restricts the available membership types');
  }

}
