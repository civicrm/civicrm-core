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
 * Class api_v3_LineItemTest
 * @group headless
 */
class api_v3_LineItemTest extends CiviUnitTestCase {
  protected $params;
  protected $_entity = 'line_item';

  /**
   * Prepare for test.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction();
    $contributionParams = [
      'contact_id' => $this->individualCreate(),
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 'Donation',
      'non_deductible_amount' => 10.00,
      'fee_amount' => 51.00,
      'net_amount' => 91.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $this->params = [
      'price_field_value_id' => 1,
      'price_field_id' => 1,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'qty' => 1,
      'unit_price' => 50,
      'line_total' => 50,
    ];
  }

  /**
   * Test basic create line item.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateLineItem() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__)['values'];
    $this->assertCount(1, $result);
    $this->getAndCheck($this->params, key($result), $this->_entity);
  }

  /**
   * Test basic get line item.
   */
  public function testGetBasicLineItem() {
    $getParams = [
      'entity_table' => 'civicrm_contribution',
    ];
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  /**
   * Test delete line item.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteLineItem() {
    $getParams = [
      'entity_table' => 'civicrm_contribution',
    ];
    $getResult = $this->callAPISuccess($this->_entity, 'get', $getParams);
    $deleteParams = ['id' => $getResult['id']];
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get');
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test getfields function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetFieldsLineItem() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'create']);
    $this->assertEquals(1, $result['values']['entity_id']['api.required']);
  }

}
