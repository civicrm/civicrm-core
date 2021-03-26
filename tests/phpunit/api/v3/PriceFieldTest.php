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
 * Class api_v3_PriceFieldTest
 * @group headless
 */
class api_v3_PriceFieldTest extends CiviUnitTestCase {
  protected $_params;
  protected $id = 0;
  protected $priceSetID = 0;
  protected $_entity = 'price_field';

  /**
   * Set up for test.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $priceSetparams = [
      'name' => 'default_goat_priceset',
      'title' => 'Goat accommodation',
      'is_active' => 1,
      'help_pre' => 'Where does your goat sleep',
      'help_post' => 'thank you for your time',
      'extends' => 2,
      'financial_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];

    $price_set = $this->callAPISuccess('price_set', 'create', $priceSetparams);
    $this->priceSetID = $price_set['id'];

    $this->_params = [
      'price_set_id' => $this->priceSetID,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
  }

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_contribution',
    ];
    $this->quickCleanup($tablesToTruncate);

    $this->callAPISuccess('PriceSet', 'delete', ['id' => $this->priceSetID]);
    parent::tearDown();
  }

  /**
   * Basic create test.
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreatePriceField(int $version) {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  /**
   * Basic get test.
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetBasicPriceField(int $version) {
    $this->_apiversion = $version;
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->id = $createResult['id'];
    $this->assertAPISuccess($createResult);
    $getParams = [
      'name' => 'contribution_amount',
    ];
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
    $this->callAPISuccess('price_field', 'delete', ['id' => $createResult['id']]);
  }

  /**
   * Basic delete test.
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider versionThreeAndFour
   */
  public function testDeletePriceField($version) {
    $this->_apiversion = $version;
    $startCount = $this->callAPISuccess($this->_entity, 'getcount', []);
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => $createResult['id']];
    $deleteResult = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($deleteResult);
    $endCount = $this->callAPISuccess($this->_entity, 'getcount', []);
    $this->assertEquals($startCount, $endCount);
  }

  /**
   * Basic getfields test.
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetFieldsPriceField(int $version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'create']);
    $this->assertEquals('number of options per line for checkbox and radio', $result['values']['options_per_line']['description']);
  }

  /**
   * Test updating the label of a text price field.
   *
   * CRM-19741 - ensure price field value label is also updated.
   *
   * @dataProvider versionThreeAndFour
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdatePriceFieldLabel(int $version) {
    $this->_apiversion = $version;
    $field = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $expectedLabel = 'Rose Variety';
    $this->updateLabel($field, $expectedLabel);
  }

  /**
   * Test that value label only updates if field type is html (CRM-19741).
   *
   * @dataProvider versionThreeAndFour
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdatePriceFieldLabelNotUpdateField(int $version) {
    $this->_apiversion = $version;
    $expectedLabel = 'juicy and healthy';
    $field = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, ['html_type' => 'Radio']));
    $this->updateLabel($field, $expectedLabel);
  }

  /**
   * Update the label using the api, check against expected final label.
   *
   * @param array $field
   * @param string $expectedLabel
   *
   * @throws \CRM_Core_Exception
   */
  protected function updateLabel(array $field, string $expectedLabel) {
    $this->callAPISuccess('PriceFieldValue', 'create', [
      'price_field_id' => $field['id'],
      'name' => 'rye grass',
      'label' => 'juicy and healthy',
      'amount' => 1,
      'financial_type_id' => 1,
    ]);
    $this->callAPISuccess($this->_entity, 'create', ['id' => $field['id'], 'label' => 'Rose Variety']);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'get', ['price_field_id' => $field['id']]);
    $this->assertEquals($expectedLabel, $priceFieldValue['values'][$priceFieldValue['id']]['label']);
    $this->callAPISuccess('PriceFieldValue', 'delete', ['id' => $priceFieldValue['id']]);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $field['id']]);
  }

}
