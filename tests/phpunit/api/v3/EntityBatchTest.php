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

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();

    $entityParams = ['contact_id' => $this->individualCreate()];

    $contributionId = $this->contributionCreate($entityParams);
    $financialTrxnId = array_values($this->callAPISuccess('EntityFinancialTrxn', 'get', [
      'entity_id' => $contributionId,
      'entity_table' => 'civicrm_contribution',
      'return' => ['financial_trxn_id'],
    ])['values'])[0]['financial_trxn_id'];

    $this->_entity = 'EntityBatch';
    $this->params = [
      'entity_id' => $financialTrxnId,
      'batch_id' => $this->batchCreate(),
      'entity_table' => 'civicrm_financial_trxn',
    ];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCreateEntityBatch(): void {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testGetEntityBatch(): void {
    $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPISuccess($this->_entity, 'get', $this->params);
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
    $this->callAPISuccess($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Ensure that submitting multiple currencies results in an error.
   * @throws \CRM_Core_Exception
   */
  public function testMultipleCurrencies(): void {
    $params['name'] = $params['title'] = 'MultiCurrencyBatch';
    $params['status_id'] = 1;
    $batchId = $this->callAPISuccess('batch', 'create', $params)['id'];

    $contributionId = $this->contributionCreate(['contact_id' => $this->individualCreate()]);
    $financialTrxnId = array_values($this->callAPISuccess('EntityFinancialTrxn', 'get', [
      'entity_id' => $contributionId,
      'entity_table' => 'civicrm_contribution',
      'return' => ['financial_trxn_id'],
    ])['values'])[0]['financial_trxn_id'];
    $firstEntityBatchParams = [
      'entity_id' => $financialTrxnId,
      'batch_id' => $batchId,
      'entity_table' => 'civicrm_financial_trxn',
    ];
    $result = $this->callAPISuccess($this->_entity, 'create', $firstEntityBatchParams);
    $this->assertEquals(1, $result['count']);
    $secondContributionId = $this->contributionCreate(['contact_id' => $this->individualCreate(), 'currency' => 'CAD']);

    $secondFinancialTrxnId = array_values($this->callAPISuccess('EntityFinancialTrxn', 'get', [
      'entity_id' => $secondContributionId,
      'entity_table' => 'civicrm_contribution',
      'return' => ['financial_trxn_id'],
    ])['values'])[0]['financial_trxn_id'];
    $secondEntityBatchParams = [
      'entity_id' => $secondFinancialTrxnId,
      'batch_id' => $batchId,
      'entity_table' => 'civicrm_financial_trxn',
    ];
    $result = $this->callAPIFailure($this->_entity, 'create', $secondEntityBatchParams);
    $this->assertEquals("You cannot add items of two different currencies to a single contribution batch. Batch id {$batchId} currency: USD. Entity id {$secondFinancialTrxnId} currency: CAD.", $result['error_message']);
  }

}
