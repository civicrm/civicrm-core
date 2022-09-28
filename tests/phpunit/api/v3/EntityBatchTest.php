<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+ */

/**
 *  Test APIv3 civicrm_entity_batch_* functions
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_EntityBatchTest extends CiviUnitTestCase {
  protected $params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();

    $entityParams = ['contact_id' => $this->individualCreate()];

    $this->_entity = 'EntityBatch';
    $this->params = [
      'entity_id' => $this->contributionCreate($entityParams),
      'batch_id' => $this->batchCreate(),
      'entity_table' => 'civicrm_financial_trxn',
    ];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCreateEntityBatch(): void {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testGetEntityBatch(): void {
    $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testDeleteEntityBatch(): void {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = ['id' => $result['id']];
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
