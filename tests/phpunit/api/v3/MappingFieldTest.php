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
 *  Test APIv3 civicrm_mapping_field_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_MappingFieldTest extends CiviUnitTestCase {

  protected $_apiversion = 3;
  protected $params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_entity = 'mapping_field';
    $mappingID = $this->mappingCreate();
    $this->params = [
      'mapping_id' => $mappingID->id,
      'name' => 'last_name',
      'contact_type' => 'Individual',
      'column_number' => 2,
      'grouping' => 1,
    ];
  }

  public function testCreateMappingField() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testGetMappingField() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  public function testDeleteMappingField() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = ['id' => $result['id']];
    $result = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testDeleteMappingFieldInvalid() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = ['id' => 600];
    $result = $this->callAPIFailure($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(1, $checkDeleted['count']);
  }

}
