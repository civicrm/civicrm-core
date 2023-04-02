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

  /**
   * Check method testCheckPermissionedLineItems()
   *
   * @throws \CRM_Core_Exception
   */
  public function testCheckPermissionedLineItems(): void {
    $contactId = $this->individualCreate();
    $paramsSet['title'] = 'Price Set_test';
    $paramsSet['name'] = CRM_Utils_String::titleToVar($paramsSet['title']);
    $paramsSet['is_active'] = TRUE;
    $paramsSet['financial_type_id'] = 1;
    $paramsSet['extends'] = 1;

    $priceSet = CRM_Price_BAO_PriceSet::create($paramsSet);
    $priceSetId = $priceSet->id;

    //Checking for price set added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceSet', $priceSetId, 'title',
      'id', $paramsSet['title'], 'Check DB for created price set'
    );
    $paramsField = [
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'CheckBox',
      'option_label' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_value' => ['1' => 100, '2' => 200],
      'option_name' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_weight' => ['1' => 1, '2' => 2],
      'option_amount' => ['1' => 100, '2' => 200],
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => ['1' => 1, '2' => 1],
      'price_set_id' => $priceSet->id,
      'is_enter_qty' => 1,
      'financial_type_id' => 1,
    ];
    $priceField = CRM_Price_BAO_PriceField::create($paramsField);
    $priceFields = $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $priceField->id]);
    $contributionParams = [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $contactId,
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'skipCleanMoney' => TRUE,
    ];

    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[1][$key] = [
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
      ];
    }
    $contributionParams['line_item'] = $lineItems;
    $contributions = CRM_Contribute_BAO_Contribution::create($contributionParams);
    CRM_Financial_BAO_FinancialType::$_statusACLFt = [];
    $this->setACL();

    $this->setPermissions([
      'view contributions of type Member Dues',
    ]);

    try {
      CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($contributions->id, 'view');
      $this->fail('Missed expected exception');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertEquals('You do not have permission to access this page.', $e->getMessage());
    }

    $this->setPermissions([
      'view contributions of type Donation',
    ]);
    $perm = CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($contributions->id, 'view');
    $this->assertEquals(TRUE, $perm, 'Verify that line items now have permission.');
  }

}
