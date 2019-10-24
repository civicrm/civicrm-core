<?php
/*
 --------------------------------------------------------------------
 | CiviCRM version 5                                                  |
 --------------------------------------------------------------------
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 --------------------------------------------------------------------
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
 --------------------------------------------------------------------
 */

/**
 *  Test APIv3 civicrm_entity_batch_* functions
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_EntityBatchTest extends CiviUnitTestCase {

  protected $_apiversion = 3;
  protected $params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $entityParams = ['contact_id' => 1];

    $this->_entity = 'EntityBatch';
    $this->_entityID = $this->contributionCreate($entityParams);
    $this->_batchID = $this->batchCreate();
    $this->params = [
      'entity_id' => $this->_entityID,
      'batch_id' => $this->_batchID,
      'entity_table' => 'civicrm_financial_trxn',
    ];
  }

  public function testCreateEntityBatch() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testGetEntityBatch() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  public function testDeleteEntityBatch() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = ['id' => $result['id']];
    $result = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
