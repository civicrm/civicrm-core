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
 * Class CRM_Financial_BAO_FinancialTypeTest
 * @group headless
 */
class CRM_Financial_BAO_FinancialTypeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->_orgContactID = $this->organizationCreate();
  }

  public function teardown() {
    global $dbLocale;
    if ($dbLocale) {
      CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    }
    $this->financialAccountDelete('Donations');
  }

  /**
   * Check method add().
   */
  public function testAdd() {
    $params = [
      'name' => 'Donations',
      'is_active' => 1,
      'is_deductible' => 1,
      'is_reserved' => 1,
    ];
    $ids = [];
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
   * Check method retrieve().
   */
  public function testRetrieve() {
    $params = [
      'name' => 'Donations',
      'is_active' => 1,
      'is_deductible' => 1,
      'is_reserved' => 1,
    ];

    $ids = [];
    CRM_Financial_BAO_FinancialType::add($params, $ids);

    $defaults = [];
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'Donations', 'Verify Name for Financial Type');
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
   * Data provider for testGitLabIssue1108
   *
   * First we run it without multiLingual mode, then with.
   *
   * This is because we test table names, which may have been translated in a
   * multiLingual context.
   *
   */
  public function multiLingual() {
    return [[0], [1]];
  }

  /**
   * Check method del()
   *
   * @dataProvider multiLingual
   */
  public function testDel($isMultiLingual) {
    if ($isMultiLingual) {
      $this->enableMultilingual();
      CRM_Core_I18n_Schema::addLocale('fr_FR', 'en_US');
    }
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $ids = [];
    $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);

    if ($isMultiLingual) {
      global $dbLocale;
      $dbLocale = '_fr_FR';
    }
    CRM_Financial_BAO_FinancialType::del($financialType->id);
    $params = ['id' => $financialType->id];
    $result = CRM_Financial_BAO_FinancialType::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
    $results = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_entity_financial_account WHERE entity_id = %1", [1 => [$financialType->id, 'Positive']])->fetchAll();
    $this->assertEquals(empty($results), TRUE, 'Assert related entity financial account has been deleted as well');
    if ($isMultiLingual) {
      global $dbLocale;
      $dbLocale = '_en_US';
    }
  }

  /**
   * Set ACLs for Financial Types()
   */
  public function setACL() {
    Civi::settings()->set('acl_financial_type', 1);
  }

  /**
   * Check method testGetAvailableFinancialTypes()
   */
  public function testGetAvailableFinancialTypes() {
    $this->setACL();
    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    ]);
    $types = [];
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
    $expectedResult = [
      1 => "Donation",
      2 => "Member Dues",
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
   * Check method testgetAvailableMembershipTypes()
   */
  public function testgetAvailableMembershipTypes() {
    // Create Membership types
    $ids = [];
    $params = [
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
    ];

    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);
    // Add another
    $params['name'] = 'Type Two';
    $params['financial_type_id'] = 2;
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

    $this->setACL();

    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    ]);
    CRM_Financial_BAO_FinancialType::getAvailableMembershipTypes($types);
    $expectedResult = [
      1 => "Type One",
      2 => "Type Two",
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
   * Check method testPermissionedFinancialTypes()
   */
  public function testPermissionedFinancialTypes() {
    // First get all core permissions
    $permissions = $checkPerms = CRM_Core_Permission::getCorePermissions();
    $this->setACL();
    CRM_Financial_BAO_FinancialType::permissionedFinancialTypes($permissions, TRUE);
    $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    $actions = [
      'add' => ts('add'),
      'view' => ts('view'),
      'edit' => ts('edit'),
      'delete' => ts('delete'),
    ];
    foreach ($financialTypes as $id => $type) {
      foreach ($actions as $action => $action_ts) {
        $checkPerms[$action . ' contributions of type ' . $type] = [
          ts("CiviCRM: %1 contributions of type %2", [1 => $action_ts, 2 => $type]),
          ts('%1 contributions of type %2', [1 => $action_ts, 2 => $type]),
        ];
      }
    }
    $checkPerms['administer CiviCRM Financial Types'] = [
      ts('CiviCRM: administer CiviCRM Financial Types'),
      ts('Administer access to Financial Types'),
    ];
    $this->assertEquals($permissions, $checkPerms, 'Verify that permissions for each financial type have been added');
  }

  /**
   * Check method testcheckPermissionedLineItems()
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCheckPermissionedLineItems() {
    $contactId = $this->individualCreate();
    $paramsSet['title'] = 'Price Set' . substr(sha1(rand()), 0, 4);
    $paramsSet['name'] = CRM_Utils_String::titleToVar($paramsSet['title']);
    $paramsSet['is_active'] = TRUE;
    $paramsSet['financial_type_id'] = 1;
    $paramsSet['extends'] = 1;

    $priceset = CRM_Price_BAO_PriceSet::create($paramsSet);
    $priceSetId = $priceset->id;

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceSet', $priceSetId, 'title',
      'id', $paramsSet['title'], 'Check DB for created priceset'
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
      'price_set_id' => $priceset->id,
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
    catch (Exception $e) {
      $this->assertEquals('A fatal error was triggered: You do not have permission to access this page.', $e->getMessage());
    }

    $this->setPermissions([
      'view contributions of type Donation',
    ]);
    $perm = CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($contributions->id, 'view');
    $this->assertEquals($perm, TRUE, 'Verify that lineitems now have permission.');
  }

  /**
   * Check method testisACLFinancialTypeStatus()
   */
  public function testBuildPermissionedClause() {
    $this->setACL();
    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    ]);
    CRM_Financial_BAO_FinancialType::buildPermissionedClause($whereClause, 'contribution');
    $this->assertEquals($whereClause, ' civicrm_contribution.financial_type_id IN (1,2)');
    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
      'view contributions of type Event Fee',
    ]);
    $whereClause = NULL;

    CRM_Financial_BAO_FinancialType::buildPermissionedClause($whereClause, 'contribution');
    $this->assertEquals($whereClause, ' civicrm_contribution.financial_type_id IN (1,4,2)');
  }

}
