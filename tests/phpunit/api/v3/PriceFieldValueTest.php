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
 * Class api_v3_PriceFieldValueTest
 * @group headless
 */
class api_v3_PriceFieldValueTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  const ENTITY = 'price_field_value';

  /**
   * @var array
   */
  protected $params;

  /**
   * @var int
   */
  protected $membershipTypeID;

  /**
   * @var int
   */
  protected $priceSetID1;

  /**
   * @var int
   */
  protected $priceFieldID1;

  /**
   * @var int
   */
  protected $priceSetID2;

  /**
   * @var int
   */
  protected $priceFieldID2;

  /**
   * Setup function.
   */
  public function setUp(): void {
    parent::setUp();
    // Put stuff here that should happen before all tests in this unit.
    $priceSetParams = [
      'name' => 'default_goat_priceset',
      'title' => 'Goat accommodation',
      'is_active' => 1,
      'help_pre' => "Where does your goat sleep",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'financial_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];

    $priceSet = $this->callAPISuccess('price_set', 'create', $priceSetParams);
    $this->priceSetID1 = $priceSet['id'];

    $priceFieldParams = [
      'price_set_id' => $this->priceSetID1,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField = $this->callAPISuccess('price_field', 'create', $priceFieldParams);
    $this->priceFieldID1 = $priceField['id'];
    $this->params = [
      'price_field_id' => $this->priceFieldID1,
      'name' => 'rye grass',
      'label' => 'juicy and healthy',
      'amount' => 1,
      'financial_type_id' => 1,
    ];

    $membershipOrgId = $this->organizationCreate(NULL);
    $this->membershipTypeID = $this->membershipTypeCreate(['member_of_contact_id' => $membershipOrgId]);

    $priceSetParams2 = [
      'name' => 'priceset',
      'title' => 'Priceset with Multiple Terms',
      'is_active' => 1,
      'extends' => 3,
      'financial_type_id' => 2,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];
    $priceSet2 = $this->callAPISuccess('price_set', 'create', $priceSetParams2);
    $this->priceSetID2 = $priceSet2['id'];
    $priceFieldParams2 = [
      'price_set_id' => $this->priceSetID2,
      'name' => 'memtype',
      'label' => 'memtype',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField2 = $this->callAPISuccess('price_field', 'create', $priceFieldParams2);
    $this->priceFieldID2 = $priceField2['id'];
  }

  /**
   * Tear down function.
   *
   * @throws \Exception
   */
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_contribution',
    ];
    $this->quickCleanup($tablesToTruncate);
    $this->membershipTypeDelete(['id' => $this->membershipTypeID]);
    $this->callAPISuccess('PriceField', 'delete', [
      'id' => $this->priceFieldID1,
    ]);
    $delete = $this->callAPISuccess('PriceSet', 'delete', [
      'id' => $this->priceSetID1,
    ]);
    $this->callAPISuccess('PriceField', 'delete', [
      'id' => $this->priceFieldID2,
    ]);
    $this->callAPISuccess('PriceSet', 'delete', [
      'id' => $this->priceSetID2,
    ]);

    $this->assertAPISuccess($delete);
  }

  public function testCreatePriceFieldValue() {
    $result = $this->callAPIAndDocument(self::ENTITY, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], self::ENTITY);
  }

  public function testGetBasicPriceFieldValue() {
    $createResult = $this->callAPISuccess(self::ENTITY, 'create', $this->params);
    $this->assertAPISuccess($createResult);
    $getParams = [
      'name' => 'contribution_amount',
    ];
    $getResult = $this->callAPIAndDocument(self::ENTITY, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
    $this->callAPISuccess('price_field_value', 'delete', ['id' => $createResult['id']]);
  }

  public function testDeletePriceFieldValue() {
    $startCount = $this->callAPISuccess(self::ENTITY, 'getcount', []);
    $createResult = $this->callAPISuccess(self::ENTITY, 'create', $this->params);
    $deleteParams = ['id' => $createResult['id']];
    $deleteResult = $this->callAPIAndDocument(self::ENTITY, 'delete', $deleteParams, __FUNCTION__, __FILE__);

    $endCount = $this->callAPISuccess(self::ENTITY, 'getcount', []);
    $this->assertEquals($startCount, $endCount);
  }

  public function testGetFieldsPriceFieldValue() {
    $result = $this->callAPISuccess(self::ENTITY, 'getfields', ['action' => 'create']);
    $this->assertEquals(1, $result['values']['max_value']['type']);
  }

  public function testCreatePriceFieldValuewithMultipleTerms() {
    $params = [
      'price_field_id' => $this->priceFieldID2,
      'membership_type_id' => $this->membershipTypeID,
      'name' => 'memType1',
      'label' => 'memType1',
      'amount' => 90,
      'membership_num_terms' => 2,
      'is_active' => 1,
      'financial_type_id' => 2,
    ];
    $result = $this->callAPIAndDocument(self::ENTITY, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['membership_num_terms'], 2);
    $this->assertEquals(1, $result['count']);
    $this->callAPISuccess(self::ENTITY, 'delete', ['id' => $result['id']]);
  }

  public function testGetPriceFieldValuewithMultipleTerms() {
    $params2 = [
      'price_field_id' => $this->priceFieldID2,
      'membership_type_id' => $this->membershipTypeID,
      'name' => 'memType1',
      'label' => 'memType1',
      'amount' => 90,
      'membership_num_terms' => 2,
      'is_active' => 1,
      'financial_type_id' => 2,
    ];
    $params2 = [
      'price_field_id' => $this->priceFieldID2,
      'membership_type_id' => $this->membershipTypeID,
      'name' => 'memType2',
      'label' => 'memType2',
      'amount' => 120,
      'membership_num_terms' => 3,
      'is_active' => 1,
      'financial_type_id' => 2,
    ];
    $result1 = $this->callAPISuccess(self::ENTITY, 'create', $params2);
    $result2 = $this->callAPISuccess(self::ENTITY, 'create', $params2);
    $result = $this->callAPISuccess(self::ENTITY, 'get', ['price_field_id' => $this->priceFieldID2]);
    $this->assertEquals(2, $result['count']);
    $this->callAPISuccess(self::ENTITY, 'delete', ['id' => $result1['id']]);
    $this->callAPISuccess(self::ENTITY, 'delete', ['id' => $result2['id']]);
  }

  public function testCreatePriceFieldValueWithDisabledFinancialType() {
    $financialTypeParams = [
      'is_active' => 0,
      'name' => 'Disabled Donations',
    ];
    $financialType = $this->callAPISuccess('financial_type', 'create', $financialTypeParams);
    $params = [
      'price_field_id' => $this->priceFieldID1,
      'name' => 'DonType1',
      'label' => 'DonType1',
      'amount' => 90,
      'is_active' => 1,
      'financial_type_id' => $financialType['id'],
    ];
    $this->callAPIFailure(self::ENTITY, 'create', $params);
  }

  /**
   * This is the same as testCreatePriceFieldValue but where is_default = 1.
   */
  public function testCreatePriceFieldValueAsDefault() {
    $params = $this->params;
    $params['is_default'] = 1;
    $result = $this->callAPISuccess(self::ENTITY, 'create', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($params, $result['id'], self::ENTITY);
  }

}
