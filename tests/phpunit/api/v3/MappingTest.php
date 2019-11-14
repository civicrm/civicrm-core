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
 *  Test APIv3 civicrm_mapping_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_MappingTest extends CiviUnitTestCase {

  protected $_apiversion = 3;
  protected $params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_entity = 'mapping';
    $this->params = [
      'name' => 'Mapping name',
      'description' => 'Mapping description',
      // 'Export Contact' mapping.
      'mapping_type_id' => 7,
    ];
  }

  public function testCreateMapping() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testGetMapping() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  public function testDeleteMapping() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = ['id' => $result['id']];
    $result = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testDeleteMappingInvalid() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = ['id' => 600];
    $result = $this->callAPIFailure($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(1, $checkDeleted['count']);
  }

}
