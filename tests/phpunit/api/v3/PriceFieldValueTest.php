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
  protected $_params;
  protected $id = 0;
  protected $priceSetID = 0;
  protected $_entity = 'price_field_value';

  public $DBResetRequired = TRUE;

  /**
   * @var int
   */
  protected $priceFieldID;

  /**
   * Setup function.
   */
  public function setUp() {
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

    $price_set = $this->callAPISuccess('price_set', 'create', $priceSetParams);
    $this->priceSetID = $price_set['id'];

    $priceFieldParams = [
      'price_set_id' => $this->priceSetID,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField = $this->callAPISuccess('price_field', 'create', $priceFieldParams);
    $this->priceFieldID = $priceField['id'];
    $this->_params = [
      'price_field_id' => $this->priceFieldID,
      'name' => 'rye grass',
      'label' => 'juicy and healthy',
      'amount' => 1,
      'financial_type_id' => 1,
    ];

    $membershipOrgId = $this->organizationCreate(NULL);
    $this->_membershipTypeID = $this->membershipTypeCreate(['member_of_contact_id' => $membershipOrgId]);
    $priceSetParams1 = [
      'name' => 'priceset',
      'title' => 'Priceset with Multiple Terms',
      'is_active' => 1,
      'extends' => 3,
      'financial_type_id' => 2,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];
    $price_set1 = $this->callAPISuccess('price_set', 'create', $priceSetParams1);
    $this->priceSetID1 = $price_set1['id'];
    $priceFieldParams1 = [
      'price_set_id' => $this->priceSetID1,
      'name' => 'memtype',
      'label' => 'memtype',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField1 = $this->callAPISuccess('price_field', 'create', $priceFieldParams1);
    $this->priceFieldID1 = $priceField1['id'];
  }

  /**
   * Tear down function.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_contribution',
    ];
    $this->quickCleanup($tablesToTruncate);
    $this->membershipTypeDelete(['id' => $this->_membershipTypeID]);
    $this->callAPISuccess('PriceField', 'delete', [
      'id' => $this->priceFieldID1,
    ]);
    $this->callAPISuccess('PriceSet', 'delete', [
      'id' => $this->priceSetID1,
    ]);
    $this->callAPISuccess('PriceField', 'delete', [
      'id' => $this->priceFieldID,
    ]);
    $delete = $this->callAPISuccess('PriceSet', 'delete', [
      'id' => $this->priceSetID,
    ]);

    $this->assertAPISuccess($delete);
  }

  public function testCreatePriceFieldValue() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  public function testGetBasicPriceFieldValue() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->id = $createResult['id'];
    $this->assertAPISuccess($createResult);
    $getParams = [
      'name' => 'contribution_amount',
    ];
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
    $this->callAPISuccess('price_field_value', 'delete', ['id' => $createResult['id']]);
  }

  public function testDeletePriceFieldValue() {
    $startCount = $this->callAPISuccess($this->_entity, 'getcount', []);
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => $createResult['id']];
    $deleteResult = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);

    $endCount = $this->callAPISuccess($this->_entity, 'getcount', []);
    $this->assertEquals($startCount, $endCount);
  }

  public function testGetFieldsPriceFieldValue() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'create']);
    $this->assertEquals(1, $result['values']['max_value']['type']);
  }

  public function testCreatePriceFieldValuewithMultipleTerms() {
    $params = [
      'price_field_id' => $this->priceFieldID1,
      'membership_type_id' => $this->_membershipTypeID,
      'name' => 'memType1',
      'label' => 'memType1',
      'amount' => 90,
      'membership_num_terms' => 2,
      'is_active' => 1,
      'financial_type_id' => 2,
    ];
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['membership_num_terms'], 2);
    $this->assertEquals(1, $result['count']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  public function testGetPriceFieldValuewithMultipleTerms() {
    $params1 = [
      'price_field_id' => $this->priceFieldID1,
      'membership_type_id' => $this->_membershipTypeID,
      'name' => 'memType1',
      'label' => 'memType1',
      'amount' => 90,
      'membership_num_terms' => 2,
      'is_active' => 1,
      'financial_type_id' => 2,
    ];
    $params2 = [
      'price_field_id' => $this->priceFieldID1,
      'membership_type_id' => $this->_membershipTypeID,
      'name' => 'memType2',
      'label' => 'memType2',
      'amount' => 120,
      'membership_num_terms' => 3,
      'is_active' => 1,
      'financial_type_id' => 2,
    ];
    $result1 = $this->callAPISuccess($this->_entity, 'create', $params1);
    $result2 = $this->callAPISuccess($this->_entity, 'create', $params2);
    $result = $this->callAPISuccess($this->_entity, 'get', ['price_field_id' => $this->priceFieldID1]);
    $this->assertEquals(2, $result['count']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result1['id']]);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result2['id']]);
  }

  public function testCreatePriceFieldValueWithDisabledFinancialType() {
    $financialTypeParams = [
      'is_active' => 0,
      'name' => 'Disabled Donations',
    ];
    $financialType = $this->callAPISuccess('financial_type', 'create', $financialTypeParams);
    $params = [
      'price_field_id' => $this->priceFieldID,
      'name' => 'DonType1',
      'label' => 'DonType1',
      'amount' => 90,
      'is_active' => 1,
      'financial_type_id' => $financialType['id'],
    ];
    $this->callAPIFailure($this->_entity, 'create', $params);
  }

}
