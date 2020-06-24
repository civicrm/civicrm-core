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
 * Test class for Batch API - civicrm_batch_*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_BatchTest extends CiviUnitTestCase {

  protected $_params = [];
  protected $_entity = 'batch';

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Test civicrm_batch_get - success expected.
   */
  public function testGet() {
    $params = [
      'id' => $this->batchCreate(),
    ];
    $result = $this->callAPIAndDocument('batch', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($params['id'], $result['id']);
  }

  /**
   * Test civicrm_batch_create - success expected.
   */
  public function testCreate() {
    $params = [
      'name' => 'New_Batch_03',
      'title' => 'New Batch 03',
      'description' => 'This is description for New Batch 03',
      'total' => '300.33',
      'item_count' => 3,
      'status_id' => 1,
    ];

    $result = $this->callAPIAndDocument('batch', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $this->getAndCheck($params, $result['id'], $this->_entity);
  }

  /**
   * Test civicrm_batch_create with id.
   */
  public function testUpdate() {
    $params = [
      'name' => 'New_Batch_04',
      'title' => 'New Batch 04',
      'description' => 'This is description for New Batch 04',
      'total' => '400.44',
      'item_count' => 4,
      'id' => $this->batchCreate(),
    ];

    $result = $this->callAPIAndDocument('batch', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $this->getAndCheck($params, $result['id'], $this->_entity);
  }

  /**
   * Test civicrm_batch_delete using the old $params['batch_id'] syntax.
   */
  public function testBatchDeleteOldSyntax() {
    $batchID = $this->batchCreate();
    $params = [
      'batch_id' => $batchID,
    ];
    $result = $this->callAPISuccess('batch', 'delete', $params);
  }

  /**
   * Test civicrm_batch_delete using the new $params['id'] syntax.
   */
  public function testBatchDeleteCorrectSyntax() {
    $batchID = $this->batchCreate();
    $params = [
      'id' => $batchID,
    ];
    $result = $this->callAPIAndDocument('batch', 'delete', $params, __FUNCTION__, __FILE__);
  }

}
