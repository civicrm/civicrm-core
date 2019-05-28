<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class api_v3_LineItemTest
 * @group headless
 */
class api_v3_LineItemTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $testAmount = 34567;
  protected $params;
  protected $id = 0;
  protected $contactIds = array();
  protected $_entity = 'line_item';
  protected $contribution_result = NULL;

  public $DBResetRequired = TRUE;
  protected $_financialTypeId = 1;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_individualId = $this->individualCreate();
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 51.00,
      'net_amount' => 91.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $this->params = array(
      'price_field_value_id' => 1,
      'price_field_id' => 1,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'qty' => 1,
      'unit_price' => 50,
      'line_total' => 50,
    );
  }

  public function testCreateLineItem() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params + array('debug' => 1), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetBasicLineItem() {
    $getParams = array(
      'entity_table' => 'civicrm_contribution',
    );
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  public function testDeleteLineItem() {
    $getParams = array(
      'entity_table' => 'civicrm_contribution',
    );
    $getResult = $this->callAPISuccess($this->_entity, 'get', $getParams);
    $deleteParams = array('id' => $getResult['id']);
    $deleteResult = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testGetFieldsLineItem() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', array('action' => 'create'));
    $this->assertEquals(1, $result['values']['entity_id']['api.required']);
  }

}
