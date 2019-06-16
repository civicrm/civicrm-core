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
 * Class api_v3_PriceFieldTest
 * @group headless
 */
class api_v3_PriceFieldTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $id = 0;
  protected $priceSetID = 0;
  protected $_entity = 'price_field';

  public $DBResetRequired = TRUE;

  public function setUp() {
    parent::setUp();
    // put stuff here that should happen before all tests in this unit
    $priceSetparams = array(
      #     [domain_id] =>
      'name' => 'default_goat_priceset',
      'title' => 'Goat accomodation',
      'is_active' => 1,
      'help_pre' => "Where does your goat sleep",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'financial_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    );

    $price_set = $this->callAPISuccess('price_set', 'create', $priceSetparams);
    $this->priceSetID = $price_set['id'];

    $this->_params = array(
      'price_set_id' => $this->priceSetID,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_active' => 1,
    );
  }

  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_contribution',
    );
    $this->quickCleanup($tablesToTruncate);

    $delete = $this->callAPISuccess('PriceSet', 'delete', array(
      'id' => $this->priceSetID,
    ));

    $this->assertAPISuccess($delete);
    parent::tearDown();
  }

  public function testCreatePriceField() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  public function testGetBasicPriceField() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->id = $createResult['id'];
    $this->assertAPISuccess($createResult);
    $getParams = array(
      'name' => 'contribution_amount',
    );
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
    $this->callAPISuccess('price_field', 'delete', array('id' => $createResult['id']));
  }

  public function testDeletePriceField() {
    $startCount = $this->callAPISuccess($this->_entity, 'getcount', array());
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = array('id' => $createResult['id']);
    $deleteResult = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($deleteResult);
    $endCount = $this->callAPISuccess($this->_entity, 'getcount', array());
    $this->assertEquals($startCount, $endCount);
  }

  public function testGetFieldsPriceField() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', array('action' => 'create'));
    $this->assertEquals(1, $result['values']['options_per_line']['type']);
  }

  /**
   * CRM-19741
   * Test updating the label of a texte price field and ensure price field value label is also updated
   */
  public function testUpdatePriceFieldLabel() {
    $field = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->callAPISuccess('price_field_value', 'create', array(
      'price_field_id' => $field['id'],
      'name' => 'rye grass',
      'label' => 'juicy and healthy',
      'amount' => 1,
      'financial_type_id' => 1,
    ));
    $priceField = $this->callAPISuccess($this->_entity, 'create', array('id' => $field['id'], 'label' => 'Rose Variety'));
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'get', array('price_field_id' => $field['id']));
    $this->assertEquals($priceField['values'][$priceField['id']]['label'], $priceFieldValue['values'][$priceFieldValue['id']]['label']);
    $this->callAPISuccess('price_field_value', 'delete', array('id' => $priceFieldValue['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $field['id']));
  }

  /**
   * CRM-19741
   * Confirm value label only updates if fiedl type is html.
   */
  public function testUpdatePriceFieldLabelNotUpdateField() {
    $field = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, array('html_type' => 'Radio')));
    $this->callAPISuccess('price_field_value', 'create', array(
      'price_field_id' => $field['id'],
      'name' => 'rye grass',
      'label' => 'juicy and healthy',
      'amount' => 1,
      'financial_type_id' => 1,
    ));
    $priceField = $this->callAPISuccess($this->_entity, 'create', array('id' => $field['id'], 'label' => 'Rose Variety'));
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'get', array('price_field_id' => $field['id']));
    $this->assertEquals('juicy and healthy', $priceFieldValue['values'][$priceFieldValue['id']]['label']);
    $this->callAPISuccess('price_field_value', 'delete', array('id' => $priceFieldValue['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $field['id']));
  }

}
